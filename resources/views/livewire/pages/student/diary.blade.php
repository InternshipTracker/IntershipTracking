<?php

use App\Models\Diary;
use App\Models\Internship;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $entry_date = '';
    public string $topic = '';
    public string $progress_description = '';
    public string $what_learned = '';
    public string $time_spent = '';
    public string $hours_studied = '';
    public string $challenges_faced = '';
    public string $skills_developed = '';
    public bool $showSuccessModal = false;
    public array $expandedEntries = [];
    public ?int $deleteEntryId = null;
    public ?int $editEntryId = null;

    public function mount(): void
    {
        $internship = Internship::where('student_id', auth()->id())->latest()->first();
        
        if (!$internship || $internship->status !== 'approved') {
            session()->flash('error', 'Daily diary is only accessible after internship approval.');
            $this->redirect(route('student.dashboard'), navigate: true);
        }
    }

    public function approvedInternship(): ?Internship
    {
        return Internship::query()
            ->where('student_id', auth()->id())
            ->where('status', 'approved')
            ->latest()
            ->first();
    }

    public function submit(): void
    {
        if ($this->editEntryId) {
            $this->updateEntry();
            return;
        }

        $internship = $this->approvedInternship();
        if (! $internship) {
            session()->flash('status', 'Daily diary is allowed only after internship approval.');
            return;
        }

        $validated = $this->validate([
            'entry_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'progress_description' => ['required', 'string', 'max:3000'],
            'what_learned' => ['required', 'string', 'max:3000'],
            'time_spent' => ['required', 'string', 'max:50'],
            'hours_studied' => ['required', 'numeric', 'min:0', 'max:24'],
            'challenges_faced' => ['nullable', 'string', 'max:3000'],
            'skills_developed' => ['nullable', 'string', 'max:3000'],
        ]);

        Diary::create([
            'internship_id' => $internship->id,
            'student_id' => auth()->id(),
            'entry_date' => $validated['entry_date'],
            'topic' => $validated['topic'],
            'progress_description' => $validated['progress_description'],
            'what_learned' => $validated['what_learned'],
            'time_spent' => $validated['time_spent'],
            'hours_studied' => $validated['hours_studied'],
            'challenges_faced' => $validated['challenges_faced'] ?? null,
            'skills_developed' => $validated['skills_developed'] ?? null,
        ]);

        $this->reset(['entry_date', 'topic', 'progress_description', 'what_learned', 'time_spent', 'hours_studied', 'challenges_faced', 'skills_developed']);
        $this->showSuccessModal = true;
    }

    public function closeSuccessModal(): void
    {
        $this->showSuccessModal = false;
    }

    public function toggleEntry(int $entryId): void
    {
        if (in_array($entryId, $this->expandedEntries)) {
            $this->expandedEntries = array_filter($this->expandedEntries, fn ($id) => $id !== $entryId);
        } else {
            $this->expandedEntries[] = $entryId;
        }
    }

    public function deleteEntry(int $entryId): void
    {
        $entry = Diary::query()
            ->where('student_id', auth()->id())
            ->findOrFail($entryId);

        $entry->delete();
        $this->expandedEntries = array_filter($this->expandedEntries, fn ($id) => $id !== $entryId);
        session()->flash('info', 'Diary entry deleted.');
    }

    public function startEdit(int $entryId): void
    {
        $entry = Diary::query()
            ->where('student_id', auth()->id())
            ->findOrFail($entryId);

        $this->editEntryId = $entry->id;
        $this->entry_date = $entry->entry_date->format('Y-m-d');
        $this->topic = $entry->topic;
        $this->progress_description = $entry->progress_description;
        $this->what_learned = $entry->what_learned;
        $this->time_spent = $entry->time_spent;
        $this->hours_studied = (string) $entry->hours_studied;
        $this->challenges_faced = $entry->challenges_faced ?? '';
        $this->skills_developed = $entry->skills_developed ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editEntryId', 'entry_date', 'topic', 'progress_description', 'what_learned', 'time_spent', 'hours_studied', 'challenges_faced', 'skills_developed']);
    }

    public function updateEntry(): void
    {
        $entry = Diary::query()
            ->where('student_id', auth()->id())
            ->findOrFail($this->editEntryId);

        $validated = $this->validate([
            'entry_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:255'],
            'progress_description' => ['required', 'string', 'max:3000'],
            'what_learned' => ['required', 'string', 'max:3000'],
            'time_spent' => ['required', 'string', 'max:50'],
            'hours_studied' => ['required', 'numeric', 'min:0', 'max:24'],
            'challenges_faced' => ['nullable', 'string', 'max:3000'],
            'skills_developed' => ['nullable', 'string', 'max:3000'],
        ]);

        $entry->update([
            'entry_date' => $validated['entry_date'],
            'topic' => $validated['topic'],
            'progress_description' => $validated['progress_description'],
            'what_learned' => $validated['what_learned'],
            'time_spent' => $validated['time_spent'],
            'hours_studied' => $validated['hours_studied'],
            'challenges_faced' => $validated['challenges_faced'] ?? null,
            'skills_developed' => $validated['skills_developed'] ?? null,
        ]);

        $this->reset(['editEntryId', 'entry_date', 'topic', 'progress_description', 'what_learned', 'time_spent', 'hours_studied', 'challenges_faced', 'skills_developed']);
        session()->flash('status', 'Diary entry updated.');
    }

    public function entries()
    {
        return Diary::query()
            ->where('student_id', auth()->id())
            ->latest('entry_date')
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
                    <a href="{{ route('student.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
                    <h1 class="text-3xl font-bold text-slate-900 mt-1">📖 Daily Diary</h1>
                </div>
            </div>
            <p class="text-slate-600 mt-1">Document your learning journey and track your progress</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-indigo-50 rounded-lg border border-indigo-200">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="text-sm font-semibold text-indigo-900">{{ $this->entries()->count() }} Entries</span>
        </div>
    </div>

    <!-- Add New Entry Form -->
    <div class="bg-gradient-to-br from-white to-blue-50 rounded-2xl border-2 border-blue-100 shadow-xl p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $editEntryId ? 'Edit Diary Entry' : 'Add New Entry' }}</h2>
                <p class="text-sm text-slate-600">{{ $editEntryId ? 'Update your diary entry details' : 'Fill in your daily learning details' }}</p>
            </div>
        </div>
        
        <form wire:submit="submit" class="space-y-6">
            <!-- Row 1: Date and Topic -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="📅 Date *" class="text-base font-semibold" />
                    <x-text-input wire:model="entry_date" type="date" class="w-full mt-2 h-11" />
                    <x-input-error :messages="$errors->get('entry_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label value="📌 Today's Topic *" class="text-base font-semibold" />
                    <x-text-input wire:model="topic" class="w-full mt-2 h-11" placeholder="e.g., React Components, Database Design" />
                    <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                </div>
            </div>

            <!-- Row 2: Work Done -->
            <div>
                <x-input-label value="💼 Work Done Today *" class="text-base font-semibold" />
                <textarea 
                    wire:model="progress_description" 
                    rows="4" 
                    class="w-full mt-2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="Describe what tasks you completed, projects you worked on, or assignments you finished..."
                ></textarea>
                <x-input-error :messages="$errors->get('progress_description')" class="mt-2" />
            </div>

            <!-- Row 3: What I Learned -->
            <div>
                <x-input-label value="💡 What I Learned *" class="text-base font-semibold" />
                <textarea 
                    wire:model="what_learned" 
                    rows="4" 
                    class="w-full mt-2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="Share new concepts, techniques, or insights you gained today..."
                ></textarea>
                <x-input-error :messages="$errors->get('what_learned')" class="mt-2" />
            </div>

            <!-- Row 4: Time and Hours -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="⏰ Time Spent *" class="text-base font-semibold" />
                    <x-text-input wire:model="time_spent" class="w-full mt-2 h-11" placeholder="e.g., 9:00 AM - 5:00 PM" />
                    <x-input-error :messages="$errors->get('time_spent')" class="mt-2" />
                </div>
                <div>
                    <x-input-label value="⏳ Hours Studied *" class="text-base font-semibold" />
                    <x-text-input wire:model="hours_studied" type="number" step="0.5" min="0" max="24" class="w-full mt-2 h-11" placeholder="e.g., 6.5" />
                    <x-input-error :messages="$errors->get('hours_studied')" class="mt-2" />
                </div>
            </div>

            <!-- Row 5: Challenges (Optional) -->
            <div>
                <x-input-label value="🚧 Challenges Faced (Optional)" class="text-base font-semibold" />
                <textarea 
                    wire:model="challenges_faced" 
                    rows="3" 
                    class="w-full mt-2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="Describe any difficulties, obstacles, or problems you encountered..."
                ></textarea>
                <x-input-error :messages="$errors->get('challenges_faced')" class="mt-2" />
            </div>

            <!-- Row 6: Skills Developed (Optional) -->
            <div>
                <x-input-label value="🎯 Skills Developed (Optional)" class="text-base font-semibold" />
                <textarea 
                    wire:model="skills_developed" 
                    rows="3" 
                    class="w-full mt-2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    placeholder="List technical or soft skills you improved or acquired today..."
                ></textarea>
                <x-input-error :messages="$errors->get('skills_developed')" class="mt-2" />
            </div>

            <!-- Submit Button -->
            <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ $editEntryId ? 'Update Entry' : 'Save Diary Entry' }}
                </button>
                @if($editEntryId)
                    <button type="button" wire:click="cancelEdit" class="px-4 py-3 border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50">Cancel Edit</button>
                @endif
                <p class="text-sm text-slate-500 italic">* Required fields</p>
            </div>
        </form>
    </div>

    <!-- Diary Timeline -->
    <div>
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-7 h-7 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-2xl font-bold text-slate-900">My Learning Timeline</h2>
        </div>

        <div class="space-y-4">
            @forelse ($this->entries() as $entry)
                <div class="bg-white rounded-xl border border-slate-200 hover:border-indigo-200 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden" wire:key="entry-{{ $entry->id }}">
                    <!-- Entry Header -->
                    <div class="bg-gradient-to-r from-indigo-500 to-blue-500 p-4 flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-bold text-white truncate">{{ $entry->topic }}</h3>
                                    <p class="text-white/90 text-xs mt-1">{{ $entry->entry_date->format('l, F d, Y') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="startEdit({{ $entry->id }})" class="px-2.5 py-1 bg-white/20 text-white rounded-md text-xs font-semibold hover:bg-white/30 border border-white/30">Edit</button>
                                    <button onclick="confirm('Delete this entry?') || event.stopImmediatePropagation()" wire:click="deleteEntry({{ $entry->id }})" class="px-2.5 py-1 bg-white/20 text-white rounded-md text-xs font-semibold hover:bg-white/30 border border-white/30">Delete</button>
                                    <button wire:click="toggleEntry({{ $entry->id }})" class="p-2 bg-white/15 rounded-md border border-white/25 text-white hover:bg-white/25 transition">
                                        <svg class="w-5 h-5 transition-transform duration-200 {{ in_array($entry->id, $expandedEntries) ? 'rotate-90' : 'rotate-0' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-white/90 flex items-center gap-3">
                                <span class="inline-flex items-center px-2 py-1 bg-white/15 rounded-full">⏳ {{ $entry->hours_studied }} hrs</span>
                                <span class="inline-flex items-center px-2 py-1 bg-white/15 rounded-full">🕒 {{ $entry->time_spent }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Entry Body - Expandable -->
                    @if (in_array($entry->id, $expandedEntries))
                        <div class="p-5 space-y-5 bg-white" style="animation: slideDown 0.2s ease-out; overflow: hidden;">
                            <!-- Work Done -->
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="font-bold text-slate-900">Work Done</h4>
                                </div>
                                <p class="text-slate-700 leading-relaxed pl-10">{{ $entry->progress_description }}</p>
                            </div>

                            <!-- What I Learned -->
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="font-bold text-slate-900">What I Learned</h4>
                                </div>
                                <p class="text-slate-700 leading-relaxed pl-10">{{ $entry->what_learned }}</p>
                            </div>

                            @if ($entry->challenges_faced)
                                <!-- Challenges -->
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                        </div>
                                        <h4 class="font-bold text-slate-900">Challenges Faced</h4>
                                    </div>
                                    <p class="text-slate-700 leading-relaxed pl-10">{{ $entry->challenges_faced }}</p>
                                </div>
                            @endif

                            @if ($entry->skills_developed)
                                <!-- Skills -->
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                        <h4 class="font-bold text-slate-900">Skills Developed</h4>
                                    </div>
                                    <p class="text-slate-700 leading-relaxed pl-10">{{ $entry->skills_developed }}</p>
                                </div>
                            @endif

                            <!-- Time and Timestamp Info -->
                            <div class="pt-3 border-t border-slate-200 space-y-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span><strong>Time:</strong> {{ $entry->time_spent }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Added on {{ $entry->created_at->format('M d, Y \a\t h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl border-2 border-dashed border-slate-300 p-16 text-center">
                    <div class="w-24 h-24 mx-auto mb-6 bg-slate-200 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">No Diary Entries Yet</h3>
                    <p class="text-slate-600">Start documenting your learning journey by adding your first entry above!</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Success Modal -->
    @if ($showSuccessModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" wire:click="closeSuccessModal">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-bounce-in" wire:click.stop>
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
                    <div class="relative flex items-center justify-center">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-8 text-center">
                    <h3 class="text-2xl font-bold text-slate-900 mb-3">🎉 Entry Saved Successfully!</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed">Your diary entry has been recorded. Keep up the great work on your learning journey!</p>
                    <div class="flex flex-col gap-2 text-sm text-slate-500 bg-slate-50 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Entry added to your timeline</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Progress tracked successfully</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Visible to your teacher</span>
                        </div>
                    </div>
                    <button wire:click="closeSuccessModal" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        Awesome, Got It!
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
