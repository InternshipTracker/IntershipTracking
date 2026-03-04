<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
}; ?>
<div>
    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">Profile Settings</h1>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            @php
                $pageUser = auth()->user();
                $pageProfilePhotoPath = $pageUser?->profile_photo_path
                    ? ltrim(preg_replace('#^(storage/|public/)#', '', $pageUser->profile_photo_path), '/')
                    : null;
                $pageProfilePhotoUrl = $pageProfilePhotoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($pageProfilePhotoPath)
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($pageProfilePhotoPath)
                    : null;
            @endphp
            <div class="flex items-start gap-4">
                @if ($pageProfilePhotoUrl)
                    <img src="{{ $pageProfilePhotoUrl }}" alt="Profile" class="h-20 w-20 rounded-full object-cover border border-slate-200" />
                @else
                    <div class="h-20 w-20 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200 flex items-center justify-center text-2xl font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full text-sm">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Full Name</p>
                        <p class="font-medium text-slate-900 mt-1">{{ auth()->user()->name }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Username</p>
                        <p class="font-medium text-slate-900 mt-1">{{ auth()->user()->username ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Email</p>
                        <p class="font-medium text-slate-900 mt-1">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Role</p>
                        <p class="font-medium text-slate-900 mt-1">{{ ucfirst(auth()->user()->role ?? 'user') }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Department</p>
                        <p class="font-medium text-slate-900 mt-1">{{ auth()->user()->department?->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Class</p>
                        <p class="font-medium text-slate-900 mt-1">{{ auth()->user()->class ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 md:col-span-2">
                        <p class="text-slate-500">Approval Status</p>
                        <p class="font-medium text-slate-900 mt-1">{{ ucfirst(auth()->user()->approval_status ?? 'pending') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <livewire:profile.update-profile-information-form />
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <livewire:profile.update-password-form />
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</div>
