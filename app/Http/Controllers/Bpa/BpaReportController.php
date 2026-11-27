<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Services\Bpa\BpaReport;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** KAMMA > Reports: Building Plan Approval figures for a date range, and the applications register (CSV). */
class BpaReportController extends Controller
{
    public function index(Request $request, BpaReport $report)
    {
        [$from, $to] = $this->range($request);

        return view('bpa.reports.index', $report->build($from, $to) + compact('from', 'to'));
    }

    public function register(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $query = Application::query()->with('bill')->whereBetween('created_at', [$from, $to])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['S/N', 'Application No.', 'Received', 'BP File No.', 'Commissioned', 'File No.', 'File title', 'Developer',
                'Plot No.', 'District', 'LGA', 'Location', 'Plot size', 'Land use (land file)', 'Proposed use', 'Land-use check',
                'Type of submission', 'Plots', 'Bill No.', 'Bill (NGN)', 'Bill status', 'Receipt No.', 'Now with', 'Status', 'Receiving officer']);
            $n = 0;
            $query->chunkById(500, function ($apps) use ($out, &$n) {
                foreach ($apps as $a) {
                    fputcsv($out, [++$n, $a->application_no, $a->created_at?->format('Y-m-d'), $a->bp_file_no, $a->commissioned_at?->format('Y-m-d'),
                        $a->file_number, $a->file_title, $a->developer_name, $a->plot_no, $a->district, $a->lga, $a->location, $a->plot_size,
                        $a->file_land_use, $a->proposed_use === 'Other' ? 'Other: '.$a->proposed_use_other : $a->proposed_use, $a->land_use_check,
                        $a->submission_type === 'Other' ? 'Other: '.$a->submission_type_other : $a->submission_type, $a->number_of_plots,
                        $a->bill?->bill_no, $a->bill ? number_format((float) $a->bill->amount, 2, '.', '') : null, $a->bill?->status, $a->bill?->receipt_no,
                        BpaStage::label($a->stage), $a->status, $a->received_by_name]);
                }
            });
            fclose($out);
        }, 'kamma-bpa-applications-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** From / to dates; this year by default (as KLAES's reports). */
    private function range(Request $request): array
    {
        $parse = function (?string $v): ?Carbon {
            try {
                return filled($v) ? Carbon::createFromFormat('Y-m-d', $v) : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $from = ($parse($request->query('from')) ?? now()->startOfYear())->copy()->startOfDay();
        $to = ($parse($request->query('to')) ?? now()->endOfYear())->copy()->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
