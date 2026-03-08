<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.blank')] class extends Component
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

        if (! Auth::attempt([$field => $validated['login'], 'password' => $validated['password'], 'role' => 'teacher'], $this->remember)) {
            throw ValidationException::withMessages(['login' => 'Invalid credentials. This account is not a teacher.']);
        }

        Session::regenerate();
        $this->redirect(route('teacher.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen overflow-hidden bg-[#e7d0bb] relative flex items-center justify-center px-4 py-10">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -left-24 top-10 h-[280px] w-[360px] rounded-full bg-[#f1dfcf] opacity-90 blur-3xl"></div>
        <div class="absolute right-[-100px] top-[18%] h-[260px] w-[340px] rounded-full bg-[#dcc1a7] opacity-80 blur-3xl"></div>
        <div class="absolute left-[8%] bottom-[-110px] h-[280px] w-[480px] rounded-[50%] bg-[#dec2aa] opacity-90"></div>
        <div class="absolute right-[8%] bottom-[-130px] h-[260px] w-[420px] rounded-[50%] bg-[#d4b498] opacity-70"></div>
    </div>

    <div class="relative z-10 w-full max-w-sm rounded-[1.75rem] bg-[#fbfbfc] shadow-[0_24px_50px_rgba(97,68,41,0.18)] border border-white/60 px-7 py-8 md:px-8 md:py-9 backdrop-blur-sm">
        <div class="text-center">
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-[1.4rem] bg-white shadow-[0_10px_24px_rgba(97,68,41,0.14)] border border-[#f0e3d8] p-2">
                <img src="{{ asset('images/clg logo.jpg') }}" alt="College Logo" class="h-full w-full rounded-[1rem] object-cover">
            </div>
            <h1 class="text-[2.1rem] font-extrabold tracking-tight text-[#4c443d]">Teacher Login</h1>
            <p class="mt-2 text-sm text-[#8f8174]">Secure access for internship management</p>
        </div>

        <x-auth-session-status class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

        <form wire:submit="authenticate" class="mt-6 space-y-4">
            <div>
                <label for="login" class="sr-only">Username or Email</label>
                <div class="flex items-center gap-3 rounded-full bg-[#f2efed] px-4 py-3 text-[#a49486] shadow-inner border border-[#ece3dc]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5a7.5 7.5 0 10-7.5 0m7.5 0a9 9 0 11-7.5 0m7.5 0a7.463 7.463 0 01-7.5 0" />
                    </svg>
                    <input wire:model="login" id="login" type="text" name="login" required autofocus autocomplete="username" placeholder="Username or email" class="w-full border-0 bg-transparent p-0 text-lg text-[#6c6259] placeholder:text-[#b2a59a] focus:outline-none focus:ring-0">
                </div>
                <x-input-error :messages="$errors->get('login')" class="mt-2 text-sm text-red-500" />
            </div>

            <div>
                <label for="password" class="sr-only">Password</label>
                <div class="flex items-center gap-3 rounded-full bg-[#f2efed] px-4 py-3 text-[#a49486] shadow-inner border border-[#ece3dc]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.125 4.125 0 10-8.25 0V10.5m-.75 0h9a1.5 1.5 0 011.5 1.5v6A1.5 1.5 0 0116.5 19.5h-9A1.5 1.5 0 016 18v-6a1.5 1.5 0 011.5-1.5z" />
                    </svg>
                    <input wire:model="password" id="password" type="password" name="password" required autocomplete="current-password" placeholder="Password" class="w-full border-0 bg-transparent p-0 text-lg text-[#6c6259] placeholder:text-[#b2a59a] focus:outline-none focus:ring-0">
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-500" />
            </div>

            <label for="remember" class="flex items-center gap-2.5 text-[0.95rem] text-[#7c6f64] select-none cursor-pointer">
                <input wire:model="remember" id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-[#d8c4b2] text-[#e4ad56] focus:ring-[#e4ad56] focus:ring-offset-0">
                <span>Remember me</span>
            </label>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-[#efbe63] to-[#e37a8a] px-6 py-3 text-[1.05rem] font-medium uppercase tracking-wide text-white shadow-[0_12px_22px_rgba(182,120,81,0.24)] transition hover:translate-y-[-1px] hover:shadow-[0_16px_28px_rgba(182,120,81,0.3)]">
                Log In
            </button>

            <div class="text-center">
                <a href="{{ route('password.request') }}" class="text-[0.95rem] text-[#8f8174] transition hover:text-[#5e5147]" wire:navigate>
                    Forget Password
                </a>
            </div>
        </form>

        <div class="mt-28 text-center text-[0.95rem] text-[#8f8174]">
            Not a member?
            <a href="{{ route('login') }}" class="font-medium text-[#5a74dc] hover:text-[#3551c7]" wire:navigate>
                Student login now
            </a>
        </div>
    </div>
</div>
