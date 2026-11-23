<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Portal\PortalApplications;
use App\Support\Portal\PortalSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Applicant portal: My requests (dashboard), Bills and Notifications. */
class DashboardController extends Controller
{
    public function index(Request $request, PortalApplications $portal): View
    {
        $applicant = PortalSession::applicant($request);
        $applications = $portal->forApplicant($applicant);

        $unpaid = $applications->flatMap(fn ($a) => $a['outstanding']);
        $cards = [
            ['label' => 'My requests', 'value' => $applications->count(), 'icon' => 'folder-open', 'tone' => 'emerald'],
            ['label' => 'Awaiting payment', 'value' => $applications->filter(fn ($a) => $a['outstanding'] !== [])->count(), 'icon' => 'receipt', 'tone' => 'amber',
                'sub' => $unpaid->isNotEmpty() ? '₦'.number_format($unpaid->sum('amount'), 2).' to pay' : null],
            ['label' => 'In progress', 'value' => $applications->filter(fn ($a) => $a['outstanding'] === [] && ! $a['timeline']['complete'])->count(), 'icon' => 'hourglass', 'tone' => 'sky'],
            ['label' => 'Returned to the Ministry', 'value' => $applications->filter(fn ($a) => $a['timeline']['complete'])->count(), 'icon' => 'badge-check', 'tone' => 'teal'],
        ];

        // What the applicant should do next, most urgent first.
        $todo = $applications->flatMap(fn ($a) => collect($a['outstanding'])->map(fn ($b) => [
            'icon' => $b['kind'] === 'betterment' ? 'landmark' : 'receipt',
            'text' => 'Pay '.mb_strtolower($b['bill_type']).' bill '.$b['bill_no'].' (₦'.number_format($b['amount'], 2).') for '.$a['ref'],
            'url' => route('portal.applications.show', $a['ref']).'#bills',
        ]))->values();

        return view('portal.dashboard', compact('applicant', 'applications', 'cards', 'todo'));
    }

    public function bills(Request $request, PortalApplications $portal): View
    {
        return view('portal.bills', ['bills' => $portal->bills(PortalSession::applicant($request))]);
    }

    public function notifications(Request $request, PortalApplications $portal): View
    {
        return view('portal.notifications', ['events' => $portal->notifications(PortalSession::applicant($request))]);
    }
}
