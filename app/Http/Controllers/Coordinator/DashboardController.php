<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('coordinator.dashboard');
    }
}
