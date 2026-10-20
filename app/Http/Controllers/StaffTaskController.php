<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffTaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'is_complex' => ['nullable', 'boolean'],
            'complexity_note' => ['required_if:is_complex,1', 'nullable', 'string'],
        ]);

        $isComplex = (bool) ($data['is_complex'] ?? false);
        $startedAt = Carbon::today()->setTimeFromTimeString($data['start_time']);
        $completedAt = ! empty($data['end_time'])
            ? Carbon::today()->setTimeFromTimeString($data['end_time'])
            : null;

        $task = Task::create([
            'task_type' => 'ksp_manual',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'user_id' => Auth::id(),
            'status' => $completedAt ? 'completed' : 'in_progress',
            'complexity_flag' => $isComplex ? 'complex' : 'normal',
            'complexity_note' => $data['complexity_note'] ?? null,
            'estimated_duration_minutes' => $isComplex ? 0 : config('task.sla_benchmark_minutes'),
            'actual_duration_minutes' => $completedAt ? (int) $startedAt->diffInMinutes($completedAt) : 0,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $task->logs()->create([
            'user_id' => Auth::id(),
            'action' => 'Task manual dibuat',
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Tugas manual berhasil dicatat.');
    }

    public function complete(Task $task): RedirectResponse
    {
        $user = Auth::user();

        if ($task->task_type !== 'ksp_manual' || $task->user_id !== $user->id || $task->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'task' => 'Hanya tugas manual milik Anda yang sedang berjalan yang dapat diselesaikan.',
            ]);
        }

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
            'actual_duration_minutes' => (int) $task->started_at->diffInMinutes(now()),
        ]);

        $task->logs()->create([
            'user_id' => $user->id,
            'action' => 'Task manual diselesaikan',
            'created_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Tugas ditandai selesai.');
    }
}
