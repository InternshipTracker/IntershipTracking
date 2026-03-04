<?php

use App\Models\Department;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function stats(): array
    {
        return [
            'departments' => Department::count(),
            'teachers' => User::where('role', 'teacher')->count(),
        ];
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold mb-5">Super Admin Dashboard</h1>

    @if (session('status'))
        <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 text-sm mb-6">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach ($this->stats() as $label => $value)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <p class="text-sm text-slate-500 capitalize">{{ $label }}</p>
                <p class="text-3xl font-bold mt-2">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
