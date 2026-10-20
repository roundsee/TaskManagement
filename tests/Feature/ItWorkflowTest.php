<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $ba;
    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $this->ba = User::where('email', 'ba@koperasi.test')->first();
        $this->dev = User::where('email', 'dev@koperasi.test')->first();
    }

    private function createTicket(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'task_type' => 'it_dev',
            'title' => 'Tiket Uji Workflow',
            'user_id' => $this->dev->id,
            'ba_id' => $this->ba->id,
            'status' => 'new_request',
            'business_requirement' => 'Wajib ada tombol export.',
            'test_case_narration' => 'Klik export, pastikan file terunduh.',
        ], $overrides));
    }

    public function test_ba_can_create_ticket(): void
    {
        $response = $this->actingAs($this->ba)->post('/it/tasks', [
            'title' => 'Fitur Cetak Struk',
            'business_requirement' => 'Struk tercetak rapi.',
            'test_case_narration' => 'Cetak struk pada 3 printer.',
            'user_id' => $this->dev->id,
        ]);

        $task = Task::where('title', 'Fitur Cetak Struk')->firstOrFail();
        $response->assertRedirect(route('it.tasks.show', $task));
        $this->assertSame('new_request', $task->status);
        $this->assertSame($this->ba->id, $task->ba_id);
        $this->assertSame('Tiket dibuat oleh BA', $task->logs()->first()->action);
    }

    public function test_ticket_cannot_be_assigned_to_non_developer(): void
    {
        $staff = User::where('email', 'siti.kredit@koperasi.test')->first();

        $this->actingAs($this->ba)->post('/it/tasks', [
            'title' => 'Tiket Salah Target',
            'business_requirement' => 'x',
            'test_case_narration' => 'y',
            'user_id' => $staff->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_full_workflow_new_request_to_deployed(): void
    {
        $task = $this->createTicket();

        // Dev mulai
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/start")->assertRedirect();
        $this->assertSame('in_progress', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->started_at);

        // Submit sebelum 100% test passed → ditolak
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/submit")
            ->assertSessionHasErrors('test');
        $this->assertSame('in_progress', $task->fresh()->status);

        // Simpan repo + centang test passed
        $this->actingAs($this->dev)->patch("/it/tasks/{$task->id}/dev-info", [
            'repository_url' => 'https://git.koperasi.test/feature/x',
            'is_test_passed_100' => '1',
        ])->assertRedirect();
        $task->refresh();
        $this->assertTrue($task->is_test_passed_100);

        // Submit for approval
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/submit")->assertRedirect();
        $this->assertSame('waiting_approval', $task->fresh()->status);
        $this->assertGreaterThan(0, $this->ba->unreadNotifications()->count());

        // Dev tidak boleh approve sendiri
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/approve")->assertForbidden();

        // BA approve
        $this->actingAs($this->ba)->post("/it/tasks/{$task->id}/approve")->assertRedirect();
        $this->assertSame('approved', $task->fresh()->status);
        $this->assertGreaterThan(0, $this->dev->unreadNotifications()->count());

        // Dev deploy (backdate mulai kerja agar durasi terhitung)
        $task->forceFill(['started_at' => now()->subHours(2)])->save();
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/deploy")->assertRedirect();
        $task->refresh();
        $this->assertSame('deployed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertGreaterThan(0, $task->actual_duration_minutes);

        $actions = $task->logs()->pluck('action')->all();
        $this->assertContains('Development dimulai', $actions);
        $this->assertContains('Submit for Approval (100% Test Passed)', $actions);
        $this->assertContains('BA Approve tiket', $actions);
        $this->assertContains('Deployed to Production', $actions);
    }

    public function test_ba_reject_returns_ticket_to_developer_with_note(): void
    {
        $task = $this->createTicket([
            'status' => 'waiting_approval',
            'repository_url' => 'https://git.koperasi.test/x',
            'is_test_passed_100' => true,
        ]);

        $this->actingAs($this->ba)->post("/it/tasks/{$task->id}/reject", [
            'rejection_note' => 'Export belum sesuai format tanggal.',
        ])->assertRedirect();

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertSame('Export belum sesuai format tanggal.', $task->rejection_note);
        $this->assertFalse($task->is_test_passed_100);
        $this->assertGreaterThan(0, $this->dev->unreadNotifications()->count());
    }

    public function test_reject_requires_note(): void
    {
        $task = $this->createTicket(['status' => 'waiting_approval']);

        $this->actingAs($this->ba)->post("/it/tasks/{$task->id}/reject", [])
            ->assertSessionHasErrors('rejection_note');
    }

    public function test_board_is_accessible_by_it_roles_only(): void
    {
        $this->actingAs($this->ba)->get('/it/board')->assertOk();
        $this->actingAs($this->dev)->get('/it/board')->assertOk();

        $staff = User::where('email', 'siti.kredit@koperasi.test')->first();
        $this->actingAs($staff)->get('/it/board')->assertForbidden();

        $owner = User::where('email', 'owner@koperasi.test')->first();
        $this->actingAs($owner)->get('/it/board')->assertForbidden();
    }

    public function test_developer_cannot_create_or_approve_tickets(): void
    {
        $this->actingAs($this->dev)->get('/it/tasks/create')->assertForbidden();

        $task = $this->createTicket(['status' => 'waiting_approval']);
        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/approve")->assertForbidden();
        $this->assertSame('waiting_approval', $task->fresh()->status);
    }

    public function test_dashboard_redirects_it_roles_to_board(): void
    {
        $this->actingAs($this->ba)->get('/dashboard')->assertRedirect(route('it.board'));
        $this->actingAs($this->dev)->get('/dashboard')->assertRedirect(route('it.board'));
    }

    public function test_notification_page_lists_and_marks_read(): void
    {
        $task = $this->createTicket([
            'status' => 'qa_testing',
            'repository_url' => 'https://git.koperasi.test/x',
            'is_test_passed_100' => true,
        ]);

        $this->actingAs($this->dev)->post("/it/tasks/{$task->id}/submit");
        $this->assertSame('waiting_approval', $task->fresh()->status);
        $notification = $this->ba->unreadNotifications()->first();

        $response = $this->actingAs($this->ba)->get('/notifications');
        $response->assertOk();
        $response->assertSee('siap diuji');

        $this->actingAs($this->ba)
            ->patch("/notifications/{$notification->id}/read")
            ->assertRedirect(route('it.tasks.show', $task));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
