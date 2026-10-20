<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat Tiket Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('it.tasks.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="title" value="Judul Tiket" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                :value="old('title')" required autofocus placeholder="Mis. Fitur ekspor laporan ke Excel" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="user_id" value="Assign ke Developer" />
                            <select id="user_id" name="user_id" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">— Pilih developer —</option>
                                @foreach ($developers as $developer)
                                    <option value="{{ $developer->id }}" @selected(old('user_id') == $developer->id)>
                                        {{ $developer->name }} ({{ $developer->department }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="business_requirement" value="Business Requirement" />
                            <textarea id="business_requirement" name="business_requirement" rows="4" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                placeholder="Jelaskan kebutuhan bisnis yang harus dipenuhi">{{ old('business_requirement') }}</textarea>
                            <x-input-error :messages="$errors->get('business_requirement')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="test_case_narration" value="Narasi Test Case" />
                            <textarea id="test_case_narration" name="test_case_narration" rows="4" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                placeholder="Langkah uji yang harus diikuti developer, mis.: Buka laporan, pilih periode, klik Export, pastikan file XLSX terunduh">{{ old('test_case_narration') }}</textarea>
                            <p class="mt-1 text-xs text-gray-400">Narasi ini menjadi acuan developer menyusun test script otomatis.</p>
                            <x-input-error :messages="$errors->get('test_case_narration')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Simpan Tiket</x-primary-button>
                            <a href="{{ route('it.board') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
