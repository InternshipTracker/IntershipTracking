<?php

use App\Models\Batch;
use App\Models\Internship;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $expandedBatchIds = [];

    public function batches()
    {
        return Batch::query()
            ->with(['department', 'internships' => function ($query) {
                $query->where('teacher_id', auth()->id())
                    ->where('status', 'approved')
                    ->with('student');
            }])
            ->withCount([
                'internships as members_count' => function ($query) {
                    $query->where('teacher_id', auth()->id())
                        ->where('status', 'approved');
                },
            ])
            ->whereHas('internships', function ($query) {
                $query->where('teacher_id', auth()->id())
                    ->where('status', 'approved');
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

    public function deleteBatch(int $batchId): void
    {
        $hasAccess = Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $batchId)
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }

        Internship::query()
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $batchId)
            ->delete();

        $batch = Batch::query()->find($batchId);
        if ($batch && ! Internship::query()->where('batch_id', $batchId)->exists()) {
            $batch->delete();
        }

        $this->expandedBatchIds = array_values(array_filter(
            $this->expandedBatchIds,
            fn (int $id) => $id !== $batchId
        ));

        session()->flash('status', 'Batch deleted successfully.');
    }

    public function batchMembers(int $batchId)
    {
        return Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('batch_id', $batchId)
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1">Batches</h1>
            <p class="text-sm text-slate-500 mt-1">Manage internship batches and student groups</p>
        </div>
        <span class="inline-flex items-center justify-center min-w-12 h-12 px-4 rounded-xl bg-indigo-600 text-white text-lg font-bold shadow-lg">
            {{ $this->batches()->count() }}
        </span>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-700 font-medium">Total Batches</p>
                    <p class="text-3xl font-bold text-blue-900 mt-1">{{ $this->batches()->count() }}</p>
                </div>
                <div class="bg-blue-600 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-700 font-medium">Total Students</p>
                    <p class="text-3xl font-bold text-green-900 mt-1">{{ $this->batches()->sum('members_count') }}</p>
                </div>
                <div class="bg-green-600 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-700 font-medium">Companies</p>
                    <p class="text-3xl font-bold text-purple-900 mt-1">{{ $this->batches()->unique('company_name')->count() }}</p>
                </div>
                <div class="bg-purple-600 rounded-full p-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-300">
                <tr>
                    <th class="p-4 text-left font-semibold text-slate-700 w-12"></th>
                    <th class="p-4 text-left font-semibold text-slate-700">Batch ID</th>
                    <th class="p-4 text-left font-semibold text-slate-700">Company Name</th>
                    <th class="p-4 text-left font-semibold text-slate-700">Department</th>
                    <th class="p-4 text-left font-semibold text-slate-700">Class</th>
                    <th class="p-4 text-center font-semibold text-slate-700">Members</th>
                    <th class="p-4 text-right font-semibold text-slate-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->batches() as $batch)
                    <tr class="border-b border-slate-200 hover:bg-blue-50 transition-colors">
                        <td class="p-4">
                            <button wire:click="toggleBatchMembers({{ $batch->id }})" class="h-5 w-5 rounded border-2 border-indigo-500 flex items-center justify-center hover:bg-indigo-50 transition {{ in_array($batch->id, $expandedBatchIds, true) ? 'bg-indigo-500' : 'bg-white' }}" title="Show members">
                                @if (in_array($batch->id, $expandedBatchIds, true))
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                        </td>
                        <td class="p-4">
                            <a href="{{ route('teacher.batch.details', $batch->id) }}" wire:navigate class="text-indigo-600 font-semibold hover:text-indigo-800 hover:underline">
                                #{{ str_pad($batch->batch_number ?? $batch->id, 4, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-slate-900 text-base">{{ $batch->company_name }}</span>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $batch->department?->name ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                {{ strtoupper($batch->class ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                {{ $batch->members_count }} {{ Str::plural('Student', $batch->members_count) }}
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <button wire:click="deleteBatch({{ $batch->id }})" wire:confirm="Delete this batch and all its members?" class="px-3 py-1.5 text-xs font-medium rounded-md border border-red-300 text-red-700 hover:bg-red-50 transition">
                                Delete
                            </button>
                        </td>
                    </tr>
                    @if (in_array($batch->id, $expandedBatchIds, true))
                        <tr class="bg-gradient-to-r from-slate-50 to-blue-50 border-b border-slate-200">
                            <td colspan="7" class="p-4">
                                <div class="ml-8">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-700">Batch Members ({{ $batch->members_count }})</p>
                                    </div>
                                    @if ($this->batchMembers($batch->id)->isEmpty())
                                        <p class="text-sm text-slate-500 italic">No members in this batch.</p>
                                    @else
                                        <div class="bg-white rounded-lg border border-slate-300 overflow-hidden shadow-sm">
                                            <table class="w-full text-xs">
                                                <thead class="bg-slate-100 border-b border-slate-300">
                                                    <tr>
                                                        <th class="p-3 text-left font-semibold text-slate-700">Student Name</th>
                                                        <th class="p-3 text-left font-semibold text-slate-700">Username</th>
                                                        <th class="p-3 text-left font-semibold text-slate-700">Email</th>
                                                        <th class="p-3 text-center font-semibold text-slate-700">Class</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($this->batchMembers($batch->id) as $entry)
                                                        <tr class="border-b border-slate-200 hover:bg-blue-50 transition">
                                                            <td class="p-3 font-medium text-slate-900">{{ $entry->student?->name ?? 'Student' }}</td>
                                                            <td class="p-3 text-slate-600">{{ $entry->student?->username ?? '-' }}</td>
                                                            <td class="p-3 text-slate-600">{{ $entry->student?->email ?? '-' }}</td>
                                                            <td class="p-3 text-center">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                                    {{ strtoupper($entry->student?->class ?? '-') }}
                                                                </span>
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
                        <td colspan="7" class="p-8 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-sm font-medium">No batches found yet.</p>
                            <p class="text-xs text-slate-400 mt-1">Approved internships will appear here grouped by batch.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
