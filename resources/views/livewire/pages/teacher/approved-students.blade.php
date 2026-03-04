<?php

use App\Models\Internship;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function approvedStudents()
    {
        // Show all approved students in teacher's assigned classes (for this department),
        // and display which teacher approved them
        $assignedClasses = auth()->user()->teacherClasses()->pluck('class_name')->map(fn($c) => strtoupper($c))->all();
        return \App\Models\User::query()
            ->with(['department', 'approvedBy'])
            ->where('role', 'student')
            ->where('department_id', auth()->user()->department_id)
            ->where('approval_status', 'approved')
            ->whereIn('class', $assignedClasses)
            ->latest()
            ->get();
    }
};
?>


<div class="space-y-8">
    <h1 class="text-3xl font-bold mb-6 tracking-tight text-slate-800 flex items-center gap-3">
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 7v-7"/></svg>
        Approved Students
    </h1>
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-base">
            <thead class="bg-gradient-to-r from-blue-50 to-slate-100 text-slate-700">
                <tr>
                    <th class="p-4 text-left font-extrabold uppercase tracking-wider text-blue-700 drop-shadow-sm text-base">Student Name</th>
                    <th class="p-4 text-left font-extrabold uppercase tracking-wider text-indigo-700 drop-shadow-sm text-base">Class</th>
                    <th class="p-4 text-left font-extrabold uppercase tracking-wider text-green-700 drop-shadow-sm text-base">Department</th>
                    <th class="p-4 text-left font-extrabold uppercase tracking-wider text-pink-700 drop-shadow-sm text-base">Approved By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->approvedStudents() as $student)
                    <tr class="border-t border-slate-200 hover:bg-blue-50 transition">
                        <td class="p-4 flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-700 font-bold text-lg shadow-sm">
                                {{ strtoupper(substr($student->name,0,1)) }}
                            </span>
                            <span class="font-medium">{{ $student->name }}</span>
                        </td>
                        <td class="p-4">{{ $student->class }}</td>
                        <td class="p-4">{{ $student->department?->name }}</td>
                        <td class="p-4">
                            @if($student->approvedBy)
                                <span class="font-bold text-pink-700 bg-pink-50 px-3 py-1 rounded-full shadow-sm border border-pink-200 inline-block tracking-wide">{{ $student->approvedBy->name }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-slate-400 text-lg">No approved students yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
