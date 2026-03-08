<?php

use App\Models\Batch;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function endingBatches()
    {
        return Batch::query()
            ->with(['internships' => function ($query) {
                $query->where('teacher_id', auth()->id())
                    ->where('status', 'approved')
                    ->whereDate('end_date', '<', now()->toDateString())
                    ->with('student');
            }])
            ->withCount([
                'internships as ended_students_count' => function ($query) {
                    $query->where('teacher_id', auth()->id())
                        ->where('status', 'approved')
                        ->whereDate('end_date', '<', now()->toDateString());
                },
            ])
            ->where('teacher_id', auth()->id())
            ->whereHas('internships', function ($q) {
                $q->where('teacher_id', auth()->id())
                    ->where('status', 'approved')
                    ->whereDate('end_date', '<', now()->toDateString());
            })
            ->orderBy('batch_number')
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Teacher</p>
            <h1 class="text-2xl font-bold text-slate-900">Ending Batches</h1>
            <p class="text-sm text-slate-600">Batches whose internship period is over. Open any batch to review students and progress.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
            {{ $this->endingBatches()->count() }} ending
        </span>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
                    <tr>
                        <th class="p-4 text-left font-semibold text-slate-700">Batch</th>
                        <th class="p-4 text-left font-semibold text-slate-700">Company</th>
                        <th class="p-4 text-left font-semibold text-slate-700">Class</th>
                        <th class="p-4 text-center font-semibold text-slate-700">Students</th>
                        <th class="p-4 text-left font-semibold text-slate-700">Ended Students</th>
                        <th class="p-4 text-right font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody>
            @forelse ($this->endingBatches() as $batch)
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                        <td class="p-4 align-top">
                            <div class="font-semibold text-slate-900">Batch #{{ str_pad($batch->batch_number ?? $batch->id, 4, '0', STR_PAD_LEFT) }}</div>
                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Ended</span>
                        </td>
                        <td class="p-4 align-top text-slate-700">{{ $batch->company_name ?? 'N/A' }}</td>
                        <td class="p-4 align-top text-slate-700">{{ strtoupper($batch->class ?? 'N/A') }}</td>
                        <td class="p-4 align-top text-center">
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ $batch->internships->count() }}
                            </span>
                        </td>
                        <td class="p-4 align-top text-slate-600">
                            {{ $batch->internships->pluck('student.name')->filter()->implode(', ') ?: 'N/A' }}
                        </td>
                        <td class="p-4 align-top text-right">
                            <a href="{{ route('teacher.batch.details', ['batch' => $batch->id, 'from' => 'ending-batches']) }}" wire:navigate class="inline-flex items-center rounded-lg border border-indigo-300 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                View Batch
                            </a>
                        </td>
                    </tr>
            @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500">No ending batches yet.</td>
                    </tr>
            @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
