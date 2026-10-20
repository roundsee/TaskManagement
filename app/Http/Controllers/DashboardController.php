<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('staff')) {
            return view('staff.dashboard', [
                'autoTasks' => Task::where('user_id', $user->id)
                    ->where('task_type', 'ksp_auto')
                    ->forToday()
                    ->orderByDesc('started_at')
                    ->orderByDesc('created_at')
                    ->get(),
                'manualTasks' => Task::where('user_id', $user->id)
                    ->where('task_type', 'ksp_manual')
                    ->forToday()
                    ->orderByDesc('started_at')
                    ->get(),
            ]);
        }

        if ($user->hasAnyRole(['ba', 'developer'])) {
            return redirect()->route('it.board');
        }

        return redirect()->route('reports.index');
    }
}
