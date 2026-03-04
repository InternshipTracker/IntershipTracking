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

    public function internship(): ?Internship
    {
        return Internship::query()
            ->with(['batch', 'teacher', 'department'])
            ->where('student_id', auth()->id())
            ->latest()
            ->first();
    }

    public function diaryCount(): int
    {
        return Diary::query()->where('student_id', auth()->id())->count();
    }

    public function announcementCount(): int
    {
        return Announcement::query()->where('student_id', auth()->id())->count();
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
@php($internship = $this->internship())
@php($profileCompletion = $this->profileCompletion())
@php($daysLeft = $this->internshipDaysLeft($internship))

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5 md:p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">Welcome, {{ $student->name }}</h1>
                        <p class="text-sm text-slate-600 mt-1">Track your internship progress and student profile from one place.</p>
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

            @if (!$internship)
                <div class="bg-yellow-50 text-yellow-800 border border-yellow-200 rounded-lg p-4">
                    <p class="font-medium">📝 Ready to Start Your Internship Journey?</p>
                    <p class="text-sm mt-1">Apply for an internship to get started.</p>
                    <a href="{{ route('student.internship.apply') }}" class="inline-block mt-3 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700" wire:navigate>Apply Now</a>
                </div>
            @elseif ($internship->status === 'pending')
                <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4">
                    <p class="font-medium">⏳ Your Internship Application is Under Review</p>
                    <p class="text-sm mt-1">Your teacher will review and approve your application soon. Daily Diary will be available after approval.</p>
                </div>
            @elseif ($internship->status === 'approved')
                <div class="bg-green-50 text-green-800 border border-green-200 rounded-lg p-4">
                    <p class="font-medium">✅ Your Internship is Approved!</p>
                    <p class="text-sm mt-1">You can now maintain your Daily Diary and track your progress.</p>
                    <a href="{{ route('student.diary') }}" class="inline-block mt-3 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700" wire:navigate>Go to Daily Diary</a>
                </div>
            @elseif ($internship->status === 'rejected')
                <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4">
                    <p class="font-medium">❌ Your Internship Application was Rejected</p>
                    <p class="text-sm mt-1">Please contact your teacher for more details.</p>
                </div>
            @endif

            @if (!$internship || $internship->status === 'approved' || $internship->status === 'pending')
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Internship Status</p>
                    <p class="text-xl font-semibold mt-2 capitalize">{{ $internship?->status ?? 'Not Applied' }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Batch Number</p>
                    <p class="text-xl font-semibold mt-2">{{ $internship?->batch?->batch_number ?? '-' }}</p>
                    @if ($internship?->batch)
                        <p class="text-xs text-slate-500 mt-1">Teacher: {{ $internship->batch->teacher?->name ?? '-' }}<br>Company: {{ $internship->batch->company_name }} ({{ $internship->batch->class }})</p>
                        <p class="text-xs text-slate-500 mt-1">Status: {{ $internship->batch->status ?? 'Active' }}</p>
                    @endif
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Diary Entries</p>
                    <p class="text-xl font-semibold mt-2">{{ $this->diaryCount() }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Announcements</p>
                    <p class="text-xl font-semibold mt-2">{{ $this->announcementCount() }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Internship Timeline</p>
                    @if ($daysLeft === null)
                        <p class="text-slate-500 mt-2">Not available</p>
                    @elseif ($daysLeft >= 0)
                        <p class="text-xl font-semibold mt-2">{{ $daysLeft }} days left</p>
                    @else
                        <p class="text-xl font-semibold mt-2">Completed</p>
                    @endif
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
                    <h2 class="font-semibold mb-3">Student Profile</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
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
                            <p class="text-slate-500">Approval Status</p>
                            <span class="inline-flex mt-1 px-2 py-1 rounded-full text-xs {{ $this->statusClasses($student->approval_status) }}">{{ ucfirst($student->approval_status ?? 'pending') }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h2 class="font-semibold mb-3">Quick Actions</h2>
                    <div class="space-y-2 text-sm">
                        @if (!$internship)
                            <a href="{{ route('student.internship.apply') }}" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50" wire:navigate>Apply for Internship</a>
                        @endif
                        @if ($internship?->status === 'approved')
                            <div class="bg-red-50 text-red-800 border border-red-200 rounded-lg p-4">
                                <p class="font-medium">❌ Your Internship Application was Rejected</p>
                                <p class="text-sm mt-1">Please contact your teacher for more details.</p>
                            </div>
                            <a href="{{ Storage::url($internship->approval_pdf_path) }}" target="_blank" class="block w-full px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50">Download Approval PDF</a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($internship)
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <!-- Internship Details section removed as requested -->
                </div>
            @endif

</div>
