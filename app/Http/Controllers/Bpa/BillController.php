<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Bill;
use Illuminate\Http\Request;

/** Assessment Stage > Initial Bill: the BPA application-fee bills. */
class BillController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $bills = Bill::query()->initial()
            ->with('application')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('bill_no', 'like', '%'.$search.'%')
                        ->orWhereHas('application', function ($q) use ($search) {
                            $q->where('application_no', 'like', '%'.$search.'%')
                                ->orWhere('file_number', 'like', '%'.$search.'%')
                                ->orWhere('developer_name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when(in_array($status, ['Unpaid', 'Paid'], true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('bpa.bills.index', compact('bills', 'search', 'status'));
    }
}
