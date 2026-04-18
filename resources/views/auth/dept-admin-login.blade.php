@extends('layouts.app')

@section('title', 'Department Admin Login - Internship Tracking System')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 85vh;">
        <div class="col-md-5 col-lg-4">

            {{-- Logo / Header --}}
            <div class="text-center mb-4">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #d97706, #f59e0b); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="bi bi-building text-white" style="font-size: 1.75rem;"></i>
                </div>
                <h4 class="text-white fw-bold mb-1">Department Admin Login</h4>
                <p class="text-white-50 mb-0" style="font-size: 0.875rem;">Sign in to manage your department</p>
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

                    <form method="POST" action="{{ route('dept_admin.login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                placeholder="admin@department.com">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="password" name="password" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 4.2rem 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="••••••••">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-decoration-none" style="font-size: 0.8rem; color: #fde68a;" onclick="const i=this.parentElement.querySelector('input[type=password],input[type=text]'); const s=i.type==='password'; i.type=s?'text':'password'; this.textContent=s?'Hide':'Show';">Show</button>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 text-white fw-semibold"
                            style="background: #d97706; border: none; border-radius: 10px; padding: 0.7rem; font-size: 0.875rem; transition: all 0.2s;"
                            onmouseover="this.style.background='#b45309'"
                            onmouseout="this.style.background='#d97706'">
                            <i class="bi bi-arrow-right-circle me-1"></i>Sign In as Dept Admin
                        </button>
                    </form>
                </div>
            </div>

            {{-- Info Note --}}
            <div class="mt-4 p-3" style="background: rgba(255,255,255,0.04); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                <p class="text-white-50 mb-0" style="font-size: 0.8125rem;">
                    <i class="bi bi-info-circle me-1" style="color: #fcd34d;"></i>
                    Department admin accounts are created by the <strong class="text-white">Super Admin</strong>. Contact them if you don't have an account.
                </p>
            </div>

            {{-- Back to main login --}}
            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-decoration-none text-white-50" style="font-size: 0.8125rem;">
                    <i class="bi bi-arrow-left me-1"></i>Back to main login
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
