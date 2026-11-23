<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\View\View;

class KammaDashboardController
{
    public function index(): View
    {
        return view('kamma.dashboard');
    }
}
