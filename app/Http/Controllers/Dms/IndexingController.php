<?php

namespace App\Http\Controllers\Dms;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Services\Dms\DmsFiles;
use App\Services\Dms\ShadowFiles;
use App\Services\Klaes\IndexedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DMS > Indexing > File Indexing Assistant: KAMMA's Shadow File Commissioning, copied from KLAES's
 * "Match Existing FileNo (Lands)" (LandsFileNoMatchingController): stats, matched records, and a Match pop-up that
 * picks a KLAES file, shows its KLAES indexing record and copies it into KAMMA's indexing table.
 */
class IndexingController extends Controller
{
    public function index(Request $request, DmsFiles $dms): View
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        $records = $dms->search($dms->query(), $search)
            ->orderByDesc('date_matched')->orderByDesc('time_matched')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('dms.indexing.index', ['records' => $records, 'stats' => $dms->indexingStats(), 'search' => $search, 'dms' => $dms]);
    }

    /** The KLAES file selector, with files KAMMA already matched marked. */
    public function lookup(Request $request, IndexedFiles $files): JsonResponse
    {
        $found = $files->search((string) $request->query('q', ''));
        $matched = FileIndexing::query()->live()->whereIn('klaes_file_indexing_id', array_column($found, 'id'))
            ->pluck('file_number', 'klaes_file_indexing_id');

        return response()->json(['files' => array_map(fn ($f) => $f + ['matched' => $matched->has($f['id'])], $found)]);
    }

    /** The KLAES indexing record of one file, as the Match pop-up shows it before matching. */
    public function klaes(int $id, ShadowFiles $shadow): JsonResponse
    {
        $row = $shadow->klaesRow($id);
        if ($row === null) {
            return response()->json(['message' => 'That file is not in KLAES indexing.'], 404);
        }
        $pick = fn ($k) => ($v = trim((string) ($row[$k] ?? ''))) === '' ? null : $v;

        return response()->json(['file' => [
            'id' => (int) $row['id'],
            'file_number' => $pick('file_number'),
            'file_title' => $pick('file_title'),
            'plot_number' => $pick('plot_number'),
            'district' => $pick('district'),
            'lga' => $pick('lga'),
            'location' => $pick('location'),
            'land_use_type' => $pick('land_use_type'),
            'plot_size' => $pick('plot_size'),
            'registry' => $pick('registry'),
            'batch_no' => $pick('batch_no'),
            'shelf_location' => $pick('shelf_location'),
            'serial_no' => $pick('serial_no'),
            'tracking_id' => $pick('tracking_id'),
            'already' => FileIndexing::query()->live()->where('klaes_file_indexing_id', $id)->first()?->date_matched?->format('jS M, Y'),
        ]]);
    }

    public function store(Request $request, ShadowFiles $shadow): JsonResponse
    {
        $data = $request->validate([
            'klaes_file_indexing_id' => ['required', 'integer'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
        $indexing = $shadow->commission((int) $data['klaes_file_indexing_id'], $request->user(), $data['remarks'] ?? null);

        return response()->json([
            'message' => "Successfully matched record: {$indexing->file_number}",
            'id' => $indexing->getKey(),
        ]);
    }

    public function show(FileIndexing $indexing, DmsFiles $dms): JsonResponse
    {
        $f = $dms->query()->findOrFail($indexing->getKey());

        return response()->json(['record' => [
            'file_number' => $f->file_number, 'file_title' => $f->file_title, 'plot_number' => $f->plot_number,
            'district' => $f->district, 'lga' => $f->lga, 'location' => $f->location, 'land_use_type' => $f->land_use_type,
            'plot_size' => $f->plot_size, 'registry' => $f->registry, 'batch_no' => $f->batch_no,
            'shelf_location' => $f->shelf_location, 'serial_no' => $f->serial_no, 'tracking_id' => $f->klaes_tracking_id,
            'matched_by' => $f->matched_by_name, 'date_matched' => $f->date_matched?->format('jS M, Y'),
            'time_matched' => substr((string) $f->time_matched, 0, 8), 'remarks' => $f->remarks,
            'pages' => $f->pages_count, 'typed' => $f->typed_count, 'status' => $dms->status($f),
            'tracking' => $f->tracker()->value('tracking_id'),
        ]]);
    }
}
