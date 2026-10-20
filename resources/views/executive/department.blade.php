<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali ke Rekap</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                    Detail Divisi: {{ $department }}
                </h2>
            </div>
            <span class="text-sm text-gray-500 shrink-0">{{ $today->translatedFormat('l, d F Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ringkasan divisi --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Tugas</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Otomatis</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['auto'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Manual</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['manual'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Kompleks</div>
                    <div class="mt-2 text-2xl font-bold text-amber-600">{{ $stats['complex'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Rata-rata Durasi</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $stats['avg_duration'] }} <span class="text-sm font-normal text-gray-400">mnt</span></div>
                </div>
            </div>

            {{-- Daftar task --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 pb-3">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Rincian Tugas Hari Ini</h3>
                    <p class="text-xs text-gray-400 mt-1">Benchmark SLA: {{ $slaBenchmark }} menit/berkas · tugas kompleks dikecualikan.</p>
                </div>

                @if ($tasks->isEmpty())
                    <div class="px-6 pb-6 text-sm text-gray-400">Belum ada tugas dari divisi ini hari ini.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">Waktu</th>
                                    <th class="px-6 py-3">Judul</th>
                                    <th class="px-6 py-3">Staf</th>
                                    <th class="px-6 py-3">No. Pengajuan</th>
                                    <th class="px-6 py-3">Tipe</th>
                                    <th class="px-6 py-3 text-center">Durasi</th>
                                    <th class="px-6 py-3 text-center">Kompleksitas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($tasks as $task)
                                    @php($overSla = $task->complexity_flag !== 'complex' && $task->actual_duration_minutes > $slaBenchmark)
                                    <tr class="align-top">
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-500">{{ $task->created_at->format('H:i') }}</td>
                                        <td class="px-6 py-3 max-w-md">
                                            <div class="font-medium text-gray-800">{{ $task->title }}</div>
                                            @if ($task->description)
                                                <div class="text-xs text-gray-500 mt-0.5">{{ $task->description }}</div>
                                            @endif
                                            @if ($task->complexity_note)
                                                <div class="text-xs text-amber-700 mt-1">Catatan: {{ $task->complexity_note }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-gray-600">{{ $task->assignee?->name ?? '-' }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap font-mono text-xs text-gray-500">{{ $task->loan_reference_no ?? '-' }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <span class="text-xs text-gray-500">
                                                {{ $task->task_type === 'ksp_auto' ? 'Otomatis' : ($task->task_type === 'ksp_manual' ? 'Manual' : 'IT') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-center whitespace-nowrap {{ $overSla ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                            {{ $task->actual_duration_minutes }} mnt
                                        </td>
                                        <td class="px-6 py-3 text-center whitespace-nowrap">
                                            @if ($task->complexity_flag === 'complex')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Kompleks</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
