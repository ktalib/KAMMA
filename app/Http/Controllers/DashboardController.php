<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard');
    }

    public function md(): View
    {
        return view('dashboard', ['mdView' => true]);
    }
}
