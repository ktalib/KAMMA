<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Kamma\Bill;
use App\Services\Kamma\Bills;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KAMMA > Bills & Payments > Payments / Payment Reports, and REV-M Awaiting Receipts / e-Receipt / Print Receipt (KLAES PP
 * Bills & Payments). Staff record the applicant's payment in a pop-up (receipt no. unique, amount must equal the bill,
 * channel with one "Other → specify"): App\Services\Kamma\Bills::pay. No online payment.
 */
class PaymentController extends Controller
{
    public const CHANNELS = ['Bank', 'POS', 'Interswitch', 'USSD', 'Other'];

    public function index(Request $request): View
    {
        return $this->list($request, false);
    }

    public function receipts(Request $request): View
    {
        return $this->list($request, true);
    }

    public function store(Request $request, int $bill, Bills $bills): RedirectResponse
    {
        $b = Bill::query()->findOrFail($bill);
        // Receipt numbers are identifiers: trimmed and upper-cased BEFORE the uniqueness check.
        if (is_string($request->input('receipt_no'))) {
            $request->merge(['receipt_no' => strtoupper(trim($request->input('receipt_no')))]);
        }
        $d = $request->validateWithBag('payment', [
            'receipt_no' => ['required', 'string', 'max:100', Rule::unique('bills', 'receipt_no')],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'channel' => ['required', Rule::in(self::CHANNELS)],
            'channel_other' => ['nullable', 'required_if:channel,Other', 'string', 'max:100'],
        ], [
            'receipt_no.unique' => 'That receipt number is already recorded on another bill.',
            'channel_other.required_if' => 'Specify the payment channel.',
        ]);

        try {
            $bills->pay($b, $d, $request->user());
        } catch (ValidationException $e) {
            throw $e->errorBag('payment'); // so the pop-up reopens with the message
        }

        return redirect()->route('kamma.payments.index', ['tab' => 'paid', 'q' => $b->bill_no])
            ->with('status', "Payment recorded on {$b->bill_no}: receipt {$d['receipt_no']}, ₦".number_format((float) $d['amount_paid'], 2).'.');
    }

    public function report(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $paid = fn () => Bill::query()->where('status', 'paid')->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()]);
        $billed = fn () => Bill::query()->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
        $rows = $paid()->orderBy('paid_on')->orderBy('id')->get();

        $months = [];
        for ($m = $from->startOfMonth(); $m <= $to; $m = $m->addMonth()) {
            $months[$m->format('Y-m')] = ['label' => $m->format('M Y'), 'amount' => 0.0, 'count' => 0];
        }
        foreach ($rows as $b) {
            $key = $b->paid_on->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['amount'] += (float) $b->amount_paid;
                $months[$key]['count']++;
            }
        }
        $group = fn (string $by) => $rows->groupBy($by)->map(fn ($g) => ['count' => $g->count(), 'amount' => (float) $g->sum('amount_paid')])->sortByDesc('amount');
        $channelOf = fn (Bill $b) => $b->channel === 'Other' && $b->channel_other ? 'Other: '.$b->channel_other : ($b->channel ?: '—');

        return view('kamma.payments-report', [
            'from' => $from, 'to' => $to, 'rows' => $rows, 'months' => $months, 'channelOf' => $channelOf,
            'byType' => $group('bill_type'),
            'bySource' => $group('source'),
            'byChannel' => $rows->groupBy($channelOf)->map(fn ($g) => ['count' => $g->count(), 'amount' => (float) $g->sum('amount_paid')])->sortByDesc('amount'),
            'totals' => [
                'collected' => (float) $rows->sum('amount_paid'),
                'payments' => $rows->count(),
                'billed' => (float) $billed()->sum('amount'),
                'bills' => $billed()->count(),
                'outstanding' => (float) Bill::query()->where('status', 'unpaid')->sum('amount'),
                'penalty' => (float) $rows->where('penalty_multiplier', '>', 1)->sum(fn ($b) => (float) $b->amount_paid - (float) $b->standard_amount),
            ],
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = Bill::query()->where('status', 'paid')->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->orderBy('paid_on')->orderBy('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['S/N', 'Bill No.', 'File No.', 'Payer', 'Bill type', 'Application', 'Category', 'Standard (NGN)', 'Multiplier', 'Bill amount (NGN)', 'Amount paid (NGN)', 'Receipt No.', 'Paid on', 'Channel']);
            foreach ($rows as $i => $b) {
                fputcsv($out, [
                    $i + 1, $b->bill_no, $b->file_number, $b->payer_name, $b->bill_type === 'processing' ? 'Processing fee' : 'Betterment',
                    $b->source, $b->category, $b->standard_amount, $b->penalty_multiplier, $b->amount, $b->amount_paid, $b->receipt_no,
                    $b->paid_on?->format('d/m/Y'), $b->channel === 'Other' ? 'Other: '.$b->channel_other : $b->channel,
                ]);
            }
            fclose($out);
        }, sprintf('kamma-payments-%s-to-%s.csv', $from->format('Ymd'), $to->format('Ymd')), ['Content-Type' => 'text/csv']);
    }

    public function receipt(int $bill): View
    {
        $b = Bill::query()->with(['file', 'report'])->findOrFail($bill);
        abort_unless($b->isPaid(), 404);

        return view('kamma.payment-receipt', ['b' => $b]);
    }

    private function list(Request $request, bool $receipts): View
    {
        $tab = $receipts ? 'paid' : (in_array($request->query('tab'), ['paid'], true) ? 'paid' : 'unpaid');
        $type = in_array($request->query('type'), ['processing', 'betterment'], true) ? $request->query('type') : null;
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $base = fn (): Builder => Bill::query()->when($type, fn ($w) => $w->where('bill_type', $type))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('bill_no', 'like', "%{$q}%")->orWhere('file_number', 'like', "%{$q}%")
                ->orWhere('payer_name', 'like', "%{$q}%")->orWhere('receipt_no', 'like', "%{$q}%")));

        $bills = $base()->where('status', $tab)->latest($tab === 'paid' ? 'paid_on' : 'id')->latest('id')->paginate(20)->withQueryString();
        $reopen = null;
        if ($request->session()->get('errors')?->getBag('payment')->any() && ($id = (int) $request->old('bill_id'))) {
            $reopen = Bill::query()->where('status', 'unpaid')->find($id);
        }

        return view('kamma.payments', [
            'bills' => $bills, 'tab' => $tab, 'type' => $type, 'q' => $q, 'receipts' => $receipts, 'reopen' => $reopen,
            'fromRevm' => $request->query('from') === 'revm', 'channels' => self::CHANNELS,
            'counts' => ['unpaid' => $base()->where('status', 'unpaid')->count(), 'paid' => $base()->where('status', 'paid')->count()],
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} the report period; default = this year to today. */
    private function range(Request $request): array
    {
        $parse = function ($v, CarbonImmutable $fallback) {
            try {
                return $v ? CarbonImmutable::parse($v) : $fallback;
            } catch (\Throwable) {
                return $fallback;
            }
        };
        $from = $parse($request->query('date_from'), CarbonImmutable::now()->startOfYear())->startOfDay();
        $to = $parse($request->query('date_to'), CarbonImmutable::now())->startOfDay();

        return $from > $to ? [$to, $from] : [$from, $to];
    }
}
