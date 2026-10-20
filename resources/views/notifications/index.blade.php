<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifikasi') }}
            </h2>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Tandai semua sudah dibaca
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                @forelse ($notifications as $notification)
                    <div class="p-4 flex items-start gap-3 {{ $notification->read_at ? 'opacity-60' : '' }}">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ $notification->data['message'] ?? 'Notifikasi' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="shrink-0">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                {{ $notification->read_at ? 'Buka' : 'Baca & Buka' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-gray-400">
                        Tidak ada notifikasi.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
