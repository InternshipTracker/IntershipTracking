<?php

use App\Models\Department;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $name = '';
    public string $searchQuery = '';
    public ?int $editingId = null;

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if ($this->editingId) {
            $rules['name'][] = 'unique:departments,name,'.$this->editingId;
            $validated = $this->validate($rules);

            Department::findOrFail($this->editingId)->update($validated);
            $this->reset(['name', 'editingId']);
            session()->flash('status', 'Department updated successfully.');

            return;
        }

        $rules['name'][] = 'unique:departments,name';
        $validated = $this->validate($rules);

        Department::create($validated);
        $this->reset('name');
        session()->flash('status', 'Department created successfully.');
    }

    public function edit(int $id): void
    {
        $department = Department::findOrFail($id);
        $this->editingId = $department->id;
        $this->name = $department->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $id): void
    {
        Department::findOrFail($id)->delete();
        session()->flash('status', 'Department deleted successfully.');
    }

    public function departments()
    {
        return Department::query()
            ->withCount(['users as teacher_count' => function ($query) {
                $query->where('role', 'teacher');
            }])
            ->when($this->searchQuery !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->searchQuery . '%');
            })
            ->latest()
            ->get();
    }
}; ?>

<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('superadmin.dashboard') }}" wire:navigate class="text-sm font-medium text-[color:var(--accent-600)] hover:text-[color:var(--accent-700)]">← Back to Dashboard</a>
                    <h1 class="mt-1 text-3xl font-bold text-[color:var(--page-text)]">Department Management</h1>
                </div>
            </div>
            <p class="mt-1 text-[color:var(--page-muted)]">Organize and manage academic departments</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl border px-4 py-2 shadow-sm backdrop-blur-sm"
            style="background: linear-gradient(135deg, rgb(var(--accent-rgb) / 0.14), rgb(var(--accent-rgb) / 0.06)); border-color: rgb(var(--accent-rgb) / 0.24);">
            <svg class="h-5 w-5 text-[color:var(--accent-600)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="text-sm font-semibold text-[color:var(--page-text)]">{{ $this->departments()->count() }} Departments</span>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-xl border-2 border-slate-200 shadow-md p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="searchQuery"
                class="w-full pl-12 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 text-slate-900 placeholder-slate-400"
                placeholder="Search departments by name..."
            />
            @if ($searchQuery !== '')
                <button 
                    wire:click="$set('searchQuery', '')"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
        @if ($searchQuery !== '')
            <div class="mt-2 text-sm text-slate-600">
                <span class="font-medium">{{ $this->departments()->count() }}</span> department(s) found for "<span class="font-semibold text-indigo-600">{{ $searchQuery }}</span>"
            </div>
        @endif
    </div>

    <!-- Add Department Form -->
    <div class="rounded-3xl border p-6 shadow-lg"
        style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.12), rgb(255 255 255 / 0.02) 24%), var(--panel-bg); border-color: var(--panel-border); box-shadow: var(--panel-shadow);">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-[color:var(--page-text)]">{{ $editingId ? 'Edit Department' : 'Add New Department' }}</h2>
                <p class="text-sm text-[color:var(--page-muted)]">{{ $editingId ? 'Update department information' : 'Create a new academic department' }}</p>
            </div>
        </div>
        
        <form wire:submit="save" class="flex gap-3">
            <div class="flex-1">
                <x-text-input 
                    wire:model="name" 
                    class="w-full h-12 text-base" 
                    placeholder="Enter department name (e.g., Computer Science, Electronics)" 
                />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                @if ($editingId)
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Department
                @endif
            </button>
            @if ($editingId)
                <button type="button" wire:click="cancelEdit" class="px-6 py-3 rounded-lg border-2 font-semibold text-[color:var(--page-text)] transition-colors duration-200 hover:bg-[rgb(var(--accent-rgb)/0.08)]" style="background: var(--panel-bg); border-color: var(--panel-border);">
                    Cancel
                </button>
            @endif
        </form>

        @if (session('status'))
            <div class="mt-4 flex items-center gap-3 rounded-lg border p-4" style="background: rgb(16 185 129 / 0.12); border-color: rgb(16 185 129 / 0.28);">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
            </div>
        @endif
    </div>

    <!-- Departments Grid -->
    <div>
        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-[color:var(--page-text)]">
            <svg class="h-6 w-6 text-[color:var(--accent-600)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            All Departments
        </h2>

        @php
            $colors = [
                ['bg' => 'from-blue-500 to-blue-600', 'light' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-700', 'icon' => 'text-blue-600'],
                ['bg' => 'from-purple-500 to-purple-600', 'light' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-700', 'icon' => 'text-purple-600'],
                ['bg' => 'from-green-500 to-green-600', 'light' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-700', 'icon' => 'text-green-600'],
                ['bg' => 'from-orange-500 to-orange-600', 'light' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-700', 'icon' => 'text-orange-600'],
                ['bg' => 'from-pink-500 to-pink-600', 'light' => 'bg-pink-50', 'border' => 'border-pink-200', 'text' => 'text-pink-700', 'icon' => 'text-pink-600'],
                ['bg' => 'from-cyan-500 to-cyan-600', 'light' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'text' => 'text-cyan-700', 'icon' => 'text-cyan-600'],
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($this->departments() as $index => $department)
                @php
                    $colorScheme = $colors[$index % count($colors)];
                @endphp
                <div class="group relative bg-white rounded-2xl border-2 {{ $colorScheme['border'] }} shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden">
                    <!-- Gradient Header -->
                    <div class="h-32 bg-gradient-to-br {{ $colorScheme['bg'] }} p-6 relative">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
                        
                        <div class="relative z-10 flex items-start justify-between">
                            <div class="w-14 h-14 bg-white bg-opacity-25 backdrop-blur-md rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="flex gap-2">
                                <button 
                                    wire:click="edit({{ $department->id }})" 
                                    class="w-9 h-9 bg-white bg-opacity-25 backdrop-blur-md hover:bg-opacity-40 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-110"
                                    title="Edit Department"
                                >
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button 
                                    wire:click="delete({{ $department->id }})" 
                                    wire:confirm="Delete {{ $department->name }}? This action cannot be undone." 
                                    class="w-9 h-9 bg-white bg-opacity-25 backdrop-blur-md hover:bg-opacity-40 hover:bg-red-500 rounded-lg flex items-center justify-center transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-110"
                                    title="Delete Department"
                                >
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors duration-200">
                            {{ $department->name }}
                        </h3>
                        
                        <div class="space-y-3">
                            <!-- Teacher Count -->
                            <div class="flex items-center gap-3 p-3 {{ $colorScheme['light'] }} rounded-lg border {{ $colorScheme['border'] }}">
                                <div class="w-10 h-10 rounded-lg {{ $colorScheme['light'] }} border {{ $colorScheme['border'] }} flex items-center justify-center">
                                    <svg class="w-5 h-5 {{ $colorScheme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Teachers</p>
                                    <p class="text-lg font-bold {{ $colorScheme['text'] }}">{{ $department->teacher_count ?? 0 }}</p>
                                </div>
                            </div>

                            <!-- Created Date -->
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Created {{ $department->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hover Effect Border -->
                    <div class="absolute inset-0 border-2 border-transparent group-hover:border-indigo-400 rounded-2xl transition-all duration-300 pointer-events-none"></div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="rounded-2xl border-2 border-dashed p-12 text-center"
                        style="background: linear-gradient(145deg, rgb(var(--accent-rgb) / 0.08), transparent 32%), var(--surface-soft); border-color: var(--panel-border);">
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full"
                            style="background: rgb(var(--accent-rgb) / 0.12);">
                            <svg class="h-10 w-10 text-[color:var(--accent-600)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-[color:var(--page-text)]">No Departments Yet</h3>
                        <p class="mb-4 text-[color:var(--page-muted)]">Get started by creating your first department above.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
