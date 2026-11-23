<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RevmController extends Controller
{
    public function dashboard(): View
    {
        return view('ui.revm.dashboard');
    }

    public function billMaster(): View
    {
        return view('ui.revm.bill-master');
    }

    public function contravention(): View
    {
        return view('ui.revm.contravention');
    }

    public function legacy(): View
    {
        return view('ui.revm.legacy');
    }

    public function tokens(): View
    {
        return view('ui.revm.tokens');
    }
}
