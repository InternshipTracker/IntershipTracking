<?php

namespace App\Http\Controllers\DepartmentAdmin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('department_admin.dashboard');
    }
}
