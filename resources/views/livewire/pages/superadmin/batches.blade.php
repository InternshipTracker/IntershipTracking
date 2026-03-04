<?php

use App\Models\Batch;
use App\Models\User;
use App\Models\Internship;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $expandedBatchIds = [];
    public ?int $selectedStudentId = null;

    public function batches()
    {
        return Batch::query()
            ->withCount([
                'internships as members_count' => function ($query) {
                    $query->where('status', 'approved');
                },
            ])
            ->whereHas('internships', function ($query) {
                $query->where('status', 'approved');
            })
            ->latest('id')
            ->get();
    }

    public function toggleBatchMembers(int $batchId): void
    {
        if (in_array($batchId, $this->expandedBatchIds, true)) {
            $this->expandedBatchIds = array_values(array_filter(
                $this->expandedBatchIds,
                fn (int $id) => $id !== $batchId
            ));

            return;
        }

        $this->expandedBatchIds[] = $batchId;
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
    }

    public function batchMembers(int $batchId)
    {
        return Internship::query()
            ->with('student')
            ->where('batch_id', $batchId)
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('superadmin.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1">Student Batches</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Batches List -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-left font-semibold text-slate-700">Batch</th>
                            <th class="p-4 text-left font-semibold text-slate-700">Company</th>
                            <th class="p-4 text-left font-semibold text-slate-700">Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->batches() as $batch)
                            <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <button wire:click="toggleBatchMembers({{ $batch->id }})" class="h-5 w-5 rounded border-2 border-red-500 flex items-center justify-center hover:bg-red-50 transition {{ in_array($batch->id, $expandedBatchIds, true) ? 'bg-red-500' : 'bg-white' }}" title="Show members">
                                            @if (in_array($batch->id, $expandedBatchIds, true))
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </button>
                                        <span class="text-indigo-600 font-semibold">Batch #{{ $batch->id }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-900">{{ $batch->company_name }}</td>
                                <td class="p-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                        {{ $batch->members_count }} {{ Str::plural('Student', $batch->members_count) }}
                                    </span>
                                </td>
                            </tr>
                            @if (in_array($batch->id, $expandedBatchIds, true))
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <td colspan="3" class="p-4">
                                        <div class="ml-8">
                                            <p class="text-sm font-semibold text-slate-700 mb-3">Batch Members</p>
                                            @if ($this->batchMembers($batch->id)->isEmpty())
                                                <p class="text-sm text-slate-500">No members in this batch.</p>
                                            @else
                                                <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                                                    <table class="w-full text-xs">
                                                        <thead class="bg-slate-100 border-b border-slate-200">
                                                            <tr>
                                                                <th class="p-3 text-left font-semibold text-slate-600">Name</th>
                                                                <th class="p-3 text-left font-semibold text-slate-600">Username</th>
                                                                <th class="p-3 text-left font-semibold text-slate-600">Class</th>
                                                                <th class="p-3 text-center font-semibold text-slate-600">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($this->batchMembers($batch->id) as $entry)
                                                                <tr class="border-b border-slate-200 hover:bg-slate-50 cursor-pointer transition" wire:click="selectStudent({{ $entry->student?->id ?? 0 }})">
                                                                    <td class="p-3">
                                                                        <div class="flex items-center gap-2">
                                                                            @if ($entry->student?->profile_photo_path)
                                                                                <img src="{{ asset('storage/' . $entry->student->profile_photo_path) }}" alt="{{ $entry->student->name }}" class="w-6 h-6 rounded-full object-cover">
                                                                            @else
                                                                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center">
                                                                                    <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                                                                </div>
                                                                            @endif
                                                                            <span>{{ $entry->student?->name ?? 'Student' }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="p-3 text-slate-600">{{ $entry->student?->username ?? '-' }}</td>
                                                                    <td class="p-3">{{ $entry->student?->class ?? '-' }}</td>
                                                                    <td class="p-3 text-center">
                                                                        <button type="button" wire:click="selectStudent({{ $entry->student?->id ?? 0 }})" class="text-indigo-600 hover:text-indigo-700 font-medium text-xs">View</button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" class="p-8 text-center text-slate-500">No batches found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Profile Card -->
        @if ($selectedStudentId)
            @php
                $student = User::find($selectedStudentId);
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
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Approval Status</p>
                                <p class="font-medium text-slate-900 capitalize">{{ $student->approval_status ?? 'Pending' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
