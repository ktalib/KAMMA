<?php

namespace App\Http\Controllers\Portal\Bpa;

use App\Http\Controllers\Controller;
use App\Support\Portal\Bpa\BpaApplications as PortalApplications;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Applicant portal: Schedule Site Inspection. Once the file is at the Zonal Office, the applicant asks for a visit on
 * a free slot; the zonal office confirms it and assigns the officer (Zonal Office is built last on the staff side).
 *
 * UI first: slots come from config('kamma.portal.bpa.inspection') (placeholder hours) and the request answers
 * "Saved (preview)"; nothing is booked.
 */
class InspectionController extends Controller
{
    public function index(Request $request, PortalApplications $portal): View
    {
        $applicant = PortalSession::applicant($request);
        $applications = $portal->forInspection($applicant);
        $selected = $applications->firstWhere('ref', (string) $request->query('application')) ?? $applications->first();

        return view('portal.bpa.inspections', [
            'applicant' => $applicant,
            'applications' => $applications,
            'selected' => $selected,
            'bookings' => $portal->forApplicant($applicant)->filter(fn ($a) => $a['inspection'])->values(),
            'calendar' => $this->calendar(),
        ]);
    }

    public function store(Request $request, PortalApplications $portal): RedirectResponse
    {
        $refs = $portal->forInspection(PortalSession::applicant($request))->pluck('ref')->all();
        $calendar = $this->calendar();

        $data = $request->validate([
            'application' => ['required', Rule::in($refs)],
            'date' => ['required', 'date_format:Y-m-d', Rule::in(array_keys($calendar['days']))],
            'time' => ['required', Rule::in($calendar['slots'])],
            'contact_phone' => ['required', 'string', 'regex:/^(\+?234|0)[789][01]\d{8}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'application.in' => 'Pick an application that is at the Zonal Office.',
            'date.in' => 'Pick a day shown as open on the calendar.',
            'contact_phone.regex' => 'Enter a Nigerian mobile number, e.g. 08031234567.',
        ], ['contact_phone' => 'phone number on site']);

        return redirect()->route('portal.bpa.inspections.index', ['application' => $data['application']])->with('status', sprintf(
            'Saved (preview): a visit for %s on %s at %s was checked but not booked.',
            $data['application'], Carbon::parse($data['date'])->format('l j F Y'), $data['time'],
        ));
    }

    /** Bookable days (date => label) and slots, from the placeholder settings. */
    private function calendar(): array
    {
        $cfg = (array) config('kamma.portal.bpa.inspection');
        $first = now()->startOfDay()->addDays((int) ($cfg['lead_days'] ?? 2));
        $last = now()->startOfDay()->addDays((int) ($cfg['horizon_days'] ?? 30));
        $weekdays = array_map('intval', (array) ($cfg['weekdays'] ?? [1, 2, 3, 4, 5]));

        $days = [];
        for ($day = $first->copy(); $day->lte($last); $day->addDay()) {
            if (in_array($day->dayOfWeekIso, $weekdays, true)) {
                $days[$day->toDateString()] = $day->format('D j M');
            }
        }

        return ['days' => $days, 'slots' => array_values((array) ($cfg['slots'] ?? [])), 'first' => $first->toDateString(), 'last' => $last->toDateString()];
    }
}
