<?php

use App\Models\Batch;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
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
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Teacher</p>
            <h1 class="text-2xl font-bold text-slate-900">Ending Batches</h1>
            <p class="text-sm text-slate-600">Batches whose internship period is over.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
            {{ $this->endingBatches()->count() }} ending
        </span>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="divide-y divide-slate-100 text-sm">
            @forelse ($this->endingBatches() as $batch)
                <div class="py-3 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">Batch #{{ $batch->batch_number }} ({{ $batch->class }})</p>
                        <p class="text-slate-600 text-sm">Company: {{ $batch->company_name ?? 'N/A' }}</p>
                        <p class="text-slate-500 text-xs mt-1">
                            Students: {{ $batch->internships->pluck('student.name')->filter()->implode(', ') ?: 'N/A' }}
                        </p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">Ended</span>
                </div>
            @empty
                <p class="py-6 text-slate-500 text-center">No ending batches yet.</p>
            @endforelse
        </div>
    </div>
</div>
