<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
}; ?>

<div>
    @php
        $pageUser = auth()->user();
        $pageProfilePhotoPath = $pageUser?->profile_photo_path
            ? ltrim(preg_replace('#^(storage/|public/)#', '', $pageUser->profile_photo_path), '/')
            : null;
        $pageProfilePhotoUrl = $pageProfilePhotoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($pageProfilePhotoPath)
            ? \Illuminate\Support\Facades\Storage::url($pageProfilePhotoPath)
            : null;
        $pageInitials = collect(explode(' ', trim($pageUser?->name ?? '')))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $pageInitials = $pageInitials !== '' ? $pageInitials : 'U';
        $pageRole = ucfirst($pageUser?->role ?? 'user');
        $pageApprovalStatus = ucfirst($pageUser?->approval_status ?? 'pending');
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 px-6 py-8 text-white sm:px-8 lg:px-10 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-200/80">Account settings</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Profile Settings</h1>
                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">
                            Keep your name, email, photo, and account details up to date from one polished profile area.
                            Every change syncs across the dashboard and user menus.
                        </p>
                    </div>

                    <div class="flex items-center gap-4 rounded-3xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                        @if ($pageProfilePhotoUrl)
                            <img src="{{ $pageProfilePhotoUrl }}" alt="Profile" class="h-16 w-16 rounded-full border border-white/20 object-cover shadow-lg shadow-slate-950/30" />
                        @else
                            <div class="flex h-16 w-16 items-center justify-center rounded-full border border-white/20 bg-white/10 text-xl font-semibold text-white shadow-lg shadow-slate-950/30">
                                {{ $pageInitials }}
                            </div>
                        @endif

                        <div>
                            <p class="text-sm font-medium text-white">{{ $pageUser?->name }}</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.25em] text-indigo-200/80">{{ $pageRole }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:grid-cols-3 sm:px-8 dark:border-slate-800 dark:bg-slate-950/60">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/20">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Role</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageRole }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/20">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Department</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageUser?->department?->name ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/20">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval Status</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageApprovalStatus }}</p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.08fr_0.92fr]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-400">Snapshot</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">Account overview</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">A clean summary of the information currently linked to your account.</p>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Full Name</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageUser?->name }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Username</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageUser?->username ?? 'Not set' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Email</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageUser?->email }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Class</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $pageUser?->class ?? 'Not assigned' }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <livewire:profile.update-profile-information-form />
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20">
                    <livewire:profile.update-password-form />
                </section>

                <section class="rounded-3xl border border-rose-200 bg-rose-50/70 p-6 shadow-sm sm:p-7 dark:border-rose-900/60 dark:bg-rose-950/25 dark:shadow-black/20">
                    <livewire:profile.delete-user-form />
                </section>
            </div>
        </div>
    </div>
</div>
