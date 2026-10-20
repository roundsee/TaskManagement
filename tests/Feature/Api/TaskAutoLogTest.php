<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAutoLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        config(['task.api_token' => 'test-token-123']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_email' => 'siti.kredit@koperasi.test',
            'loan_reference_no' => 'LOAN-2026-0089',
            'action_title' => 'Validasi Berkas Agunan',
            'description' => 'Validasi dokumen sertifikat tanah atas nama Budi Santoso',
            'is_complex' => false,
            'complexity_note' => '',
        ], $overrides);
    }

    public function test_valid_request_creates_auto_task(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-123',
        ])->postJson('/api/v1/tasks/auto-log', $this->payload());

        $response->assertCreated();
        $response->assertJsonPath('task.status', 'completed');
        $response->assertJsonPath('task.loan_reference_no', 'LOAN-2026-0089');

        $task = Task::where('title', 'Validasi Berkas Agunan')->firstOrFail();
        $this->assertSame('ksp_auto', $task->task_type);
        $this->assertSame('normal', $task->complexity_flag);
        $this->assertSame(config('task.sla_benchmark_minutes'), $task->estimated_duration_minutes);
        $this->assertSame('Auto-created by System KSP', $task->logs()->first()->action);
    }

    public function test_request_without_token_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/tasks/auto-log', $this->payload());
        $response->assertUnauthorized();
        $this->assertSame(0, Task::count());
    }

    public function test_request_with_wrong_token_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-token',
        ])->postJson('/api/v1/tasks/auto-log', $this->payload());

        $response->assertUnauthorized();
        $this->assertSame(0, Task::count());
    }

    public function test_unknown_email_returns_validation_error(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-123',
        ])->postJson('/api/v1/tasks/auto-log', $this->payload([
            'user_email' => 'tidak.ada@koperasi.test',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('user_email');
    }

    public function test_complex_task_skips_sla_and_keeps_note(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-123',
        ])->postJson('/api/v1/tasks/auto-log', $this->payload([
            'loan_reference_no' => 'LOAN-2026-0091',
            'action_title' => 'Survey Lokasi Agunan',
            'is_complex' => true,
            'complexity_note' => 'Agunan sengketa waris.',
        ]));

        $response->assertCreated();

        $task = Task::where('title', 'Survey Lokasi Agunan')->firstOrFail();
        $this->assertSame('complex', $task->complexity_flag);
        $this->assertSame('Agunan sengketa waris.', $task->complexity_note);
        $this->assertSame(0, $task->estimated_duration_minutes);
    }

    public function test_missing_required_fields_return_validation_error(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer test-token-123',
        ])->postJson('/api/v1/tasks/auto-log', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_email', 'loan_reference_no', 'action_title']);
    }
}
