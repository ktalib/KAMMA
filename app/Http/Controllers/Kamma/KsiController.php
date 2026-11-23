<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Kamma\KsiReport;
use App\Services\Klaes\KsiLookups;
use App\Support\KammaKinds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * KSI (KAMMA Site Inspection) and Planning Recommendation, copied from KLAES Physical Planning
 * (physical_planning/conversion|sltr/planning_recommendation + partials/jsi_form, and master_jsi): "it's not JSI in
 * KAMMA" (briefing 2026-11-22), and the Director of Physical Planning is the MD KAMMA. The list keeps KLAES's columns
 * and figure cards; the form keeps KLAES's four steps (Application Details, Boundary & Inspection, Measurements &
 * Utilities, Land Use & Review) as a wizard with a review step, and adds KAMMA's two penalty questions (building on site,
 * BPA approved) that drive the 4× betterment rule. Dropdowns come from KLAES's JSI lookups (App\Services\Klaes\KsiLookups).
 */
class KsiController extends Controller
{
    public const COMPLIANCE = ['Obtainable', 'Not Obtainable'];

    public const SERVICES = ['Water', 'Electricity', 'Drainage', 'Road', 'None'];

    public function index(Request $request, string $kind): View
    {
        return $this->list($request, $kind);
    }

    public function masterIndex(Request $request): View
    {
        return $this->list($request, 'master');
    }

    public function create(Request $request, string $kind, KsiLookups $lookups): View
    {
        return $this->form($request, $kind, $lookups);
    }

    public function masterCreate(Request $request, KsiLookups $lookups): View
    {
        return $this->form($request, 'master', $lookups);
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        return $this->save($request, $kind);
    }

    public function masterStore(Request $request): RedirectResponse
    {
        return $this->save($request, 'master');
    }

    public function show(string $kind, int $report): View
    {
        return $this->detail($kind, $report);
    }

    public function masterShow(int $report): View
    {
        return $this->detail('master', $report);
    }

