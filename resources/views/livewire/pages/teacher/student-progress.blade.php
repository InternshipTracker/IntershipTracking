<?php

use App\Models\Announcement;
use App\Models\Diary;
use App\Models\Internship;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public User $student;
    public array $expandedDates = [];

    public function mount(User $student): void
    {
        if ($student->role !== 'student' || $student->department_id !== auth()->user()->department_id) {
            abort(403);
        }

        $this->student = $student;
    }

    public function toggleDate(string $date): void
    {
        if (in_array($date, $this->expandedDates)) {
            $this->expandedDates = array_filter($this->expandedDates, fn ($d) => $d !== $date);
        } else {
            $this->expandedDates[] = $date;
        }
    }

    public function internships()
    {
        return Internship::query()
            ->with('batch')
            ->where('student_id', $this->student->id)
            ->where('teacher_id', auth()->id())
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

    public function diariesGroupedByDate()
    {
        $diaries = Diary::query()
            ->where('student_id', $this->student->id)
            ->latest('entry_date')
            ->get();

        // Group by date
        $grouped = [];
        foreach ($diaries as $diary) {
            $entryDate = $diary->entry_date instanceof \DateTime ? $diary->entry_date : \Carbon\Carbon::parse($diary->entry_date);
            $dateKey = $entryDate->format('Y-m-d');
            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [];
            }
            $grouped[$dateKey][] = $diary;
        }

        $result = [];
        foreach ($grouped as $dateStr => $entries) {
            $result[] = [
                'date' => \Carbon\Carbon::parse($dateStr),
                'entries' => collect($entries),
                'total_hours' => collect($entries)->sum('hours_studied'),
                'total_entries' => count($entries),
            ];
        }

        return $result;
    }

    public function progressStats()
    {
        $internship = $this->internships()->first();
        if (!$internship) {
            return null;
        }

        $diaries = Diary::query()
            ->where('student_id', $this->student->id)
            ->where('internship_id', $internship->id)
            ->get();

        $totalHours = $diaries->sum('hours_studied');
        $entryCount = $diaries->count();

        $internshipDuration = $internship->start_date && $internship->end_date 
            ? \Carbon\Carbon::parse($internship->start_date)->diffInDays(\Carbon\Carbon::parse($internship->end_date))
            : 0;

        $progressPercentage = $internshipDuration > 0 ? min(100, (int)(($entryCount / max(1, $internshipDuration)) * 100)) : 0;

        return [
            'total_hours' => $totalHours,
            'entry_count' => $entryCount,
            'progress_percentage' => $progressPercentage,
            'internship' => $internship,
        ];
    }

    public function announcements()
    {
        return Announcement::query()
            ->where('teacher_id', auth()->id())
            ->where('student_id', $this->student->id)
            ->latest()
            ->get();
    }
}; ?>

