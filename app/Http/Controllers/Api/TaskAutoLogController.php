<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskAutoLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_email' => ['required', 'email', 'exists:users,email'],
            'loan_reference_no' => ['required', 'string', 'max:100'],
            'action_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_complex' => ['sometimes', 'boolean'],
            'complexity_note' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['user_email'])->firstOrFail();
        $isComplex = (bool) ($data['is_complex'] ?? false);

        $task = Task::create([
            'task_type' => 'ksp_auto',
            'title' => $data['action_title'],
            'description' => $data['description'] ?? null,
            'user_id' => $user->id,
            'status' => 'completed',
            'loan_reference_no' => $data['loan_reference_no'],
            'complexity_flag' => $isComplex ? 'complex' : 'normal',
            'complexity_note' => $isComplex ? ($data['complexity_note'] ?? null) : null,
            'estimated_duration_minutes' => $isComplex ? 0 : config('task.sla_benchmark_minutes'),
        ]);

        $task->logs()->create([
            'user_id' => $user->id,
            'action' => 'Auto-created by System KSP',
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Task log created.',
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'loan_reference_no' => $task->loan_reference_no,
                'status' => $task->status,
                'complexity_flag' => $task->complexity_flag,
                'created_at' => $task->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
