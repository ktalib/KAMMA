<?php

namespace App\Http\Controllers;

use App\Services\MainDashboard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(MainDashboard $dashboard): View
    {
        return view('dashboard', $dashboard->build());
    }

    public function md(MainDashboard $dashboard): View
    {
        return view('dashboard', $dashboard->build() + ['mdView' => true]);
    }
}