<div class="max-w-6xl mx-auto px-3 md:px-0 space-y-8">
    <!-- Header with Back Button -->
    <div class="rounded-2xl border p-5 shadow-sm md:p-6"
        style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.08), transparent 28%), var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('teacher.dashboard') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-[color:var(--accent-600)] hover:text-[color:var(--accent-700)]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl md:text-4xl font-bold leading-tight text-[color:var(--page-text)]">👨‍🎓 {{ $student->name }}'s Progress</h1>
                <p class="mt-2 text-sm text-[color:var(--page-muted)] md:text-base">{{ $student->email }}</p>
            </div>
            @php($stats = $this->progressStats())
            @if ($stats)
                <div class="min-w-[220px] rounded-xl border p-4 text-right"
                    style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.16), rgb(var(--accent-rgb) / 0.06)); border-color: rgb(var(--accent-rgb) / 0.24);">
                    <div class="text-4xl font-extrabold text-[color:var(--accent-600)]">{{ $stats['progress_percentage'] }}%</div>
                    <p class="mt-1 text-sm text-[color:var(--page-muted)]">Overall Progress</p>
                    <div class="mt-3 space-y-1 text-sm">
                        <p class="text-[color:var(--page-text)]"><strong>{{ $stats['entry_count'] }}</strong> Entries</p>
                        <p class="text-[color:var(--page-text)]"><strong>{{ round($stats['total_hours'], 1) }}</strong> Hours Studied</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Internship hero -->
    @php($latestInternship = $this->internships()->first())
    @if ($latestInternship)
        <div class="overflow-hidden rounded-2xl border shadow-sm"
            style="background: var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
            <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 text-white p-6 md:p-7">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                    <div>
                        <p class="text-blue-100 text-sm uppercase tracking-wide font-semibold">Current Internship</p>
                        <h2 class="text-3xl md:text-4xl font-bold leading-tight">{{ $latestInternship->company_name }}</h2>
                        <p class="text-blue-100 text-sm mt-1">Class {{ $latestInternship->student->class ?? $latestInternship->batch->class ?? '-' }}</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm md:text-base">
                        <div class="bg-white/15 rounded-lg px-4 py-3 text-center">
                            <p class="text-blue-100 font-semibold">Batch</p>
                            <p class="text-2xl font-bold">#{{ $latestInternship->batch?->batch_number ?? '-' }}</p>
                        </div>
                        <div class="bg-white/15 rounded-lg px-4 py-3 text-center">
                            <p class="text-blue-100 font-semibold">Start</p>
                            <p class="text-2xl font-bold">{{ $latestInternship->start_date?->format('M d') ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-white/15 rounded-lg px-4 py-3 text-center">
                            <p class="text-blue-100 font-semibold">End</p>
                            <p class="text-2xl font-bold">{{ $latestInternship->end_date?->format('M d') ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-white/15 rounded-lg px-4 py-3 text-center">
                            <p class="text-blue-100 font-semibold">Status</p>
                            <p class="text-2xl font-bold">{{ ucfirst($latestInternship->status ?? 'pending') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Diary Entries by Date -->
    <div class="rounded-3xl border p-6 shadow-sm space-y-4"
        style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.1), transparent 24%), var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-[color:var(--page-text)]">📖 Learning Timeline</h2>
            @php($totalEntries = 0)
            @php($groups = $this->diariesGroupedByDate())
            @foreach($groups as $g)
                @php($totalEntries += $g['total_entries'])
            @endforeach
            <span class="rounded-lg px-4 py-2 text-sm font-semibold"
                style="background: rgb(var(--accent-rgb) / 0.12); color: var(--accent-700);">{{ $totalEntries }} Entries</span>
        </div>

        @forelse ($this->diariesGroupedByDate() as $dateGroup)
            <!-- Date Section -->
            <div class="border-l-4 pl-4 py-3" style="border-color: var(--accent-500);">
                <!-- Clickable Date Header with Arrow -->
                <button 
                    wire:click="toggleDate('{{ $dateGroup['date']->format('Y-m-d') }}')"
                    class="w-full text-left hover:opacity-80 transition-opacity"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <!-- Arrow Icon -->
                            <svg class="w-5 h-5 text-[color:var(--page-muted)] transition-transform {{ in_array($dateGroup['date']->format('Y-m-d'), $expandedDates) ? 'rotate-90' : 'rotate-0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                            
                            <!-- Date Info -->
                            <div>
                                <h3 class="text-lg font-bold text-[color:var(--page-text)]">{{ $dateGroup['date']->format('l, F d, Y') }}</h3>
                                <p class="text-xs text-[color:var(--page-muted)]">{{ $dateGroup['date']->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="space-y-1 text-right">
                            <span class="block px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold text-xs">⏳ {{ $dateGroup['total_hours'] }} hrs</span>
                            <span class="block px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-semibold text-xs">📝 {{ $dateGroup['total_entries'] }} entry</span>
                        </div>
                    </div>
                </button>
                <!-- Entries for this date (Expandable) -->
                @if (in_array($dateGroup['date']->format('Y-m-d'), $expandedDates))
                    <div class="space-y-3 mt-4 animate-slideDown">
        @foreach ($dateGroup['entries'] as $entry)
                        <div class="rounded-lg border p-4 transition-all hover:shadow-md"
                            style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.06), transparent 28%), var(--surface-soft); border-color: var(--panel-border);">
                            <!-- Entry Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="text-base font-bold text-[color:var(--page-text)]">{{ $entry->topic }}</h4>
                                    <p class="mt-1 text-xs text-[color:var(--page-muted)]">Entry added at {{ $entry->created_at->format('h:i A') }}</p>
                                </div>
                                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs font-semibold">{{ $entry->hours_studied }} hrs</span>
                            </div>

                            <!-- Work Done -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-[color:var(--page-text)]">Work Done</span>
                                </div>
                                <p class="pl-8 text-sm text-[color:var(--page-muted)]">{{ $entry->progress_description }}</p>
                            </div>

                            <!-- What I Learned -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 bg-green-100 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold text-[color:var(--page-text)]">What I Learned</span>
                                </div>
                                <p class="pl-8 text-sm text-[color:var(--page-muted)]">{{ $entry->what_learned }}</p>
                            </div>

                            <!-- Challenges & Skills Row -->
                            <div class="grid grid-cols-2 gap-4">
                                @if ($entry->challenges_faced)
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-6 h-6 bg-orange-100 rounded flex items-center justify-center">
                                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-semibold text-[color:var(--page-text)]">Challenges</span>
                                        </div>
                                        <p class="pl-8 text-sm text-[color:var(--page-muted)]">{{ $entry->challenges_faced }}</p>
                                    </div>
                                @endif

                                @if ($entry->skills_developed)
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-6 h-6 bg-purple-100 rounded flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-semibold text-[color:var(--page-text)]">Skills</span>
                                        </div>
                                        <p class="pl-8 text-sm text-[color:var(--page-muted)]">{{ $entry->skills_developed }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Time Spent -->
                            <div class="mt-3 border-t border-[color:var(--panel-border)] pt-3 text-xs text-[color:var(--page-muted)]">
                                <span>⏱️ {{ $entry->time_spent }}</span>
                            </div>
                        </div>
                    @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="py-12 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full"
                    style="background: rgb(var(--accent-rgb) / 0.12);">
                    <svg class="h-10 w-10 text-[color:var(--accent-600)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-[color:var(--page-text)]">No Diary Entries Yet</h3>
                <p class="mt-1 text-[color:var(--page-muted)]">Student hasn't added any diary entries yet. Check back soon!</p>
            </div>
        @endforelse
    </div>

    <!-- Announcements -->
    <div class="rounded-2xl border p-6"
        style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.08), transparent 34%), var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
        <h2 class="mb-4 text-xl font-bold text-[color:var(--page-text)]">📢 Announcements</h2>
        <div class="space-y-3">
            @forelse ($this->announcements() as $announcement)
                <div class="rounded-lg border p-4 transition-all hover:shadow-md"
                    style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.05), transparent 28%), var(--surface-soft); border-color: var(--panel-border);">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="font-bold text-[color:var(--page-text)]">{{ $announcement->title }}</h3>
                        @if ($announcement->read_at)
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">✓ Read</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Unread</span>
                        @endif
                    </div>
                    <p class="mb-2 text-[color:var(--page-muted)]">{{ $announcement->message }}</p>
                    <p class="text-xs text-[color:var(--page-muted)]">Sent: {{ $announcement->created_at->format('d M Y h:i A') }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-[color:var(--page-muted)]">No announcements sent to this student yet.</p>
            @endforelse
        </div>
    </div>
</div>
