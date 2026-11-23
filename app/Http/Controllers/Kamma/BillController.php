<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Kamma\Bill;
use App\Models\Kamma\KsiReport;
use App\Services\Kamma\Bills;
use App\Services\Klaes\KsiLookups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * KAMMA > Bills & Payments > Bills (copied from KLAES PP Bills & Payments central / physical_planning/bills): the bills
 * list, and Generate Bill: the Application Processing Fee on a shadow file, or the Betterment Bill from a KSI report with
 * KLAES PP's rate formula on the recommended area and the 4× no-BPA penalty (App\Services\Kamma\Bills).
 * REV-M > Betterment Bill / Initial Bill open the same list (?from=revm&type=…).
 */
class BillController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['processing', 'betterment'], true) ? $request->query('type') : null;
        $status = in_array($request->query('status'), ['unpaid', 'paid'], true) ? $request->query('status') : null;
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $base = fn () => Bill::query()->when($type, fn ($w) => $w->where('bill_type', $type));

        $bills = $base()->with('file')
            ->when($status, fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('bill_no', 'like', "%{$q}%")->orWhere('file_number', 'like', "%{$q}%")
                ->orWhere('payer_name', 'like', "%{$q}%")->orWhere('receipt_no', 'like', "%{$q}%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.bills.index', [
            'bills' => $bills, 'type' => $type, 'status' => $status, 'q' => $q, 'fromRevm' => $request->query('from') === 'revm',
            'totals' => [
                'count' => $base()->count(),
                'billed' => (float) $base()->sum('amount'),
                'paid' => (float) $base()->where('status', 'paid')->sum('amount_paid'),
                'outstanding' => (float) $base()->where('status', 'unpaid')->sum('amount'),
                'penalised' => $base()->where('penalty_multiplier', '>', 1)->count(),
            ],
        ]);
    }

    public function create(Request $request, KsiLookups $lookups, Bills $bills): View
    {
        $report = $request->integer('report') ? KsiReport::query()->find($request->integer('report')) : null;
        $file = $request->integer('file') ? FileIndexing::query()->live()->find($request->integer('file')) : ($report?->file);

        return view('kamma.bills.create', [
            'report' => $report, 'file' => $file,
            'reports' => KsiReport::query()->whereDoesntHave('bills', fn ($b) => $b->where('bill_type', 'betterment'))
                ->when($file, fn ($w) => $w->where('file_indexing_id', $file->id))
                ->latest('id')->limit(200)->get(['id', 'kind', 'file_number', 'applicant_name', 'recommended_site_area', 'existing_site_area', 'building_exists', 'bpa_approved', 'applied_land_use', 'plot_number', 'district', 'lga', 'location']),
            'rates' => $lookups->bettermentRates(),
            'ratesFromKlaes' => $lookups->ratesFromKlaes(),
            'processingFee' => $bills->processingFee(),
            'multiplier' => (int) config('kamma.bills.no_bpa_penalty_multiplier', 4),
        ]);
    }

    public function store(Request $request, Bills $bills): RedirectResponse
    {
        $d = $request->validate([
            'bill_type' => ['required', Rule::in(['processing', 'betterment'])],
            'file_indexing_id' => ['nullable', 'required_if:bill_type,processing', 'integer'],
            'ksi_report_id' => ['nullable', 'required_if:bill_type,betterment', 'integer'],
            'category' => ['nullable', 'required_if:bill_type,betterment', 'string', 'max:100'],
            'bpa_approved' => ['nullable', Rule::in(['1', '0'])],
        ], [
            'ksi_report_id.required_if' => 'Pick the KSI report the betterment is charged on.',
            'file_indexing_id.required_if' => 'Pick the shadow file the processing fee is for.',
            'category.required_if' => 'Pick the betterment category.',
        ]);

        if ($d['bill_type'] === 'processing') {
            $bill = $bills->raiseProcessingFee(FileIndexing::query()->live()->findOrFail($d['file_indexing_id']), $request->user());
        } else {
            $report = KsiReport::query()->findOrFail($d['ksi_report_id']);
            $bill = $bills->raiseBetterment($report, $d['category'], ($d['bpa_approved'] ?? ($report->bpa_approved ? '1' : '0')) === '1', $request->user());
        }

        return redirect()->route('kamma.bills.index')->with('status', "Bill {$bill->bill_no} generated: ₦".number_format((float) $bill->amount, 2).($bill->penalty_multiplier > 1 ? ' (no-BPA penalty × '.$bill->penalty_multiplier.')' : '').'.');
    }

    public function print(int $bill): View
    {
        return view('kamma.bills.print', ['b' => Bill::query()->with(['file', 'report'])->findOrFail($bill)]);
    }
}
