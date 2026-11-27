<?php

namespace App\Http\Controllers\Portal\Bpa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\BpaApplicationRequest as PortalApplicationRequest;
use App\Services\Bpa\SubmitApplication;
use App\Support\Portal\Bpa\BpaApplications as PortalApplications;
use App\Support\Bpa\BpaForm;
use App\Support\LandUse;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Applicant portal: New Building Plan Application (the staff wizard's steps, in applicant wording), the application
 * page, and a portal copy of the KLAES land-file selector.
 *
 * Since 2026-11-25 Submit saves through App\Services\Bpa\SubmitApplication, the staff form's service: the application,
 * its bill and the plans land in the staff Applications list, linked to the portal applicant. The demo applicant's
 * Submit is checked and answers "Saved (preview)".
 */
class ApplicationController extends Controller
{
    public function create(): View
    {
        return view('portal.bpa.create', [
            'proposedUses' => [...array_keys(LandUse::PROPOSED), LandUse::OTHER],
            'proposedCodes' => LandUse::PROPOSED,
            'landUseLabels' => LandUse::LABELS,
            'submissionTypes' => BpaForm::SUBMISSION_TYPES,
            'documents' => BpaForm::DOCUMENTS,
            'drawings' => BpaForm::DRAWINGS,
            'receivedAs' => PortalApplicationRequest::RECEIVED_AS,
            'accept' => '.'.implode(',.', array_merge(BpaForm::PLAN_EXTENSIONS, BpaForm::CAD_EXTENSIONS)),
            'feePerPlot' => (int) config('kamma.portal.bpa.fee_per_plot'),
            'applicationTypes' => BpaForm::APPLICATION_TYPES,
            'multiplier' => (int) config('kamma.portal.bpa.registration_multiplier', 4),
            'applicant' => PortalSession::applicant(),
        ]);
    }

    public function store(PortalApplicationRequest $request, SubmitApplication $submit): RedirectResponse
    {
        $data = $request->validated();
        $account = PortalSession::account($request);
        if ($account !== null) {
            $application = $submit->handle($data + [
                'submitted_by_name' => $account->name,
                'submitted_on' => now()->toDateString(),
            ], $request->file('plan_files', []), null, $account);

            return redirect()->route('portal.bpa.applications.show', ['reference' => $application->application_no])->with('status', sprintf(
                'Application %s received. Pay bill %s (₦%s) to move it on.',
                $application->application_no, $application->bill->bill_no, number_format((float) $application->bill->amount, 2),
            ));
        }

        $plots = (int) $data['number_of_plots'];
        $files = count($request->file('plan_files', []));
        // Registration (built without approval) pays 4 × the normal fee, as on the staff form.
        $multiplier = $data['application_type'] === BpaForm::REGISTRATION ? (int) config('kamma.portal.bpa.registration_multiplier', 4) : 1;

        return redirect()->route('portal.bpa.index')->with('status', sprintf(
            'Saved (preview): your %s for %s was checked (%d plan file%s, bill ₦%s) but not submitted. Submitting opens with the portal backend.',
            $data['application_type'],
            $data['developer_name'],
            $files,
            $files === 1 ? '' : 's',
            number_format($plots * (int) config('kamma.portal.bpa.fee_per_plot') * $multiplier, 2),
        ));
    }

    public function show(string $reference, PortalApplications $portal): View
    {
        $application = $portal->find($reference, PortalSession::applicant());
        abort_if($application === null, 404);

        return view('portal.bpa.show', ['a' => $application]);
    }

    /** "Track an application" box: open an application by its application or BP number. */
    public function track(Request $request): RedirectResponse
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:60']], [], ['number' => 'application or BP number']);

        return redirect()->route('portal.bpa.applications.show', ['reference' => mb_strtoupper(trim($data['number']))]);
    }
}
