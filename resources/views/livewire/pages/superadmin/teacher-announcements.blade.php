<?php

use App\Models\AdminTeacherAnnouncement;
use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;
    public ?int $teacher_id = null;
    public string $department_query = '';
    public string $teacher_query = '';
    public string $title = '';
    public string $message = '';

    public function departmentSuggestions()
    {
        $query = trim($this->department_query);

        if ($query === '') {
            return collect();
        }

        return Department::query()
            ->where('name', 'like', $query.'%')
            ->orWhere('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function teacherSuggestions()
    {
        $departmentName = trim($this->department_query);
        $teacherName = trim($this->teacher_query);

        $teachers = User::query()
            ->with('department')
            ->where('role', 'teacher')
            ->when($departmentName !== '', function ($query) use ($departmentName) {
                $query->whereHas('department', function ($departmentQuery) use ($departmentName) {
                    $departmentQuery->where('name', 'like', $departmentName.'%')
                        ->orWhere('name', 'like', '%'.$departmentName.'%');
                });
            })
            ->when($teacherName !== '', function ($query) use ($teacherName) {
                $query->where('name', 'like', $teacherName.'%')
                    ->orWhere('name', 'like', '%'.$teacherName.'%');
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        if ($this->teacher_id) {
            $teachers = $teachers->reject(fn ($teacher) => $teacher->id === $this->teacher_id)->values();
        }

        return $teachers;
    }

    public function selectTeacher(int $teacherId): void
    {
        $teacher = User::query()
            ->with('department')
            ->where('role', 'teacher')
            ->findOrFail($teacherId);

        $this->teacher_id = $teacher->id;
        $this->teacher_query = $teacher->name;
        $this->department_query = $teacher->department?->name ?? $this->department_query;
    }

    public function clearTeacherSelection(): void
    {
        $this->teacher_id = null;
        $this->teacher_query = '';
    }

    public function save(): void
    {
        $rules = [
            'teacher_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ];

        $validated = $this->validate($rules);

        User::query()->where('id', $validated['teacher_id'])->where('role', 'teacher')->firstOrFail();

        if ($this->editingId) {
            $record = AdminTeacherAnnouncement::query()
                ->where('superadmin_id', auth()->id())
                ->findOrFail($this->editingId);

            $record->update($validated);
            session()->flash('status', 'Announcement updated successfully.');
            $this->reset(['editingId', 'teacher_id', 'department_query', 'teacher_query', 'title', 'message']);

            return;
        }

        $teacherName = $this->teacher_query;

        AdminTeacherAnnouncement::create([
            'superadmin_id' => auth()->id(),
            'teacher_id' => $validated['teacher_id'],
            'title' => $validated['title'],
            'message' => $validated['message'],
        ]);

        session()->flash('status', 'Announcement sent to ' . $teacherName . '!');
        $this->reset(['teacher_id', 'department_query', 'teacher_query', 'title', 'message']);
    }

    public function edit(int $id): void
    {
        $record = AdminTeacherAnnouncement::query()
            ->where('superadmin_id', auth()->id())
            ->findOrFail($id);

        $this->editingId = $record->id;
        $this->teacher_id = $record->teacher_id;
        $this->teacher_query = $record->teacher?->name ?? '';
        $this->department_query = $record->teacher?->department?->name ?? '';
        $this->title = $record->title;
        $this->message = $record->message;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'teacher_id', 'department_query', 'teacher_query', 'title', 'message']);
    }

    public function delete(int $id): void
    {
        AdminTeacherAnnouncement::query()
            ->where('superadmin_id', auth()->id())
            ->findOrFail($id)
            ->delete();

        session()->flash('status', 'Announcement deleted successfully.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('superadmin.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
            <h1 class="text-2xl font-semibold mt-1">Send Teacher Announcements</h1>
        </div>
    </div>

    <div class="bg-white rounded-2xl border-2 border-indigo-300 shadow-xl p-6 animate-fade-in">
        <form wire:submit="save" class="bg-gradient-to-br from-blue-50 via-white to-slate-100 rounded-2xl p-8 shadow-2xl border border-blue-200 max-w-xl mx-auto flex flex-col gap-6 mt-8">
            <div>
                <x-input-label value="Department (Type)" class="block text-base font-bold text-blue-700 mb-2 tracking-wide" />
                <x-text-input wire:model.live.debounce.250ms="department_query" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white mt-1" placeholder="Type department name" />

                @if (filled($department_query))
                    <div class="mt-2 border border-slate-200 rounded-md bg-white max-h-32 overflow-auto">
                        @forelse ($this->departmentSuggestions() as $department)
                            <button
                                type="button"
                                wire:click="$set('department_query', '{{ addslashes($department->name) }}')"
                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50"
                            >
                                {{ $department->name }}
                            </button>
                        @empty
                            <p class="px-3 py-2 text-sm text-slate-500">No matching department.</p>

                            
                        @endforelse
                    </div>
                @endif
            </div>

            <div>
                <x-input-label value="Teacher (Type)" class="block text-base font-bold text-blue-700 mb-2 tracking-wide" />
                <div class="flex items-center gap-2 mt-1">
                    <x-text-input wire:model.live.debounce.250ms="teacher_query" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white" placeholder="Type teacher name" />
                    @if ($teacher_id)
                        <button type="button" wire:click="clearTeacherSelection" class="px-3 py-2 text-sm rounded-md border border-slate-300">Clear</button>
                    @endif
                </div>

                @if (filled($teacher_query) && $this->teacherSuggestions()->isNotEmpty() && !$teacher_id)
                    <div class="mt-2 border border-indigo-300 shadow-lg rounded-xl bg-gradient-to-br from-white via-indigo-50 to-indigo-100 max-h-40 overflow-auto animate-fade-in">
                        @foreach ($this->teacherSuggestions() as $teacher)
                            @if (str_starts_with($teacher->name, $teacher_query))
                                <button type="button" wire:click="selectTeacher({{ $teacher->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 transition-all border-b border-indigo-100 last:border-none">
                                    <span class="font-semibold text-indigo-700">{{ $teacher->name }}</span> <span class="text-xs text-slate-500">({{ $teacher->department?->name ?? '-' }})</span>
                                </button>
                            @endif
                        @endforeach
                        @if($this->teacherSuggestions()->filter(fn($t) => str_starts_with($t->name, $teacher_query))->isEmpty())
                            <p class="px-3 py-2 text-sm text-slate-500">No matching teacher.</p>
                        @endif
                    </div>
                @endif

                @if ($teacher_id)
                    <p class="mt-2 text-xs text-green-700">Selected teacher: {{ $teacher_query }}</p>
                @endif

                <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Title" class="block text-base font-bold text-blue-700 mb-2 tracking-wide" />
                <x-text-input wire:model="title" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white mt-1" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label value="Message" class="block text-base font-bold text-blue-700 mb-2 tracking-wide" />
                <textarea wire:model="message" rows="4" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white mt-1"></textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />
            </div>

            <div>
                <x-primary-button>{{ $editingId ? 'Update Announcement' : 'Send Announcement' }}</x-primary-button>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="ml-2 px-4 py-2 text-sm rounded-md border border-slate-300">Cancel</button>
                @endif
            </div>
        </form>

        @if (session('status'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show"
                 class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-green-600 text-white font-semibold px-6 py-3 rounded-xl shadow-lg text-lg z-50 animate-bounce">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <p class="text-sm text-slate-600 mt-6">📢 <strong>Click the bell icon</strong> in the header to view sent announcements and their read status.</p>
</div>
