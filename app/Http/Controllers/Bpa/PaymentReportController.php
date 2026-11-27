<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Bill;
use App\Services\Bpa\RecordPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * KAMMA > Bills & Payments > Payments Report. Borrowed from KLAES's Payments Report
 * (klas programmes.payments?url=report: summary cards, payment-type breakdown, status / trend /
 * comparison charts, print), over KAMMA's own bills, with date and channel filters, the list of
 * payments and a CSV download.
 */
class PaymentReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $paid = $this->paidQuery($filters);

        $payments = (clone $paid)->with('application')->orderByDesc('paid_on')->orderByDesc('id')->paginate(25)->withQueryString();
        $rows = (clone $paid)->get(['bill_type', 'payment_channel', 'payment_channel_other', 'amount_paid', 'paid_on']);

        $summary = [
            'total' => (float) $rows->sum('amount_paid'),
            'count' => $rows->count(),
            'paid_bills' => Bill::initial()->where('status', 'Paid')->count(),
            'unpaid_bills' => Bill::initial()->where('status', 'Unpaid')->count(),
            'outstanding' => (float) Bill::initial()->where('status', 'Unpaid')->sum('amount'),
        ];

        $byType = $rows->groupBy('bill_type')->map(fn ($g) => ['count' => $g->count(), 'amount' => (float) $g->sum('amount_paid')])->sortKeys();
        $byChannel = $rows->groupBy(fn ($b) => $b->payment_channel === 'Other' ? 'Other: '.$b->payment_channel_other : $b->payment_channel)
            ->map(fn ($g) => ['count' => $g->count(), 'amount' => (float) $g->sum('amount_paid')])->sortByDesc('amount');

        // Twelve months up to the "to" date (or this month): amount paid per month.
        $end = ($filters['to'] ?? now())->copy()->startOfMonth();
        $months = collect(range(11, 0))->map(fn ($i) => $end->copy()->subMonths($i));
        $perMonth = $rows->groupBy(fn ($b) => Carbon::parse($b->paid_on)->format('Y-m'));
        $trend = $months->map(fn ($m) => [
            'label' => $m->format('M Y'),
            'amount' => (float) ($perMonth->get($m->format('Y-m'))?->sum('amount_paid') ?? 0),
            'count' => $perMonth->get($m->format('Y-m'))?->count() ?? 0,
        ])->values();

        return view('bpa.reports.payments', [
            'payments' => $payments,
            'summary' => $summary,
            'byType' => $byType,
            'byChannel' => $byChannel,
            'trend' => $trend,
            'filters' => $filters,
            'channels' => RecordPayment::CHANNELS,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $query = $this->paidQuery($this->filters($request))->with('application')->orderBy('paid_on')->orderBy('id');
        $name = 'kamma-bpa-payments-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 mark so Excel shows ₦ and names correctly
            fputcsv($out, ['S/N', 'Receipt No.', 'Paid on', 'Amount paid', 'Channel', 'Bill No.', 'Bill type', 'Application No.', 'BP File No.',
                'File No.', 'Developer', 'Plot No.', 'District', 'LGA', 'Location', 'Recorded by']);
            $n = 0;
            $query->chunkById(500, function ($bills) use ($out, &$n) {
                foreach ($bills as $bill) {
                    $a = $bill->application;
                    fputcsv($out, [++$n, $bill->receipt_no, $bill->paid_on?->format('Y-m-d'), number_format((float) $bill->amount_paid, 2, '.', ''),
                        $bill->payment_channel === 'Other' ? 'Other: '.$bill->payment_channel_other : $bill->payment_channel,
                        $bill->bill_no, $bill->bill_type, $a?->application_no, $a?->bp_file_no, $a?->file_number, $a?->developer_name,
                        $a?->plot_no, $a?->district, $a?->lga, $a?->location, $bill->payment_recorded_by_name]);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{from: ?Carbon, to: ?Carbon, channel: string, search: string} */
    private function filters(Request $request): array
    {
        $date = function (?string $v): ?Carbon {
            try {
                return filled($v) ? Carbon::createFromFormat('Y-m-d', $v)->startOfDay() : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $from = $date($request->query('from'));
        $to = $date($request->query('to'));
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $channel = (string) $request->query('channel', '');

        return [
            'from' => $from,
            'to' => $to,
            'channel' => in_array($channel, RecordPayment::CHANNELS, true) ? $channel : '',
            'search' => trim((string) $request->query('search', '')),
        ];
    }

    private function paidQuery(array $f): Builder
    {
        return Bill::query()->initial()
            ->where('status', 'Paid')
            ->when($f['from'], fn ($q, $d) => $q->where('paid_on', '>=', $d->toDateString()))
            ->when($f['to'], fn ($q, $d) => $q->where('paid_on', '<=', $d->toDateString()))
            ->when($f['channel'] !== '', fn ($q) => $q->where('payment_channel', $f['channel']))
            ->when($f['search'] !== '', function ($q) use ($f) {
                $s = '%'.$f['search'].'%';
                $q->where(fn ($q) => $q->where('receipt_no', 'like', $s)->orWhere('bill_no', 'like', $s)
                    ->orWhereHas('application', fn ($a) => $a->where('application_no', 'like', $s)->orWhere('bp_file_no', 'like', $s)
                        ->orWhere('file_number', 'like', $s)->orWhere('developer_name', 'like', $s)));
            });
    }
}
