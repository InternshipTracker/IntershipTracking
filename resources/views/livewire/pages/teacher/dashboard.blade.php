<?php

use App\Models\Internship;
use App\Models\User;
use App\Models\Batch;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $endingBatchId = null;

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

    public function endingBatches()
    {
        return Batch::query()
            ->with(['internships.student'])
            ->where('teacher_id', auth()->id())
            ->where('status', 'Active')
            ->whereHas('internships', function ($q) {
                $q->whereDate('end_date', '<', now()->toDateString());
            })
            ->orderBy('batch_number')
            ->get();
    }

    public function markBatchEnded(int $batchId): void
    {
        $batch = Batch::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($batchId);

        $batch->update(['status' => 'Ended']);
        session()->flash('status', "Batch #{$batch->batch_number} marked as ended.");
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
            <h1 class="text-2xl font-semibold text-[color:var(--page-text)]">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-[color:var(--page-muted)]">Quick snapshot of your students and internships.</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase tracking-wide text-[color:var(--page-muted)]">Department</p>
            <p class="text-sm font-semibold text-[color:var(--accent-600)]">{{ auth()->user()->department?->name ?? 'No Department Assigned' }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="rounded-xl border p-5 shadow-sm"
        style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.14), rgb(var(--accent-rgb) / 0.04) 52%, transparent 100%), var(--panel-bg); border-color: rgb(var(--accent-rgb) / 0.24); box-shadow: var(--panel-shadow);">
        <div class="flex items-center gap-3 mb-3">
            <div class="bg-indigo-600 rounded-full p-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div>
                <p class="mb-1 text-sm text-[color:var(--page-muted)]">Teacher Snapshot</p>
                <p class="text-lg font-semibold text-[color:var(--page-text)]">{{ auth()->user()->name }}</p>
                <p class="text-sm text-[color:var(--page-muted)]">Classes: {{ implode(', ', $this->assignedClasses()) ?: 'Not assigned' }}</p>
            </div>
        </div>
        <p class="text-sm text-[color:var(--page-text)]">Keep track of your department, pending approvals, and active internships from one place.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border p-5 shadow-md" style="background: linear-gradient(145deg, rgb(16 185 129 / 0.2), rgb(16 185 129 / 0.06) 45%, var(--panel-bg)); border-color: rgb(16 185 129 / 0.22); box-shadow: var(--panel-shadow);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-[color:var(--page-muted)]">Pending Internships</p>
                    <p class="mt-2 text-3xl font-bold text-[color:var(--page-text)]">{{ $this->pendingInternships() }}</p>
                    <p class="mt-2 text-xs text-emerald-700">Awaiting your approval</p>
                </div>
                <div class="w-11 h-11 rounded-full bg-emerald-500 text-white flex items-center justify-center text-lg">📂</div>
            </div>
        </div>

        <div class="rounded-2xl border p-5 shadow-md" style="background: linear-gradient(145deg, rgb(244 63 94 / 0.2), rgb(244 63 94 / 0.06) 45%, var(--panel-bg)); border-color: rgb(244 63 94 / 0.22); box-shadow: var(--panel-shadow);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-[color:var(--page-muted)]">Approved Internships</p>
                    <p class="mt-2 text-3xl font-bold text-[color:var(--page-text)]">{{ $this->approvedInternships() }}</p>
                    <p class="mt-2 text-xs text-rose-700">Total approved by you</p>
                </div>
                <div class="w-11 h-11 rounded-full bg-rose-500 text-white flex items-center justify-center text-lg">✅</div>
            </div>
        </div>

        <div class="rounded-2xl border p-5 shadow-md" style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.2), rgb(var(--accent-rgb) / 0.06) 45%, var(--panel-bg)); border-color: rgb(var(--accent-rgb) / 0.22); box-shadow: var(--panel-shadow);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-[color:var(--page-muted)]">Active Internships</p>
                    <p class="mt-2 text-3xl font-bold text-[color:var(--page-text)]">{{ $this->activeInternships() }}</p>
                    <p class="mt-2 text-xs text-[color:var(--accent-700)]">Currently running</p>
                </div>
                <div class="w-11 h-11 rounded-full bg-indigo-500 text-white flex items-center justify-center text-lg">🚀</div>
            </div>
        </div>

        <div class="rounded-2xl border p-5 shadow-md" style="background: linear-gradient(145deg, rgb(245 158 11 / 0.2), rgb(245 158 11 / 0.06) 45%, var(--panel-bg)); border-color: rgb(245 158 11 / 0.22); box-shadow: var(--panel-shadow);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-[color:var(--page-muted)]">Pending Student Requests</p>
                    <p class="mt-2 text-3xl font-bold text-[color:var(--page-text)]">{{ $this->pendingStudentRequests() }}</p>
                    <p class="mt-2 text-xs text-amber-700">Students awaiting approval</p>
                </div>
                <div class="w-11 h-11 rounded-full bg-amber-500 text-white flex items-center justify-center text-lg">👥</div>
            </div>
        </div>
    </div>

</div>
