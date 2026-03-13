<?php

use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function stats(): array
    {
        return [
            'departments' => Department::count(),
            'teachers' => User::where('role', 'teacher')->count(),
        ];
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[color:var(--accent-600)]">Administration</p>
            <h1 class="mt-2 text-3xl font-bold text-[color:var(--page-text)]">Super Admin Dashboard</h1>
            <p class="mt-2 max-w-2xl text-sm text-[color:var(--page-muted)]">Monitor department setup and teacher coverage from one place. Use these quick stats as the top-level overview of academic management.</p>
        </div>
        <div class="rounded-2xl border px-4 py-3 text-sm"
            style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.14), rgb(var(--accent-rgb) / 0.05)); border-color: rgb(var(--accent-rgb) / 0.24); color: var(--page-text);">
            <span class="font-semibold">System Snapshot</span>
            <span class="ml-2 text-[color:var(--page-muted)]">Live administrative totals</span>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm mb-6">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @php
            $cards = [
                'departments' => [
                    'title' => 'Departments',
                    'copy' => 'Academic units configured in the system.',
                    'icon' => 'building',
                    'accent' => 'rgb(37 99 235 / 0.18)',
                ],
                'teachers' => [
                    'title' => 'Teachers',
                    'copy' => 'Faculty members currently assigned to departments.',
                    'icon' => 'users',
                    'accent' => 'rgb(16 185 129 / 0.18)',
                ],
            ];
        @endphp

        @foreach ($this->stats() as $label => $value)
            @php($card = $cards[$label] ?? ['title' => ucfirst($label), 'copy' => 'Administrative total.', 'icon' => 'chart', 'accent' => 'rgb(var(--accent-rgb) / 0.18)'])
            <div class="relative overflow-hidden rounded-3xl border p-6"
                style="background: linear-gradient(145deg, {{ $card['accent'] }}, transparent 45%, var(--panel-bg)); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
                <div class="absolute right-0 top-0 h-28 w-28 rounded-full blur-3xl"
                    style="background: {{ $card['accent'] }};"></div>

                <div class="relative z-10 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[color:var(--page-muted)]">Overview</p>
                        <h2 class="mt-3 text-xl font-semibold text-[color:var(--page-text)]">{{ $card['title'] }}</h2>
                        <p class="mt-2 max-w-xs text-sm leading-6 text-[color:var(--page-muted)]">{{ $card['copy'] }}</p>
                    </div>

                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border"
                        style="background: rgb(255 255 255 / 0.08); border-color: rgb(var(--accent-rgb) / 0.16); color: var(--accent-600);">
                        @if ($card['icon'] === 'building')
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        @elseif ($card['icon'] === 'users')
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        @else
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3v18h18M7 14l3-3 3 2 4-5"></path>
                            </svg>
                        @endif
                    </div>
                </div>

                <div class="relative z-10 mt-8 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-5xl font-black tracking-tight text-[color:var(--page-text)]">{{ $value }}</p>
                        <p class="mt-2 text-sm font-medium text-[color:var(--page-muted)]">Current total</p>
                    </div>

                    @if ($label === 'departments')
                        <a href="{{ route('superadmin.departments') }}" wire:navigate class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition hover:translate-y-[-1px]"
                            style="border-color: rgb(var(--accent-rgb) / 0.22); color: var(--accent-700); background: rgb(var(--accent-rgb) / 0.08);">
                            Manage
                        </a>
                    @elseif ($label === 'teachers')
                        <a href="{{ route('superadmin.teachers') }}" wire:navigate class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition hover:translate-y-[-1px]"
                            style="border-color: rgb(var(--accent-rgb) / 0.22); color: var(--accent-700); background: rgb(var(--accent-rgb) / 0.08);">
                            Open
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
