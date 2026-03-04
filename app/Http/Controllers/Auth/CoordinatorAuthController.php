<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoordinatorAuthController extends Controller
{
    /**
     * Show the coordinator login form.
     */
    public function showLoginForm()
    {
        return view('auth.coordinator-login');
    }

    /**
     * Handle coordinator login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Must be a coordinator
            if ($user->role !== 'coordinator') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This login is only for coordinators. Please use the correct login page.',
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ]);
            }

            return redirect()->route('coordinator.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show the coordinator registration form.
     */
    public function showRegistrationForm()
    {
        $departments = Department::all();
        return view('auth.coordinator-register', compact('departments'));
    }

    /**
     * Handle coordinator registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6|confirmed',
            'phone'         => 'nullable|string|max:20',
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => $validated['password'],
                'role'      => 'coordinator',
                'phone'     => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            $user->coordinatorProfile()->create([
                'department_id' => $validated['department_id'],
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('coordinator.dashboard');
    }
}
