<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('superadmin/login', 'pages.auth.superadmin-login')
        ->name('superadmin.login');

    Volt::route('teacher/login', 'pages.auth.teacher-login')
        ->name('teacher.login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Teacher Routes
    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Volt::route('dashboard', 'pages.teacher.dashboard')
            ->name('dashboard');

        Volt::route('students', 'pages.teacher.students')
            ->name('students');

        Volt::route('student/{student}/progress', 'pages.teacher.student-progress')
            ->name('student.progress');

        Volt::route('batch/{batch}/details', 'pages.teacher.batch-details')
            ->name('batch.details');

        Volt::route('announcements', 'pages.teacher.announcements')
            ->name('announcements');

        Volt::route('internships', 'pages.teacher.internships')
            ->name('internships');
    });

    // Student Routes
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Volt::route('dashboard', 'pages.student.dashboard')
            ->name('dashboard');
    });
});
