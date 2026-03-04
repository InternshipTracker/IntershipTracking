<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $student_id = null;
    public string $student_query = '';
    public string $title = '';
    public string $message = '';
    public array $expandedIds = [];

    public function mount(Request $request): void
    {
        $studentId = $request->integer('student');

        if ($studentId > 0) {
            $student = User::query()
                ->where('id', $studentId)
                ->where('role', 'student')
                ->where('department_id', auth()->user()->department_id)
                ->where('is_approved', true)
                ->first();

            if ($student) {
                $this->student_id = $student->id;
                $this->student_query = $student->name;
            }
        }
    }

    public function studentSuggestions()
    {
        $teacher = auth()->user();
        $assignedClasses = $teacher->teacherClasses()->pluck('class_name')->map(fn($c) => strtoupper($c))->toArray();
        return User::query()
            ->where('role', 'student')
            ->where('department_id', $teacher->department_id)
            ->where('is_approved', true)
            ->whereIn('class', $assignedClasses)
            ->when(trim($this->student_query) !== '', function ($query) {
                $search = trim($this->student_query);
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', $search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function selectStudent(int $studentId): void
    {
        $student = User::query()
            ->where('id', $studentId)
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->where('is_approved', true)
            ->firstOrFail();

        $this->student_id = $student->id;
        $this->student_query = $student->name;
    }

    public function clearStudentSelection(): void
    {
        $this->student_id = null;
        $this->student_query = '';
    }

    public function announcements()
    {
        return Announcement::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->latest()
            ->get();
    }

    public function toggle(int $id): void
    {
        if (in_array($id, $this->expandedIds, true)) {
            $this->expandedIds = array_values(array_filter($this->expandedIds, fn ($i) => $i !== $id));
        } else {
            $this->expandedIds[] = $id;
        }
    }

    public function deleteAnnouncement(int $id): void
    {
        Announcement::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($id)
            ->delete();
        session()->flash('status', 'Announcement deleted successfully.');
    }

    public function send(): void
    {
        $validated = $this->validate([
            'student_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $teacher = auth()->user();
        $assignedClasses = $teacher->teacherClasses()->pluck('class_name')->map(fn($c) => strtoupper($c))->toArray();
        $student = User::query()
            ->where('id', $validated['student_id'])
            ->where('role', 'student')
            ->where('department_id', $teacher->department_id)
            ->where('is_approved', true)
            ->whereIn('class', $assignedClasses)
            ->firstOrFail();

        Announcement::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'sender_type' => 'teacher',
            'sender_id' => $teacher->id,
            'parent_id' => null,
        ]);

        $this->reset(['student_id', 'student_query', 'title', 'message']);
        session()->flash('status', 'Announcement sent to '.$student->name.'!');
    }
}; ?>

<div class="space-y-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-slate-500 mb-1">Teacher Announcements</p>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Craft a focused note for your student</h1>
        </div>
        <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">Back to Dashboard</a>
    </div>
    <hr class="mb-8 border-t border-slate-200">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-2">
            <div class="bg-gradient-to-br from-blue-50 via-white to-slate-100 rounded-3xl border border-blue-200 shadow-2xl p-8">
                <form wire:submit="send" class="flex flex-col gap-5">
                    <div>
                        <x-input-label value="Student" class="block text-base font-bold text-blue-800 mb-2 tracking-wide" />
                        <div class="mt-1 flex items-center gap-2">
                            <x-text-input wire:model.live.debounce.250ms="student_query" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white" placeholder="Type student name" />
                            @if ($student_id)
                                <button type="button" wire:click="clearStudentSelection" class="px-3 py-2 text-sm rounded-md border border-slate-300">Clear</button>
                            @endif
                        </div>

                        @if (filled($student_query) && $this->studentSuggestions()->isNotEmpty() && !$student_id)
                            <div class="mt-2 border border-indigo-300 shadow-lg rounded-xl bg-gradient-to-br from-white via-indigo-50 to-indigo-100 max-h-40 overflow-auto animate-fade-in">
                                @foreach ($this->studentSuggestions() as $student)
                                    <button type="button" wire:click="selectStudent({{ $student->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 transition-all border-b border-indigo-100 last:border-none">
                                        <span class="font-semibold text-indigo-700">{{ $student->name }}</span> <span class="text-xs text-slate-500">({{ $student->class }})</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if ($student_id)
                            <p class="mt-2 text-xs text-green-700">Selected student: {{ $student_query }}</p>
                        @endif

                        <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Title" class="block text-base font-bold text-blue-800 mb-2 tracking-wide" />
                        <x-text-input wire:model="title" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white mt-1" />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Message" class="block text-base font-bold text-blue-800 mb-2 tracking-wide" />
                        <textarea wire:model="message" rows="4" class="w-full border border-blue-300 rounded-xl px-4 py-3 text-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 shadow-sm bg-white mt-1"></textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button class="px-5 py-2 text-base">Send Announcement</x-primary-button>
                    </div>
                </form>

                @if (session('status'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 2000)" x-show="show"
                         class="fixed top-24 left-1/2 transform -translate-x-1/2 bg-emerald-600 text-white font-semibold px-6 py-3 rounded-xl shadow-2xl text-lg z-50">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Student Profile Section -->
        @if ($student_id)
            @php
                $student = \App\Models\User::find($student_id);
            @endphp
            @if ($student)
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-20">
                        <h3 class="font-semibold text-slate-900 mb-4">Selected Student</h3>
                        <div class="text-center mb-4">
                            @if ($student->profile_photo_path)
                                <img src="{{ asset('storage/' . $student->profile_photo_path) }}" alt="{{ $student->name }}" class="w-16 h-16 rounded-full mx-auto object-cover mb-3">
                            @else
                                <div class="w-16 h-16 rounded-full mx-auto bg-slate-200 flex items-center justify-center mb-3">
                                    <svg class="w-8 h-8 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                </div>
                            @endif
                        </div>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Name</p>
                                <p class="font-medium text-slate-900">{{ $student->name }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Username</p>
                                <p class="font-medium text-slate-900">{{ $student->username }}</p>
                            </div>
                            <div>
                                <p class="text-slate-500 text-xs uppercase font-semibold">Email</p>
                                <p class="font-medium text-slate-900 break-all">{{ $student->email }}</p>
                            </div>
                            @if ($student->class)
                                <div>
                                    <p class="text-slate-500 text-xs uppercase font-semibold">Class</p>
                                    <p class="font-medium text-slate-900">{{ $student->class }}</p>
                                </div>
                            @endif
                            @if ($student->department)
                                <div>
                                    <p class="text-slate-500 text-xs uppercase font-semibold">Department</p>
                                    <p class="font-medium text-slate-900">{{ $student->department->name }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <div class="bg-white rounded-2xl border-2 border-indigo-300 shadow-xl p-6 animate-fade-in">
        <h2 class="font-semibold mb-3">Sent Announcements</h2>
        <ul class="space-y-3 text-sm">
            @forelse ($this->announcements() as $item)
                <li class="border-2 border-indigo-200 rounded-xl p-4 shadow-md bg-gradient-to-br from-white via-indigo-50 to-indigo-100">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-medium">{{ $item->title }} → {{ $item->student?->name }}</div>
                        <div class="flex items-center gap-2">
                            @if ($item->read_at)
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-green-100 text-green-700">Read</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded-full text-[11px] bg-red-100 text-red-700">Unread</span>
                            @endif
                            <button wire:click="deleteAnnouncement({{ $item->id }})" class="px-2 py-1 text-xs rounded-md border border-red-300 text-red-700 hover:bg-red-50">Delete</button>
                        </div>
                    </div>
                    <div class="text-slate-600 mt-1">{{ $item->message }}</div>
                    @if ($item->read_at)
                        <div class="text-xs text-slate-500 mt-2">Read at: {{ $item->read_at->format('d M Y h:i A') }}</div>
                    @endif
                </li>
            @empty
                <li class="text-slate-500">No announcements yet.</li>
            @endforelse
        </ul>
    </div>
</div>
