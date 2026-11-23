<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController
{
    public function masterKsi(): View
    {
        return view('kamma.approvals');
    }

    public function pr(string $kind): View
    {
        return view('kamma.approvals', ['kind' => $kind, 'type' => 'planning recommendation']);
    }

    public function memo(string $kind): View
    {
        return view('kamma.approvals', ['kind' => $kind, 'type' => 'memo']);
    }

    public function decide(Request $request, int $report)
    {
        return back()->with('status', 'Decision saved (preview)');
    }

    public function decideMemo(Request $request, int $memo)
    {
        return back()->with('status', 'Decision saved (preview)');
    }
}
