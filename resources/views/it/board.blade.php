<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('IT Board') }}
            </h2>
            @role('ba')
                <a href="{{ route('it.tasks.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    + Tiket Baru
                </a>
            @endrole
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4">
                @foreach ($columns as $status => $label)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center justify-between mb-3 px-1">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $label }}</h3>
                            <span class="text-xs text-gray-400 font-medium">{{ $tasksByColumn[$status]->count() }}</span>
                        </div>

                        <div class="space-y-3 min-h-[120px]">
                            @forelse ($tasksByColumn[$status] as $task)
                                <a href="{{ route('it.tasks.show', $task) }}"
                                   class="block bg-white border border-gray-200 rounded-md p-3 shadow-sm hover:border-indigo-300 hover:shadow transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-sm font-medium text-gray-800 leading-snug">{{ $task->title }}</span>
                                        @if ($task->status === 'approved')
                                            <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-cyan-100 text-cyan-800 uppercase">siap deploy</span>
                                        @endif
                                    </div>

                                    <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                                        <span class="truncate">{{ $task->assignee?->name ?? '-' }}</span>
                                        <span>{{ $task->created_at->diffForHumans(short: true) }}</span>
                                    </div>

                                    @if ($task->is_test_passed_100 && in_array($task->status, ['qa_testing', 'waiting_approval'], true))
                                        <div class="mt-2 text-[11px] font-medium text-green-700 bg-green-50 inline-block px-1.5 py-0.5 rounded">
                                            ✓ 100% Test Passed
                                        </div>
                                    @endif

                                    @if ($task->status === 'waiting_approval' && $task->ba_id === auth()->id())
                                        <div class="mt-2 text-[11px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">
                                            Perlu tindakan Anda
                                        </div>
                                    @endif
                                </a>
                            @empty
                                <div class="text-center text-xs text-gray-300 py-4">Kosong</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
