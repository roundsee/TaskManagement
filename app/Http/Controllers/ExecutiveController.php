<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutiveController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $operationalStaff = User::role('staff')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        // ===== Rekap harian keseluruhan =====
        $todayQuery = Task::whereDate('created_at', $today);

        $summary = [
            'total_today' => (clone $todayQuery)->count(),
            'auto_today' => (clone $todayQuery)->where('task_type', 'ksp_auto')->count(),
            'manual_today' => (clone $todayQuery)->where('task_type', 'ksp_manual')->count(),
            'complex_today' => (clone $todayQuery)->where('complexity_flag', 'complex')->count(),
            'total_month' => Task::whereDate('created_at', '>=', $monthStart)->count(),
        ];

        // ===== Rasio penanganan kasus normal vs kompleks (bulan berjalan) =====
        $monthFlags = Task::whereDate('created_at', '>=', $monthStart)
            ->select('complexity_flag', DB::raw('count(*) as total'))
            ->groupBy('complexity_flag')
            ->pluck('total', 'complexity_flag');

        $normalCount = (int) ($monthFlags['normal'] ?? 0);
        $complexCount = (int) ($monthFlags['complex'] ?? 0);
        $ratioTotal = max($normalCount + $complexCount, 1);

        $ratio = [
            'normal' => $normalCount,
            'complex' => $complexCount,
            'normal_pct' => round($normalCount / $ratioTotal * 100),
            'complex_pct' => round($complexCount / $ratioTotal * 100),
        ];

        // ===== Produktivitas per divisi (hari ini) =====
        $byDepartment = Task::query()
            ->join('users', 'users.id', '=', 'tasks.user_id')
            ->whereDate('tasks.created_at', $today)
            ->select(
                'users.department',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when tasks.complexity_flag = "complex" then 1 else 0 end) as complex_total'),
            )
            ->groupBy('users.department')
            ->orderByDesc('total')
            ->get();

        // ===== Status deployment IT =====
        $itStatuses = Task::where('task_type', 'it_dev')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $itSummary = [
            'active' => (int) $itStatuses->only(['new_request', 'in_progress', 'qa_testing', 'waiting_approval', 'approved'])->sum(),
            'deployed' => (int) ($itStatuses['deployed'] ?? 0),
        ];

        // ===== Daftar fitur IT terbaru yang di-deploy ke Production =====
        $recentDeployments = Task::where('task_type', 'it_dev')
            ->where('status', 'deployed')
            ->with(['assignee', 'ba'])
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        // ===== Keaktifan & SLA per staf operasional (hari ini) =====
        $taskStats = Task::whereDate('created_at', $today)
            ->select(
                'user_id',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when task_type = "ksp_manual" then 1 else 0 end) as manual_total'),
                DB::raw('avg(actual_duration_minutes) as avg_duration'),
                DB::raw('sum(case when complexity_flag = "complex" then 1 else 0 end) as complex_total'),
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $slaBenchmark = (int) config('task.sla_benchmark_minutes');

        $staffActivity = $operationalStaff->map(function (User $staff) use ($taskStats, $slaBenchmark) {
            $stat = $taskStats->get($staff->id);

            return (object) [
                'user' => $staff,
                'total' => (int) ($stat->total ?? 0),
                'manual_total' => (int) ($stat->manual_total ?? 0),
                'complex_total' => (int) ($stat->complex_total ?? 0),
                'avg_duration' => $stat && $stat->avg_duration !== null ? round((float) $stat->avg_duration) : null,
                'claimed' => $stat !== null,
                'sla_warning' => $stat && $stat->avg_duration !== null && (float) $stat->avg_duration > $slaBenchmark,
            ];
        });

        $participation = [
            'total_staff' => $operationalStaff->count(),
            'claimed' => $staffActivity->where('claimed', true)->count(),
            'pct' => $operationalStaff->count() > 0
                ? round($staffActivity->where('claimed', true)->count() / $operationalStaff->count() * 100)
                : 0,
        ];

        return view('executive.dashboard', compact(
            'today',
            'summary',
            'ratio',
            'byDepartment',
            'itSummary',
            'recentDeployments',
            'staffActivity',
            'participation',
            'slaBenchmark',
        ));
    }

    public function department(string $department)
    {
        $today = Carbon::today();

        $tasks = Task::query()
            ->whereDate('tasks.created_at', $today)
            ->whereHas('assignee', fn ($query) => $query->where('department', $department))
            ->with(['assignee', 'ba', 'logs.user'])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $tasks->count(),
            'auto' => $tasks->where('task_type', 'ksp_auto')->count(),
            'manual' => $tasks->where('task_type', 'ksp_manual')->count(),
            'complex' => $tasks->where('complexity_flag', 'complex')->count(),
            'avg_duration' => (int) round($tasks->where('actual_duration_minutes', '>', 0)->avg('actual_duration_minutes') ?? 0),
        ];

        return view('executive.department', [
            'department' => $department,
            'today' => $today,
            'tasks' => $tasks,
            'stats' => $stats,
            'slaBenchmark' => (int) config('task.sla_benchmark_minutes'),
        ]);
    }
}
