<?php

use App\Models\Internship;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public User $student;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $class = '';
    public string $course = '';
    public string $registration_number = '';
    public bool $confirmingDelete = false;

    public function mount(User $student): void
    {
        $this->student = $this->authorizedStudent($student);
        $this->fillForm();
    }

    protected function authorizedStudent(User $student): User
    {
        $assignedClasses = auth()->user()
            ->teacherClasses()
            ->pluck('class_name')
            ->map(fn (string $className) => strtoupper($className))
            ->all();

        return User::query()
            ->with(['department', 'studentProfile', 'approvedBy'])
            ->whereKey($student->id)
            ->where('role', 'student')
            ->whereIn('department_id', auth()->user()->assignedDepartmentIds())
            ->where('approval_status', 'approved')
            ->where('approved_by', auth()->id())
            ->whereIn('class', $assignedClasses)
            ->firstOrFail();
    }

    protected function fillForm(): void
    {
        $this->name = $this->student->name;
        $this->email = $this->student->email;
        $this->phone = (string) ($this->student->phone ?? '');
        $this->class = (string) ($this->student->class ?? '');
        $this->course = (string) ($this->student->studentProfile?->course ?? '');
        $this->registration_number = (string) ($this->student->studentProfile?->registration_number ?? '');
    }

    protected function assignedClasses(): array
    {
        return auth()->user()
            ->teacherClasses()
            ->pluck('class_name')
            ->map(fn (string $className) => strtoupper($className))
            ->all();
    }

    public function internships()
    {
        return Internship::query()
            ->with('teacher')
            ->where('student_id', $this->student->id)
            ->latest('id')
            ->get();
    }

    public function updateStudent(): void
    {
        $student = $this->authorizedStudent($this->student);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'class' => ['required', 'string', Rule::in($this->assignedClasses())],
            'course' => ['required', 'string', 'max:255'],
            'registration_number' => ['required', 'string', 'max:255', Rule::unique('student_profiles', 'registration_number')->ignore($student->studentProfile?->id)],
        ]);

        $student->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'class' => $validated['class'],
        ]);

        if ($student->studentProfile) {
            $student->studentProfile->update([
                'course' => $validated['course'],
                'class' => $validated['class'],
                'registration_number' => $validated['registration_number'],
            ]);
        }

        $this->student = $this->authorizedStudent($student->fresh());
        $this->fillForm();
        session()->flash('status', 'Student profile updated successfully.');
    }

    public function deleteStudent(): void
    {
        $student = $this->authorizedStudent($this->student);
        $student->delete();

        session()->flash('status', 'Student deleted successfully.');
        $this->redirectRoute('teacher.approved-students', navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('teacher.approved-students') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Approved Students</a>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">Student Profile</h1>
        </div>
        <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">Approved Student</span>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-700">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">{{ $student->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $student->email }}</p>
                </div>
            </div>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Department</dt><dd class="font-medium text-slate-900">{{ $student->department?->name ?? '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Class</dt><dd class="font-medium text-slate-900">{{ $student->class ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Approved by</dt><dd class="font-medium text-slate-900">{{ $student->approvedBy?->name ?? '-' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Edit student details</h2>
            <form wire:submit="updateStudent" class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['name', 'Name', 'text'],
                    ['email', 'Email', 'email'],
                    ['phone', 'Phone', 'text'],
                    ['class', 'Class', 'text'],
                    ['course', 'Course', 'text'],
                    ['registration_number', 'Registration Number', 'text'],
                ] as [$field, $label, $type])
                    <div>
                        <label for="{{ $field }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input id="{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error($field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <div class="flex flex-wrap gap-3 sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Update Student</button>
                    <button type="button" wire:click="$set('confirmingDelete', true)" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Delete Student</button>
                </div>
            </form>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Internship history</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 text-left text-slate-500"><tr><th class="p-3">Company</th><th class="p-3">Dates</th><th class="p-3">Status</th></tr></thead>
                <tbody>
                    @forelse ($this->internships() as $internship)
                        <tr class="border-b border-slate-100"><td class="p-3 font-medium">{{ $internship->company_name }}</td><td class="p-3">{{ $internship->start_date?->format('d M Y') }} - {{ $internship->end_date?->format('d M Y') }}</td><td class="p-3 capitalize">{{ str_replace('_', ' ', $internship->status) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="p-3 text-center text-slate-500">No internships found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($confirmingDelete)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-900">Delete this student?</h2>
                <p class="mt-2 text-sm text-slate-600">The student account and its internship records will be permanently deleted.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" wire:click="$set('confirmingDelete', false)" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                    <button type="button" wire:click="deleteStudent" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Yes, Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
