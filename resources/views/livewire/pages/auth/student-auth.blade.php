<?php

use App\Models\Department;
use App\Models\User;
use App\Livewire\Forms\LoginForm;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.blank')] class extends Component
{
    public LoginForm $loginForm;
    
    // Registration fields
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $class = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $department_id = null;

    public function departments()
    {
        return Department::query()->orderBy('name')->get();
    }

    public function login(): void
    {
        $this->loginForm->validate();
        $this->loginForm->authenticate();
        
        Session::regenerate();
        $this->redirectIntended(default: '/dashboard');
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'department_id' => ['required', 'exists:departments,id'],
            'class' => ['required', 'in:FYBCS,SYBCS,TYBCS'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'student';
        $validated['is_approved'] = false;
        $validated['approval_status'] = 'pending';

        event(new Registered($user = User::create($validated)));

        $this->reset(['name', 'username', 'email', 'class', 'password', 'password_confirmation', 'department_id']);
        $this->redirect(route('student.registration-success'));
    }
}; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Internship Tracking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        :root {
            --primary: #2652f1;
            --primary-hover: #820404;
            --secondary: #ec4899;
            --bg-color: #f4d9c3;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: radial-gradient(circle at 10% 20%, #ffffff 0%, #fdebd3 35%, #f4d9c3 60%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: var(--text-dark);
        }

        .auth-wrapper {
            background: var(--bg-color);
            width: 100%;
            max-width: 1200px;
            min-height: 700px;
            border-radius: 24px;
            box-shadow: none;
            display: grid;
            grid-template-columns: 0.45fr 0.55fr;
            overflow: hidden;
            position: relative;
        }

        /* Left Side: Image */
        .left-pane {
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #0f172a;
        }
        .left-pane::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(17,24,39,0.35) 0%, rgba(17,24,39,0.45) 55%, rgba(17,24,39,0.5) 100%);
        }
        .left-pane-content {
            position: relative;
            z-index: 1;
            color: #e5e7eb;
            height: 100%;
            padding: 2.5rem 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .left-pane-content .branding {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .left-pane-content .branding img {
            height: 56px;
            width: 56px;
            object-fit: contain;
            border-radius: 10px;
            background: #fff;
            padding: 6px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .left-pane-content .headline {
            margin-top: 1.8rem;
        }
        .left-pane-content h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 0.35rem;
        }
        .left-pane-content p {
            color: #cbd5e1;
            line-height: 1.5;
        }
        .left-pane-content .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            background: rgba(79, 70, 229, 0.25);
            color: #e0e7ff;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1.2rem;
        }

        /* Right Side: Forms */
        .auth-forms {
            padding: 3rem 3.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: transparent;
            max-height: 850px;
            overflow-y: auto;
        }

        .auth-forms::-webkit-scrollbar {
            width: 6px;
        }
        .auth-forms::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .input-group {
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.4rem;
        }

        input, select {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--text-dark);
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background-color: transparent;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2rem;
            padding-right: 2.5rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .form-switch {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .form-switch a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .form-switch a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Alerts & Errors */
        .error-message {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: block;
            font-weight: 500;
        }

        .success-alert, .error-alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .success-alert {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .error-alert {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        /* Two columns for register form */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-wrapper {
                flex-direction: column;
                min-height: auto;
            }
            .left-image-container {
                min-height: 250px !important;
            }
            .auth-forms {
                padding: 2rem;
            }
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <div class="left-pane" aria-hidden="true" style="background-image: url('{{ asset('images/sangamner_clg.jpg.jpeg') }}');">
            <div class="left-pane-content">
                <div class="branding">
                    <img src="/images/clg logo.jpg" alt="College Logo">
                    <div>
                        <div style="font-size: 1.05rem; font-weight: 700;">Internship Tracking</div>
                        <div style="font-size: 0.95rem; color: #cbd5e1;">Sangamner College</div>
                    </div>
                </div>
                <div class="headline">
                    <h1>Internships made simple</h1>
                    <p>Register, get approved by your department, and track your internship journey from one place.</p>
                    <span class="badge">Campus verified</span>
                </div>
            </div>
        </div>

        <div class="auth-forms">
            <div id="login-section" class="fade-in" style="width:100%; max-width:560px; margin:0 auto;">
                <div class="form-header">
                    <p class="text-sm" style="color:var(--text-muted); font-weight:600; letter-spacing:0.02em;">Student Portal</p>
                    <h1>Welcome back</h1>
                    <p>Sign in with your username or email to continue.</p>
                </div>

                <form wire:submit="login" class="fade-in" style="background:#fff; border:1px solid var(--border-color); border-radius:18px; padding:1.5rem 1.75rem; box-shadow:0 14px 48px rgba(24,30,42,0.08);">
                    <div class="input-group">
                        <label for="login">Username or Email</label>
                        <input wire:model.defer="loginForm.login" id="login" name="login" type="text" placeholder="Username or email" autocomplete="username">
                        @error('loginForm.login') <span class="error-message">{{ $message }}</span> @enderror
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input wire:model.defer="loginForm.password" id="password" name="password" type="password" placeholder="********" autocomplete="current-password">
                        @error('loginForm.password') <span class="error-message">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                        <input wire:model="loginForm.remember" id="remember" type="checkbox" style="width:16px; height:16px; accent-color:var(--primary);">
                        <label for="remember" style="font-size:0.9rem; color:var(--text-muted); cursor:pointer;">Remember me</label>
                    </div>

                    <button class="btn-submit" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Login</span>
                        <span wire:loading>Signing in...</span>
                    </button>
                </form>

                <div class="form-switch">
                    Don't have an account?
                    <a id="show-register">Create one</a>
                </div>
            </div>

            <div id="register-section" style="display:none; width:100%; max-width:760px; margin:0 auto;" class="fade-in">
                <div class="form-header">
                    <p class="text-sm" style="color:var(--text-muted); font-weight:600; letter-spacing:0.02em;">Student Registration</p>
                    <h1>Create your account</h1>
                    <p>Fill the details below. Your department teacher will approve your access.</p>
                </div>

                <form wire:submit="register" class="fade-in" style="background:#fff; border:1px solid var(--border-color); border-radius:18px; padding:1.5rem 1.75rem; box-shadow:0 14px 48px rgba(24,30,42,0.08);">
                    <div class="grid-2">
                        <div class="input-group">
                            <label for="name">Full name</label>
                        <input wire:model.defer="name" id="name" name="name" type="text" placeholder="Full name" autocomplete="name">
                            @error('name') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                        <div class="input-group">
                            <label for="username">Username</label>
                        <input wire:model.defer="username" id="username" name="username" type="text" placeholder="Username" autocomplete="username">
                            @error('username') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label for="email">Email</label>
                        <input wire:model.defer="email" id="email" name="email" type="email" placeholder="Email" autocomplete="email">
                            @error('email') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                        <div class="input-group">
                            <label for="class">Class</label>
                            <select wire:model="class" id="class" name="class">
                                <option value="">Select class</option>
                                <option value="FYBCS">FYBCS</option>
                                <option value="SYBCS">SYBCS</option>
                                <option value="TYBCS">TYBCS</option>
                            </select>
                            @error('class') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label for="department_id">Department</label>
                            <select wire:model="department_id" id="department_id" name="department_id">
                                <option value="">Select department</option>
                                @foreach ($this->departments() as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                        <div></div>
                    </div>

                    <div class="grid-2">
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input wire:model.defer="password" id="password" name="password" type="password" placeholder="Password" autocomplete="new-password">
                            @error('password') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                        <div class="input-group">
                            <label for="password_confirmation">Confirm password</label>
                            <input wire:model.defer="password_confirmation" id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm password" autocomplete="new-password">
                            @error('password_confirmation') <span class="error-message">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button class="btn-submit" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Submit registration</span>
                        <span wire:loading>Submitting...</span>
                    </button>
                </form>

                <div class="form-switch">
                    Already registered?
                    <a id="show-login">Back to login</a>
                </div>
            </div>
    </div>

    @livewireScripts
        <script>
            // Auth form switch
            const loginSection = document.getElementById('login-section');
            const registerSection = document.getElementById('register-section');
            const showRegister = document.getElementById('show-register');
            const showLogin = document.getElementById('show-login');
            const defaultMode = "{{ request()->routeIs('register') ? 'register' : 'login' }}";

            const showLoginSection = () => {
                if (!loginSection || !registerSection) return;
                registerSection.style.display = 'none';
                loginSection.style.display = 'block';
                loginSection.classList.add('fade-in');
            };

            const showRegisterSection = () => {
                if (!loginSection || !registerSection) return;
                loginSection.style.display = 'none';
                registerSection.style.display = 'block';
                registerSection.classList.add('fade-in');
            };

            if (defaultMode === 'register') {
                showRegisterSection();
            } else {
                showLoginSection();
            }

            showRegister?.addEventListener('click', showRegisterSection);
            showLogin?.addEventListener('click', showLoginSection);
        </script>
</body>
</html>
