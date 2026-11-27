<?php

namespace App\Http\Controllers\Portal\Bpa;

use App\Http\Controllers\Controller;
use App\Support\Portal\Bpa\BpaApplications as PortalApplications;
use App\Support\Portal\PortalSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Applicant portal: Construction Progress. The applicant photographs the building at each stage; the browser stamps
 * every photo with the phone's GPS position and the time (geocoded evidence, as the zonal officers' site photos).
 * Only approved applications can report progress: building before approval is penalised (4 x, briefing).
 *
 * UI first: the upload is checked and answers "Saved (preview)"; photos are not kept.
 */
class ProgressController extends Controller
{
    public function index(Request $request, PortalApplications $portal): View
    {
        $applications = $portal->forProgress(PortalSession::applicant($request));
        $selected = $applications->firstWhere('ref', (string) $request->query('application')) ?? $applications->first();

        return view('portal.bpa.progress', [
            'applications' => $applications,
            'selected' => $selected,
            'stages' => (array) config('kamma.portal.bpa.construction_stages'),
            'maxPhotos' => (int) config('kamma.portal.bpa.progress_max_photos'),
            'maxKb' => (int) config('kamma.portal.bpa.progress_photo_max_kb'),
        ]);
    }

    public function store(Request $request, PortalApplications $portal): RedirectResponse
    {
        $refs = $portal->forProgress(PortalSession::applicant($request))->pluck('ref')->all();
        $max = (int) config('kamma.portal.bpa.progress_max_photos');

        $data = $request->validate([
            'application' => ['required', Rule::in($refs)],
            'construction_stage' => ['required', Rule::in((array) config('kamma.portal.bpa.construction_stages'))],
            'photos' => ['required', 'array', 'min:1', 'max:'.$max],
            'photos.*' => ['image', 'max:'.(int) config('kamma.portal.bpa.progress_photo_max_kb')],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'captured_at' => ['required', 'date'],
            'photo_times' => ['nullable', 'array'],
            'photo_times.*' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'application.in' => 'Pick one of your approved applications.',
            'latitude.required' => 'Your location was not captured. Allow location on your phone and try again.',
            'photos.required' => 'Take or choose at least one photo.',
        ], ['photos.*' => 'photo']);

        $count = count($data['photos']);

        return redirect()->route('portal.bpa.progress.index', ['application' => $data['application']])->with('status', sprintf(
            'Saved (preview): %d photo%s for %s on %s, at %s, %s. Nothing was stored.',
            $count, $count === 1 ? '' : 's', $data['construction_stage'], $data['application'],
            number_format((float) $data['latitude'], 6), number_format((float) $data['longitude'], 6),
        ));
    }
}
