<?php

use App\Models\Announcement;
use App\Models\Diary;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function student(): User
    {
        return auth()->user();
    }

    public function latestInternship(): ?Internship
    {
        return Internship::query()
            ->with(['batch', 'teacher', 'department', 'diaries'])
            ->where('student_id', auth()->id())
            ->latest('id')
            ->first();
    }

    public function currentInternship(): ?Internship
    {
        return Internship::query()
            ->with(['batch', 'teacher', 'department', 'diaries'])
            ->where('student_id', auth()->id())
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->latest('id')
            ->first();
    }

    public function historyInternships()
    {
        $currentInternshipId = $this->currentInternship()?->id;

        return Internship::query()
            ->with(['batch', 'teacher', 'department', 'diaries'])
            ->where('student_id', auth()->id())
            ->when($currentInternshipId, fn ($query) => $query->where('id', '!=', $currentInternshipId))
            ->latest('id')
            ->get();
    }

    public function diaryCount(): int
    {
        return Diary::query()->where('student_id', auth()->id())->count();
    }

    public function announcementCount(): int
    {
        return Announcement::query()->where('student_id', auth()->id())->count();
    }

    public function historyCount(): int
    {
        return $this->historyInternships()->count();
    }

    public function canApplyAgain(): bool
    {
        $latestInternship = $this->latestInternship();

        return ! $latestInternship
            || $latestInternship->status === 'rejected'
            || ($latestInternship->status === 'approved'
                && $latestInternship->end_date
                && Carbon::parse($latestInternship->end_date)->isPast());
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

    public function profileCompletion(): int
    {
        $student = $this->student();

        $fields = [
            filled($student->name),
            filled($student->username),
            filled($student->email),
            filled($student->department_id),
            filled($student->class),
        ];

        $completed = collect($fields)->filter()->count();

        return (int) round(($completed / count($fields)) * 100);
    }

    public function internshipDaysLeft(?Internship $internship): ?int
    {
        if (! $internship?->end_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($internship->end_date)->startOfDay(), false);
    }
}; ?>

@php($student = $this->student())
@php($currentInternship = $this->currentInternship())
@php($latestInternship = $currentInternship ?? $this->latestInternship())
@php($historyInternships = $this->historyInternships())
@php($profileCompletion = $this->profileCompletion())
@php($daysLeft = $currentInternship ? $this->internshipDaysLeft($currentInternship) : null)
@php($canApplyAgain = $this->canApplyAgain())

