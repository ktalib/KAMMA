<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\View\View;

class EregistryController
{
    public function index(string $kind): View
    {
        return view('kamma.eregistry', ['kind' => $kind]);
    }
}
