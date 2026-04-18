@extends('layouts.app')

@section('title', 'Login - Internship Tracking System')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center align-items-center" style="min-height: 85vh;">
        <!-- Left Side: College Info & Logo -->
        <div class="col-md-6 d-none d-md-flex flex-column justify-content-center align-items-start" style="background: #474449; border-radius: 2.5rem 0 0 2.5rem; min-height: 70vh; padding: 3rem 2.5rem;">
            <img src="/images/sangamner_clg.jpeg" alt="Sangamner College Logo" style="width: 70px; height: 70px; border-radius: 16px; margin-bottom: 1.5rem; background: #fff; object-fit: cover; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <h3 class="text-white fw-bold mb-1" style="font-size: 2rem;">Internship Tracking</h3>
            <h5 class="text-white-50 mb-4" style="font-size: 1.1rem;">Sangamner College</h5>
            <div class="mt-auto">
                <h4 class="text-white fw-bold mb-2">Internships made simple</h4>
                <p class="text-white-50 mb-3" style="max-width: 350px;">Register, get approved by your department, and track your internship journey from one place.</p>
                <span class="badge bg-primary" style="font-size: 1rem; border-radius: 8px;">Campus verified</span>
            </div>
        </div>
        <!-- Right Side: Login Form -->
        <div class="col-md-5 col-lg-4">
            {{-- Logo / Header --}}
            <div class="text-center mb-4">
                <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="bi bi-mortarboard-fill text-white" style="font-size: 1.75rem;"></i>
                </div>
                <h4 class="text-white fw-bold mb-1">Welcome Back</h4>
                <p class="text-white-50 mb-0" style="font-size: 0.875rem;">Sign in to Internship Tracking System</p>
            </div>

            {{-- Login Card --}}
            <div class="card border-0 shadow-lg" style="border-radius: 16px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08);">
                <div class="card-body p-4">

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 px-3" style="font-size: 0.8125rem; border-radius: 10px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                placeholder="you@example.com">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="password" name="password" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 4.2rem 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="••••••••">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-decoration-none" style="font-size: 0.8rem; color: #c7d2fe;" onclick="const i=this.parentElement.querySelector('input[type=password],input[type=text]'); const s=i.type==='password'; i.type=s?'text':'password'; this.textContent=s?'Hide':'Show';">Show</button>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 text-white fw-semibold"
                            style="background: var(--primary); border: none; border-radius: 10px; padding: 0.7rem; font-size: 0.875rem; transition: all 0.2s;"
                            onmouseover="this.style.background='var(--primary-hover)'"
                            onmouseout="this.style.background='var(--primary)'">
                            <i class="bi bi-arrow-right-circle me-1"></i>Sign In
                        </button>
                    </form>
                </div>
            </div>

            {{-- Register Link --}}
            <div class="text-center mt-3">
                <p class="text-white-50 mb-0" style="font-size: 0.8125rem;">
                    New student? <a href="{{ route('register') }}" class="text-decoration-none" style="color: #818cf8; font-weight: 500;">Create an account</a>
                </p>
            </div>

            {{-- Role Info --}}
            <div class="mt-4 p-3" style="background: rgba(255,255,255,0.04); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                <p class="text-white-50 mb-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Other login portals:</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('coordinator.login') }}" class="badge text-decoration-none" style="background: rgba(16,185,129,0.2); color: #6ee7b7; font-weight: 500; padding: 0.35rem 0.75rem; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='rgba(16,185,129,0.35)'" onmouseout="this.style.background='rgba(16,185,129,0.2)'">
                        <i class="bi bi-people-fill me-1"></i>Coordinator Login
                    </a>
                    <a href="{{ route('dept_admin.login') }}" class="badge text-decoration-none" style="background: rgba(245,158,11,0.2); color: #fcd34d; font-weight: 500; padding: 0.35rem 0.75rem; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='rgba(245,158,11,0.35)'" onmouseout="this.style.background='rgba(245,158,11,0.2)'">
                        <i class="bi bi-building me-1"></i>Dept Admin Login
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
