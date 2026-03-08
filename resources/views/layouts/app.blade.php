<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Internship Tracking System') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* Sidebar + shell layout tweaks (collapsible) */
        .sidebar {
            width: 16rem; /* slightly slimmer */
            transition: width 0.25s ease;
        }

        .app-shell {
            padding-left: 0;
            transition: padding-left 0.25s ease;
        }

        @media (min-width: 768px) {
            .app-shell {
                padding-left: 16rem;
            }
            body.sidebar-collapsed .sidebar {
                width: 4.5rem;
            }
            body.sidebar-collapsed .app-shell {
                padding-left: 4.5rem;
            }
        }

        body.sidebar-collapsed .sidebar .item-label,
        body.sidebar-collapsed .sidebar .section-title,
        body.sidebar-collapsed .sidebar .brand-text {
            display: none;
        }

        .sidebar nav a {
            position: relative;
        }

        body.sidebar-collapsed .sidebar nav a {
            justify-content: center;
        }

        body.sidebar-collapsed .sidebar nav a .nav-icon {
            margin-right: 0;
        }

        .sidebar .item-badge {
            position: absolute;
            right: 12px;
        }

        /* keep header toggle button aligned */
        #sidebarToggle {
            transition: transform 0.2s ease, background 0.2s ease;
        }

        #sidebarToggle:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body data-theme-user="{{ auth()->id() }}" class="theme-enabled min-h-screen bg-slate-100 text-slate-900 antialiased">
    @php
        $user = auth()->user();
        $role = $user?->role;
        $dashboardTitle = match ($role) {
            'superadmin' => 'Admin Dashboard',
            'teacher' => 'Teacher Dashboard',
            default => 'Student Dashboard',
        };

        $hasApprovedInternship = $role === 'student'
            ? \App\Models\Internship::query()
                ->where('student_id', $user?->id)
                ->where('status', 'approved')
                ->exists()
            : false;

        $teacherAssignedClasses = $role === 'teacher'
            ? $user?->teacherClasses()
                ->pluck('class_name')
                ->map(fn (string $className) => strtoupper($className))
                ->all()
            : [];

        $teacherPendingStudentCount = $role === 'teacher'
            ? (empty($teacherAssignedClasses)
                ? 0
                : \App\Models\User::query()
                    ->where('role', 'student')
                    ->where('department_id', $user?->department_id)
                    ->whereIn('class', $teacherAssignedClasses)
                    ->where('approval_status', 'pending')
                    ->count())
            : 0;

        $teacherPendingInternshipCount = $role === 'teacher'
            ? \App\Models\Internship::query()
                ->where('teacher_id', $user?->id)
                ->where('status', 'pending')
                ->count()
            : 0;

        $endingBatchCount = $role === 'teacher'
            ? \App\Models\Batch::query()
                ->where('teacher_id', $user?->id)
                ->whereHas('internships', function ($q) use ($user) {
                    $q->where('teacher_id', $user?->id)
                        ->where('status', 'approved')
                        ->whereDate('end_date', '<', now()->toDateString());
                })
                ->count()
            : 0;

        $menu = match ($role) {
            'superadmin' => [
                ['label' => 'Dashboard', 'route' => 'superadmin.dashboard', 'icon' => '📊'],
                ['label' => 'Departments', 'route' => 'superadmin.departments', 'icon' => '🏫'],
                ['label' => 'Teachers', 'route' => 'superadmin.teachers', 'icon' => '👨‍🏫'],
                ['label' => 'Teacher Notices', 'route' => 'superadmin.teacher-announcements', 'icon' => '📢'],
            ],
            'teacher' => [
                ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'icon' => '📊'],
                ['label' => 'Batches', 'route' => 'teacher.students', 'icon' => '🎓'],
                ['label' => 'Internships', 'route' => 'teacher.internships', 'icon' => '💼', 'badge' => $teacherPendingInternshipCount],
                ['label' => 'Pending Student Requests', 'route' => 'teacher.pending-students', 'icon' => '🧾', 'badge' => $teacherPendingStudentCount],
                ['label' => 'Announcements', 'route' => 'teacher.announcements', 'icon' => '📢'],
                ['label' => 'Approved Students', 'route' => 'teacher.approved-students', 'icon' => '✅'],
                ['label' => 'Ending Batches', 'route' => 'teacher.ending-batches', 'icon' => '⏳', 'badge' => $endingBatchCount],
            ],
            default => [
                ['label' => 'Dashboard', 'route' => 'student.dashboard', 'icon' => '📊'],
                ...($hasApprovedInternship ? [] : [['label' => 'Apply Internship', 'route' => 'student.internship.apply', 'icon' => '📝']]),
                ['label' => 'Daily Diary', 'route' => 'student.diary', 'icon' => '📘'],
                ['label' => 'Announcements', 'route' => 'student.announcements', 'icon' => '📢'],
            ],
        };

        $notificationRoute = match ($role) {
            'teacher' => route('teacher.admin-announcements'),
            'student' => route('student.announcements'),
            'superadmin' => route('superadmin.teacher-announcements-inbox'),
            default => null,
        };

        $notificationCount = match ($role) {
            'teacher' => \App\Models\AdminTeacherAnnouncement::query()
                ->where('teacher_id', $user?->id)
                ->whereNull('read_at')
                ->count(),
            'student' => \App\Models\Announcement::query()
                ->where('student_id', $user?->id)
                ->whereNull('read_at')
                ->count(),
            'superadmin' => \App\Models\AdminTeacherAnnouncement::query()
                ->where('superadmin_id', $user?->id)
                ->whereNull('read_at')
                ->count(),
            default => 0,
        };

        $normalizedProfilePhotoPath = $user?->profile_photo_path
            ? ltrim(preg_replace('#^(storage/|public/)#', '', $user->profile_photo_path), '/')
            : null;

        $profilePhotoExists = $normalizedProfilePhotoPath
            ? Storage::disk('public')->exists($normalizedProfilePhotoPath)
            : false;

        $profilePhotoUrl = $profilePhotoExists
            ? Storage::url($normalizedProfilePhotoPath)
            : ($user?->role === 'teacher'
                ? asset('/images/default-teacher.png')
                : asset('/images/default-user.png'));

        // Build two-letter initials (first + last) for users without a profile photo
        $initials = '';
        if ($user?->name) {
            $nameParts = preg_split('/\s+/', trim($user->name));
            if (!empty($nameParts)) {
                $initials .= strtoupper(mb_substr($nameParts[0], 0, 1));
                if (count($nameParts) > 1) {
                    $initials .= strtoupper(mb_substr(end($nameParts), 0, 1));
                }
            }
        }
        if ($initials === '' && $user?->username) {
            $initials = strtoupper(substr($user->username, 0, 2));
        }
    @endphp

    @if ($user)
    <div class="min-h-screen">
        <aside class="sidebar hidden md:block fixed inset-y-0 left-0 z-40 text-slate-100">

            <div class="h-16 px-5 flex items-center border-b border-slate-800 gap-3">
                <img src="/images/clg%20logo.jpg" alt="College Logo" class="w-10 h-10 rounded-lg object-cover shadow bg-white" />
                <div class="leading-tight brand-text">
                    <p class="text-sm font-semibold text-white">Internship Tracking</p>
                    <p class="text-[11px] text-slate-300">System</p>
                </div>
            </div>

            <div class="px-4 py-3 border-b border-slate-800">
                <p class="text-sm font-semibold text-slate-200 section-title">{{ $dashboardTitle }}</p>
            </div>

            <nav class="p-4 space-y-1">
                @foreach ($menu as $item)
                    @php
                        $active = request()->routeIs($item['route']);
                        $href = route($item['route'], $item['params'] ?? []);
                        if (isset($item['anchor'])) {
                            $href .= $item['anchor'];
                        }
                        $badgeVal = $item['badge'] ?? null;
                        $badgeIsPositive = is_numeric($badgeVal) && $badgeVal > 0;
                    @endphp
                    <a href="{{ $href }}" wire:navigate class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="mr-2 nav-icon">{{ $item['icon'] }}</span>
                        <span class="item-label">{{ $item['label'] }}</span>
                        @isset($item['badge'])
                            <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-[10px] font-semibold item-badge {{ $badgeIsPositive ? 'bg-red-500 text-white' : 'bg-slate-600 text-slate-100' }}">
                                {{ $badgeVal }}
                            </span>
                        @endisset
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="app-shell md:pl-72 min-h-screen">
            <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" type="button" class="inline-flex items-center justify-center h-10 w-10 rounded-lg" aria-label="Toggle sidebar" aria-expanded="true">
                        <svg id="sidebarToggleIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div>
                        <p class="text-sm text-slate-500">{{ $user?->name }}</p>
                        <p class="text-base font-semibold">{{ $dashboardTitle }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    @include('partials.theme-switcher')
                    @if ($notificationRoute)
                        <a href="{{ $notificationRoute }}" wire:navigate class="relative inline-flex items-center justify-center h-10 w-10 rounded-full border border-slate-300 hover:bg-slate-50" title="Notifications">
                            <svg class="h-5 w-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($notificationCount > 0)
                                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold">{{ $notificationCount }}</span>
                            @endif
                        </a>
                    @endif
                    <button onclick="document.getElementById('profileModal').classList.toggle('hidden')" class="inline-flex items-center justify-center relative" title="Profile">
                        @if ($profilePhotoExists)
                            <img src="{{ $profilePhotoUrl }}" alt="Profile" class="h-10 w-10 rounded-full object-cover border border-slate-300 cursor-pointer hover:border-slate-400" />
                        @else
                            <div class="h-10 w-10 rounded-full flex items-center justify-center bg-indigo-600 text-white text-xl font-bold border border-slate-300 cursor-pointer hover:border-slate-400">
                                {{ $initials }}
                            </div>
                        @endif
                    </button>
                    <div class="hidden sm:block">
                        <p class="text-sm font-semibold">{{ $user?->name }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="px-3 py-2 rounded-lg border border-slate-300 text-sm font-medium hover:bg-slate-50">Logout</button>
                    </form>
                </div>
            </header>

            <div class="md:hidden bg-slate-900 px-3 py-2 flex gap-2 overflow-x-auto">
                @foreach ($menu as $item)
                    @php
                        $active = request()->routeIs($item['route']);
                    @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate class="whitespace-nowrap px-3 py-1.5 rounded-md text-xs font-medium {{ $active ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-200' }}">
                        {{ $item['icon'] }} {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-semibold">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <main class="p-4 md:p-6">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Profile</h2>
                    <button onclick="document.getElementById('profileModal').classList.add('hidden')" class="text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="text-center mb-4">
                    @if ($profilePhotoExists)
                        <img src="{{ $profilePhotoUrl }}" alt="Profile" class="w-16 h-16 rounded-full mx-auto object-cover border border-slate-200 mb-3">
                    @else
                        <div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center bg-indigo-600 text-white text-2xl font-bold border border-slate-200 mb-3">
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <div class="space-y-3 text-sm border-t border-slate-200 pt-4">
                    <div>
                        <p class="text-slate-500 text-xs uppercase font-semibold">Name</p>
                        <p class="font-medium text-slate-900 mt-1">{{ $user?->name }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase font-semibold">Email</p>
                        <p class="font-medium text-slate-900 mt-1 break-all">{{ $user?->email }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-xs uppercase font-semibold">Role</p>
                        <p class="font-medium text-slate-900 mt-1 capitalize">{{ $user?->role ?? 'User' }}</p>
                    </div>
                    @if ($user?->department)
                        <div>
                            <p class="text-slate-500 text-xs uppercase font-semibold">Department</p>
                            <p class="font-medium text-slate-900 mt-1">{{ $user->department->name }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4 border-t border-slate-200 pt-4 flex gap-2">
                    <a href="{{ route('profile') }}" onclick="document.getElementById('profileModal').classList.add('hidden')" wire:navigate class="flex-1 text-center px-3 py-2 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50 text-sm font-medium">
                        Full Profile
                    </a>
                    <button onclick="document.getElementById('profileModal').classList.add('hidden')" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-xs w-full">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Confirm Logout</h2>
                <p class="mb-6 text-slate-700">Are you sure you want to logout?</p>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 text-sm font-medium">Confirm</button>
                    </form>
                    <button type="button" onclick="document.getElementById('logoutModal').classList.add('hidden')" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @else
        <main class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
            <div class="w-full max-w-4xl">
                @hasSection('content')
                    @yield('content')
                @elseif (isset($slot))
                    {{ $slot }}
                @endif
            </div>
        </main>
    @endif
    @livewireScripts

    <script>
        (() => {
            const body = document.body;
            const toggle = document.getElementById('sidebarToggle');
            const icon = document.getElementById('sidebarToggleIcon');

            if (!toggle || !icon) return;

            const setIcon = (collapsed) => {
                // collapsed -> arrow pointing right (expand), expanded -> left (collapse)
                icon.innerHTML = collapsed
                    ? '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M9 5l7 7-7 7\" />'
                    : '<path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19l-7-7 7-7\" />';
            };

            const applyState = (collapsed) => {
                body.classList.toggle('sidebar-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', (!collapsed).toString());
                setIcon(collapsed);
            };

            const initial = localStorage.getItem('sidebarCollapsed') === '1';
            applyState(initial);

            toggle.addEventListener('click', () => {
                const collapsed = !body.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
            });
        })();
    </script>
</body>
</html>
