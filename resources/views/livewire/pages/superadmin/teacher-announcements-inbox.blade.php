<?php

use App\Models\AdminTeacherAnnouncement;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $selectedTeacherIds = [];

    protected $casts = [
        'selectedTeacherIds' => 'array',
    ];

    public function records()
    {
        return AdminTeacherAnnouncement::query()
            ->with('teacher')
            ->where('superadmin_id', auth()->id())
            ->get();
    }
};
?>

<div class="space-y-6">

    <h1 class="text-2xl font-bold">
        Teacher Announcements
    </h1>

    @php
        $teacherGroups = $this->records()
            ->filter(fn($row) => $row->teacher)
            ->groupBy(fn($row) => (string) $row->teacher->id);
    @endphp

    <div class="bg-white rounded-xl shadow border overflow-hidden">

        <table class="w-full border-collapse">

            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left w-16">Select</th>
                    <th class="px-4 py-3 text-left">Teacher</th>
                    <th class="px-4 py-3 text-left">Total</th>
                    <th class="px-6 py-4 text-left">Read</th>
                </tr>
            </thead>

            <tbody class="divide-y">

                @foreach ($teacherGroups as $teacherId => $announcements)

                    <tr class="hover:bg-indigo-50">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                   wire:model.live="selectedTeacherIds"
                                   value="{{ $teacherId }}"
                                   class="h-5 w-5 accent-indigo-600">
                        </td>

                        <td class="px-4 py-3 font-semibold text-indigo-700">
                            {{ $announcements->first()->teacher?->name ?? 'Unknown' }}
                            @if($announcements->first()->teacher?->department)
                                / <span class="text-xs text-slate-500">{{ $announcements->first()->teacher->department->name }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $announcements->count() }}
                        </td>

                        <td class="px-4 py-3 text-slate-600">
                            {{ $announcements->whereNotNull('read_at')->count() }}
                        </td>
                    </tr>

                    {{-- ✅ FIXED CONDITION --}}
                    @if(in_array((string)$teacherId, $selectedTeacherIds, true))

                        <tr>
                            <td colspan="3" class="bg-indigo-50 p-6">

                                <div class="bg-white border rounded-xl shadow">

                                    <table class="w-full text-sm">

                                        <thead class="bg-indigo-100">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Title</th>
                                                <th class="px-4 py-3 text-left">Date</th>
                                                <th class="px-4 py-3 text-left">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y">

                                            @foreach ($announcements->sortByDesc('created_at') as $item)
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <span class="text-indigo-800 font-semibold">
                                                            {{ $item->title }}
                                                        </span>
                                                        <span class="text-xs text-slate-500 font-normal">
                                                            —
                                                            {{ $item->teacher?->name }}
                                                            @if($item->teacher?->department)
                                                                / {{ $item->teacher->department->name }}
                                                            @endif
                                                        </span>
                                                    </td>

                                                    <td class="px-4 py-3 text-slate-600">
                                                        {{ $item->created_at->format('d M Y h:i A') }}
                                                    </td>

                                                    <td class="px-4 py-3">
                                                        @if ($item->read_at)
                                                            <span class="text-green-600 font-medium">
                                                                Read
                                                            </span>
                                                        @else
                                                            <span class="text-red-600 font-medium">
                                                                Unread
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>

                            </td>
                        </tr>

                    @endif

                @endforeach

            </tbody>

        </table>

    </div>

</div>