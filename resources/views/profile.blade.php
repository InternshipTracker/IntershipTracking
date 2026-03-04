<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border border-slate-200">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    @php
                        $profilePhotoPath = auth()->user()->profile_photo_path
                            ? ltrim(preg_replace('#^(storage/|public/)#', '', auth()->user()->profile_photo_path), '/')
                            : null;
                        $profilePhotoUrl = $profilePhotoPath && Storage::disk('public')->exists($profilePhotoPath)
                            ? Storage::disk('public')->url($profilePhotoPath)
                            : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=0f172a&color=fff';
                    @endphp
                    <img
                        src="{{ $profilePhotoUrl }}"
                        alt="Profile"
                        style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #2652f1; background: #fff; box-shadow: 0 2px 8px #0001;"
                    />
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</h3>
                        <p class="text-sm text-slate-600">{{ auth()->user()->email }}</p>
                        <p class="text-xs text-slate-500 mt-1 capitalize">Role: {{ auth()->user()->role }}</p>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
