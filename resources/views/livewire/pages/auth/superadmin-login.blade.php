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

        if (! Auth::attempt([$field => $validated['login'], 'password' => $validated['password'], 'role' => 'superadmin'], $this->remember)) {
            throw ValidationException::withMessages(['login' => 'Invalid credentials. This account is not a super admin.']);
        }

        Session::regenerate();
        $this->redirect(route('superadmin.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen overflow-hidden bg-[#e7d0bb] text-white relative flex items-center justify-center px-4 py-10">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.34),_transparent_30%),radial-gradient(circle_at_85%_18%,_rgba(209,170,135,0.26),_transparent_24%),linear-gradient(135deg,#e7d0bb_0%,#dfc2a5_48%,#d5b392_100%)]"></div>
        <div class="absolute -left-24 top-12 h-[320px] w-[320px] rounded-full bg-white/20 blur-3xl"></div>
        <div class="absolute right-[-90px] bottom-[-40px] h-[280px] w-[340px] rounded-full bg-[#c89f79]/20 blur-3xl"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
    </div>

    <div class="relative z-10 grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-white/40 bg-white/45 shadow-[0_30px_80px_rgba(97,68,41,0.22)] backdrop-blur-xl md:grid-cols-[1.1fr_0.9fr]">
        <div class="hidden md:flex flex-col justify-between border-r border-white/35 p-10 bg-[linear-gradient(180deg,rgba(255,255,255,0.26),rgba(255,255,255,0.12))]">
            <div>
                <div class="inline-flex items-center gap-3 rounded-full border border-white/35 bg-white/30 px-4 py-2 text-sm text-[#6f5646]">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f2d8a6]/60 text-lg">👑</span>
                    <span>Super Admin Access</span>
                </div>

                <h1 class="mt-8 max-w-md text-4xl font-black leading-tight text-[#4f4035]">Control departments, teachers, and internship operations from one secure console.</h1>
                <p class="mt-5 max-w-lg text-sm leading-7 text-[#755f50]">This space is reserved for super administrators. Use it to manage departments, assign teachers, review internal notices, and monitor the full internship workflow across the system.</p>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-white/35 bg-white/25 p-4">
                    <p class="text-xs uppercase tracking-[0.24em] text-[#8d5b78]">Security</p>
                    <p class="mt-2 text-sm text-[#6d5b4d]">Only authorized super admin credentials can access this control panel.</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-[#7a6557]">
                    <img src="{{ asset('images/clg logo.jpg') }}" alt="College Logo" class="h-10 w-10 rounded-xl object-cover border border-white/35">
                    <div>
                        <p class="font-semibold text-[#5a4a3f]">Internship Tracking System</p>
                        <p>Sangamner College</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-8 md:p-10">
            <div class="mx-auto max-w-md">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-[#97647e]">Administrative Login</p>
                        <h2 class="mt-2 text-3xl font-black text-[#4d3d32]">Welcome back</h2>
                    </div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/35 bg-white/25 text-2xl shadow-inner shadow-white/20 text-[#7d5f3d]">👑</div>
                </div>

                <x-auth-session-status class="mt-6 rounded-2xl border border-emerald-300/50 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

                <form wire:submit="authenticate" class="mt-6 space-y-4">
                    <div>
                        <label for="login" class="mb-2 block text-sm font-medium text-[#6f5d50]">Username or Email</label>
                        <div class="flex items-center gap-3 rounded-2xl border border-white/40 bg-white/35 px-4 py-3.5 text-[#9b8777] shadow-inner shadow-white/20 focus-within:border-[#cb88b7]/60 focus-within:bg-white/55">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5a7.5 7.5 0 10-7.5 0m7.5 0a9 9 0 11-7.5 0m7.5 0a7.463 7.463 0 01-7.5 0" />
                            </svg>
                            <input wire:model="login" id="login" type="text" name="login" required autofocus autocomplete="username" placeholder="Enter username or email" class="w-full border-0 bg-transparent p-0 text-[15px] text-[#4d3d32] placeholder:text-[#a69082] focus:outline-none focus:ring-0">
                        </div>
                        <x-input-error :messages="$errors->get('login')" class="mt-2 text-sm text-rose-600" />
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-[#6f5d50]">Password</label>
                        <div class="flex items-center gap-3 rounded-2xl border border-white/40 bg-white/35 px-4 py-3.5 text-[#9b8777] shadow-inner shadow-white/20 focus-within:border-[#cb88b7]/60 focus-within:bg-white/55">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875a4.125 4.125 0 10-8.25 0V10.5m-.75 0h9a1.5 1.5 0 011.5 1.5v6A1.5 1.5 0 0116.5 19.5h-9A1.5 1.5 0 016 18v-6a1.5 1.5 0 011.5-1.5z" />
                            </svg>
                            <input wire:model="password" id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" class="w-full border-0 bg-transparent p-0 text-[15px] text-[#4d3d32] placeholder:text-[#a69082] focus:outline-none focus:ring-0">
                            <button type="button" class="shrink-0 text-xs font-semibold text-[#8a6f5f] hover:text-[#6f5d50]" onclick="const i=this.parentElement.querySelector('input[type=password],input[type=text]'); const s=i.type==='password'; i.type=s?'text':'password'; this.textContent=s?'Hide':'Show';">Show</button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-1">
                        <label for="remember" class="flex items-center gap-2.5 text-sm text-[#6f5d50] cursor-pointer select-none">
                            <input wire:model="remember" id="remember" type="checkbox" name="remember" class="h-4 w-4 rounded border-[#d6beab] bg-white/70 text-[#cf6ca7] focus:ring-[#cf6ca7] focus:ring-offset-0">
                            <span>Remember me</span>
                        </label>

                        <span class="rounded-full border border-[#e7c994] bg-[#f7e8c9]/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#91673d]">Restricted</span>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-[linear-gradient(90deg,#b76adf_0%,#de7ca5_48%,#f0b466_100%)] px-6 py-3.5 text-sm font-bold uppercase tracking-[0.24em] text-white shadow-[0_16px_34px_rgba(183,106,223,0.22)] transition hover:translate-y-[-1px] hover:shadow-[0_18px_38px_rgba(183,106,223,0.28)]">
                        Enter Dashboard
                    </button>

                    <div class="flex items-center justify-center gap-2 pt-2 text-sm text-[#7d6758]">
                        <span>Need student access?</span>
                        <a href="{{ route('login') }}" class="font-medium text-[#b25a93] hover:text-[#954777]" wire:navigate>
                            Go to student login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
