<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                {{ $task->title }}
            </h2>
            <x-status-badge :status="$task->status" class="shrink-0" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @foreach ($errors->all() as $error)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                    {{ $error }}
                </div>
            @endforeach

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Konten utama --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Rincian Tiket</h3>

                        <dl class="space-y-4 text-sm">
                            <div>
                                <dt class="text-gray-400 mb-1">Business Requirement</dt>
                                <dd class="text-gray-800 whitespace-pre-line">{{ $task->business_requirement ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400 mb-1">Narasi Test Case (dari BA)</dt>
                                <dd class="text-gray-800 whitespace-pre-line bg-gray-50 border border-gray-100 rounded p-3">{{ $task->test_case_narration ?: '—' }}</dd>
                            </div>
                            @if ($task->description)
                                <div>
                                    <dt class="text-gray-400 mb-1">Deskripsi</dt>
                                    <dd class="text-gray-800 whitespace-pre-line">{{ $task->description }}</dd>
                                </div>
                            @endif
                            @if ($task->rejection_note)
                                <div>
                                    <dt class="text-red-400 mb-1">Catatan Revisi BA</dt>
                                    <dd class="text-red-700 bg-red-50 border border-red-100 rounded p-3">{{ $task->rejection_note }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Form developer: repo + 100% test passed --}}
                    @if ($isAssignee && in_array($task->status, ['in_progress', 'qa_testing']))
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Development & Scripting</h3>

                            <form method="POST" action="{{ route('it.tasks.dev-info', $task) }}" class="space-y-4">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <x-input-label for="repository_url" value="URL Repository" />
                                    <x-text-input id="repository_url" name="repository_url" type="url" class="mt-1 block w-full"
                                        :value="old('repository_url', $task->repository_url)" placeholder="https://git.koperasi.test/..." />
                                    <x-input-error :messages="$errors->get('repository_url')" class="mt-2" />
                                </div>

                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="is_test_passed_100" value="1" @checked(old('is_test_passed_100', $task->is_test_passed_100))
                                        class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" />
                                    <span class="text-sm text-gray-700 font-medium">100% Test Passed</span>
                                </label>
                                <p class="text-xs text-gray-400 -mt-2">Tombol Submit for Approval hanya aktif setelah status test script 100% passed.</p>

                                <x-secondary-button>Simpan</x-secondary-button>
                            </form>
                        </div>
                    @endif

                    {{-- Log aktivitas --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Riwayat Aktivitas</h3>
                        <ul class="space-y-3">
                            @forelse ($task->logs as $log)
                                <li class="flex items-start gap-3 text-sm">
                                    <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-indigo-400 shrink-0"></span>
                                    <div class="min-w-0">
                                        <span class="text-gray-800">{{ $log->action }}</span>
                                        <span class="text-gray-400">— {{ $log->user?->name ?? 'Sistem' }}</span>
                                        <div class="text-xs text-gray-400">{{ $log->created_at?->format('d M Y H:i') }}</div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-gray-400">Belum ada aktivitas.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                {{-- Panel samping: info + aksi --}}
                <div class="space-y-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Informasi</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">Developer</dt>
                                <dd class="text-gray-800 text-right">{{ $task->assignee?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">BA Penanggung Jawab</dt>
                                <dd class="text-gray-800 text-right">{{ $task->ba?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">Repository</dt>
                                <dd class="text-right">
                                    @if ($task->repository_url)
                                        <a href="{{ $task->repository_url }}" target="_blank" class="text-indigo-600 hover:underline break-all">buka link</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">Status Test</dt>
                                <dd class="text-right">
                                    @if ($task->is_test_passed_100)
                                        <span class="text-green-700 font-medium">100% Passed</span>
                                    @else
                                        <span class="text-gray-400">Belum passed</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-400">Dibuat</dt>
                                <dd class="text-gray-800 text-right">{{ $task->created_at->format('d M Y H:i') }}</dd>
                            </div>
                            @if ($task->started_at)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-400">Mulai dikerjakan</dt>
                                    <dd class="text-gray-800 text-right">{{ $task->started_at->format('d M Y H:i') }}</dd>
                                </div>
                            @endif
                            @if ($task->completed_at)
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-400">Selesai / Deploy</dt>
                                    <dd class="text-gray-800 text-right">{{ $task->completed_at->format('d M Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-400">Durasi</dt>
                                    <dd class="text-gray-800 text-right">{{ $task->actual_duration_minutes }} menit</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Aksi Developer --}}
                    @if ($isAssignee)
                        @if ($task->status === 'new_request')
                            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                <p class="text-sm text-gray-500 mb-3">Tiket menunggu untuk dikerjakan.</p>
                                <form method="POST" action="{{ route('it.tasks.start', $task) }}">
                                    @csrf
                                    <x-primary-button class="w-full justify-center">Mulai Kerjakan</x-primary-button>
                                </form>
                            </div>
                        @endif

                        @if ($task->status === 'in_progress')
                            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                <p class="text-sm text-gray-500 mb-3">Selesai coding & masuk tahap pengetesan?</p>
                                <form method="POST" action="{{ route('it.tasks.testing', $task) }}">
                                    @csrf
                                    <x-secondary-button class="w-full justify-center">Masuk Kolom Testing</x-secondary-button>
                                </form>
                            </div>
                        @endif

                        @if (in_array($task->status, ['in_progress', 'qa_testing']))
                            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                <p class="text-sm text-gray-500 mb-3">
                                    @if ($task->is_test_passed_100)
                                        Test sudah 100% passed — tiket siap dikirim ke BA.
                                    @else
                                        Tombol terkunci sampai status <strong>100% Test Passed</strong> dicentang.
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('it.tasks.submit', $task) }}">
                                    @csrf
                                    @if ($task->is_test_passed_100 && $task->repository_url)
                                        <x-primary-button class="w-full justify-center">Submit for Approval</x-primary-button>
                                    @else
                                        <x-primary-button class="w-full justify-center" disabled>Submit for Approval</x-primary-button>
                                    @endif
                                </form>
                            </div>
                        @endif

                        @if ($task->status === 'waiting_approval')
                            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                <p class="text-sm text-gray-500">Menunggu persetujuan BA...</p>
                            </div>
                        @endif

                        @if ($task->status === 'approved')
                            <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-green-200">
                                <p class="text-sm text-green-700 mb-3">Tiket sudah di-approve. Siap rilis ke Production.</p>
                                <form method="POST" action="{{ route('it.tasks.deploy', $task) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full justify-center inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Deploy to Production
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if ($task->status === 'deployed')
                            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                                <p class="text-sm text-green-700 font-medium">Tiket sudah di-deploy ke Production (Closed).</p>
                            </div>
                        @endif
                    @endif

                    {{-- Aksi BA --}}
                    @if ($isBa && $task->status === 'waiting_approval')
                        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Keputusan BA</h3>

                            <form method="POST" action="{{ route('it.tasks.approve', $task) }}">
                                @csrf
                                <button type="submit"
                                    class="w-full justify-center inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Approve
                                </button>
                            </form>

                            <details class="group" {{ old('rejection_note') !== null ? 'open' : '' }}>
                                <summary class="cursor-pointer list-none text-center text-xs font-semibold uppercase tracking-widest text-red-600 hover:text-red-700 px-4 py-2 border border-red-200 rounded-md">
                                    Reject / Revisi
                                </summary>
                                <form method="POST" action="{{ route('it.tasks.reject', $task) }}" class="mt-3 space-y-2">
                                    @csrf
                                    <textarea name="rejection_note" rows="3" required
                                        class="block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm"
                                        placeholder="Jelaskan alasan reject / revisi">{{ old('rejection_note') }}</textarea>
                                    <x-input-error :messages="$errors->get('rejection_note')" class="mt-1" />
                                    <button type="submit"
                                        class="w-full justify-center inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Kirim Reject
                                    </button>
                                </form>
                            </details>
                        </div>
                    @endif

                    @if ($isBa && $task->status !== 'waiting_approval' && $task->status !== 'deployed')
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <p class="text-sm text-gray-500">Menunggu developer men-submit untuk approval.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
