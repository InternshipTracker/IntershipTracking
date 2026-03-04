<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?string $approvedStudentName = null;
    public bool $showApprovalModal = false;

    private function assignedClasses(): array
    {
        return auth()->user()
            ->teacherClasses()
            ->pluck('class_name')
            ->map(fn (string $className) => strtoupper($className))
            ->all();
    }

    public function pendingRequests()
    {
        $classes = $this->assignedClasses();

        if (empty($classes)) {
            return collect();
        }

        return User::query()
            ->with('department')
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('class', $classes)
            ->where('approval_status', 'pending')
            ->latest()
            ->get();
    }

    public function approveStudent(int $studentId): void
    {
        $classes = $this->assignedClasses();

        $student = User::query()
            ->where('id', $studentId)
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('class', $classes)
            ->where('approval_status', 'pending')
            ->firstOrFail();

        $student->update([
            'is_approved' => true,
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        $this->approvedStudentName = $student->name;
        $this->showApprovalModal = true;
    }

    public function closeModal(): void
    {
        $this->showApprovalModal = false;
        $this->approvedStudentName = null;
    }

    public function rejectStudent(int $studentId): void
    {
        $classes = $this->assignedClasses();

        $student = User::query()
            ->where('id', $studentId)
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->whereIn('class', $classes)
            ->where('approval_status', 'pending')
            ->firstOrFail();

        $student->update([
            'is_approved' => false,
            'approval_status' => 'rejected',
        ]);

        session()->flash('status', 'Student request rejected.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('teacher.dashboard') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-700">← Back to Dashboard</a>
                    <h1 class="text-2xl font-semibold mt-1">Pending Student Requests</h1>
                </div>
            </div>
            <p class="text-sm text-slate-500 mt-1">Requests are filtered by your assigned classes and department.</p>
        </div>
        <span class="inline-flex items-center justify-center min-w-7 h-7 px-2 rounded-full bg-red-500 text-white text-xs font-semibold">
            {{ $this->pendingRequests()->count() }}
        </span>
    </div>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm">{{ session('status') }}</div>
    @endif

    <!-- Approval Success Modal - Placed at top for proper rendering -->
    @if ($showApprovalModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" role="alertdialog">
            <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center">
                <div class="flex justify-center mb-4">
                    <div class="bg-green-100 rounded-full p-4">
                        <svg class="w-8 h-8 text-green-600 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-3">Request Approved!</h2>
                <p class="text-slate-600 mb-1">You have approved</p>
                <p class="text-xl font-bold text-indigo-600 mb-1">{{ $approvedStudentName }}</p>
                <p class="text-slate-600 mb-6">as <span class="font-semibold text-indigo-600">{{ auth()->user()->name }}</span></p>
                <button wire:click="closeModal" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition">
                    Close
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-300 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gradient-to-r from-slate-100 to-slate-200 text-slate-800 border-b border-slate-300">
                    <tr>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Student Name</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Username</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Department</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Class</th>
                        <th class="p-4 text-left font-bold border-r border-slate-200">Registered At</th>
                        <th class="p-4 text-center font-bold border-r border-slate-200">Status</th>
                        <th class="p-4 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pendingRequests() as $student)
                        <tr class="align-middle hover:bg-blue-50 transition-colors border-b border-slate-200 {{ $loop->even ? 'bg-slate-50' : 'bg-white' }}">
                            <td class="p-4 border-r border-slate-100">
                                <div class="font-semibold text-slate-900">{{ $student->name }}</div>
                                <div class="text-xs text-slate-500">{{ $student->email }}</div>
                            </td>
                            <td class="p-4 border-r border-slate-100 text-slate-700">{{ $student->username }}</td>
                            <td class="p-4 border-r border-slate-100 text-slate-700">{{ $student->department?->name ?? '-' }}</td>
                            <td class="p-4 border-r border-slate-100 text-slate-700">{{ strtoupper((string) $student->class) }}</td>
                            <td class="p-4 border-r border-slate-100 text-slate-600">{{ $student->created_at->format('d M Y h:i A') }}</td>
                            <td class="p-4 border-r border-slate-100 text-center">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200 shadow-sm">Pending</span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button wire:click="approveStudent({{ $student->id }})" wire:loading.attr="disabled" class="px-4 py-1.5 rounded-lg border border-green-400 text-green-800 bg-green-50 hover:bg-green-100 font-medium shadow-sm transition disabled:opacity-50">Approve</button>
                                    <button wire:click="rejectStudent({{ $student->id }})" wire:loading.attr="disabled" class="px-4 py-1.5 rounded-lg border border-red-400 text-red-800 bg-red-50 hover:bg-red-100 font-medium shadow-sm transition disabled:opacity-50">Reject</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-500">No pending requests right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
