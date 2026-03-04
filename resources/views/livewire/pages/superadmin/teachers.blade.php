<?php

use App\Models\Department;
use App\Models\TeacherClass;
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
    public string $activeClassLevel = '';
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $class_query = '';
    public array $selected_classes = [];

    private function normalizeClassName(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function pushClassName(string $value): void
    {
        $normalized = $this->normalizeClassName($value);

        if ($normalized === '') {
            return;
        }

        if (! in_array($normalized, $this->selected_classes, true)) {
            $this->selected_classes[] = $normalized;
        }
    }

    public function selectDepartment(int $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
        // Reset class level when department changes
        $this->activeClassLevel = '';
    }

    public function selectClassLevel(string $level): void
    {
        $this->activeClassLevel = $this->activeClassLevel === $level ? '' : $level;
    }

    private function getClassLevelsForDepartment(?int $departmentId): array
    {
        if (!$departmentId) {
            return [];
        }

        $department = Department::find($departmentId);
        if (!$department) {
            return [];
        }

        // Base levels for all departments
        $levels = ['FY', 'SY', 'TY'];

        // Add master's programs based on department
        if (in_array($department->name, ['Computer Science', 'Information Technology'])) {
            $levels[] = 'MCS';
            $levels[] = 'MCA';
        } elseif (in_array($department->name, ['Electronics & Communication', 'Electronics', 'Mechanical Engineering', 'Civil Engineering'])) {
            $levels[] = 'ME';
        }

        return $levels;
    }

    private function allowedClassLevels(): array
    {
        return ['FY', 'SY', 'TY', 'MCS', 'MCA', 'ME'];
    }

    private function classLevel(string $className): string
    {
        $normalized = strtoupper(trim($className));
        
        // Check for 3-character levels first (MCS, MCA)
        $threeChar = substr($normalized, 0, 3);
        if (in_array($threeChar, ['MCS', 'MCA'], true)) {
            return $threeChar;
        }
        
        // Check for 2-character levels (FY, SY, TY, ME)
        return substr($normalized, 0, 2);
    }

    public function addClass(): void
    {
        if ($this->activeClassLevel === '') {
            $this->addError('activeClassLevel', 'Select a class level box first.');
            return;
        }

        $this->pushClassName($this->class_query);

        if (! empty($this->selected_classes)) {
            $lastAdded = end($this->selected_classes);

            if ($this->classLevel($lastAdded) !== $this->activeClassLevel) {
                $this->removeClass($lastAdded);
                $this->addError('selected_classes', 'Added class must match selected class level box.');
            }
        }

        $this->class_query = '';
    }

    public function addSuggestedClass(string $className): void
    {
        if ($this->activeClassLevel === '') {
            $this->addError('activeClassLevel', 'Select a class level box first.');
            return;
        }

        $this->pushClassName($className);
        $this->class_query = '';
    }

    public function removeClass(string $className): void
    {
        $this->selected_classes = array_values(array_filter(
            $this->selected_classes,
            fn (string $item) => $item !== $className
        ));
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];

        if (filled($this->class_query)) {
            $this->pushClassName($this->class_query);
            $this->class_query = '';
        }

        $departmentId = $this->selectedDepartmentId;

        if (! $departmentId) {
            $this->addError('selectedDepartmentId', 'Select a department box first.');
            return;
        }

        if ($this->activeClassLevel === '' || ! in_array($this->activeClassLevel, $this->allowedClassLevels(), true)) {
            $this->addError('activeClassLevel', 'Select a class level box first.');
            return;
        }

        if (count($this->selected_classes) === 0) {
            $this->addError('selected_classes', 'Please add at least one class.');
            return;
        }

        $hasInvalidClass = collect($this->selected_classes)
            ->contains(fn (string $className) => $this->classLevel($className) !== $this->activeClassLevel);

        if ($hasInvalidClass) {
            $this->addError('selected_classes', 'All classes must match selected class level box.');
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
            $this->reset(['editingId', 'name', 'username', 'email', 'password', 'class_query', 'selected_classes']);
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
        $this->reset(['name', 'username', 'email', 'password', 'class_query', 'selected_classes']);
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
        $this->activeClassLevel = count($this->selected_classes) ? $this->classLevel((string) $this->selected_classes[0]) : '';
        $this->class_query = '';
        $this->password = '';
        
        // Close profile modal if open
        $this->viewingProfileId = null;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'username', 'email', 'password', 'class_query', 'selected_classes']);
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
        return Department::query()->orderBy('name')->get();
    }

    public function classSuggestions()
    {
        $query = strtolower(trim($this->class_query));

        $studentClasses = User::query()
            ->where('role', 'student')
            ->whereNotNull('class')
            ->when($this->selectedDepartmentId, fn ($q) => $q->where('department_id', $this->selectedDepartmentId))
            ->distinct()
            ->pluck('class');

        $teacherClasses = TeacherClass::query()
            ->when($this->selectedDepartmentId, function ($q) {
                $q->whereHas('teacher', fn ($tq) => $tq->where('department_id', $this->selectedDepartmentId));
            })
            ->distinct()
            ->pluck('class_name');

        return $studentClasses
            ->merge($teacherClasses)
            ->map(fn ($item) => strtoupper(trim((string) $item)))
            ->filter()
            ->unique()
            ->sort()
            ->filter(fn (string $item) => $this->activeClassLevel === '' || str_starts_with($item, $this->activeClassLevel))
            ->values()
            ->filter(fn (string $item) => $query === '' || str_contains(strtolower($item), $query))
            ->reject(fn (string $item) => in_array($item, $this->selected_classes, true))
            ->take(8)
            ->values();
    }

    public function teachers()
    {
        // Only show teachers when department is selected
        if (!$this->selectedDepartmentId) {
            return collect([]);
        }

        return User::query()
            ->with(['department', 'teacherClasses'])
            ->where('role', 'teacher')
            ->where('department_id', $this->selectedDepartmentId)
            ->when($this->activeClassLevel !== '', function ($query) {
                $query->whereHas('teacherClasses', function ($classQuery) {
                    $classQuery->where('class_name', 'like', $this->activeClassLevel.'%');
                });
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

    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6 shadow-sm">
        <div>
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Step 1 · Department</h2>
                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-500">Choose first</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($this->departments() as $department)
                    <button
                        type="button"
                        wire:click="selectDepartment({{ $department->id }})"
                        class="group rounded-xl border-2 p-4 text-left transition-all duration-200 {{ $selectedDepartmentId === $department->id ? 'border-indigo-600 bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-900 shadow-lg ring-2 ring-indigo-200' : 'border-slate-200 bg-white hover:bg-slate-50 hover:border-indigo-300 text-slate-700 hover:shadow-md' }}"
                    >
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $selectedDepartmentId === $department->id ? 'bg-indigo-600' : 'bg-slate-300 group-hover:bg-indigo-400' }}"></div>
                            <div class="font-semibold text-sm">{{ $department->name }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('selectedDepartmentId')" class="mt-2" />
        </div>

        @if ($selectedDepartmentId)
            <div class="border-t pt-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">Step 2: Select Class Level for {{ optional($this->departments()->firstWhere('id', $selectedDepartmentId))->name }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    @foreach ($this->getClassLevelsForDepartment($selectedDepartmentId) as $level)
                        <button
                            type="button"
                            wire:click="selectClassLevel('{{ $level }}')"
                            class="rounded-lg border-2 p-4 text-center font-bold text-base transition-all duration-200 {{ $activeClassLevel === $level ? 'border-green-500 bg-gradient-to-br from-green-50 to-green-100 text-green-800 shadow-lg ring-2 ring-green-200' : 'border-slate-300 bg-white hover:bg-green-50 hover:border-green-400 text-slate-700' }}"
                        >
                            {{ $level }}
                        </button>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('activeClassLevel')" class="mt-2" />
            </div>
        @else
            <div class="border-t pt-6">
                <div class="text-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm font-medium">Please select a department first to view class levels</p>
                </div>
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
                <label class="block text-sm font-semibold mb-1">Class</label>
                <div class="flex gap-2">
                    <input type="text" wire:model.live.debounce.250ms="class_query" class="w-full border rounded-lg px-4 py-2 focus:ring-indigo-500" placeholder="Type class from selected level and click Add">
                    <button type="button" wire:click="addClass" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700">Add</button>
                </div>
                <!-- Suggestions and badges as before -->
            </div>
            <div class="col-span-2 flex items-center gap-4 mt-6">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold text-lg hover:bg-green-700 transition">ADD TEACHER</button>
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

    <!-- Teachers List by Department -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm mt-8">
        <div class="bg-gradient-to-r from-indigo-50 to-white border-b border-slate-200 p-4">
            <h2 class="text-lg font-semibold text-slate-800">
                Teachers List
                @if ($selectedDepartmentId)
                    <span class="text-sm font-normal text-slate-600">
                    - {{ optional($this->departments()->firstWhere('id', $selectedDepartmentId))->name }}
                </span>
            @else
                <span class="text-sm font-normal text-slate-500">
                    - Select a department to view teachers
                </span>
            @endif
        </h2>
    </div>
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
                @foreach ($this->teachers() as $teacher)
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
                                        <button type="button" wire:click="$set('activeClassLevel', '{{ $class->class_name }}')" class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-700 text-xs font-medium font-bold hover:bg-green-200">
                                            {{ $class->class_name }}
                                        </button>
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
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($viewingProfileId)
    @php
        $viewTeacher = App\Models\User::with(['department', 'teacherClasses'])->find($viewingProfileId);
        $viewPhotoPath = $viewTeacher?->profile_photo_path
            ? ltrim(preg_replace('#^(storage/|public/)#', '', $viewTeacher->profile_photo_path), '/')
            : null;
        $viewPhotoUrl = $viewPhotoPath && Storage::disk('public')->exists($viewPhotoPath)
            ? Storage::disk('public')->url($viewPhotoPath)
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
