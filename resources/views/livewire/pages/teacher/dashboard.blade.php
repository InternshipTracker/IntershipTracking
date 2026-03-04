<?php

use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $expandedBatchIds = [];

    public function assignedClasses(): array
    {
        return auth()->user()
            ->teacherClasses()
            ->pluck('class_name')
            ->map(fn (string $className) => strtoupper($className))
            ->all();
    }

    public function pendingInternships(): int
    {
        return Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'pending')
            ->count();
    }

    public function approvedInternships(): int
    {
        return Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'approved')
            ->count();
    }

    public function activeInternships(): int
    {
        return Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->count();
    }

    public function upcomingTasks()
    {
        $pendingApproval = Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->take(2)
            ->get()
            ->map(function (Internship $internship) {
                return [
                    'title' => 'Review internship request',
                    'meta' => ($internship->student?->name ?? 'Student') . ' - ' . $internship->company_name,
                    'time' => 'Pending now',
                ];
            });

        $endingSoon = Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('status', 'approved')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('end_date')
            ->take(3)
            ->get()
            ->map(function (Internship $internship) {
                return [
                    'title' => 'Follow up with student',
                    'meta' => ($internship->student?->name ?? 'Student') . ' - ends ' . ($internship->end_date ? Carbon::parse($internship->end_date)->format('d M Y') : '-'),
                    'time' => 'Due soon',
                ];
            });

        return $pendingApproval
            ->concat($endingSoon)
            ->take(5)
            ->values();
    }

    public function approvedStudents()
    {
        return User::query()
            ->with('department', 'approvedBy')
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->where('approval_status', 'approved')
            ->where('approved_by', auth()->id())
            ->latest()
            ->take(4)
            ->get();
    }

    public function pendingStudentRequests(): int
    {
        $classes = $this->assignedClasses();

        if (empty($classes)) {
            return 0;
        }

        return User::query()
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('class', $classes)
            ->where('approval_status', 'pending')
            ->count();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Quick snapshot of your students and internships.</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Department</p>
            <p class="text-sm font-semibold text-indigo-700">{{ auth()->user()->department?->name ?? 'No Department Assigned' }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Pending Internships</p>
                @if ($this->pendingInternships() > 0)
                    <span class="inline-flex items-center justify-center min-w-6 h-6 px-2 rounded-full bg-red-500 text-white text-xs font-semibold">{{ $this->pendingInternships() }}</span>
                @endif
            </div>
            <p class="text-3xl font-bold mt-2">{{ $this->pendingInternships() }}</p>
            <p class="text-xs text-slate-500 mt-1">Awaiting your approval</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Approved Internships</p>
            <p class="text-3xl font-bold mt-2">{{ $this->approvedInternships() }}</p>
            <p class="text-xs text-slate-500 mt-1">Total approved by you</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active Internships</p>
            <p class="text-3xl font-bold mt-2">{{ $this->activeInternships() }}</p>
            <p class="text-xs text-slate-500 mt-1">Currently running</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm text-slate-500">Pending Student Requests</p>
                @if ($this->pendingStudentRequests() > 0)
                    <span class="inline-flex items-center justify-center min-w-6 h-6 px-2 rounded-full bg-amber-500 text-white text-xs font-semibold">{{ $this->pendingStudentRequests() }}</span>
                @endif
            </div>
            <p class="text-3xl font-bold mt-2">{{ $this->pendingStudentRequests() }}</p>
            <p class="text-xs text-slate-500 mt-1">Students awaiting approval</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-200 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="bg-indigo-600 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-600 mb-1">Teacher Snapshot</p>
                    <p class="text-lg font-semibold text-indigo-900">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-slate-600">Classes: {{ implode(', ', $this->assignedClasses()) ?: 'Not assigned' }}</p>
                </div>
            </div>
            <p class="text-sm text-slate-700">Keep track of your department, pending approvals, and active internships from one place.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h2 class="text-lg font-semibold mb-4">Upcoming Tasks</h2>
            <div class="space-y-4 text-sm">
                @forelse ($this->upcomingTasks() as $task)
                    <label class="flex items-start gap-3 border-b border-slate-100 pb-3 last:border-b-0 last:pb-0">
                        <input type="checkbox" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <span>
                            <span class="font-medium text-slate-900 block">{{ $task['title'] }}</span>
                            <span class="text-slate-600 block mt-1">{{ $task['meta'] }}</span>
                            <span class="text-xs text-slate-500 block mt-1">{{ $task['time'] }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-slate-500">No upcoming tasks.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold">Recently Approved Students</h2>
            <a href="{{ route('teacher.approved-students') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">View all</a>
        </div>
        <div class="divide-y divide-slate-100 text-sm">
            @forelse ($this->approvedStudents() as $student)
                <div class="py-3 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                        <p class="text-slate-600">{{ $student->department?->name ?? 'No Department' }} • Class {{ $student->class ?? '-' }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Approved</span>
                </div>
            @empty
                <p class="text-slate-500">No students approved yet.</p>
            @endforelse
        </div>
    </div>
</div>
