<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\View\View;

class ReportController
{
    public function index(): View
    {
        return view('kamma.reports');
    }

    public function csv()
    {
        return response("report,item,status\n", 200, ['Content-Type' => 'text/csv']);
    }
}