    private function list(Request $request, string $kind): View
    {
        $k = KammaKinds::get($kind);
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $compliance = in_array($request->query('compliance'), self::COMPLIANCE, true) ? $request->query('compliance') : null;
        $base = fn () => KsiReport::query()->where('kind', $kind);

        $reports = $base()
            ->when($compliance, fn ($w) => $w->where('compliance_status', $compliance))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('file_number', 'like', "%{$q}%")->orWhere('applicant_name', 'like', "%{$q}%")
                ->orWhere('plot_number', 'like', "%{$q}%")->orWhere('tp_no', 'like', "%{$q}%")->orWhere('lpkn_no', 'like', "%{$q}%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.ksi.index', [
            'k' => $k, 'kind' => $kind, 'reports' => $reports, 'q' => $q, 'compliance' => $compliance,
            'stats' => [
                'total' => $base()->count(),
                'obtainable' => $base()->where('compliance_status', 'Obtainable')->count(),
                'not_obtainable' => $base()->where('compliance_status', 'Not Obtainable')->count(),
                'approved' => $base()->where('md_decision', 'approved')->count(),
            ],
        ]);
    }

    private function form(Request $request, string $kind, KsiLookups $lookups): View
    {
        $file = $request->integer('file') ? FileIndexing::query()->live()->find($request->integer('file')) : null;

        return view('kamma.ksi.create', [
            'k' => KammaKinds::get($kind), 'kind' => $kind, 'file' => $file,
            'landUses' => $lookups->landUses(), 'purposes' => $lookups->purposes(), 'roads' => $lookups->roadCategories(),
            'reservations' => $lookups->reservations(), 'landUseTypes' => $lookups->landUseTypes(),
            'services' => self::SERVICES, 'compliance' => self::COMPLIANCE, 'postCofo' => KammaKinds::POST_COFO,
        ]);
    }

    private function save(Request $request, string $kind): RedirectResponse
    {
        KammaKinds::get($kind);
        $yesNo = ['required', Rule::in(['1', '0'])];
        $d = $request->validate([
            'file_indexing_id' => ['required', 'integer'],
            'post_cofo_type' => [Rule::requiredIf($kind === 'master'), 'nullable', Rule::in(KammaKinds::POST_COFO)],
            'inspection_date' => ['required', 'date', 'before_or_equal:today'],
            'applicant_name' => ['required', 'string', 'max:300'],
            'applicant_address' => ['required', 'string', 'max:500'],
            'lpkn_no' => ['nullable', 'string', 'max:100'], 'tp_no' => ['nullable', 'string', 'max:100'], 'scheme_no' => ['nullable', 'string', 'max:100'],
            'boundary_north' => ['required', 'string', 'max:300'], 'boundary_east' => ['required', 'string', 'max:300'],
            'boundary_south' => ['required', 'string', 'max:300'], 'boundary_west' => ['required', 'string', 'max:300'],
            'existing_structure' => $yesNo, 'existing_structure_dims' => ['nullable', 'required_if:existing_structure,1', 'string', 'max:200'],
            'service' => ['required', 'string', 'max:191'], 'private_layout_no' => ['nullable', 'string', 'max:100'],
            'detailed_land_use' => ['required', 'string', 'max:191'],
            'purpose' => ['required', 'string', 'max:191'], 'purpose_other' => ['nullable', 'required_if:purpose,Other', 'string', 'max:191'],
            'road_category' => ['required', 'string', 'max:191'], 'road_category_other' => ['nullable', 'required_if:road_category,Other', 'string', 'max:191'],
            'conformity' => $yesNo, 'accessibility' => $yesNo, 'reservation_required' => $yesNo,
            'existing_reservation' => ['nullable', 'string', 'max:1000'],
            'recommended_reservation' => ['nullable', 'required_if:reservation_required,1', 'string', 'max:1000'],
            'recommended_reservation_other' => ['nullable', 'required_if:recommended_reservation,Other', 'string', 'max:191'],
            'reservation_meters' => ['nullable', 'string', 'max:50'],
            'adequate_size_requirement' => $yesNo, 'land_traversing_utility' => $yesNo,
            'existing_site_length' => ['nullable', 'numeric', 'min:0'], 'existing_site_width' => ['nullable', 'numeric', 'min:0'], 'existing_site_area' => ['required', 'numeric', 'min:0'],
            'recommended_site_length' => ['nullable', 'numeric', 'min:0'], 'recommended_site_width' => ['nullable', 'numeric', 'min:0'], 'recommended_site_area' => ['required', 'numeric', 'min:0'],
            'has_extension' => $yesNo, 'extension_dims' => ['nullable', 'required_if:has_extension,1', 'string', 'max:100'],
            'extension_site_area' => ['nullable', 'required_if:has_extension,1', 'numeric', 'min:0'],
            'units_count' => [Rule::requiredIf($kind === 'st'), 'nullable', 'integer', 'min:1'],
            'building_exists' => $yesNo, 'bpa_approved' => ['nullable', 'required_if:building_exists,1', Rule::in(['1', '0'])],
            'need_for_eia' => $yesNo, 'available_on_ground' => $yesNo,
            'prevailing_land_use' => ['required', 'string', 'max:191'], 'applied_land_use' => ['required', 'string', 'max:191'],
            'compliance_status' => ['required', Rule::in([...self::COMPLIANCE, 'Other'])], 'compliance_status_other' => ['nullable', 'required_if:compliance_status,Other', 'string', 'max:191'],
            'inspection_officer' => ['required', 'string', 'max:300'], 'officer_rank' => ['nullable', 'string', 'max:150'],
            'additional_observations' => ['required', 'string', 'max:5000'],
        ], [
            'file_indexing_id.required' => 'Select the shadow file inspected.',
            '*.required_if' => 'This is required for the answer given.',
        ]);

        $file = FileIndexing::query()->live()->findOrFail($d['file_indexing_id']);
        $other = fn (string $k) => ($d[$k] ?? null) === 'Other' ? trim((string) $d[$k.'_other']) : ($d[$k] ?? null);
        $clean = fn (?string $v) => $v === null ? null : trim(preg_replace('/\s+/', ' ', $v));

        $report = KsiReport::create([
            'kind' => $kind, 'post_cofo_type' => $d['post_cofo_type'] ?? null,
            'file_indexing_id' => $file->id, 'klaes_file_indexing_id' => $file->klaes_file_indexing_id, 'file_number' => $file->file_number,
            'inspection_date' => $d['inspection_date'],
            'applicant_name' => $clean($d['applicant_name']), 'applicant_address' => $clean($d['applicant_address']),
            'lpkn_no' => $d['lpkn_no'] ?? null, 'tp_no' => $d['tp_no'] ?? null, 'scheme_no' => $d['scheme_no'] ?? null,
            // Property details come from the shadow file (KLAES's indexing record), never retyped.
            'plot_number' => $file->plot_number, 'district' => $file->district, 'lga' => $file->lga, 'location' => $file->location,
            'boundary_north' => $clean($d['boundary_north']), 'boundary_east' => $clean($d['boundary_east']),
            'boundary_south' => $clean($d['boundary_south']), 'boundary_west' => $clean($d['boundary_west']),
            'existing_structure' => $d['existing_structure'] === '1', 'existing_structure_dims' => $d['existing_structure_dims'] ?? null,
            'service' => $d['service'], 'private_layout_no' => $d['private_layout_no'] ?? null, 'detailed_land_use' => $d['detailed_land_use'],
            'purpose' => $other('purpose'), 'road_category' => $other('road_category'),
            'conformity' => $d['conformity'] === '1', 'accessibility' => $d['accessibility'] === '1', 'reservation_required' => $d['reservation_required'] === '1',
            'existing_reservation' => $d['existing_reservation'] ?? null, 'recommended_reservation' => $other('recommended_reservation'),
            'reservation_meters' => $d['reservation_meters'] ?? null,
            'adequate_size_requirement' => $d['adequate_size_requirement'] === '1', 'land_traversing_utility' => $d['land_traversing_utility'] === '1',
            'existing_site_length' => $d['existing_site_length'] ?? null, 'existing_site_width' => $d['existing_site_width'] ?? null, 'existing_site_area' => $d['existing_site_area'],
            'recommended_site_length' => $d['recommended_site_length'] ?? null, 'recommended_site_width' => $d['recommended_site_width'] ?? null, 'recommended_site_area' => $d['recommended_site_area'],
            'has_extension' => $d['has_extension'] === '1', 'extension_dims' => $d['extension_dims'] ?? null, 'extension_site_area' => $d['extension_site_area'] ?? null,
            'units_count' => $d['units_count'] ?? null,
            'building_exists' => $d['building_exists'] === '1', 'bpa_approved' => ($d['bpa_approved'] ?? '0') === '1',
            'need_for_eia' => $d['need_for_eia'] === '1', 'available_on_ground' => $d['available_on_ground'] === '1',
            'prevailing_land_use' => $d['prevailing_land_use'], 'applied_land_use' => $d['applied_land_use'], 'compliance_status' => $other('compliance_status'),
            'inspection_officer' => $clean($d['inspection_officer']), 'officer_rank' => $d['officer_rank'] ?? null,
            'additional_observations' => trim($d['additional_observations']),
            'status' => 'submitted', 'created_by' => $request->user()->getKey(), 'created_by_name' => $request->user()->name,
        ]);

        return redirect()->to($this->showUrl($report))->with('status', "KSI report saved on {$report->file_number} and sent for the MD KAMMA's approval.");
    }

    private function detail(string $kind, int $id): View
    {
        $report = KsiReport::query()->with(['bills', 'file'])->where('kind', $kind)->findOrFail($id);

        return view('kamma.ksi.show', ['k' => KammaKinds::get($kind), 'kind' => $kind, 'r' => $report]);
    }

    public static function showUrl(KsiReport $r): string
    {
        return $r->kind === 'master' ? route('kamma.master-ksi.show', $r->id) : route('kamma.pr.show', [$r->kind, $r->id]);
    }
}
