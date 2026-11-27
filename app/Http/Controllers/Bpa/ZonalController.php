<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Models\Bpa\Application;
use App\Models\Bpa\Conveyance;
use App\Models\Bpa\Memo;
use App\Models\Bpa\SiteInspection;
use App\Models\Bpa\SiteInspectionPhoto;
use App\Models\Bpa\ZonalAssignment;
use App\Models\User;
use App\Services\Bpa\ZonalOffice;
use App\Services\Serials;
use App\Support\Bpa\BpaStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * KAMMA after the Deputy Director (Arch.): Head of Zonal Office (assign, review), the officer's Site Inspection Report,
 * Field Data Integration, Final Conveyance and the KAMMA Memo. Screens were built UI first (views under ui/kadca);
 * this controller makes them save. Workflow rules live in App\Services\Bpa\ZonalOffice.
 */
class ZonalController extends Controller
{
    public const CONDITIONS = [
        'Development is not commenced within the prescribed period',
        'The approved land use is changed without authorization;',
        'Substantial changes are made to the approved plans without approval',
        'False information or documents are discovered;',
        'Development is undertaken outside the approved plot or boundaries; or other applicable statutory requirements are not complied with',
    ];

    public const SCOPES = ['Initial Site Visit', 'Inspection / Site Assessment'];

    /* ── Head of Zonal Office ─────────────────────────────────────────────── */

    public function approval(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['awaiting', 'assigned', 'reports'], true) ? $request->query('tab') : 'awaiting';
        $stage = ['awaiting' => BpaStage::ZONAL_OFFICE, 'assigned' => BpaStage::INSPECTION, 'reports' => BpaStage::ZONAL_REVIEW][$tab];

        $files = Application::query()->where('stage', $stage)
            ->with(['assignments' => fn ($q) => $q->latest('id'), 'inspections' => fn ($q) => $q->latest('id')])
            ->latest('updated_at')->paginate(20)->withQueryString();

        $counts = Application::query()->whereIn('stage', [BpaStage::ZONAL_OFFICE, BpaStage::INSPECTION, BpaStage::ZONAL_REVIEW])
            ->selectRaw('stage, COUNT(*) as n')->groupBy('stage')->pluck('n', 'stage');

