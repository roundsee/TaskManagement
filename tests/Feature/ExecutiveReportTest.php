<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $hrd;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);

        $this->owner = User::where('email', 'owner@koperasi.test')->first();
        $this->hrd = User::where('email', 'hrd@koperasi.test')->first();
        $this->staff = User::where('email', 'siti.kredit@koperasi.test')->first();
    }

    public function test_owner_and_hrd_can_access_reports(): void
    {
        $this->actingAs($this->owner)->get('/reports')->assertOk();
        $this->actingAs($this->hrd)->get('/reports')->assertOk();
    }

    public function test_other_roles_cannot_access_reports(): void
    {
        $this->actingAs($this->staff)->get('/reports')->assertForbidden();

        $ba = User::where('email', 'ba@koperasi.test')->first();
        $this->actingAs($ba)->get('/reports')->assertForbidden();
    }

    public function test_dashboard_redirects_owner_and_hrd_to_reports(): void
    {
        $this->actingAs($this->owner)->get('/dashboard')->assertRedirect(route('reports.index'));
        $this->actingAs($this->hrd)->get('/dashboard')->assertRedirect(route('reports.index'));
    }

    public function test_report_reflects_today_activity_and_complex_ratio(): void
    {
        // 2 normal + 1 kompleks hari ini untuk staf
        Task::create([
            'task_type' => 'ksp_auto',
            'title' => 'Validasi Berkas A',
            'user_id' => $this->staff->id,
            'status' => 'completed',
            'complexity_flag' => 'normal',
            'actual_duration_minutes' => 15,
        ]);
        Task::create([
            'task_type' => 'ksp_auto',
            'title' => 'Validasi Berkas B',
            'user_id' => $this->staff->id,
            'status' => 'completed',
            'complexity_flag' => 'normal',
            'actual_duration_minutes' => 18,
        ]);
        Task::create([
            'task_type' => 'ksp_manual',
            'title' => 'Investigasi Sengketa',
            'user_id' => $this->staff->id,
            'status' => 'completed',
            'complexity_flag' => 'complex',
            'complexity_note' => 'Perlu penelusuran.',
            'actual_duration_minutes' => 120,
        ]);

        $response = $this->actingAs($this->owner)->get('/reports');

        $response->assertOk();
        $response->assertSee('Kepatuhan SLA');
        $response->assertSee($this->staff->name);
        $response->assertSee('Belum mengisi');
    }

    public function test_report_lists_recent_it_deployments(): void
    {
        $dev = User::where('email', 'dev@koperasi.test')->first();
        $ba = User::where('email', 'ba@koperasi.test')->first();

        Task::create([
            'task_type' => 'it_dev',
            'title' => 'Fitur Cetak Struk v2',
            'user_id' => $dev->id,
            'ba_id' => $ba->id,
            'status' => 'deployed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->hrd)->get('/reports');

        $response->assertOk();
        $response->assertSee('Fitur IT Terbaru di Production');
        $response->assertSee('Fitur Cetak Struk v2');
    }

    public function test_owner_can_drill_down_to_department_detail(): void
    {
        Task::create([
            'task_type' => 'ksp_auto',
            'title' => 'Validasi Berkas Agunan Kredit',
            'description' => 'Cek sertifikat.',
            'user_id' => $this->staff->id,
            'status' => 'completed',
            'complexity_flag' => 'complex',
            'complexity_note' => 'Berkas sengketa waris.',
            'loan_reference_no' => 'LOAN-2026-0777',
            'actual_duration_minutes' => 90,
        ]);

        $response = $this->actingAs($this->owner)->get('/reports/department/Kredit');

        $response->assertOk();
        $response->assertSee('Detail Divisi: Kredit');
        $response->assertSee('Validasi Berkas Agunan Kredit');
        $response->assertSee('LOAN-2026-0777');
        $response->assertSee($this->staff->name);
        $response->assertSee('Berkas sengketa waris.');
    }

    public function test_department_detail_excludes_tasks_from_other_departments(): void
    {
        Task::create([
            'task_type' => 'ksp_auto',
            'title' => 'Task Divisi Kredit',
            'user_id' => $this->staff->id,
            'status' => 'completed',
        ]);

        $ops = User::where('email', 'agus.operasional@koperasi.test')->first();
        Task::create([
            'task_type' => 'ksp_manual',
            'title' => 'Task Divisi Operasional',
            'user_id' => $ops->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->hrd)->get('/reports/department/Kredit');

        $response->assertOk();
        $response->assertSee('Task Divisi Kredit');
        $response->assertDontSee('Task Divisi Operasional');
    }

    public function test_department_detail_shows_empty_state(): void
    {
        $response = $this->actingAs($this->owner)->get('/reports/department/Kredit');

        $response->assertOk();
        $response->assertSee('Belum ada tugas dari divisi ini hari ini.');
    }

    public function test_department_detail_requires_executive_role(): void
    {
        $this->actingAs($this->staff)->get('/reports/department/Kredit')->assertForbidden();
    }
}
