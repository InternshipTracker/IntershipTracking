<?php

use App\Models\Internship;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function internships()
    {
        return Internship::query()
            ->with(['batch', 'teacher', 'department', 'diaries'])
            ->where('student_id', auth()->id())
            ->latest('id')
            ->get();
    }

    public function statusClasses(?string $status): string
    {
        return match ($status) {
            'approved' => 'bg-green-100 text-green-700',
            'pending' => 'bg-amber-100 text-amber-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function canApplyAgain(): bool
    {
        $latestInternship = Internship::query()
            ->where('student_id', auth()->id())
            ->latest('id')
            ->first();

        return ! $latestInternship
            || $latestInternship->status === 'rejected'
            || ($latestInternship->status === 'approved'
                && $latestInternship->end_date
                && Carbon::parse($latestInternship->end_date)->isPast());
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <a href="{{ route('student.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1 text-slate-900">Internship History</h1>
            <p class="text-sm text-slate-600 mt-1">Review your previous internships, letters, and diary records.</p>
        </div>
        @if ($this->canApplyAgain())
            <a href="{{ route('student.internship.apply') }}" wire:navigate class="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Apply Internship</a>
        @endif
    </div>

    @forelse ($this->internships() as $internship)
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $internship->company_name }}</h2>
                        <span class="inline-flex px-2 py-1 rounded-full text-xs {{ $this->statusClasses($internship->status) }}">{{ ucfirst($internship->status) }}</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Teacher: {{ $internship->teacher?->name ?? '-' }} | Department: {{ $internship->department?->name ?? '-' }}</p>
                </div>
                <div class="text-sm text-slate-500">
                    <p>{{ $internship->start_date ? $internship->start_date->format('d M Y') : '-' }} to {{ $internship->end_date ? $internship->end_date->format('d M Y') : '-' }}</p>
                    <p class="mt-1">Batch: {{ $internship->batch?->batch_number ? '#'.$internship->batch->batch_number : '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 text-sm">
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Duration</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $internship->duration ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Company</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $internship->company_name }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Joining Letter</p>
                    @if ($internship->joining_letter_path)
                        <a href="{{ Storage::url($internship->joining_letter_path) }}" target="_blank" class="font-medium text-indigo-600 mt-1 inline-block">Open File</a>
                    @else
                        <p class="font-medium text-slate-900 mt-1">Not available</p>
                    @endif
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Permission Letter</p>
                    @if ($internship->approval_pdf_path)
                        <a href="{{ Storage::url($internship->approval_pdf_path) }}" target="_blank" class="font-medium text-green-600 mt-1 inline-block">Open File</a>
                    @else
                        <p class="font-medium text-slate-900 mt-1">Not available</p>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-slate-900">Diary Records</h3>
                    <span class="text-sm text-slate-500">{{ $internship->diaries->count() }} entries</span>
                </div>

                @if ($internship->diaries->isEmpty())
                    <p class="text-sm text-slate-500">No diary entries for this internship.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($internship->diaries as $diary)
                            <div class="rounded-lg bg-slate-50 border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <p class="font-medium text-slate-900">{{ $diary->entry_date ? $diary->entry_date->format('d M Y') : 'Diary Entry' }}</p>
                                    <span class="text-xs text-slate-500">{{ $diary->topic }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-2">{{ $diary->progress_description }}</p>
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-lg bg-white border border-slate-200 p-3">
                                        <p class="text-slate-500">What Learned</p>
                                        <p class="text-slate-700 mt-1">{{ $diary->what_learned }}</p>
                                    </div>
                                    <div class="rounded-lg bg-white border border-slate-200 p-3">
                                        <p class="text-slate-500">Time</p>
                                        <p class="text-slate-700 mt-1">{{ $diary->time_spent }} | {{ $diary->hours_studied }} hrs</p>
                                    </div>
                                    @if ($diary->challenges_faced)
                                        <div class="rounded-lg bg-white border border-slate-200 p-3">
                                            <p class="text-slate-500">Challenges</p>
                                            <p class="text-slate-700 mt-1">{{ $diary->challenges_faced }}</p>
                                        </div>
                                    @endif
                                    @if ($diary->skills_developed)
                                        <div class="rounded-lg bg-white border border-slate-200 p-3">
                                            <p class="text-slate-500">Skills Developed</p>
                                            <p class="text-slate-700 mt-1">{{ $diary->skills_developed }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500">
            No internship history found yet.
        </div>
    @endforelse
</div>