        return view('bpa.ui.zonal-approval', [
            'files' => $files, 'tab' => $tab, 'scopes' => self::SCOPES,
            'counts' => ['awaiting' => (int) ($counts[BpaStage::ZONAL_OFFICE] ?? 0), 'assigned' => (int) ($counts[BpaStage::INSPECTION] ?? 0),
                'reports' => (int) ($counts[BpaStage::ZONAL_REVIEW] ?? 0)],
        ]);
    }

    public function assign(Request $request, Application $application, ZonalOffice $zonal): RedirectResponse
    {
        $data = $request->validateWithBag('assign', [
            'officer_id' => ['required', 'integer'],
            'scope' => ['required', 'array', 'min:1'],
            'scope.*' => [Rule::in(self::SCOPES)],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'instructions' => ['nullable', 'string', 'max:3000'],
        ], ['officer_id.required' => 'Pick the inspecting officer.', 'scope.required' => 'Tick at least one part of the scope.']);

        $officer = User::query()->where('is_active', 1)->find($data['officer_id']);
        if (! $officer) {
            return back()->withErrors(['officer_id' => 'That officer is not an active staff account.'], 'assign')->withInput();
        }

        $assignment = $zonal->assign($application, ['officer' => $officer] + $data, $request->user());

        return redirect()->route('bpa.zonal-approval', ['tab' => 'assigned'])
            ->with('status', "{$application->bp_file_no} assigned to {$officer->name} ({$assignment->instruction_no}).");
    }

    public function review(Request $request, Application $application, ZonalOffice $zonal): RedirectResponse
    {
        $data = $request->validateWithBag('review', [
            'decision' => ['required', Rule::in(['approve', 'return', 'refer'])],
            'remarks' => ['nullable', 'required_unless:decision,approve', 'string', 'max:2000'],
        ], ['remarks.required_unless' => 'Give the reason.']);

        try {
            $moved = $zonal->review($application, $data['decision'], $data['remarks'] ?? null, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e->errorBag('review');
        }

        $word = ['approve' => 'report approved; the file goes to Final Conveyance', 'return' => 'report returned to the officer',
            'refer' => 'referred back to the Deputy Director (Arch.)'][$data['decision']];

        return redirect()->route('bpa.zonal-approval', ['tab' => 'reports'])->with('status', "{$moved->bp_file_no}: {$word}.");
    }

    /** The Instruction to Inspect, printable. */
    public function instruction(ZonalAssignment $assignment): View
    {
        return view('bpa.zonal.instruction', ['assignment' => $assignment->load('application')]);
    }

    /* ── Inspecting officer ───────────────────────────────────────────────── */

    public function inspection(Request $request): View
    {
        $files = $this->at(BpaStage::INSPECTION)->load(['assignments' => fn ($q) => $q->latest('id')]);
        $selected = $files->firstWhere('id', (int) $request->query('file'));
        $filed = SiteInspection::query()->with('application')->latest('filed_at')->limit(20)->get();

        return view('bpa.ui.inspection', ['files' => $files, 'selected' => $selected, 'filed' => $filed,
            'recommendations' => SiteInspection::RECOMMENDATIONS]);
    }

    public function storeInspection(Request $request, ZonalOffice $zonal): RedirectResponse
    {
        $in = fn (string $field, array $values) => Rule::in($values);
        $data = $request->validate([
            'bpa_application_id' => ['required', 'integer'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'visit_time' => ['nullable', 'date_format:H:i'],
            'person_met' => ['nullable', 'string', 'max:200'],
            'visit_notes' => ['nullable', 'string', 'max:3000'],
            'plot_no' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:150'],
            'cadastral_zone' => ['nullable', 'string', 'max:150'],
            'land_use' => ['required', 'string', 'max:150'],
            'purpose_clause' => ['nullable', 'string', 'max:300'],
            'plot_size' => ['nullable', 'string', 'max:100'],
            'density' => ['nullable', 'string', 'max:100'],
            'street_name' => ['nullable', 'string', 'max:200'],
            'nature_of_plot' => ['required', $in('', ['Well drained', 'Rocky', 'Water logged / marshy', 'Other'])],
            'nature_of_plot_other' => ['nullable', 'required_if:nature_of_plot,Other', 'string', 'max:200'],
            'slope' => ['required', $in('', ['Below 5% (relatively flat)', '5%-8% (sloppy)', '8%-12% (steep)', '12%-18% (very steep)', 'Above 18% (hilly)'])],
            'vacancy' => ['required', $in('', ['Vacant', 'Not vacant'])],
            'existing_development' => ['nullable', 'required_if:vacancy,Not vacant', 'string', 'max:3000'],
            'approved_earlier' => ['nullable', 'required_if:vacancy,Not vacant', $in('', ['Yes', 'No'])],
            'conformity' => ['nullable', $in('', ['Conforms', 'Does not conform'])],
            'non_conformity' => ['nullable', 'required_if:conformity,Does not conform', 'string', 'max:3000'],
            'level_of_service' => ['required', $in('', ['Fully serviced', 'Not serviced', 'Partially serviced'])],
            'level_of_service_details' => ['nullable', 'required_if:level_of_service,Partially serviced', 'string', 'max:300'],
            'front_setback' => ['nullable', 'numeric', 'min:0'], 'rear_setback' => ['nullable', 'numeric', 'min:0'],
            'left_setback' => ['nullable', 'numeric', 'min:0'], 'right_setback' => ['nullable', 'numeric', 'min:0'],
            'plot_frontage' => ['nullable', 'numeric', 'min:0'], 'plot_depth' => ['nullable', 'numeric', 'min:0'],
            'building_footprint' => ['nullable', 'numeric', 'min:0'], 'building_height' => ['nullable', 'numeric', 'min:0'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:60'],
            'recommendation' => ['required', Rule::in(SiteInspection::RECOMMENDATIONS)],
            'remarks' => ['nullable', Rule::requiredIf(fn () => $request->input('recommendation') !== SiteInspection::RECOMMEND_APPROVAL), 'string', 'max:3000'],
            'officer_rank' => ['nullable', 'string', 'max:150'],
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['file', 'image', 'max:15360'],
            'photo_lat' => ['nullable', 'array'], 'photo_lat.*' => ['nullable', 'numeric', 'between:-90,90'],
            'photo_lng' => ['nullable', 'array'], 'photo_lng.*' => ['nullable', 'numeric', 'between:-180,180'],
            'photo_taken_at' => ['nullable', 'array'], 'photo_taken_at.*' => ['nullable', 'date'],
        ], [
            'photos.required' => 'Add at least one geocoded site photograph.',
            'remarks.required' => 'Give remarks when the file is not recommended for approval.',
            '*.required_if' => 'This field is required for the answer chosen above.',
        ]);

        // Fields that do not apply to the answers chosen are not kept.
        if ($data['nature_of_plot'] !== 'Other') {
            $data['nature_of_plot_other'] = null;
        }
        if ($data['vacancy'] !== 'Not vacant') {
            $data['existing_development'] = $data['approved_earlier'] = $data['conformity'] = $data['non_conformity'] = null;
        }
        if (($data['conformity'] ?? null) !== 'Does not conform') {
            $data['non_conformity'] = null;
        }
        if ($data['level_of_service'] !== 'Partially serviced') {
            $data['level_of_service_details'] = null;
        }

        $photos = [];
        foreach ($request->file('photos', []) as $i => $file) {
            $photos[] = ['file' => $file, 'lat' => $data['photo_lat'][$i] ?? null, 'lng' => $data['photo_lng'][$i] ?? null,
                'taken_at' => $data['photo_taken_at'][$i] ?? null];
        }
        $application = Application::query()->findOrFail($data['bpa_application_id']);
        unset($data['bpa_application_id'], $data['photos'], $data['photo_lat'], $data['photo_lng'], $data['photo_taken_at']);

        $inspection = $zonal->fileReport($application, $data, $photos, $request->user());

        return redirect()->route('bpa.inspection.show', $inspection)
            ->with('status', "Inspection report on {$application->bp_file_no} filed and sent to the Head of Zonal Office.");
    }

    public function showInspection(SiteInspection $inspection): View
    {
        return view('bpa.zonal.inspection-show', ['inspection' => $inspection->load(['application', 'photos', 'assignment'])]);
    }

    public function photo(SiteInspectionPhoto $photo)
    {
        $disk = Storage::disk(config('kamma.bpa.uploads_disk'));
        abort_unless($disk->exists($photo->path), 404);

        return response()->file($disk->path($photo->path));
    }

    public function fieldData(): View
    {
        $files = $this->at(BpaStage::INSPECTION)->load(['assignments' => fn ($q) => $q->latest('id')]);
        $reports = SiteInspection::query()->with('application')->withCount('photos')->latest('filed_at')->limit(50)->get();

        return view('bpa.ui.field-data', ['files' => $files, 'reports' => $reports]);
    }

    /* ── Final Conveyance ─────────────────────────────────────────────────── */

    public function conveyance(Request $request): View
    {
        $files = $this->at(BpaStage::CONVEYANCE);
        $issued = Conveyance::query()->with('application')->latest('id')->limit(30)->get();

        return view('bpa.ui.conveyance', ['files' => $files, 'issued' => $issued, 'conditions' => self::CONDITIONS,
            'selected' => $files->firstWhere('id', (int) $request->query('file'))]);
    }

    public function storeConveyance(Request $request, ZonalOffice $zonal): RedirectResponse
    {
        $data = $request->validate([
            'bpa_application_id' => ['required', 'integer'],
            'applicant' => ['required', 'string', 'max:300'],
            'date_of_approval' => ['required', 'date'],
            'commencement_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after:commencement_date'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*' => [Rule::in(self::CONDITIONS)],
            'additional_conditions' => ['nullable', 'string', 'max:3000'],
            'declarant_name' => ['required', 'string', 'max:300'],
            'declarant_date' => ['nullable', 'date'],
            'authorized_officer' => ['required', 'string', 'max:300'],
            'officer_date' => ['nullable', 'date'],
        ], ['expiry_date.after' => 'The expiry date must be after the commencement date.', 'conditions.required' => 'Keep at least one condition.']);

        $application = Application::query()->findOrFail($data['bpa_application_id']);
        unset($data['bpa_application_id']);
        $conveyance = $zonal->convey($application, $data, $request->user());

        return redirect()->route('bpa.conveyance.show', $conveyance)
            ->with('status', "Development Permit {$conveyance->permit_no} raised. {$application->bp_file_no} is BPA approved.");
    }

    public function showConveyance(Conveyance $conveyance): View
    {
        return view('bpa.zonal.conveyance-show', ['conveyance' => $conveyance->load('application')]);
    }

    /* ── KAMMA Memo ───────────────────────────────────────────────────────── */

    public function memo(): View
    {
        return view('bpa.ui.memo', [
            'files' => Application::query()->whereNotNull('bp_file_no')->latest('commissioned_at')->limit(10)->get(),
            'memos' => Memo::query()->with('application')->latest('id')->limit(30)->get(),
        ]);
    }

    public function storeMemo(Request $request, Serials $serials): RedirectResponse
    {
        $data = $request->validate([
            'bpa_application_id' => ['required', 'integer'],
            'to' => ['required', 'string', 'max:300'],
            'through' => ['nullable', 'string', 'max:300'],
            'from' => ['required', 'string', 'max:300'],
            'memo_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:20000'],
            'recommendation' => ['nullable', 'string', 'max:5000'],
        ], ['bpa_application_id.required' => 'Pick the BP file the memo is about.']);

        $application = Application::query()->whereNotNull('bp_file_no')->find($data['bpa_application_id']);
        if (! $application) {
            return back()->withErrors(['bpa_application_id' => 'Pick a commissioned BP file.'])->withInput();
        }

        $memo = \Illuminate\Support\Facades\DB::transaction(fn () => $application->memos()->create($data + [
            'memo_no' => sprintf('KMA-MEMO/%s/%05d', now()->format('Y'), $serials->next('bpa_memo')),
            'created_by' => $request->user()->getKey(),
            'created_by_name' => $request->user()->name,
        ]));

        return redirect()->route('bpa.memo.show', $memo)->with('status', "Memo {$memo->memo_no} saved.");
    }

    public function showMemo(Memo $memo): View
    {
        return view('bpa.zonal.memo-show', ['memo' => $memo->load('application')]);
    }

    private function at(string $stage): Collection
    {
        return Application::query()->where('stage', $stage)->latest('updated_at')->limit(200)->get();
    }
}
