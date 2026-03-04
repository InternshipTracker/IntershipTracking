<?php

use App\Models\AdminTeacherAnnouncement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $selectedAnnouncementId = null;

    public function announcements()
    {
        return AdminTeacherAnnouncement::query()
            ->with('superadmin')
            ->where('teacher_id', auth()->id())
            ->latest()
            ->get();
    }

    public function openAnnouncement(int $announcementId): void
    {
        $announcement = AdminTeacherAnnouncement::query()
            ->where('id', $announcementId)
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        if (! $announcement->read_at) {
            $announcement->update(['read_at' => now()]);
        }

        $this->selectedAnnouncementId = $announcement->id;
    }

    public function selectedAnnouncement(): ?AdminTeacherAnnouncement
    {
        if (! $this->selectedAnnouncementId) {
            return null;
        }

        return AdminTeacherAnnouncement::query()
            ->with('superadmin')
            ->where('id', $this->selectedAnnouncementId)
            ->where('teacher_id', auth()->id())
            ->first();
    }
}; ?>

@php($selectedAnnouncement = $this->selectedAnnouncement())

<div class="space-y-6">
    <div>
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
                <h1 class="text-2xl font-semibold mt-1">Super Admin Announcements</h1>
            </div>
        </div>
        <p class="text-sm text-slate-500 mt-1">All official letters from Super Admin are listed below.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <ul class="space-y-3 text-sm">
            @forelse ($this->announcements() as $item)
                <li class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50/60">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $item->title }}</div>
                            <div class="text-xs text-slate-500 mt-1">
                                From {{ $item->superadmin?->name ?? 'Super Admin' }} · {{ $item->created_at->format('d M Y h:i A') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($item->read_at)
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-green-100 text-green-700">Read</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-red-100 text-red-700">Unread</span>
                            @endif
                            <button wire:click="openAnnouncement({{ $item->id }})" class="px-3 py-1.5 rounded-md border border-slate-300 text-xs hover:bg-slate-100">Open</button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="text-center text-slate-500 py-8">No Announcement Yet!</li>
            @endforelse
        </ul>
    </div>

    @if ($selectedAnnouncement)
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/40">
                <p class="text-sm text-slate-500">Date: {{ $selectedAnnouncement->created_at->format('d M Y') }}</p>
                <p class="text-sm text-slate-500 mt-1">To: {{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-500">From: {{ $selectedAnnouncement->superadmin?->name ?? 'Super Admin' }}</p>

                <h2 class="text-xl font-semibold text-slate-900 mt-4">{{ $selectedAnnouncement->title }}</h2>
                <div class="mt-4 text-slate-700 whitespace-pre-line leading-7">{{ $selectedAnnouncement->message }}</div>

                @if ($selectedAnnouncement->read_at)
                    <div class="mt-6 pt-4 border-t border-slate-200 text-sm text-slate-500">
                        Read on {{ $selectedAnnouncement->read_at->format('d M Y h:i A') }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
