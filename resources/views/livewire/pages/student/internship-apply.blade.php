<?php

use App\Models\Batch;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public string $company_name = '';
    public string $duration = '';
    public string $start_date = '';
    public string $end_date = '';
    public ?int $teacher_id = null;
    public $joining_letter;
    public bool $showSuccessModal = false;

    public function mount(): void
    {
        $existingInternship = Internship::where('student_id', auth()->id())->latest()->first();
        // Allow re-apply if internship is missing, batch deleted, or previous application was rejected
        if ($existingInternship && $existingInternship->batch_id && Batch::find($existingInternship->batch_id) && $existingInternship->status !== 'rejected') {
            session()->flash('info', 'You have already applied for an internship.');
            $this->redirect(route('student.dashboard'), navigate: true);
        }
    }

    public function teachers()
    {
        $student = auth()->user();

        return User::query()
            ->where('role', 'teacher')
            ->where('department_id', $student->department_id)
            ->whereHas('teacherClasses', function ($query) use ($student) {
                $query->where('class_name', strtoupper((string) $student->class));
            })
            ->orderBy('name')
            ->get();
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'teacher_id' => ['required', 'exists:users,id'],
            'joining_letter' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $student = auth()->user();

        $teacher = User::query()
            ->where('id', $validated['teacher_id'])
            ->where('role', 'teacher')
            ->where('department_id', $student->department_id)
            ->whereHas('teacherClasses', function ($query) use ($student) {
                $query->where('class_name', strtoupper((string) $student->class));
            })
            ->firstOrFail();

        $joiningLetterPath = $this->joining_letter->store('joining_letters', 'public');

            $batch = Batch::firstOrCreate([
                'company_name' => trim($validated['company_name']),
                'department_id' => $student->department_id,
                'class' => $student->class,
                'teacher_id' => $validated['teacher_id'],
            ]);

            // Set batch_number (teacher-wise sequence) and status
            if (!$batch->batch_number) {
                $previousBatch = Batch::where('teacher_id', $validated['teacher_id'])->orderBy('batch_number', 'desc')->first();
                $batch->batch_number = $previousBatch ? ($previousBatch->batch_number + 1) : 1;
                $batch->status = 'Active';
                $batch->save();
            }

        Internship::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'coordinator_id' => $teacher->id,
            'department_id' => $student->department_id,
            'batch_id' => $batch->id,
            'company_name' => trim($validated['company_name']),
            'duration' => $validated['duration'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'joining_letter_path' => $joiningLetterPath,
            'status' => 'pending',
            'type' => 'individual',
            'company_address' => 'N/A',
            'duration_weeks' => 0,
        ]);

        $this->reset(['company_name', 'duration', 'start_date', 'end_date', 'teacher_id', 'joining_letter']);
        $this->showSuccessModal = true;
    }

    public function internships()
    {
        return Internship::query()
            ->with('teacher')
            ->where('student_id', auth()->id())
            ->latest()
            ->get();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('student.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1">Apply Internship</h1>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <form wire:submit="submit" class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-1 md:col-span-2 mb-2">
                <h2 class="text-lg font-bold text-slate-800 mb-2">Internship Details</h2>
            </div>
            <div>
                <x-input-label value="Company Name" />
                <x-text-input wire:model="company_name" class="w-full mt-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-200 h-12 text-base px-4" placeholder="e.g., Infosys Pvt Ltd" />
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Duration" />
                <x-text-input wire:model="duration" class="w-full mt-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-200 h-12 text-base px-4" placeholder="e.g., 3 Months" />
                <x-input-error :messages="$errors->get('duration')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Start Date" />
                <x-text-input wire:model="start_date" type="date" class="w-full mt-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-200 h-12 text-base px-4" placeholder="dd/mm/yyyy" />
                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="End Date" />
                <x-text-input wire:model="end_date" type="date" class="w-full mt-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-200 h-12 text-base px-4" placeholder="dd/mm/yyyy" />
                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Teacher" />
                <select wire:model="teacher_id" class="mt-1 w-full border border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 h-12 text-base px-4">
                    <option value="">Select Teacher</option>
                    @foreach ($this->teachers() as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Joining Letter" />
                <input type="file" wire:model="joining_letter" class="mt-1 w-full text-base border border-slate-300 rounded-lg h-12 px-4 file:mr-4 file:py-2 file:px-5 file:rounded-lg file:border-0 file:text-base file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 transition file:shadow-sm" />
                <x-input-error :messages="$errors->get('joining_letter')" class="mt-2" />
            </div>
            <div class="md:col-span-2 flex justify-end mt-4">
                <button type="submit" class="px-8 py-3 rounded-lg bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700 transition text-base">Submit Application</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3 text-left">Company</th>
                    <th class="p-3 text-left">Teacher</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Reason / Notes</th>
                    <th class="p-3 text-left">Joining / Approval</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->internships() as $internship)
                    <tr class="border-t border-slate-200">
                        <td class="p-3">{{ $internship->company_name }}</td>
                        <td class="p-3">{{ $internship->teacher?->name ?? '-' }}</td>
                        <td class="p-3">
                            @php
                                $status = $internship->status;
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                {{ $status === 'approved' ? 'bg-green-100 text-green-700' : ($status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                <span class="h-2 w-2 rounded-full
                                    {{ $status === 'approved' ? 'bg-green-500' : ($status === 'rejected' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="p-3 text-sm text-slate-700">
                            @if($internship->status === 'rejected' && $internship->rejection_reason)
                                <div class="rounded-lg border border-red-100 bg-red-50 text-red-700 px-3 py-2">
                                    {{ $internship->rejection_reason }}
                                </div>
                            @elseif($internship->status === 'approved')
                                <div class="rounded-lg border border-green-100 bg-green-50 text-green-700 px-3 py-2">
                                    Approved by teacher
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($internship->joining_letter_path)
                                <a href="{{ Storage::url($internship->joining_letter_path) }}" target="_blank" class="text-indigo-600">View</a>
                                @if ($internship->approval_pdf_path ?? false)
                                    <span class="mx-2 text-slate-400">|</span>
                                    <a href="{{ Storage::url($internship->approval_pdf_path) }}" target="_blank" class="text-green-600">Approval</a>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4 text-center text-slate-500">No internship applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Success Modal -->
@if ($showSuccessModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data="{ countdown: 3 }" x-init="setInterval(() => { countdown--; if (countdown === 0) window.location.href = '{{ route('student.dashboard') }}'; }, 1000)">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-md text-center animate-bounce">
            <div class="flex justify-center mb-4">
                <div class="bg-green-100 rounded-full p-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Application Submitted!</h2>
            <p class="text-slate-600 mb-4">Your internship application has been submitted successfully. Your teacher will review it soon.</p>
            <p class="text-sm text-slate-500 mb-6">Redirecting in <span x-text="countdown"></span>s...</p>
            <a href="{{ route('student.dashboard') }}" wire:navigate class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Go to Dashboard
            </a>
        </div>
    </div>
@endif
