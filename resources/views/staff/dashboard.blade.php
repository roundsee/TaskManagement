<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Staf Operasional') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Bagian 1: Aktivitas Otomatis Hari Ini --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">Aktivitas Otomatis Hari Ini</h3>
                    <p class="text-sm text-gray-500 mb-4">Data terisi otomatis saat Anda bekerja di sistem KSP.</p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Waktu</th>
                                    <th class="px-4 py-3">Judul</th>
                                    <th class="px-4 py-3">No. Pengajuan</th>
                                    <th class="px-4 py-3">Durasi</th>
                                    <th class="px-4 py-3">Kompleksitas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($autoTasks as $task)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                            {{ $task->started_at?->format('H:i') ?? $task->created_at->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-800">{{ $task->title }}</div>
                                            <div class="text-xs text-gray-500">{{ $task->description }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ $task->loan_reference_no ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $task->actual_duration_minutes }} mnt</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($task->complexity_flag === 'complex')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    Kompleks
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                    Normal
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                            Belum ada aktivitas otomatis hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Bagian 2a: Form Input Tugas Manual --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{ complex: false }">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Input Tugas Manual</h3>
                        <p class="text-sm text-gray-500 mb-4">Untuk kegiatan di luar sistem (survey, rapat, mediasi, dll).</p>

                        <form method="POST" action="{{ route('staff.tasks.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="title" value="Judul Tugas" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                    :value="old('title')" required autofocus placeholder="Mis. Survey Lapangan Calon Anggota" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="description" value="Deskripsi" />
                                <textarea id="description" name="description" rows="2"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    placeholder="Keterangan singkat kegiatan">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="start_time" value="Jam Mulai" />
                                    <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full"
                                        :value="old('start_time', now()->format('H:i'))" required />
                                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="end_time" value="Jam Selesai (opsional)" />
                                    <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full"
                                        :value="old('end_time')" />
                                    <p class="mt-1 text-xs text-gray-400">Kosongkan jika masih berjalan.</p>
                                    <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <input id="is_complex" name="is_complex" type="checkbox" value="1"
                                    x-model="complex"
                                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <div>
                                    <x-input-label for="is_complex" value="Kompleks / Investigasi Extra" />
                                    <p class="text-xs text-gray-400">Aktifkan untuk pengajuan bermasalah; bebas benchmark SLA {{ config('task.sla_benchmark_minutes') }} menit.</p>
                                </div>
                            </div>

                            <div x-show="complex" x-cloak>
                                <x-input-label for="complexity_note" value="Catatan Kendala" />
                                <textarea id="complexity_note" name="complexity_note" rows="2"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                    placeholder="Jelaskan kendala yang dihadapi">{{ old('complexity_note') }}</textarea>
                                <x-input-error :messages="$errors->get('complexity_note')" class="mt-2" />
                            </div>

                            {{-- Sinkronkan checkbox Alpine <-> input form (hidden saat false) --}}
                            <input type="hidden" name="is_complex" value="0" x-bind:disabled="complex" />

                            <x-primary-button>Simpan Tugas</x-primary-button>
                        </form>
                    </div>
                </div>

                {{-- Bagian 2b: Tugas Manual Hari Ini --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Tugas Manual Hari Ini</h3>
                        <p class="text-sm text-gray-500 mb-4">Catatan kegiatan non-sistem Anda hari ini.</p>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($manualTasks as $task)
                                <li class="py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-800 truncate">{{ $task->title }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ $task->started_at?->format('H:i') }}
                                            @if ($task->completed_at)
                                                – {{ $task->completed_at->format('H:i') }}
                                                <span class="text-gray-400">({{ $task->actual_duration_minutes }} mnt)</span>
                                            @else
                                                <span class="text-amber-600 font-medium">· sedang berjalan</span>
                                            @endif
                                        </div>
                                        @if ($task->complexity_flag === 'complex')
                                            <div class="mt-1 text-xs text-amber-700 bg-amber-50 inline-block px-2 py-0.5 rounded">
                                                Kompleks: {{ $task->complexity_note ?: 'tanpa catatan' }}
                                            </div>
                                        @endif
                                    </div>

                                    @if ($task->status === 'in_progress')
                                        <form method="POST" action="{{ route('staff.tasks.complete', $task) }}" class="shrink-0">
                                            @csrf
                                            @method('PATCH')
                                            <x-secondary-button class="text-xs">Selesaikan</x-secondary-button>
                                        </form>
                                    @else
                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Selesai
                                        </span>
                                    @endif
                                </li>
                            @empty
                                <li class="py-6 text-center text-gray-400 text-sm">
                                    Belum ada tugas manual hari ini.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
