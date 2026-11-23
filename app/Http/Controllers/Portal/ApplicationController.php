<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Klaes\IndexedFiles;
use App\Support\LandUse;
use App\Support\Portal\PortalApplications;
use App\Support\Portal\PortalForm;
use App\Support\Portal\PortalSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Applicant portal: New request for a Planning Recommendation (land application or Post-CofO: Sub-Division, Merger,
 * Extension, Change of Purpose), the request page, track-by-number and a portal copy of the KLAES land-file selector.
 *
 * UI first (user 2026-11-23): Submit checks every field and answers "Saved (preview)". It does not create the request,
 * commission the Shadow File, raise the processing fee bill or keep the files.
 */
class ApplicationController extends Controller
{
    public function create(): View
    {
        return view('portal.applications.create', [
            'categories' => PortalForm::CATEGORIES,
            'types' => PortalForm::types(),
            'proposedUses' => [...array_keys(LandUse::PROPOSED), LandUse::OTHER],
            'landUseLabels' => LandUse::LABELS,
            'documents' => PortalForm::DOCUMENTS,
            'accept' => '.'.implode(',.', PortalForm::EXTENSIONS),
            'processingFee' => config('kamma.bills.processing_fee') !== null ? (int) config('kamma.bills.processing_fee') : null,
            'penalty' => (int) config('kamma.bills.no_bpa_penalty_multiplier', 4),
            'applicant' => PortalSession::applicant(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            // Names and addresses: single spaces (saved in capitals, as the paper forms ask). Phone and email as typed / lower case.
            'applicant_name' => $this->squash($request->input('applicant_name')),
            'contact_address' => $this->squash($request->input('contact_address')),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
            'phone' => is_string($request->input('phone')) ? trim($request->input('phone')) : $request->input('phone'),
        ]);

        $type = (string) $request->input('request_type');
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(PortalForm::CATEGORIES))],
            'request_type' => ['required', Rule::in(PortalForm::allTypes())],
            'klaes_file_indexing_id' => ['required', 'integer', 'min:1'],
            'merge_file_ids' => [Rule::requiredIf($type === 'Merger'), 'array', 'max:10'],
            'merge_file_ids.*' => ['integer', 'min:1', 'different:klaes_file_indexing_id', 'distinct'],
            'proposed_plots' => [Rule::requiredIf($type === 'Sub-Division'), 'nullable', 'integer', 'min:2', 'max:200'],
            'extension_area' => [Rule::requiredIf($type === 'Extension'), 'nullable', 'numeric', 'min:1', 'max:1000000'],
            'proposed_use' => [Rule::requiredIf($type === 'Change of Purpose'), 'nullable', Rule::in([...array_keys(LandUse::PROPOSED), LandUse::OTHER])],
            'proposed_use_other' => ['required_if:proposed_use,'.LandUse::OTHER, 'nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'building_exists' => ['required', Rule::in(['yes', 'no'])],
            'has_bpa' => ['required_if:building_exists,yes', 'nullable', Rule::in(['yes', 'no'])],
            'applicant_name' => ['required', 'string', 'min:3', 'max:300'],
            'contact_address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'regex:/^(\+?234|0)[789][01]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'documents' => ['nullable', 'array'],
            'documents.*' => [Rule::in(PortalForm::DOCUMENTS)],
            'documents_other' => [Rule::requiredIf(in_array(PortalForm::OTHER, (array) $request->input('documents', []), true)), 'nullable', 'string', 'max:200'],
            'files' => ['required', 'array', 'min:1', 'max:'.PortalForm::MAX_FILES],
            'files.*' => ['file', 'mimes:'.implode(',', PortalForm::EXTENSIONS), 'max:'.(int) config('kamma.upload_max_kb', 102400)],
            'declaration' => ['accepted'],
        ], [
            'klaes_file_indexing_id.required' => 'Find and pick your land file first.',
            'merge_file_ids.required' => 'Pick the other land file(s) to merge with.',
            'merge_file_ids.*.different' => 'A file cannot be merged with itself.',
            'phone.regex' => 'Enter a Nigerian mobile number, e.g. 08031234567.',
            'declaration.accepted' => 'Tick the declaration to submit.',
            'files.required' => 'Upload at least one document.',
        ], [
            'request_type' => 'type of request', 'proposed_plots' => 'number of proposed plots', 'extension_area' => 'extension area',
            'building_exists' => 'building on the land', 'has_bpa' => 'Building Plan Approval', 'applicant_name' => 'applicant name',
        ]);

        if (PortalForm::categoryOf($data['request_type']) !== $data['category']) {
            return back()->withInput()->withErrors(['request_type' => 'Pick a type of request from the chosen group.']);
        }

        $files = count($request->file('files', []));
        $penalty = $data['building_exists'] === 'yes' && ($data['has_bpa'] ?? null) === 'no';

        return redirect()->route('portal.dashboard')->with('status', sprintf(
            'Saved (preview): your %s request for %s was checked (%d document%s) but not submitted.%s Submitting opens with the portal backend.',
            $data['request_type'],
            $data['applicant_name'],
            $files,
            $files === 1 ? '' : 's',
            $penalty ? ' Note: a building without a Building Plan Approval makes the betterment fee ×'.(int) config('kamma.bills.no_bpa_penalty_multiplier', 4).'.' : '',
        ));
    }

    public function show(string $reference, PortalApplications $portal): View
    {
        $application = $portal->find($reference, PortalSession::applicant());
        abort_if($application === null, 404);

        return view('portal.applications.show', ['a' => $application]);
    }

    /** "Track a request" box: open one of the applicant's requests by its number. */
    public function track(Request $request, PortalApplications $portal): RedirectResponse
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:60']], [], ['number' => 'request number']);
        $number = mb_strtoupper(trim($data['number']));

        if ($portal->find($number, PortalSession::applicant($request)) === null) {
            return back()->withInput()->withErrors(['number' => 'No request of yours has that number. Check it and try again.']);
        }

        return redirect()->route('portal.applications.show', ['reference' => $number]);
    }

    /** Land-file selector: file numbers only (never the holder's name) and no phone or address in the answer. */
    public function fileSearch(Request $request, IndexedFiles $files): JsonResponse
    {
        $found = $files->search((string) $request->query('q', ''), 10, true);

        return response()->json(['files' => array_map(fn ($f) => $this->publicFile($f), $found)]);
    }

    public function fileShow(int $id, IndexedFiles $files): JsonResponse
    {
        $file = $files->find($id);

        return $file
            ? response()->json(['file' => $this->publicFile($file)])
            : response()->json(['message' => 'That land file was not found in KLAES.'], 404);
    }

    /** @param array<string, mixed> $file */
    private function publicFile(array $file): array
    {
        return array_intersect_key($file, array_flip([
            'id', 'file_number', 'file_title', 'plot_no', 'district', 'lga', 'location', 'plot_size', 'street_name',
            'land_use', 'land_use_code', 'title_status',
        ]));
    }

    private function squash(mixed $value): mixed
    {
        return is_string($value) ? Str::upper(trim(preg_replace('/\s+/u', ' ', $value))) : $value;
    }
}
