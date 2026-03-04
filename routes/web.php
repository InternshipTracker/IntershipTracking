<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('home');
    });

    Route::get('superadmin', function () {
        return redirect()->route('superadmin.login');
    })->name('superadmin.entry');

    Route::get('superadin', function () {
        return redirect()->route('superadmin.login');
    })->name('superadmin.entry.alias');

    Volt::route('student/auth', 'pages.auth.student-auth')->name('student.auth');
    Volt::route('register', 'pages.auth.student-auth')->name('register');
    Volt::route('login', 'pages.auth.student-auth')->name('login');
    Volt::route('superadmin/login', 'pages.auth.superadmin-login')->name('superadmin.login');
    Volt::route('teacher/login', 'pages.auth.teacher-login')->name('teacher.login');
    Volt::route('forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect()->route('login');
    })->name('logout');

    Volt::route('verify-email', 'pages.auth.verify-email')->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')->name('password.confirm');
    Volt::route('profile', 'pages.profile')->name('profile');

    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'superadmin'       => redirect()->route('superadmin.dashboard'),
            'teacher'          => redirect()->route('teacher.dashboard'),
            'student'          => redirect()->route('student.dashboard'),
            default            => abort(403),
        };
    })->name('dashboard');

    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Volt::route('dashboard', 'pages.superadmin.dashboard')->name('dashboard');
        Volt::route('departments', 'pages.superadmin.departments')->name('departments');
        Volt::route('teachers', 'pages.superadmin.teachers')->name('teachers');
        Volt::route('teacher-announcements', 'pages.superadmin.teacher-announcements')->name('teacher-announcements');
        Volt::route('teacher-announcements-inbox', 'pages.superadmin.teacher-announcements-inbox')->name('teacher-announcements-inbox');
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Volt::route('dashboard', 'pages.teacher.dashboard')->name('dashboard');
        Volt::route('students', 'pages.teacher.students')->name('students');
        Volt::route('batch/{batch}/details', 'pages.teacher.batch-details')->name('batch.details');
        Volt::route('student/{student}/progress', 'pages.teacher.student-progress')->name('student.progress');
        Volt::route('internships', 'pages.teacher.internships')->name('internships');
        Volt::route('pending-students', 'pages.teacher.pending-students')->name('pending-students');
        Volt::route('admin-announcements', 'pages.teacher.admin-announcements')->name('admin-announcements');
        Volt::route('announcements', 'pages.teacher.announcements')->name('announcements');
        Volt::route('approved-students', 'pages.teacher.approved-students')->name('approved-students');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Volt::route('dashboard', 'pages.student.dashboard')->name('dashboard');
        Volt::route('internship-apply', 'pages.student.internship-apply')->name('internship.apply');
        Volt::route('diary', 'pages.student.diary')->name('diary');
        Volt::route('announcements', 'pages.student.announcements')->name('announcements');
    });
});

// Registration success page must be reachable right after signup (before login)
Route::view('/student/registration-success', 'livewire.pages.student.registration-success')->name('student.registration-success');
