<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskApprovalDecision;
use App\Notifications\TaskReadyForApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ItTaskController extends Controller
{
    public const COLUMNS = [
        'new_request' => 'New Request',
        'in_progress' => 'In Dev',
        'qa_testing' => 'Testing',
        'waiting_approval' => 'Waiting BA Approval',
        'deployed' => 'Deployed',
    ];

    public function board(): View
    {
        $tasks = Task::where('task_type', 'it_dev')
            ->with(['assignee', 'ba'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Task $task) => $task->status === 'approved' ? 'deployed' : $task->status);

        return view('it.board', [
            'columns' => self::COLUMNS,
            'tasksByColumn' => collect(self::COLUMNS)
                ->mapWithKeys(fn (string $label, string $status) => [
                    $status => $tasks->get($status, collect()),
                ]),
        ]);
    }

    public function create(): View
    {
        $developers = User::role('developer')->get();

        return view('it.create', compact('developers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'business_requirement' => ['required', 'string'],
            'test_case_narration' => ['required', 'string'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $developer = User::whereKey($data['user_id'])->firstOrFail();

        if (! $developer->hasRole('developer')) {
            throw ValidationException::withMessages([
                'user_id' => 'Tiket hanya dapat di-assign ke Developer.',
            ]);
        }

        $task = Task::create([
            'task_type' => 'it_dev',
            'title' => $data['title'],
            'user_id' => $developer->id,
            'ba_id' => Auth::id(),
            'status' => 'new_request',
            'business_requirement' => $data['business_requirement'],
            'test_case_narration' => $data['test_case_narration'],
        ]);

        $task->logs()->create([
            'user_id' => Auth::id(),
            'action' => 'Tiket dibuat oleh BA',
            'created_at' => now(),
        ]);

        return redirect()->route('it.tasks.show', $task)
            ->with('success', 'Tiket baru berhasil dibuat.');
    }

    public function show(Task $task): View
    {
        $this->guardItTask($task);

        return view('it.show', [
            'task' => $task->load(['assignee', 'ba', 'logs.user']),
            'isBa' => Auth::user()->hasRole('ba'),
            'isAssignee' => Auth::id() === $task->user_id,
        ]);
    }

    public function start(Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardAssignee($task);
        $this->guardStatus($task, ['new_request']);

        $task->update([
            'status' => 'in_progress',
            'started_at' => $task->started_at ?? now(),
        ]);

        $this->log($task, 'Development dimulai');

        return $this->back($task, 'Tiket diproses. Kolom: In Dev.');
    }

    public function updateDevInfo(Request $request, Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardAssignee($task);
        $this->guardStatus($task, ['in_progress', 'qa_testing']);

        $data = $request->validate([
            'repository_url' => ['nullable', 'url', 'max:255'],
            'is_test_passed_100' => ['nullable', 'boolean'],
        ]);

        $task->update([
            'repository_url' => $data['repository_url'] ?? $task->repository_url,
            'is_test_passed_100' => (bool) ($data['is_test_passed_100'] ?? false),
        ]);

        $this->log($task, 'Update repo & status test script');

        return $this->back($task, 'Informasi development diperbarui.');
    }

    public function markTesting(Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardAssignee($task);
        $this->guardStatus($task, ['in_progress']);

        $task->update(['status' => 'qa_testing']);

        $this->log($task, 'Masuk tahap QA / pengetesan script');

        return $this->back($task, 'Tiket dipindahkan ke kolom Testing.');
    }

    public function submitForApproval(Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardAssignee($task);
        $this->guardStatus($task, ['in_progress', 'qa_testing']);

        if (! $task->is_test_passed_100) {
            throw ValidationException::withMessages([
                'test' => 'Submit for Approval terkunci. Centang "100% Test Passed" terlebih dahulu.',
            ]);
        }

        if (! $task->repository_url) {
            throw ValidationException::withMessages([
                'test' => 'Isi URL repository sebelum submit for approval.',
            ]);
        }

        $task->update(['status' => 'waiting_approval']);

        $recipients = $task->ba ? collect([$task->ba]) : User::role('ba')->get();
        foreach ($recipients as $ba) {
            $ba->notify(new TaskReadyForApproval($task));
        }

        $this->log($task, 'Submit for Approval (100% Test Passed)');

        return $this->back($task, 'Tiket dikirim ke BA untuk approval.');
    }

    public function approve(Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardStatus($task, ['waiting_approval']);

        $task->update([
            'status' => 'approved',
            'rejection_note' => null,
        ]);

        $task->assignee?->notify(new TaskApprovalDecision($task, 'approved'));

        $this->log($task, 'BA Approve tiket');

        return $this->back($task, 'Tiket di-approve. Developer dapat melakukan deploy.');
    }

    public function reject(Request $request, Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardStatus($task, ['waiting_approval']);

        $data = $request->validate([
            'rejection_note' => ['required', 'string', 'max:1000'],
        ]);

        $task->update([
            'status' => 'in_progress',
            'is_test_passed_100' => false,
            'rejection_note' => $data['rejection_note'],
        ]);

        $task->assignee?->notify(new TaskApprovalDecision($task, 'rejected', $data['rejection_note']));

        $this->log($task, 'BA Reject / Revisi: '.$data['rejection_note']);

        return $this->back($task, 'Tiket direject dan dikembalikan ke Developer.');
    }

    public function deploy(Task $task): RedirectResponse
    {
        $this->guardItTask($task);
        $this->guardAssignee($task);
        $this->guardStatus($task, ['approved']);

        $task->update([
            'status' => 'deployed',
            'completed_at' => now(),
            'actual_duration_minutes' => $task->started_at
                ? (int) $task->started_at->diffInMinutes(now())
                : $task->actual_duration_minutes,
        ]);

        $this->log($task, 'Deployed to Production');

        return $this->back($task, 'Tiket di-deploy ke Production dan ditutup.');
    }

    private function guardItTask(Task $task): void
    {
        if ($task->task_type !== 'it_dev') {
            abort(404);
        }
    }

    private function guardAssignee(Task $task): void
    {
        if (Auth::id() !== $task->user_id) {
            abort(403, 'Hanya developer pengerja yang dapat mengeksekusi aksi ini.');
        }
    }

    private function guardStatus(Task $task, array $allowed): void
    {
        if (! in_array($task->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'Aksi tidak valid untuk status saat ini ('.$task->status.').',
            ]);
        }
    }

    private function log(Task $task, string $action): void
    {
        $task->logs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'created_at' => now(),
        ]);
    }

    private function back(Task $task, string $message): RedirectResponse
    {
        return redirect()->route('it.tasks.show', $task)->with('success', $message);
    }
}
