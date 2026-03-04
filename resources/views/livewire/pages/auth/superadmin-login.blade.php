<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $login = '';
    public string $password = '';
    public bool $remember = false;

    public function authenticate(): void
    {
        $validated = $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$field => $validated['login'], 'password' => $validated['password'], 'role' => 'superadmin'], $this->remember)) {
            throw ValidationException::withMessages(['login' => 'Invalid credentials. This account is not a super admin.']);
        }

        Session::regenerate();
        $this->redirect(route('superadmin.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-slate-900">👑 Super Admin Login</h1>
        <p class="text-slate-600 mt-2">Sign in to your super admin account</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="authenticate" class="space-y-4">
        <!-- Username or Email -->
        <div>
            <x-input-label for="login" :value="__('Username or Email')" />
            <x-text-input wire:model="login" id="login" class="block mt-1 w-full" type="text" name="login" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input wire:model="remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
            <label for="remember" class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</label>
        </div>

        <div class="flex flex-col gap-2">
            <x-primary-button class="w-full justify-center">
                {{ __('Log in as Super Admin') }}
            </x-primary-button>
            
            <a href="{{ route('login') }}" class="text-center text-sm text-slate-600 hover:text-slate-900" wire:navigate>
                Student Login >>
            </a>
        </div>
    </form>
</div>
