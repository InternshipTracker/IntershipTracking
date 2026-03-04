@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg" style="border-radius: 16px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08);">
                <div class="card-body p-5 text-center">
                    <div style="width: 80px; height: 80px; background: rgba(239,68,68,0.15); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                        <i class="bi bi-shield-fill" style="font-size: 2.25rem; color: #fca5a5;"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-2">Hello, {{ auth()->user()->name }}!</h3>
                    <p class="text-white-50 mb-0" style="font-size: 1rem;">
                        Welcome to the <strong class="text-white">Super Admin</strong> dashboard.
                    </p>
                    <p class="text-white-50 mt-2" style="font-size: 0.875rem;">
                        You have full control over the system. Manage departments, users, and monitor all internship activities.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