<div class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Welcome, {{ $student->name }}</h1>
                <p class="text-sm text-slate-600 mt-1">Track your active internship, revisit past records, and manage your student profile from one place.</p>
                <div class="flex flex-wrap gap-2 mt-3 text-xs">
                    <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700">{{ $student->department?->name ?? 'No Department' }}</span>
                    <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700">Class: {{ $student->class ?? '-' }}</span>
                    <span class="px-2 py-1 rounded-full {{ $this->statusClasses($student->approval_status) }}">Account: {{ ucfirst($student->approval_status ?? 'pending') }}</span>
                </div>
            </div>
            <div class="w-full md:w-64">
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>Profile Completion</span>
                    <span>{{ $profileCompletion }}%</span>
                </div>
                <div class="mt-2 h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                    <div class="h-full bg-indigo-600" style="width: {{ $profileCompletion }}%"></div>
                </div>
            </div>
        </div>
    </div>

    @if (session('info'))
        <div class="bg-blue-50 text-blue-700 border border-blue-200 rounded-lg p-3 text-sm">{{ session('info') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg p-3 text-sm">{{ session('error') }}</div>
    @endif

    @if (! $latestInternship)
        <div class="bg-yellow-50 text-yellow-800 border border-yellow-200 rounded-lg p-4">
            <p class="font-medium">Ready to start your internship journey?</p>
            <p class="text-sm mt-1">Apply for your first internship to begin tracking batches, letters, and diary activity.</p>
            <a href="{{ route('student.internship.apply') }}" class="inline-block mt-3 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700" wire:navigate>Apply Now</a>
        </div>
    @elseif ($currentInternship)
        <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4">
            <p class="font-medium">Your internship is active.</p>
            <p class="text-sm mt-1">Keep your diary updated and use the approval documents below whenever needed.</p>
            <div class="flex flex-wrap gap-3 mt-3">
                <a href="{{ route('student.diary') }}" class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" wire:navigate>Open Daily Diary</a>
                <a href="{{ route('student.history') }}" class="inline-block px-4 py-2 bg-white text-green-700 border border-green-200 rounded-md hover:bg-green-100" wire:navigate>Open History</a>
            </div>
        </div>
    @elseif ($latestInternship->status === 'pending')
        <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4">
            <p class="font-medium">Your latest internship application is under review.</p>
            <p class="text-sm mt-1">Your teacher will review it soon. Approval documents and batch details will appear after approval.</p>
        </div>
    @elseif ($latestInternship->status === 'rejected')
        <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4">
            <p class="font-medium">Your latest internship application was rejected.</p>
            <p class="text-sm mt-1">You can apply again when you are ready. Previous applications remain available in History.</p>
            <div class="flex flex-wrap gap-3 mt-3">
                <a href="{{ route('student.internship.apply') }}" class="inline-block px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" wire:navigate>Apply Again</a>
                <a href="{{ route('student.history') }}" class="inline-block px-4 py-2 bg-white text-red-700 border border-red-200 rounded-md hover:bg-red-100" wire:navigate>Open History</a>
            </div>
        </div>
    @elseif ($canApplyAgain)
        <div class="rounded-lg p-4 border"
            style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.14), rgb(var(--accent-rgb) / 0.04)); border-color: rgb(var(--accent-rgb) / 0.24); color: var(--page-text);">
            <p class="font-medium">Your last internship has ended.</p>
            <p class="text-sm mt-1" style="color: var(--page-muted);">You can apply for a new internship now. Your previous letters, batch details, and diary records remain available in History.</p>
            <div class="flex flex-wrap gap-3 mt-3">
                <a href="{{ route('student.internship.apply') }}" class="inline-block px-4 py-2 text-white rounded-md" style="background: var(--accent-600);" wire:navigate>Apply New Internship</a>
                <a href="{{ route('student.history') }}" class="inline-block px-4 py-2 rounded-md border" style="background: var(--panel-bg); color: var(--page-text); border-color: var(--panel-border);" wire:navigate>Open History</a>
            </div>
        </div>
    @endif

    @php($diaryCount = $this->diaryCount())
    @php($announcementCount = $this->announcementCount())
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-slate-600 text-sm font-semibold">Latest Status</p>
                <span class="text-emerald-600 text-lg">📁</span>
            </div>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ ucfirst($latestInternship->status ?? 'Not applied') }}</p>
            <p class="text-sm text-emerald-600 mt-2">{{ $latestInternship?->company_name ?? 'No internship yet' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-slate-600 text-sm font-semibold">Current Batch</p>
                <span class="text-blue-600 text-lg">🏷️</span>
            </div>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $currentInternship?->batch?->batch_number ? '#'.$currentInternship->batch->batch_number : '-' }}</p>
            <p class="text-sm text-blue-600 mt-2">{{ $currentInternship?->batch?->class ?? 'No active batch' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-purple-50 to-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-slate-600 text-sm font-semibold">Diary Entries</p>
                <span class="text-purple-600 text-lg">📝</span>
            </div>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $currentInternship?->diaries?->count() ?? 0 }}</p>
            <p class="text-sm text-purple-600 mt-2">{{ $currentInternship ? 'Active internship records' : 'Old entries are in History' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-slate-600 text-sm font-semibold">Timeline</p>
                <span class="text-amber-600 text-lg">⏳</span>
            </div>
            <p class="text-3xl font-bold text-slate-900 mt-2">
                @if ($daysLeft === null)
                    —
                @elseif ($daysLeft >= 0)
                    {{ $daysLeft }}d left
                @else
                    Completed
                @endif
            </p>
            <p class="text-sm text-amber-600 mt-2">{{ $announcementCount }} announcements</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-slate-900">{{ $currentInternship ? 'Current Internship Details' : 'Latest Internship Details' }}</h2>
                @if ($latestInternship)
                    <span class="inline-flex px-2 py-1 rounded-full text-xs {{ $this->statusClasses($latestInternship->status) }}">{{ ucfirst($latestInternship->status) }}</span>
                @endif
            </div>

            @if ($latestInternship)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Company</p>
                        <p class="font-medium text-slate-900 mt-1">{{ $latestInternship->company_name }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Teacher</p>
                        <p class="font-medium text-slate-900 mt-1">{{ $latestInternship->teacher?->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Duration</p>
                        <p class="font-medium text-slate-900 mt-1">{{ $latestInternship->duration ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Dates</p>
                        <p class="font-medium text-slate-900 mt-1">
                            {{ $latestInternship->start_date ? Carbon::parse($latestInternship->start_date)->format('d M Y') : '-' }}
                            to
                            {{ $latestInternship->end_date ? Carbon::parse($latestInternship->end_date)->format('d M Y') : '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-slate-500">Batch</p>
                        <p class="font-medium text-slate-900 mt-1">{{ $latestInternship->batch?->batch_number ? '#'.$latestInternship->batch->batch_number : '-' }}</p>
                    </div>
                    @if ($currentInternship)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <p class="text-slate-500">Diary Records</p>
                            <p class="font-medium text-slate-900 mt-1">{{ $currentInternship->diaries->count() }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($currentInternship?->joining_letter_path)
                        <a href="{{ Storage::url($currentInternship->joining_letter_path) }}" target="_blank" class="inline-flex px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">Joining Letter</a>
                    @endif
                    @if ($currentInternship?->approval_pdf_path)
                        <a href="{{ Storage::url($currentInternship->approval_pdf_path) }}" target="_blank" class="inline-flex px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">Permission Letter</a>
                    @endif
                    <a href="{{ route('student.history') }}" class="inline-flex px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" wire:navigate>View Full History</a>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-500">
                    No internship activity yet. Once you apply, your status, letters, and diary history will appear here.
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold mb-3">Quick Actions</h2>
            <div class="space-y-2 text-sm">
                @if ($canApplyAgain)
                    <a href="{{ route('student.internship.apply') }}" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" wire:navigate>Apply for Internship</a>
                @endif
                <a href="{{ route('student.diary') }}" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" wire:navigate>Open Daily Diary</a>
                <a href="{{ route('student.history') }}" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" wire:navigate>Open History</a>
                @if ($currentInternship?->approval_pdf_path)
                    <a href="{{ Storage::url($currentInternship->approval_pdf_path) }}" target="_blank" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">Download Permission Letter</a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Diary Entry History</h2>
                    <p class="text-sm text-slate-500 mt-1">Open old diary records from your previous internships.</p>
                </div>
            </div>

            @if ($historyInternships->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-300 p-5 text-sm text-slate-500">
                    No old diary history available yet.
                </div>
            @else
                <a href="{{ route('student.history') }}" wire:navigate class="block rounded-xl border border-slate-200 p-4 hover:bg-slate-50 transition">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">Diary Entries</h3>
                            <p class="text-sm text-slate-500 mt-1">Tap to view old diary entries from previous internships.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-slate-900">{{ $historyInternships->sum(fn ($internship) => $internship->diaries->count()) }}</p>
                            <p class="text-xs text-slate-500">saved entries</p>
                        </div>
                    </div>
                </a>
                <p class="mt-3 text-xs text-slate-500">Old diary history is available in view mode only.</p>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h2 class="font-semibold mb-3">Student Profile</h2>
            <div class="grid grid-cols-1 gap-3 text-sm">
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Name</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $student->name }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Username</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $student->username }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Email</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $student->email }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Department</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $student->department?->name ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">Class</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $student->class ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-3">
                    <p class="text-slate-500">History Count</p>
                    <p class="font-medium text-slate-900 mt-1">{{ $this->historyCount() }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
