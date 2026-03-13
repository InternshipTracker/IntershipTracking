<?php

use App\Models\Department;
use App\Models\DepartmentCourse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;
    public ?int $viewingProfileId = null;
    public ?int $selectedDepartmentId = null;
    public ?int $activeCourseId = null;
    public ?string $activeFilterClass = null;
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public array $selected_classes = [];

    private function normalizeClassName(string $value): string
    {
        return strtoupper(trim($value));
    }

    public function selectDepartment(int $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
        $this->activeCourseId = null;
        $this->activeFilterClass = null;
        $this->selected_classes = [];
        $this->resetErrorBag(['selectedDepartmentId', 'activeCourseId', 'selected_classes']);
    }

    public function selectCourse(int $courseId): void
    {
        if (! $this->selectedDepartmentId) {
            $this->addError('selectedDepartmentId', 'Select a department first.');
            return;
        }

        $course = $this->departmentCourses()->firstWhere('id', $courseId);

        if (! $course) {
            $this->addError('activeCourseId', 'Select a valid faculty for the chosen department.');
            return;
        }

        $this->activeCourseId = $course->id;
        $this->activeFilterClass = null;
        $this->selected_classes = array_values(array_filter(
            $this->selected_classes,
            fn (string $className) => $course->supportsClass($className)
        ));
        $this->resetErrorBag(['activeCourseId', 'selected_classes']);
    }

    public function selectFilterClass(string $className): void
    {
        $course = $this->activeCourse();

        if (! $course) {
            $this->addError('activeCourseId', 'Select a faculty first.');
            return;
        }

        $normalizedClass = $this->normalizeClassName($className);

        if (! $course->supportsClass($normalizedClass)) {
            return;
        }

        $this->activeFilterClass = $this->activeFilterClass === $normalizedClass ? null : $normalizedClass;
    }

    public function toggleSelectedClass(string $className): void
    {
        $course = $this->activeCourse();

        if (! $course) {
            $this->addError('activeCourseId', 'Select a faculty before assigning classes.');
            return;
        }

        $normalizedClass = $this->normalizeClassName($className);

        if (! $course->supportsClass($normalizedClass)) {
            $this->addError('selected_classes', 'Selected class does not belong to the chosen faculty.');
            return;
        }

        if (in_array($normalizedClass, $this->selected_classes, true)) {
            $this->removeClass($normalizedClass);
        } else {
            $this->selected_classes[] = $normalizedClass;
        }

        $this->resetErrorBag('selected_classes');
    }

    public function removeClass(string $className): void
    {
        $this->selected_classes = array_values(array_filter(
            $this->selected_classes,
            fn (string $item) => $item !== $className
        ));
    }

    private function activeCourse(): ?DepartmentCourse
    {
        if (! $this->activeCourseId) {
            return null;
        }

        return $this->departmentCourses()->firstWhere('id', $this->activeCourseId)
            ?? DepartmentCourse::query()->find($this->activeCourseId);
    }

    private function activeCourseClasses(): array
    {
        return $this->activeCourse()?->normalizedClassNames() ?? [];
    }

    private function inferCourseIdFromClass(?string $className, ?int $departmentId): ?int
    {
        if (! $departmentId) {
            return null;
        }

        $courses = DepartmentCourse::query()
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();

        if (! filled($className)) {
            return $courses->first()?->id;
        }

        $normalizedClass = $this->normalizeClassName((string) $className);

        return $courses->first(fn (DepartmentCourse $course) => $course->supportsClass($normalizedClass))?->id
            ?? $courses->first()?->id;
    }

    private function hasValidClassSelection(): bool
    {
        if (! $this->selectedDepartmentId) {
            $this->addError('selectedDepartmentId', 'Select a department box first.');
            return false;
        }

        $course = $this->activeCourse();

        if (! $course) {
            $this->addError('activeCourseId', 'Select a faculty box first.');
            return false;
        }

        if (count($this->selected_classes) === 0) {
            $this->addError('selected_classes', 'Please choose at least one class from the selected faculty.');
            return false;
        }

        $hasInvalidClass = collect($this->selected_classes)
            ->contains(fn (string $className) => ! $course->supportsClass($className));

        if ($hasInvalidClass) {
            $this->addError('selected_classes', 'All selected classes must belong to the selected faculty.');
            return false;
        }

        return true;
    }

    public function save(): void
    {
        $this->resetErrorBag();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];

        $departmentId = $this->selectedDepartmentId;

        if (! $this->hasValidClassSelection()) {
            return;
        }

        if ($this->editingId) {
            $rules['username'][] = 'unique:users,username,'.$this->editingId;
            $rules['email'][] = 'unique:users,email,'.$this->editingId;
            $validated = $this->validate($rules);

            $teacher = User::query()->where('role', 'teacher')->findOrFail($this->editingId);
            $teacher->update([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'department_id' => $departmentId,
            ]);

            $teacher->teacherClasses()->delete();
            $teacher->teacherClasses()->createMany(
                collect($this->selected_classes)
                    ->map(fn (string $className) => ['class_name' => $className])
                    ->all()
            );

            if (filled($this->password)) {
                $this->validate(['password' => ['string', Rules\Password::defaults()]]);
                $teacher->update(['password' => Hash::make($this->password)]);
            }

            $updatedName = $teacher->name;
            $this->reset(['editingId', 'name', 'username', 'email', 'password', 'selected_classes']);
            session()->flash('status', "Updated teacher: {$updatedName}");

            return;
        }

        $rules['username'][] = 'unique:users,username';
        $rules['email'][] = 'unique:users,email';
        $rules['password'] = ['required', 'string', Rules\Password::defaults()];
        $validated = $this->validate($rules);

        $teacher = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'department_id' => $departmentId,
            'is_approved' => true,
            'approval_status' => 'approved',
        ]);

        $teacher->teacherClasses()->createMany(
            collect($this->selected_classes)
                ->map(fn (string $className) => ['class_name' => $className])
                ->all()
        );

        $addedName = $teacher->name;
        $this->reset(['name', 'username', 'email', 'password', 'selected_classes']);
        session()->flash('status', "Added teacher: {$addedName}");
    }

    public function edit(int $teacherId): void
    {
        $teacher = User::query()->where('role', 'teacher')->findOrFail($teacherId);

        $this->editingId = $teacher->id;
        $this->name = $teacher->name;
        $this->username = $teacher->username;
        $this->email = $teacher->email;
        $this->selectedDepartmentId = $teacher->department_id;
        $this->selected_classes = $teacher->teacherClasses()->pluck('class_name')->map(fn (string $item) => strtoupper($item))->all();
        $this->activeCourseId = $this->inferCourseIdFromClass($this->selected_classes[0] ?? null, $teacher->department_id);
        $this->password = '';
        
        $this->viewingProfileId = null;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'username', 'email', 'password', 'selected_classes']);
    }

    public function delete(int $teacherId): void
    {
        $teacher = User::query()->where('role', 'teacher')->findOrFail($teacherId);
        $deletedName = $teacher->name;
        $teacher->delete();
        session()->flash('status', "Deleted teacher: {$deletedName}");
    }

    public function viewProfile(int $teacherId): void
    {
        $this->viewingProfileId = $teacherId;
    }

    public function closeProfile(): void
    {
        $this->viewingProfileId = null;
    }

    public function departments()
    {
        return Department::query()->with('courses')->orderBy('name')->get();
    }

    public function departmentCourses()
    {
        if (! $this->selectedDepartmentId) {
            return collect();
        }

        return DepartmentCourse::query()
            ->where('department_id', $this->selectedDepartmentId)
            ->orderBy('name')
            ->get();
    }

    public function teachers()
    {
        if (! $this->selectedDepartmentId || ! $this->activeCourseId || ! $this->activeFilterClass) {
            return collect([]);
        }

        return User::query()
            ->with(['department', 'teacherClasses'])
            ->where('role', 'teacher')
            ->where('department_id', $this->selectedDepartmentId)
            ->whereHas('teacherClasses', function ($classQuery) {
                $classQuery->where('class_name', $this->activeFilterClass);
            })
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('superadmin.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1">Teacher Management</h1>
        </div>
    </div>

    <div class="rounded-2xl border p-6 space-y-6 shadow-sm"
        style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.08), transparent 28%), var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
        <div>
            <div class="flex items-center justify-between">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-[color:var(--page-text)]">Step 1 · Department</h2>
                <span class="rounded-full px-2 py-1 text-xs" style="background: var(--surface-soft); color: var(--page-muted);">Choose first</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($this->departments() as $department)
                    <button
                        type="button"
                        wire:click="selectDepartment({{ $department->id }})"
                        class="group rounded-xl border-2 p-4 text-left transition-all duration-200"
                        style="border-color: {{ $selectedDepartmentId === $department->id ? 'rgb(var(--accent-rgb) / 0.52)' : 'var(--panel-border)' }}; background: {{ $selectedDepartmentId === $department->id ? 'linear-gradient(145deg, rgb(var(--accent-rgb) / 0.18), rgb(var(--accent-rgb) / 0.06) 55%, var(--panel-bg))' : 'var(--panel-bg)' }}; color: var(--page-text); box-shadow: {{ $selectedDepartmentId === $department->id ? '0 16px 32px rgb(15 23 42 / 0.16)' : 'none' }};"
                    >
                        <div class="flex items-center gap-2">
                            <div class="h-2 w-2 rounded-full" style="background: {{ $selectedDepartmentId === $department->id ? 'var(--accent-600)' : 'rgb(var(--accent-rgb) / 0.28)' }};"></div>
                            <div class="font-semibold text-sm">{{ $department->name }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('selectedDepartmentId')" class="mt-2" />
        </div>

        @if ($selectedDepartmentId)
            <div class="border-t pt-6" style="border-color: var(--panel-border);">
                <h2 class="mb-3 text-sm font-semibold text-[color:var(--page-text)]">Step 2: Select Faculty for {{ optional($this->departments()->firstWhere('id', $selectedDepartmentId))->name }}</h2>

                @if ($this->departmentCourses()->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($this->departmentCourses() as $course)
                            <button
                                type="button"
                                wire:click="selectCourse({{ $course->id }})"
                                class="rounded-2xl border-2 p-5 text-left transition-all duration-200"
                                style="border-color: {{ $activeCourseId === $course->id ? 'rgb(var(--accent-rgb) / 0.48)' : 'var(--panel-border)' }}; background: {{ $activeCourseId === $course->id ? 'linear-gradient(145deg, rgb(var(--accent-rgb) / 0.18), rgb(var(--accent-rgb) / 0.06) 48%, var(--panel-bg))' : 'linear-gradient(145deg, rgb(var(--accent-rgb) / 0.06), transparent 38%, var(--panel-bg))' }}; color: var(--page-text); box-shadow: {{ $activeCourseId === $course->id ? '0 18px 32px rgb(15 23 42 / 0.18)' : '0 8px 20px rgb(15 23 42 / 0.08)' }};"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-2xl font-bold tracking-tight text-[color:var(--page-text)]">{{ $course->normalizedCode() }}</p>
                                        <p class="mt-2 text-sm text-[color:var(--page-muted)]">{{ $course->name }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-3 py-1.5 text-[11px] font-semibold"
                                        style="background: {{ $activeCourseId === $course->id ? 'rgb(var(--accent-rgb) / 0.14)' : 'var(--surface-soft)' }}; color: {{ $activeCourseId === $course->id ? 'var(--accent-700)' : 'var(--page-muted)' }}; border: 1px solid rgb(var(--accent-rgb) / 0.12);">
                                        {{ count($course->normalizedClassNames()) }} classes
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border px-4 py-3 text-sm"
                        style="background: rgb(245 158 11 / 0.12); border-color: rgb(245 158 11 / 0.24); color: #fcd34d;">
                        No faculty is configured for this department yet. Add faculty and classes first from Department Management.
                    </div>
                @endif

                <x-input-error :messages="$errors->get('activeCourseId')" class="mt-2" />
            </div>
        @else
            <div class="border-t pt-6" style="border-color: var(--panel-border);">
                <div class="py-8 text-center text-[color:var(--page-muted)]">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm font-medium">Please select a department first to view faculties</p>
                </div>
            </div>
        @endif

        @if ($activeCourseId)
            <div class="border-t pt-6" style="border-color: var(--panel-border);">
                <h2 class="mb-3 text-sm font-semibold text-[color:var(--page-text)]">Step 3: Tap a Class to Filter Teachers for {{ optional($this->activeCourse())->normalizedCode() }}</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach ($this->activeCourseClasses() as $className)
                        <button
                            type="button"
                            wire:click="selectFilterClass('{{ $className }}')"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition-all duration-200"
                            style="border-color: {{ $activeFilterClass === $className ? 'rgb(var(--accent-rgb) / 0.48)' : 'var(--panel-border)' }}; background: {{ $activeFilterClass === $className ? 'var(--accent-600)' : 'var(--surface-soft)' }}; color: {{ $activeFilterClass === $className ? '#ffffff' : 'var(--page-text)' }};"
                        >
                            {{ $className }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-[color:var(--page-muted)]">Teachers will be shown only after you tap a class here.</p>
            </div>
        @endif
    </div>

    <div class="bg-white shadow-xl rounded-2xl p-8 max-w-3xl mx-auto mt-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            Add New Teacher
        </h2>
        <form wire:submit.prevent="save" class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold mb-1">Name</label>
                <div class="relative">
                    <input type="text" wire:model="name" class="w-full border rounded-lg px-4 py-2 pl-10 focus:ring-indigo-500" placeholder="Enter name">
                    <span class="absolute left-3 top-2.5 text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Username</label>
                <div class="relative">
                    <input type="text" wire:model="username" class="w-full border rounded-lg px-4 py-2 pl-10 focus:ring-indigo-500" placeholder="Enter username">
                    <span class="absolute left-3 top-2.5 text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Email</label>
                <div class="relative">
                    <input type="email" wire:model="email" class="w-full border rounded-lg px-4 py-2 pl-10 focus:ring-indigo-500" placeholder="Enter email">
                    <span class="absolute left-3 top-2.5 text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/></svg></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Password</label>
                <div class="relative">
                    <input type="password" wire:model="password" class="w-full border rounded-lg px-4 py-2 pl-10 focus:ring-indigo-500" placeholder="Enter password">
                    <span class="absolute left-3 top-2.5 text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 17v-6"/></svg></span>
                </div>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-1">Selected Department</label>
                <div class="bg-slate-100 border rounded-lg px-4 py-2 text-slate-700 font-bold">
                    {{ optional($this->departments()->firstWhere('id', $selectedDepartmentId))->name ?? 'Select department from boxes above' }}
                </div>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-1">Selected Faculty</label>
                <div class="bg-slate-100 border rounded-lg px-4 py-2 text-slate-700 font-bold">
                    {{ optional($this->activeCourse())->name ? optional($this->activeCourse())->normalizedCode().' - '.optional($this->activeCourse())->name : 'Select faculty from boxes above' }}
                </div>
                <x-input-error :messages="$errors->get('activeCourseId')" class="mt-2" />
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-semibold mb-2">Assigned Classes</label>
                @if ($activeCourseId)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->activeCourseClasses() as $className)
                            <button type="button" wire:click="toggleSelectedClass('{{ $className }}')" class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold transition {{ in_array($className, $selected_classes, true) ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                                <span>{{ $className }}</span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-lg border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">Select a faculty above, then choose class assignments here.</p>
                @endif

                @if ($selected_classes)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($selected_classes as $className)
                            <button type="button" wire:click="toggleSelectedClass('{{ $className }}')" class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-200">
                                <span>{{ $className }}</span>
                                <span aria-hidden="true">×</span>
                            </button>
                        @endforeach
                    </div>
                @endif
                <x-input-error :messages="$errors->get('selected_classes')" class="mt-2" />
            </div>
            <div class="col-span-2 flex items-center gap-4 mt-6">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold text-lg hover:bg-green-700 transition">{{ $editingId ? 'UPDATE TEACHER' : 'ADD TEACHER' }}</button>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">Cancel</button>
                @endif
            </div>
        </form>
    </div>

    <!-- Toast Popup -->
    @if (session('status'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-init="setTimeout(() => show = false, 2500)"
            class="fixed top-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-lg font-semibold bg-white/90 backdrop-blur"
            :class="{
                'bg-green-100 text-green-800 border-green-200': session('status').toLowerCase().includes('add') || session('status').toLowerCase().includes('update') || session('status').toLowerCase().includes('edit'),
                'bg-red-100 text-red-800 border-red-200': session('status').toLowerCase().includes('delete')
            }"
        >
            <template x-if="session('status').toLowerCase().includes('add') || session('status').toLowerCase().includes('update')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg>
            </template>
            <template x-if="session('status').toLowerCase().includes('edit')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" /></svg>
            </template>
            <template x-if="session('status').toLowerCase().includes('delete')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
            </template>
            {{ session('status') }}
        </div>
    @endif

    <!-- Teachers List by Department/Class -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mt-8">
        <div class="bg-gradient-to-r from-indigo-50 to-white border-b border-slate-200 p-4">
            <h2 class="text-lg font-semibold text-slate-800">
                Teachers List
                @if ($selectedDepartmentId && $activeFilterClass)
                    <span class="text-sm font-normal text-slate-600">
                    - {{ optional($this->departments()->firstWhere('id', $selectedDepartmentId))->name }} / {{ $activeFilterClass }}
                    </span>
                @elseif ($selectedDepartmentId)
                    <span class="text-sm font-normal text-slate-500">
                    - Select faculty and tap a class to view teachers
                    </span>
            @else
                    <span class="text-sm font-normal text-slate-500">
                    - Select a department first
                    </span>
                @endif
            </h2>
        </div>
        @if ($selectedDepartmentId && $activeCourseId && $activeFilterClass)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700 border-b-2 border-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Teacher Name</th>
                            <th class="px-4 py-3 text-left font-semibold">Username</th>
                            <th class="px-4 py-3 text-left font-semibold">Email Address</th>
                            <th class="px-4 py-3 text-left font-semibold">Department</th>
                            <th class="px-4 py-3 text-left font-semibold">Classes</th>
                            <th class="px-4 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($this->teachers() as $teacher)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $teacher->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $teacher->username }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $teacher->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-700 text-xs font-medium">
                                        <span class="font-bold">{{ $teacher->department?->name ?? '-' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($teacher->teacherClasses->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($teacher->teacherClasses as $class)
                                                <span class="inline-block px-2 py-0.5 rounded {{ $class->class_name === $activeFilterClass ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700' }} text-xs font-medium font-bold">
                                                    {{ $class->class_name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button
                                        type="button"
                                        wire:click="viewProfile({{ $teacher->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                                    >
                                        View
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="edit({{ $teacher->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        x-data
                                        x-on:click.prevent="if (confirm('Delete teacher {{ $teacher->name }}?')) { $wire.delete({{ $teacher->id }}); }"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md border border-red-200 text-red-700 bg-red-50 hover:bg-red-100 transition"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No teachers found for {{ $activeFilterClass }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-10 text-center text-sm text-slate-500">
                Select a department, then faculty, then tap a class to see teachers from that class only.
            </div>
        @endif
</div>

@if ($viewingProfileId)
    @php
        $viewTeacher = App\Models\User::with(['department', 'teacherClasses'])->find($viewingProfileId);
        $viewPhotoPath = $viewTeacher?->profile_photo_path
            ? ltrim(preg_replace('#^(storage/|public/)#', '', $viewTeacher->profile_photo_path), '/')
            : null;
        $viewPhotoUrl = $viewPhotoPath && Storage::disk('public')->exists($viewPhotoPath)
            ? Storage::url($viewPhotoPath)
            : null;
    @endphp
    @if ($viewTeacher)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="closeProfile"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50 border-b border-slate-200">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Teacher Profile</p>
                        <h3 class="text-xl font-semibold text-slate-900">{{ $viewTeacher->name }}</h3>
                    </div>
                    <button type="button" wire:click="closeProfile" class="p-2 rounded-full hover:bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <div class="flex items-center gap-3">
                        @if ($viewPhotoUrl)
                            <img src="{{ $viewPhotoUrl }}" alt="Profile" class="h-14 w-14 rounded-full object-cover border border-slate-200">
                        @else
                            <div class="h-14 w-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold border border-indigo-200">
                                {{ strtoupper(substr($viewTeacher->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="text-sm text-slate-500">Username</p>
                            <p class="font-semibold text-slate-900">{{ $viewTeacher->username }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="p-3 rounded-lg border border-slate-200 bg-slate-50">
                            <p class="text-slate-500 text-xs uppercase font-semibold">Email</p>
                            <p class="font-medium text-slate-900 break-all">{{ $viewTeacher->email }}</p>
                        </div>
                        <div class="p-3 rounded-lg border border-slate-200 bg-slate-50">
                            <p class="text-slate-500 text-xs uppercase font-semibold">Department</p>
                            <p class="font-medium text-slate-900">{{ $viewTeacher->department?->name ?? '-' }}</p>
                        </div>
                        <div class="p-3 rounded-lg border border-slate-200 bg-slate-50 sm:col-span-2">
                            <p class="text-slate-500 text-xs uppercase font-semibold">Classes</p>
                            @if ($viewTeacher->teacherClasses->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach ($viewTeacher->teacherClasses as $class)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">{{ $class->class_name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-slate-400 mt-1">No classes assigned</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-200 flex justify-end gap-2 bg-slate-50 rounded-b-2xl">
                    <button type="button" wire:click="closeProfile" class="px-4 py-2 text-sm rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100">Close</button>
                </div>
            </div>
        </div>
    @endif
@endif
</div>
