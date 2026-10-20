<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard Eksekutif & Monitoring') }}
            </h2>
            <span class="text-sm text-gray-500">{{ $today->translatedFormat('l, d F Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Tugas Hari Ini</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800">{{ $summary['total_today'] }}</div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $summary['auto_today'] }} otomatis · {{ $summary['manual_today'] }} manual
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Keaktifan Staf</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800">{{ $participation['pct'] }}%</div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $participation['claimed'] }} dari {{ $participation['total_staff'] }} staf mengisi laporan
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Kasus Kompleks (bulan ini)</div>
                    <div class="mt-2 text-3xl font-bold text-amber-600">{{ $ratio['complex_pct'] }}%</div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $ratio['complex'] }} kompleks · {{ $ratio['normal'] }} normal
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Fitur IT Deployed</div>
                    <div class="mt-2 text-3xl font-bold text-green-600">{{ $itSummary['deployed'] }}</div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $itSummary['active'] }} tiket IT masih dalam proses
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Produktivitas per divisi --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Produktivitas per Divisi (Hari Ini)</h3>

                    @if ($byDepartment->isEmpty())
                        <p class="text-sm text-gray-400">Belum ada aktivitas hari ini.</p>
                    @else
                        <div class="space-y-4">
                            @php($maxDept = $byDepartment->max('total'))
                            @foreach ($byDepartment as $dept)
                                <a href="{{ route('reports.department', $dept->department) }}"
                                   class="block rounded-md -mx-2 px-2 py-1.5 hover:bg-gray-50 transition group">
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700 group-hover:text-indigo-600">
                                            {{ $dept->department }}
                                            <span class="text-gray-300 group-hover:text-indigo-400 text-xs">›</span>
                                        </span>
                                        <span class="text-gray-500">
                                            {{ $dept->total }} tugas
                                            @if ($dept->complex_total > 0)
                                                · <span class="text-amber-600">{{ $dept->complex_total }} kompleks</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-indigo-500 group-hover:bg-indigo-600 h-2 rounded-full"
                                            style="width: {{ $maxDept > 0 ? round($dept->total / $maxDept * 100) : 0 }}%"></div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Rasio Normal vs Kompleks (Bulan Berjalan)</h4>
                        <div class="flex h-3 rounded-full overflow-hidden bg-gray-100">
                            <div class="bg-gray-400" style="width: {{ $ratio['normal_pct'] }}%"></div>
                            <div class="bg-amber-500" style="width: {{ $ratio['complex_pct'] }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-gray-400 inline-block"></span> Normal {{ $ratio['normal_pct'] }}%</span>
                            <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500 inline-block"></span> Kompleks {{ $ratio['complex_pct'] }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Rilis IT terbaru --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Fitur IT Terbaru di Production</h3>

                    <ul class="divide-y divide-gray-100">
                        @forelse ($recentDeployments as $task)
                            <li class="py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800">{{ $task->title }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Dev: {{ $task->assignee?->name ?? '-' }} · BA: {{ $task->ba?->name ?? '-' }}
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-xs text-gray-400 whitespace-nowrap">
                                        {{ $task->completed_at?->diffForHumans() }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-gray-400">Belum ada fitur yang di-deploy.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Keaktifan & SLA per staf --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 pb-3">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Keaktifan & Kepatuhan SLA per Staf Operasional (Hari Ini)</h3>
                    <p class="text-xs text-gray-400 mt-1">Benchmark SLA: {{ $slaBenchmark }} menit/berkas · tugas kompleks dikecualikan.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Divisi</th>
                                <th class="px-6 py-3 text-center">Total Tugas</th>
                                <th class="px-6 py-3 text-center">Manual</th>
                                <th class="px-6 py-3 text-center">Kompleks</th>
                                <th class="px-6 py-3 text-center">Rata-rata Durasi</th>
                                <th class="px-6 py-3 text-center">Status Pengisian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($staffActivity as $row)
                                <tr>
                                    <td class="px-6 py-3 font-medium text-gray-800">{{ $row->user->name }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $row->user->department }}</td>
                                    <td class="px-6 py-3 text-center">{{ $row->total }}</td>
                                    <td class="px-6 py-3 text-center">{{ $row->manual_total }}</td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($row->complex_total > 0)
                                            <span class="text-amber-600 font-medium">{{ $row->complex_total }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($row->avg_duration === null)
                                            <span class="text-gray-400">—</span>
                                        @else
                                            <span class="{{ $row->sla_warning ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                                {{ $row->avg_duration }} mnt
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($row->claimed)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Belum mengisi</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
