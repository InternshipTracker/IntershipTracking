<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Show the student registration form.
     */
    public function showRegistrationForm()
    {
        $departments = Department::all();
        $courses = Course::all();
        return view('auth.register', compact('departments', 'courses'));
    }

    /**
     * Handle student registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email',
            'password'            => 'required|string|min:6|confirmed',
            'phone'               => 'nullable|string|max:20',
            'department_id'       => 'required|exists:departments,id',
            'registration_number' => 'required|string|unique:student_profiles,registration_number',
            'course'              => 'required|string|exists:courses,code',
            'class'               => 'required|string|in:FY,SY,TY,Fourth Year',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => $validated['password'],
                'role'      => 'student',
                'phone'     => $validated['phone'] ?? null,
                'is_active' => true,
                'is_approved' => false,
                'approval_status' => 'pending',
                'department_id' => $validated['department_id'],
                'class' => $validated['class'],
            ]);

            $user->studentProfile()->create([
                'department_id'       => $validated['department_id'],
                'registration_number' => $validated['registration_number'],
                'course'              => $validated['course'],
                'class'               => $validated['class'],
            ]);

            return $user;
        });

        // Auto-login after registration
        Auth::login($user);

        return redirect()->route('student.dashboard');
    }
}
