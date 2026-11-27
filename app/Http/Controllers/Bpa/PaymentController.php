<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpa\RecordPaymentRequest;
use App\Models\Bpa\Bill;
use App\Services\Bpa\RecordPayment;
use Illuminate\Http\Request;

/** Architecture > Payment: confirm that application bills were paid. */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'paid' ? 'paid' : 'unpaid';
        $search = trim((string) $request->query('search', ''));

        $bills = Bill::query()->initial()
            ->with('application')
            ->where('status', $tab === 'paid' ? 'Paid' : 'Unpaid')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('bill_no', 'like', '%'.$search.'%')
                        ->orWhere('receipt_no', 'like', '%'.$search.'%')
                        ->orWhereHas('application', fn ($q) => $q->where('application_no', 'like', '%'.$search.'%')
                            ->orWhere('file_number', 'like', '%'.$search.'%')
                            ->orWhere('developer_name', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy($tab === 'paid' ? 'payment_recorded_at' : 'id', $tab === 'paid' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'unpaid' => Bill::initial()->where('status', 'Unpaid')->count(),
            'paid' => Bill::initial()->where('status', 'Paid')->count(),
        ];

        // After a failed save, reopen the pop-up on the same bill.
        $reopen = $request->old('bill_id') ? Bill::with('application')->find($request->old('bill_id')) : null;

        return view('bpa.payments.index', [
            'bills' => $bills, 'tab' => $tab, 'search' => $search, 'counts' => $counts,
            'channels' => RecordPayment::CHANNELS, 'reopen' => $reopen,
        ]);
    }

    public function store(RecordPaymentRequest $request, Bill $bill, RecordPayment $record)
    {
        try {
            $record->handle($bill, $request->validated(), $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e->errorBag('payment');
        }

        return redirect()->route('bpa.payments.index', ['tab' => 'unpaid'])
            ->with('status', "Payment recorded on {$bill->bill_no}. {$bill->application?->application_no} can now be commissioned.");
    }
}
