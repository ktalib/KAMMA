<?php

namespace App\Http\Controllers\Ui;

use Illuminate\View\View;

class RevenueController
{
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
