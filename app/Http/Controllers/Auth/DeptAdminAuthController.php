<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeptAdminAuthController extends Controller
{
    /**
     * Show the department admin login form.
     */
    public function showLoginForm()
    {
        return view('auth.dept-admin-login');
    }

    /**
     * Handle department admin login request.
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

            // Must be a department_admin
            if ($user->role !== 'department_admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'This login is only for department administrators. Please use the correct login page.',
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the super administrator.',
                ]);
            }

            return redirect()->route('department_admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
}
