<?php

use App\Models\Batch;
use App\Models\Internship;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Batch $batch;
    public ?int $selectedStudentId = null;

    public function mount(Batch $batch): void
    {
        $hasAccess = Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $batch->id)
            ->where('status', 'approved')
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }

        $this->batch = $batch;
    }

    public function members()
    {
        return Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $this->batch->id)
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
    }

    public function deleteBatch(): void
    {
        Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $this->batch->id)
            ->delete();

        $batchId = $this->batch->id;
        $batch = Batch::query()->find($batchId);
        if ($batch && ! Internship::query()->where('batch_id', $batchId)->exists()) {
            $batch->delete();
        }

        session()->flash('status', 'Batch deleted successfully.');
        $this->redirect(route('teacher.students'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('teacher.students') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Batches</a>
            <h1 class="text-2xl font-semibold mt-1">Batch #{{ $batch->id }}</h1>
            <p class="text-sm text-slate-600">{{ $batch->company_name }}</p>
        </div>

        <button wire:click="deleteBatch" wire:confirm="Delete this batch?" class="px-3 py-2 text-sm rounded-md border border-red-300 text-red-700 hover:bg-red-50">
            Delete Batch
        </button>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Members Table -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="p-3 text-left font-semibold">Student</th>
                            <th class="p-3 text-left font-semibold">Username</th>
                            <th class="p-3 text-left font-semibold">Class</th>
                            <th class="p-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->members() as $entry)
                            <tr class="border-t border-slate-200 hover:bg-slate-50 cursor-pointer transition" wire:click="selectStudent({{ $entry->student?->id ?? 0 }})">
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        @if ($entry->student?->profile_photo_path)
                                            <img src="{{ asset('storage/' . $entry->student->profile_photo_path) }}" alt="{{ $entry->student->name }}" class="w-8 h-8 rounded-full object-cover">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                            </div>
                                        @endif
                                        <span class="font-medium">{{ $entry->student?->name ?? 'Student' }}</span>
                                    </div>
                                </td>
                                <td class="p-3">{{ $entry->student?->username ?? '-' }}</td>
                                <td class="p-3">{{ $entry->student?->class ?? '-' }}</td>
                                <td class="p-3 text-right space-x-2">
                                    @if ($entry->student)
                                        <a href="{{ route('teacher.student.progress', $entry->student->id) }}" wire:navigate class="inline-flex px-3 py-1.5 text-xs rounded-md border border-indigo-300 text-indigo-700 hover:bg-indigo-50">Progress</a>
                                        <a href="{{ route('teacher.announcements', ['student' => $entry->student->id]) }}" wire:navigate class="inline-flex px-3 py-1.5 text-xs rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Message</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-500">No students found in this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Profile Card -->
        @if ($selectedStudentId)
            @php
                $student = \App\Models\User::find($selectedStudentId);
            @endphp
            @if ($student)
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-20">
                        <h3 class="font-semibold text-slate-900 mb-4">Student Profile</h3>
                        <div class="text-center mb-4">
                            @if ($student->profile_photo_path)
                                <img src="{{ asset('storage/' . $student->profile_photo_path) }}" alt="{{ $student->name }}" class="w-20 h-20 rounded-full mx-auto object-cover mb-3">
                            @else
                                <div class="w-20 h-20 rounded-full mx-auto bg-slate-200 flex items-center justify-center mb-3">
                                    <svg class="w-10 h-10 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                </div>
                            @endif
                        </div>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Name</p>
                                <p class="font-medium text-slate-900">{{ $student->name }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Username</p>
                                <p class="font-medium text-slate-900">{{ $student->username }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Email</p>
                                <p class="font-medium text-slate-900 break-all text-xs">{{ $student->email }}</p>
                            </div>
                            @if ($student->class)
                                <div>
                                    <p class="text-slate-500 text-xs uppercase font-semibold">Class</p>
                                    <p class="font-medium text-slate-900">{{ $student->class }}</p>
                                </div>
                            @endif
                            @if ($student->department)
                                <div>
                                    <p class="text-slate-500 text-xs uppercase font-semibold">Department</p>
                                    <p class="font-medium text-slate-900">{{ $student->department->name }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
