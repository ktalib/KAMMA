<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Portal\PortalForm;
use App\Support\Portal\PortalSession;
use App\Support\Portal\PortalTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The portal's public landing page (user 2026-11-23: "they should have a landing page, not straight to login ... that
 * will have those login and sign up buttons"). Laid out like the KLAES LAAS and MLPP-SG portal landings, copied from
 * the KADCA portal landing. A signed-in applicant goes straight to the dashboard.
 */
class LandingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (PortalSession::applicant($request) !== null) {
            return redirect()->route('portal.dashboard');
        }

        $fee = config('kamma.bills.processing_fee');

        return view('portal.landing', [
            'steps' => PortalTimeline::STEPS,
            'types' => PortalForm::types(),
            'documents' => array_values(array_filter(PortalForm::DOCUMENTS, fn ($d) => $d !== PortalForm::OTHER)),
            'fileTypes' => array_map('strtoupper', PortalForm::EXTENSIONS),
            'processingFee' => $fee !== null ? (int) $fee : null,
            'penalty' => (int) config('kamma.bills.no_bpa_penalty_multiplier', 4),
            'uploadMaxMb' => (int) (config('kamma.upload_max_kb') / 1024),
            'demoEnabled' => (bool) config('kamma.portal.demo_enabled'),
        ]);
    }
}
