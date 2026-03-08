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

    // Added to track registration success
    public bool $isRegistered = false;

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
        
        // Show success message instead of redirecting
        $this->isRegistered = true;
    }
}; ?>

<div class="auth-page-shell">
    <style>
        :root {
            --primary: #2652f1;
            --primary-hover: #1e40af;
            --secondary: #ec4899;
            --bg-color: #f4d9c3;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --input-bg: #f9fafb;
            --card-bg: #ffffff;
            --image-pane-bg: #e5e7eb;
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

        .auth-page-shell {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Perfect Proportions for the main card */
        .auth-wrapper {
            background: var(--bg-color);
            width: 100%;
            max-width: 1000px;
            min-height: 620px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: row;
            overflow: hidden;
            position: relative;
        }

        /* Left Pane - Clear Image */
        .left-pane {
            width: 50%;
            position: relative;
            background-color: var(--image-pane-bg);
        }

        .left-pane img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        /* Right Pane - Forms */
        .auth-forms {
            width: 50%;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--bg-color);
            overflow-y: auto;
            max-height: 90vh;
        }

        .auth-forms::-webkit-scrollbar {
            width: 6px;
        }
        .auth-forms::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .form-header {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-header .logo-container img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 12px;
            background: #fff;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .form-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 0.1rem;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Form Card Styling */
        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            width: 100%;
        }

        .input-group {
            margin-bottom: 1.1rem;
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
            font-weight: 500;
            background-color: var(--input-bg);
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            background-color: transparent;
            box-shadow: 0 0 0 4px rgba(38, 82, 241, 0.15);
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
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(38, 82, 241, 0.2);
            text-align: center;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(38, 82, 241, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .form-switch {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
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

        .error-message {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.3rem;
            display: block;
            font-weight: 500;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Success Message Styling */
        .success-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 3rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .success-icon {
            background: #10b981; 
            color: white; 
            width: 70px; 
            height: 70px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 1.5rem; 
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Responsive Mobile View */
        @media (max-width: 900px) {
            body {
                padding: 1rem;
            }
            .auth-wrapper {
                flex-direction: column;
                min-height: auto;
                max-width: 500px;
                margin: 0 auto;
            }
            .left-pane {
                width: 100%;
                min-height: 250px;
            }
            .auth-forms {
                width: 100%;
                padding: 2rem 1.5rem;
                max-height: none;
            }
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="auth-wrapper">
        <div class="left-pane">
            <img src="{{ asset('images/sangamner_clg.jpeg') }}" alt="Sangamner College Campus">
        </div>

        <div class="auth-forms">
            
            @if($isRegistered)
                <div id="success-section" class="fade-in" style="width:100%;">
                    <div class="success-card">
                        <div class="success-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" style="width: 35px; height: 35px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>

                        <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-dark); margin-bottom: 0.8rem;">Registration Successful!</h1>
                        
                        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 2rem;">
                            Your request has been sent to your department teacher to approve your registration.
                        </p>
                        
                        <a href="/" class="btn-submit" style="display: inline-block; text-decoration: none; width: auto; padding: 0.85rem 2rem;">Back to Home Page</a>
                        
                        <button wire:click="$set('isRegistered', false)" style="background: none; border: none; color: var(--text-muted); font-weight: 500; font-size: 0.9rem; margin-top: 1.5rem; cursor: pointer;">
                            <span style="text-decoration: underline;">Back to Login</span>
                        </button>
                    </div>
                </div>
            @else
                <div id="login-section" class="fade-in" style="width:100%;">
                    <div class="form-header">
                        <div class="logo-container">
                            <img src="{{ asset('images/clg logo.jpg') }}" alt="Sangamner College Logo">
                        </div>
                        <div>
                            <p class="text-sm" style="color:var(--text-muted); font-weight:600; letter-spacing:0.02em; margin-bottom:0.1rem;">Student Portal</p>
                            <h1>Welcome back</h1>
                        </div>
                    </div>

                    <form wire:submit="login" class="form-card">
                        <div class="input-group">
                            <label for="login">Username or Email</label>
                            <input wire:model.defer="loginForm.login" id="login" name="login" type="text" placeholder="Username or email" autocomplete="username">
                            @error('loginForm.login') <span class="error-message">{{ $message }}</span> @enderror
                        </div>

                        <div class="input-group">
                            <label for="password">Password</label>
                            <input wire:model.defer="loginForm.password" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password">
                            @error('loginForm.password') <span class="error-message">{{ $message }}</span> @enderror
                        </div>

                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                            <input wire:model="loginForm.remember" id="remember" type="checkbox" style="width:15px; height:15px; accent-color:var(--primary); cursor:pointer;">
                            <label for="remember" style="font-size:0.85rem; color:var(--text-dark); cursor:pointer;">Remember me</label>
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

                <div id="register-section" style="display:none; width:100%;" class="fade-in">
                    <div class="form-header">
                        <div class="logo-container">
                            <img src="{{ asset('images/clg logo.jpg') }}" alt="Sangamner College Logo">
                        </div>
                        <div>
                            <p class="text-sm" style="color:var(--text-muted); font-weight:600; letter-spacing:0.02em; margin-bottom:0.1rem;">Student Registration</p>
                            <h1>Create account</h1>
                        </div>
                    </div>

                    <form wire:submit="register" class="form-card">
                        <div class="grid-2">
                            <div class="input-group">
                                <label for="name">Full name</label>
                                <input wire:model.defer="name" id="name" name="name" type="text" placeholder="Full name">
                                @error('name') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div class="input-group">
                                <label for="username">Username</label>
                                <input wire:model.defer="username" id="username" name="username" type="text" placeholder="Username">
                                @error('username') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="input-group">
                                <label for="email">Email</label>
                                <input wire:model.defer="email" id="email" name="email" type="email" placeholder="Email">
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

                        <div class="grid-2">
                            <div class="input-group">
                                <label for="password">Password</label>
                                <input wire:model.defer="password" id="password" name="password" type="password" placeholder="••••••••">
                                @error('password') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                            <div class="input-group">
                                <label for="password_confirmation">Confirm</label>
                                <input wire:model.defer="password_confirmation" id="password_confirmation" name="password_confirmation" type="password" placeholder="••••••••">
                                @error('password_confirmation') <span class="error-message">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <button class="btn-submit" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Registration</span>
                            <span wire:loading>Submitting...</span>
                        </button>
                    </form>

                    <div class="form-switch">
                        Already registered?
                        <a id="show-login">Back to login</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
            // Initialize form switcher logic
            const initSwitcher = () => {
                const loginSection = document.getElementById('login-section');
                const registerSection = document.getElementById('register-section');
                const showRegister = document.getElementById('show-register');
                const showLogin = document.getElementById('show-login');
                const defaultMode = "{{ request()->routeIs('register') ? 'register' : 'login' }}";

                if (!loginSection || !registerSection) return;

                const showLoginSection = () => {
                    registerSection.style.display = 'none';
                    loginSection.style.display = 'block';
                    loginSection.classList.add('fade-in');
                };

                const showRegisterSection = () => {
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
            };

            // Run on load and after Livewire updates
            document.addEventListener('DOMContentLoaded', initSwitcher);
            document.addEventListener('livewire:navigated', initSwitcher);

            if (window.Livewire) {
                Livewire.hook('morph.updated', () => {
                    initSwitcher();
                });
            }
    </script>
</div>