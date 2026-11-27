<?php

namespace App\Services\Bpa;

use App\Models\Bpa\Application;
use App\Models\Bpa\Bill;
use App\Models\Bpa\Movement;
use App\Support\Bpa\BpaStage;
use App\Support\LandUse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * KAMMA > Reports, borrowed from KLAES's Survey Reports (klas Survey\ReportController@index +
 * _page-reports): figures for a date range, each chart with the table of its figures. Desk counts are as of
 * today; everything else is for the range. Every figure is queried; nothing is hard-coded.
 */
class BpaReport
{
    public function build(Carbon $from, Carbon $to): array
    {
        $apps = Application::query()->with('bill')->whereBetween('created_at', [$from, $to])->get();
        $commissioned = Application::query()->whereBetween('commissioned_at', [$from, $to])->get(['id', 'commissioned_at']);
        $paidBills = Bill::query()->where('status', 'Paid')->whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->get(['amount_paid']);
        $decisions = Movement::query()->whereIn('action', [BpaStage::RECOMMENDED, BpaStage::NOT_RECOMMENDED])
            ->whereBetween('acted_at', [$from, $to])->get(['from_stage', 'action']);

        $kpis = [
            'applications' => [
                'received' => $apps->count(),
                'plots' => (int) $apps->sum('number_of_plots'),
                'flagged' => $apps->where('land_use_check', LandUse::MISMATCH)->count(),
                'billed' => (float) $apps->sum(fn ($a) => (float) ($a->bill?->amount ?? 0)),
            ],
            'payments' => [
                'paid' => $paidBills->count(),
                'amount' => (float) $paidBills->sum('amount_paid'),
                'commissioned' => $commissioned->count(),
            ],
            'desks' => [ // as of today
                'director' => Application::where('stage', BpaStage::DIRECTOR)->count(),
                'deputy' => Application::where('stage', BpaStage::DEPUTY_DIRECTOR)->count(),
                'returned' => Application::whereNotNull('bp_file_no')->where('stage', BpaStage::ARCHITECTURE)->count(),
                'zonal' => Application::where('stage', BpaStage::ZONAL_OFFICE)->count(),
            ],
        ];

        return [
            'kpis' => $kpis,
            'monthly' => $this->monthly($from, $to, $apps, $commissioned),
            'byLga' => $this->byLga($apps),
            'byUse' => $this->byUse($apps),
            'decisions' => $this->decisions($decisions),
        ];
    }

    /** Applications received and BP files commissioned per month of the range. */
    private function monthly(Carbon $from, Carbon $to, Collection $apps, Collection $commissioned): Collection
    {
        $received = $apps->countBy(fn ($a) => $a->created_at->format('Y-m'));
        $bp = $commissioned->countBy(fn ($a) => Carbon::parse($a->commissioned_at)->format('Y-m'));
        $months = collect();
        for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonth()) {
            $key = $m->format('Y-m');
            $months->push(['label' => $m->format('M Y'), 'received' => $received->get($key, 0), 'commissioned' => $bp->get($key, 0)]);
        }

        return $months;
    }

    /** Applications by LGA (case-insensitive), most first. */
    private function byLga(Collection $apps): Collection
    {
        return $apps->groupBy(fn ($a) => mb_strtoupper(trim((string) $a->lga)) ?: 'NOT RECORDED')
            ->map(fn ($g, $lga) => ['lga' => $lga, 'count' => $g->count(), 'flagged' => $g->where('land_use_check', LandUse::MISMATCH)->count()])
            ->sortByDesc('count')->values();
    }

    /** Proposed use against the land-use check result. */
    private function byUse(Collection $apps): Collection
    {
        return $apps->groupBy('proposed_use')->map(fn ($g, $use) => [
            'use' => $use,
            'match' => $g->where('land_use_check', LandUse::MATCH)->count(),
            'mismatch' => $g->where('land_use_check', LandUse::MISMATCH)->count(),
            'unchecked' => $g->where('land_use_check', LandUse::NOT_CHECKED)->count(),
            'total' => $g->count(),
        ])->sortByDesc('total')->values();
    }

    /** Recommended / Not recommended at each desk in the range. */
    private function decisions(Collection $moves): Collection
    {
        return collect(BpaStage::DESKS)->map(fn ($desk) => [
            'desk' => $desk[1],
            'recommended' => $moves->where('from_stage', $desk[0])->where('action', BpaStage::RECOMMENDED)->count(),
            'not_recommended' => $moves->where('from_stage', $desk[0])->where('action', BpaStage::NOT_RECOMMENDED)->count(),
        ])->values();
    }
}
