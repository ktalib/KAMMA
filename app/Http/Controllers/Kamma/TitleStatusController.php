<?php

namespace App\Http\Controllers\Kamma;

use App\Http\Controllers\Controller;
use App\Models\Kamma\TitleStatusUpdate;
use App\Services\Klaes\IndexedFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * KAMMA > Title Status Update (KLAES title_status: pick the land file, Title Type, Initiated By, Reason; the remark is
 * generated with KLAES's template). KAMMA keeps the record in kamma_title_status_updates only; the KLAES land file is
 * never flagged or decommissioned from here. The file is picked from KLAES (never typed) and its property details
 * (Plot No., District, LGA, Location) are copied from the KLAES indexing record.
 */
class TitleStatusController extends Controller
{
    public function index(Request $request): View
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $type = in_array($request->query('type'), [...TitleStatusUpdate::TYPES, 'Other'], true) ? $request->query('type') : null;

        $records = TitleStatusUpdate::query()
            ->when($type, fn ($w) => $w->where('title_type', $type))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('file_number', 'like', "%{$q}%")->orWhere('file_title', 'like', "%{$q}%")
                ->orWhere('title_type', 'like', "%{$q}%")->orWhere('title_type_other', 'like', "%{$q}%")
                ->orWhere('plot_number', 'like', "%{$q}%")->orWhere('district', 'like', "%{$q}%")))
            ->latest('id')->paginate(20)->withQueryString();

        return view('kamma.title-status', [
            'records' => $records, 'q' => $q, 'type' => $type,
            'types' => TitleStatusUpdate::TYPES, 'initiatedBy' => TitleStatusUpdate::INITIATED_BY,
            'initiatedByByType' => TitleStatusUpdate::INITIATED_BY_BY_TYPE,
        ]);
    }

    public function store(Request $request, IndexedFiles $files): RedirectResponse
    {
        $d = $request->validate([
            'klaes_file_indexing_id' => ['required', 'integer'],
            'title_type' => ['required', Rule::in([...TitleStatusUpdate::TYPES, 'Other'])],
            'title_type_other' => ['nullable', 'required_if:title_type,Other', 'string', 'max:200'],
            'initiated_by' => ['required', Rule::in([...TitleStatusUpdate::INITIATED_BY, 'Other'])],
            'initiated_by_other' => ['nullable', 'required_if:initiated_by,Other', 'string', 'max:200'],
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'klaes_file_indexing_id.required' => 'Select the land file.',
            'title_type_other.required_if' => 'Specify the title type.',
            'initiated_by_other.required_if' => 'Specify who initiated it.',
        ]);

        if ($d['title_type'] !== 'Other' && $d['initiated_by'] !== 'Other'
            && ! in_array($d['initiated_by'], TitleStatusUpdate::initiatedByFor($d['title_type']), true)) {
            throw ValidationException::withMessages(['initiated_by' => "{$d['title_type']} is initiated by ".implode(' or ', TitleStatusUpdate::initiatedByFor($d['title_type'])).'.']);
        }

        $file = $files->find((int) $d['klaes_file_indexing_id']);
        if ($file === null) {
            throw ValidationException::withMessages(['klaes_file_indexing_id' => 'That land file was not found in KLAES.']);
        }

        $clean = fn (?string $v) => $v === null ? null : trim(preg_replace('/\s+/', ' ', $v));
        $typeOther = $d['title_type'] === 'Other' ? $clean($d['title_type_other']) : null;
        $byOther = $d['initiated_by'] === 'Other' ? $clean($d['initiated_by_other']) : null;
        $typeLabel = $typeOther ?? $d['title_type'];

        $record = DB::transaction(function () use ($d, $file, $clean, $typeOther, $byOther, $typeLabel, $request) {
            $open = TitleStatusUpdate::query()->where('klaes_file_indexing_id', $file['id'])->where('title_type', $d['title_type'])
                ->when($typeOther !== null, fn ($w) => $w->where('title_type_other', $typeOther))->lockForUpdate()->exists();
            if ($open) {
                throw ValidationException::withMessages(['title_type' => "{$file['file_number']} already has a {$typeLabel} title status in KAMMA."]);
            }

            return TitleStatusUpdate::create([
                'file_number' => $file['file_number'],
                'klaes_file_indexing_id' => $file['id'],
                'file_title' => $file['file_title'],
                'title_type' => $d['title_type'],
                'title_type_other' => $typeOther,
                'initiated_by' => $d['initiated_by'],
                'initiated_by_other' => $byOther,
                'plot_number' => $file['plot_no'],
                'district' => $file['district'],
                'lga' => $file['lga'],
                'location' => $file['location'],
                'reason' => $clean($d['reason']),
                'remark' => self::remark($typeLabel, $byOther ?? $d['initiated_by'], $clean($d['reason']), (string) $file['file_title'], $file['file_number']),
                'status' => 'recorded',
                'created_by' => $request->user()->getKey(),
                'created_by_name' => $request->user()->name,
            ]);
        });

        return redirect()->route('kamma.title-status.index')->with('status', "Title status \"{$typeLabel}\" recorded on {$record->file_number}.");
    }

    /** KLAES TitleStatusService::generateRemark's general template. */
    public static function remark(string $type, string $initiatedBy, string $reason, string $holder, string $fileNo): string
    {
        $initiator = in_array($initiatedBy, ['Allottee', 'Applicant'], true) && $holder !== '' ? "{$holder} ({$initiatedBy})" : $initiatedBy;

        return "{$type} FileNo {$fileNo} was initiated by {$initiator} on ".now()->format('d/m/Y H:i')." due to {$reason}";
    }
}
