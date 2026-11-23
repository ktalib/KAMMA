<?php

namespace App\Http\Controllers\Kamma;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController
{
    public function index(): View
    {
        return view('kamma.payments');
    }

    public function store(Request $request, int $bill)
    {
        return back()->with('status', 'Payment saved (preview)');
    }

    public function report(): View
    {
        return view('kamma.payments-report');
    }

    public function csv()
    {
        return response("bill_no,amount,status\n", 200, ['Content-Type' => 'text/csv']);
    }

    public function receipts(): View
    {
        return view('kamma.payments');
    }

    public function receipt(int $bill): View
    {
        return view('kamma.payment-receipt', ['bill' => $bill]);
    }
}
