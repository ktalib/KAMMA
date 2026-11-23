<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TitleStatusController
{
    public function index(Request $request): View
    {
        return view('kamma.title-status');
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Saved (preview)');
    }
}
