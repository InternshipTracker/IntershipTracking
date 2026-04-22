<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo;

    /**
     * Delete the profile photo for the currently authenticated user.
     */
    public function deleteProfilePhoto(): void
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        $this->photo = null;
        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->fill($validated);

        if ($this->photo) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $this->photo->store('profile-photos', 'public');
            $this->photo = null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header class="flex flex-col gap-2 border-b border-slate-200 pb-5 dark:border-slate-800">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-400">Profile editing</p>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="text-sm leading-6 text-slate-600 dark:text-slate-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-8">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 sm:p-6 dark:border-slate-800 dark:bg-slate-950/60">
            <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div class="flex items-start gap-4">
                    @php
                        $user = auth()->user();
                        $profilePhotoPath = $user->profile_photo_path
                            ? ltrim(preg_replace('#^(storage/|public/)#', '', $user->profile_photo_path), '/')
                            : null;
                        $currentPhotoUrl = $profilePhotoPath && Storage::disk('public')->exists($profilePhotoPath)
                            ? Storage::url($profilePhotoPath)
                            : ($user->role === 'teacher' ? asset('/images/default-teacher.png') : asset('/images/default-user.png'));
                    @endphp

                    <img
                        src="{{ $photo ? $photo->temporaryUrl() : $currentPhotoUrl }}"
                        alt="Profile photo"
                        class="h-24 w-24 rounded-full border border-slate-200 object-cover shadow-sm dark:border-slate-700"
                    >

                    <div class="space-y-3">
                        <div>
                            <x-input-label for="photo" :value="__('Profile Photo')" />
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose a clear photo that makes the account feel personal and easy to recognize.</p>
                        </div>

                        <label for="photo" class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                            {{ __('Choose photo') }}
                        </label>
                        <input wire:model="photo" id="photo" type="file" class="sr-only" accept="image/*" />

                        <p class="text-xs text-slate-500 dark:text-slate-400">PNG, JPG, JPEG, or WEBP. Max size: 2 MB.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('photo')" />
                    </div>
                </div>

                @if(auth()->user()->profile_photo_path)
                    <button type="button" wire:click="deleteProfilePhoto" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-600 shadow-sm transition hover:border-rose-300 hover:bg-rose-50 md:self-start dark:border-rose-900/60 dark:bg-slate-900 dark:text-rose-300 dark:hover:border-rose-700 dark:hover:bg-rose-950/30">Remove photo</button>
                @endif
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-2 block w-full border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-2 block w-full border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                <p class="text-sm text-amber-900 dark:text-amber-100">
                    {{ __('Your email address is unverified.') }}

                    <button wire:click.prevent="sendVerification" class="ml-1 inline-flex items-center rounded-md font-medium text-amber-900 underline decoration-amber-400 decoration-2 underline-offset-4 hover:text-amber-950 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:text-amber-200 dark:decoration-amber-600 dark:hover:text-amber-100 dark:focus:ring-offset-slate-950">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-3 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">Review your changes before saving. Updated details appear immediately in the account menu.</p>

            <div class="flex items-center gap-4">
                <x-primary-button wire:loading.attr="disabled" wire:target="updateProfileInformation,photo">
                    {{ __('Save Changes') }}
                </x-primary-button>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </div>
    </form>
</section>
