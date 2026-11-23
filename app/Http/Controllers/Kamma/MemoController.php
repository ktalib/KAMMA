<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoController
{
    public function index(Request $request, string $kind): View
    {
        return view('kamma.memos', ['kind' => $kind]);
    }

    public function store(Request $request, string $kind)
    {
        return back()->with('status', 'Memo saved (preview)');
    }
}
