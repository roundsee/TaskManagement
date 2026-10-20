<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManualTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(): User
    {
        $user = User::create([
            'name' => 'Staf Uji',
            'email' => 'staf.uji@koperasi.test',
            'password' => 'password',
            'role' => 'staff',
            'department' => 'Kredit',
        ]);
        $user->assignRole('staff');

        return $user;
    }

    public function test_staff_dashboard_shows_today_activities(): void
    {
        $staff = $this->staff();

        Task::create([
            'task_type' => 'ksp_auto',
            'title' => 'Validasi Berkas',
            'user_id' => $staff->id,
            'status' => 'completed',
            'actual_duration_minutes' => 15,
        ]);

        $response = $this->actingAs($staff)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Aktivitas Otomatis Hari Ini');
        $response->assertSee('Validasi Berkas');
    }

    public function test_staff_can_create_manual_task_with_end_time(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff)->post('/staff/tasks', [
            'title' => 'Rapat Koordinasi',
            'description' => 'Rapat tim kredit',
            'start_time' => '09:00',
            'end_time' => '10:15',
            'is_complex' => '0',
        ]);

        $response->assertRedirect('/dashboard');

        $task = Task::where('title', 'Rapat Koordinasi')->firstOrFail();
        $this->assertSame('ksp_manual', $task->task_type);
        $this->assertSame('completed', $task->status);
        $this->assertSame(75, $task->actual_duration_minutes);
        $this->assertSame(20, $task->estimated_duration_minutes);
        $this->assertSame('normal', $task->complexity_flag);
    }

    public function test_staff_can_create_running_manual_task_and_complete_it(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/staff/tasks', [
            'title' => 'Survey Lapangan',
            'start_time' => now()->format('H:i'),
            'is_complex' => '0',
        ])->assertRedirect('/dashboard');

        $task = Task::where('title', 'Survey Lapangan')->firstOrFail();
        $this->assertSame('in_progress', $task->status);

        $response = $this->actingAs($staff)->patch("/staff/tasks/{$task->id}/complete");
        $response->assertRedirect('/dashboard');

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_complex_task_requires_note_and_skips_sla(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/staff/tasks', [
            'title' => 'Investigasi Sengketa',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'is_complex' => '1',
        ])->assertSessionHasErrors('complexity_note');

        $this->actingAs($staff)->post('/staff/tasks', [
            'title' => 'Investigasi Sengketa',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'is_complex' => '1',
            'complexity_note' => 'Berkas waris perlu penelusuran.',
        ])->assertRedirect('/dashboard');

        $task = Task::where('title', 'Investigasi Sengketa')->firstOrFail();
        $this->assertSame('complex', $task->complexity_flag);
        $this->assertSame(0, $task->estimated_duration_minutes);
        $this->assertSame(90, $task->actual_duration_minutes);
    }

    public function test_non_staff_cannot_create_manual_task(): void
    {
        $owner = User::create([
            'name' => 'Owner Uji',
            'email' => 'owner.uji@koperasi.test',
            'password' => 'password',
            'role' => 'owner',
            'department' => 'Direksi',
        ]);
        $owner->assignRole('owner');

        $this->actingAs($owner)->post('/staff/tasks', [
            'title' => 'Tidak Boleh',
            'start_time' => '09:00',
        ])->assertForbidden();
    }
}
