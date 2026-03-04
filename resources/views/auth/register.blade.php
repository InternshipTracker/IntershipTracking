@extends('layouts.app')

@section('title', 'Register - Internship Tracking System')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 85vh;">
        <div class="col-md-6 col-lg-5">

            {{-- Logo / Header --}}
            <div class="text-center mb-4">
                <div style="width: 60px; height: 60px; background: var(--primary); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="bi bi-mortarboard-fill text-white" style="font-size: 1.75rem;"></i>
                </div>
                <h4 class="text-white fw-bold mb-1">Student Registration</h4>
                <p class="text-white-50 mb-0" style="font-size: 0.875rem;">Create your account to get started</p>
            </div>

            {{-- Register Card --}}
            <div class="card border-0 shadow-lg" style="border-radius: 16px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08);">
                <div class="card-body p-4">

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 px-3" style="font-size: 0.8125rem; border-radius: 10px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                            <ul class="mb-0 list-unstyled">
                                @foreach ($errors->all() as $error)
                                    <li><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        {{-- Personal Info Section --}}
                        <p class="text-white-50 mb-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bi bi-person me-1"></i>Personal Information
                        </p>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="name" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Phone <span class="text-white-50">(optional)</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}"
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="1234567890">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                placeholder="you@example.com">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="password" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="Min 6 characters">
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                    placeholder="Repeat password">
                            </div>
                        </div>

                        {{-- Academic Info Section --}}
                        <hr style="border-color: rgba(255,255,255,0.08); margin: 1.25rem 0;">
                        <p class="text-white-50 mb-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="bi bi-mortarboard me-1"></i>Academic Information
                        </p>

                        <div class="mb-3">
                            <label for="department_id" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Department</label>
                            <select class="form-select" id="department_id" name="department_id" required
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;">
                                <option value="" disabled selected style="color: #6b7280;">Select your department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" style="color: #000;" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="registration_number" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Registration Number</label>
                            <input type="text" class="form-control" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" required
                                style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;"
                                placeholder="e.g. CS2024001">
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="course" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Course</label>
                                <select class="form-select" id="course" name="course" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;">
                                    <option value="" disabled selected style="color: #6b7280;">Select course</option>
                                    @foreach ($courses as $course)
                                        <option value="{{ $course->code }}" style="color: #000;" {{ old('course') == $course->code ? 'selected' : '' }}>
                                            {{ $course->code }} - {{ $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="class" class="form-label text-white-50" style="font-size: 0.8125rem; font-weight: 500;">Class / Year</label>
                                <select class="form-select" id="class" name="class" required
                                    style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 10px; padding: 0.65rem 0.875rem; font-size: 0.875rem;">
                                    <option value="" disabled selected style="color: #6b7280;">Select year</option>
                                    <option value="FY" style="color: #000;" {{ old('class') == 'FY' ? 'selected' : '' }}>FY - First Year</option>
                                    <option value="SY" style="color: #000;" {{ old('class') == 'SY' ? 'selected' : '' }}>SY - Second Year</option>
                                    <option value="TY" style="color: #000;" {{ old('class') == 'TY' ? 'selected' : '' }}>TY - Third Year</option>
                                    <option value="Fourth Year" style="color: #000;" {{ old('class') == 'Fourth Year' ? 'selected' : '' }}>Fourth Year</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn w-100 text-white fw-semibold"
                            style="background: var(--primary); border: none; border-radius: 10px; padding: 0.7rem; font-size: 0.875rem; transition: all 0.2s;"
                            onmouseover="this.style.background='var(--primary-hover)'"
                            onmouseout="this.style.background='var(--primary)'">
                            <i class="bi bi-person-plus me-1"></i>Create Account
                        </button>
                    </form>
                </div>
            </div>

            {{-- Login Link --}}
            <div class="text-center mt-3">
                <p class="text-white-50 mb-0" style="font-size: 0.8125rem;">
                    Already have an account? <a href="{{ route('login') }}" class="text-decoration-none" style="color: #818cf8; font-weight: 500;">Sign in</a>
                </p>
            </div>

        </div>
    </div>
</div>
@endsection
