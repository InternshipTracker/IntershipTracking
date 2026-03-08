<?php

use App\Models\Batch;
use App\Models\Internship;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $selectedInternshipId = null;
    public string $student_name = '';
    public string $letter_content = '';
    public ?int $rejectInternshipId = null;
    public string $reject_reason = '';
    public string $batch_number_input = '';

    public function internships()
    {
        return Internship::query()
            ->with(['student', 'batch'])
            ->where('teacher_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function startReject(int $internshipId): void
    {
        $this->rejectInternshipId = $internshipId;
        $this->reject_reason = '';
    }

    public function reject(): void
    {
        $this->validate([
            'rejectInternshipId' => ['required', 'exists:internships,id'],
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        $internship = Internship::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($this->rejectInternshipId);

        $internship->update([
            'status' => 'rejected',
            'rejection_reason' => trim($this->reject_reason),
        ]);

        $this->rejectInternshipId = null;
        $this->reject_reason = '';
        session()->flash('status', 'Internship request rejected.');
    }

    public function continueApprove(int $internshipId): void
    {
        $internship = Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->findOrFail($internshipId);

        $this->selectedInternshipId = $internship->id;
        $this->student_name = $internship->student->name;
        $this->letter_content = "This is to certify that {$internship->student->name} is permitted to continue internship at {$internship->company_name}.";

        $this->batch_number_input = (string) Batch::nextAvailableNumberForTeacher($internship->teacher_id);
    }

    public function confirmApprove(): void
    {
        $validated = $this->validate([
            'selectedInternshipId' => ['required', 'exists:internships,id'],
            'student_name' => ['required', 'string', 'max:255'],
            'letter_content' => ['required', 'string', 'max:5000'],
            'batch_number_input' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $internship = Internship::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->findOrFail($validated['selectedInternshipId']);

        $batchNumber = (int) $validated['batch_number_input'];

        // Reuse existing active batch for same company/department/class/teacher
        $existingBatch = Batch::query()
            ->where('teacher_id', $internship->teacher_id)
            ->where('department_id', $internship->department_id)
            ->where('class', $internship->student->class)
            ->where('company_name', trim($internship->company_name))
            ->where('status', 'Active')
            ->first();

        // Limit to 25 active batches per teacher unless reusing an existing one
        $activeBatchCount = Batch::query()
            ->where('teacher_id', auth()->id())
            ->where('status', 'Active')
            ->count();

        if (!$existingBatch && $activeBatchCount >= 25) {
            session()->flash('status', 'You already have 25 active batches. End a batch before approving a new internship.');
            return;
        }

        // If creating new batch, only active batches should reserve the number.
        if (!$existingBatch) {
            $numberTaken = Batch::query()
                ->where('teacher_id', auth()->id())
                ->where('status', 'Active')
                ->where('batch_number', $batchNumber)
                ->exists();

            if ($numberTaken) {
                session()->flash('status', "Batch number {$batchNumber} is already active. Use another number or the suggested free batch number.");
                return;
            }
        }

        $batch = $existingBatch ?? DB::transaction(function () use ($internship, $batchNumber) {
            return Batch::create([
                'company_name' => trim($internship->company_name),
                'department_id' => $internship->department_id,
                'class' => $internship->student->class,
                'teacher_id' => $internship->teacher_id,
                'batch_number' => $batchNumber,
                'status' => 'Active',
            ]);
        });

        $pdf = Pdf::loadView('pdfs.approval-letter', [
            'college' => 'Sangamner College, Sangamner',
            'studentName' => $validated['student_name'],
            'content' => $validated['letter_content'],
            'teacherName' => auth()->user()->name,
            'date' => now()->format('d-m-Y'),
        ]);

        $filePath = 'approval_letters/'.Str::uuid().'.pdf';
        Storage::disk('public')->put($filePath, $pdf->output());

        $internship->update([
            'batch_id' => $batch->id,
            'status' => 'approved',
            'approval_pdf_path' => $filePath,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->reset(['selectedInternshipId', 'student_name', 'letter_content', 'batch_number_input']);
        session()->flash('status', 'Internship approved! Student assigned to Batch #'.$batch->batch_number.'.');
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-semibold">Pending Internship Requests</h1>
    <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>

@if (session('status'))
    <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
@endif

@if ($rejectInternshipId)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" wire:click="$set('rejectInternshipId', null)"></div>
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-blue-600 text-white flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wide opacity-80">Reject Internship</p>
                    <h3 class="text-lg font-semibold">Add a short reason</h3>
                </div>
                <button type="button" wire:click="$set('rejectInternshipId', null)" class="p-2 rounded-full hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-3">
                <label class="text-sm font-semibold text-slate-700">Reason</label>
                <textarea wire:model.defer="reject_reason" rows="4" class="w-full border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-400 p-3 text-sm" placeholder="e.g., Missing documents, incorrect dates, etc."></textarea>
                <x-input-error :messages="$errors->get('reject_reason')" />
            </div>
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" wire:click="$set('rejectInternshipId', null)" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm">Cancel</button>
                <button type="button" wire:click="reject" class="px-4 py-2 rounded-md bg-red-600 text-white text-sm hover:bg-red-700">Submit Rejection</button>
            </div>
        </div>
    </div>
@endif

<div class="bg-white rounded-2xl border border-slate-300 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gradient-to-r from-slate-100 to-slate-200 text-slate-800 border-b border-slate-300">
                    <tr>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Student</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Company</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Duration</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Joining Letter</th>
                        <th class="p-4 text-right font-bold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->internships() as $internship)
                        <tr class="align-middle hover:bg-blue-50 transition-colors border-b border-slate-200 {{ $loop->even ? 'bg-slate-50' : 'bg-white' }}">
                            <td class="p-4 border-r border-slate-100">
                                <div class="font-semibold text-slate-900">{{ $internship->student?->name }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $internship->student?->email }}</div>
                            </td>
                            <td class="p-4 border-r border-slate-100">{{ $internship->company_name }}</td>
                            <td class="p-4 border-r border-slate-100">{{ $internship->start_date?->format('d M Y') }} - {{ $internship->end_date?->format('d M Y') }}</td>
                            <td class="p-4 border-r border-slate-100">
                                @if ($internship->joining_letter_path)
                                    <a href="{{ Storage::url($internship->joining_letter_path) }}" target="_blank" class="text-indigo-600 font-semibold hover:underline">Download</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-3">
                                <button wire:click="continueApprove({{ $internship->id }})" class="px-4 py-1.5 rounded-lg border border-green-400 text-green-800 bg-green-50 hover:bg-green-100 font-medium shadow-sm transition disabled:opacity-50">Approve</button>
                                <button wire:click="startReject({{ $internship->id }})" class="px-4 py-1.5 rounded-lg border border-red-400 text-red-800 bg-red-50 hover:bg-red-100 font-medium shadow-sm transition disabled:opacity-50">Reject</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center text-slate-500">No pending internship requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($selectedInternshipId)
        <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
            <h2 class="text-lg font-semibold">Edit Approval Letter</h2>
            <div class="text-sm text-slate-600">College: <strong>Sangamner College, Sangamner</strong></div>

            <div>
                <x-input-label value="Student Name" />
                <x-text-input wire:model="student_name" class="w-full mt-1" />
                <x-input-error :messages="$errors->get('student_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Batch Number (set by you)" />
                <x-text-input wire:model="batch_number_input" type="number" min="1" max="99999" class="w-full mt-1" />
                <x-input-error :messages="$errors->get('batch_number_input')" class="mt-2" />
                <p class="text-xs text-slate-500 mt-1">Max 25 active batches per teacher. Ended batch numbers can be reused automatically, and same company plus class stays in the same active batch.</p>
            </div>

            <div>
                <x-input-label value="Content" />
                <textarea wire:model="letter_content" rows="6" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('letter_content')" class="mt-2" />
            </div>

            <div>
                <x-primary-button wire:click="confirmApprove">Confirm & Approve</x-primary-button>
            </div>
        </div>
    @endif
</div>
