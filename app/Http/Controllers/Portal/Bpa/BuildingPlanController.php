<?php

namespace App\Http\Controllers\Portal\Bpa;

use App\Http\Controllers\Controller;
use App\Support\Portal\Bpa\BpaApplications as PortalApplications;
use App\Support\Portal\PortalSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Applicant portal: My building plan applications (copied from the KADCA portal dashboard, 2026-11-25). */
class BuildingPlanController extends Controller
{
    public function index(Request $request, PortalApplications $portal): View
    {
        $applicant = PortalSession::applicant($request);
        $applications = $portal->forApplicant($applicant);

        $unpaid = $applications->filter(fn ($a) => $a['bill_outstanding']);
        $cards = [
            ['label' => 'My applications', 'value' => $applications->count(), 'icon' => 'folder-open', 'tone' => 'indigo'],
            ['label' => 'Awaiting payment', 'value' => $unpaid->count(), 'icon' => 'receipt', 'tone' => 'amber',
                'sub' => $unpaid->isNotEmpty() ? '₦'.number_format($unpaid->sum(fn ($a) => $a['bill']['amount']), 2).' to pay' : null],
            ['label' => 'Under review', 'value' => $applications->filter(fn ($a) => ! $a['bill_outstanding'] && ! $a['timeline']['complete'])->count(), 'icon' => 'hourglass', 'tone' => 'sky'],
            ['label' => 'Approved', 'value' => $applications->filter(fn ($a) => $a['timeline']['complete'])->count(), 'icon' => 'badge-check', 'tone' => 'emerald'],
        ];

        // What the applicant should do next, most urgent first.
        $todo = collect()
            ->merge($unpaid->map(fn ($a) => ['icon' => 'receipt', 'text' => 'Pay bill '.$a['bill']['bill_no'].' (₦'.number_format($a['bill']['amount'], 2).') for '.$a['ref'], 'url' => route('portal.bpa.applications.show', $a['ref']).'#bill']))
            ->merge($applications->filter(fn ($a) => $a['can_book_inspection'] && ! $a['inspection'])->map(fn ($a) => ['icon' => 'calendar-plus', 'text' => 'Book the site inspection for '.$a['ref'], 'url' => route('portal.bpa.inspections.index', ['application' => $a['ref']])]))
            ->merge($applications->filter(fn ($a) => $a['can_report_progress'])->map(fn ($a) => ['icon' => 'camera', 'text' => 'Upload construction progress for '.$a['ref'], 'url' => route('portal.bpa.progress.index', ['application' => $a['ref']])]))
            ->values();

        return view('portal.bpa.index', compact('applicant', 'applications', 'cards', 'todo'));
    }
}
