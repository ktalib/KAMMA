<?php

namespace App\Http\Controllers\Dms;

use App\Http\Controllers\Controller;
use App\Models\Dms\BlindScan;
use App\Models\Dms\FileIndexing;
use App\Services\Dms\BlindScans;
use App\Services\Dms\EdmsFolders;
use App\Services\Dms\ScanUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DMS > Blind Scanning (copied from KLAES BlindScanningController, user 2026-11-25), on KAMMA shadow files:
 * Upload & Migrate (batches of a picked folder into EDMS/BLIND_SCAN/KAMMA_Raw/<file no.>), the Server Browser and
 * Upload History, the Blind Scanning Summary, page preview and delete; and the Scan Upload side (Upload Method =
 * Blind scan): the file's pending pages and Add, which takes them into the file.
 */
class BlindScanController extends Controller
{
    /** Pages per Migrate to Server request (the page sends a big folder in batches, as KLAES does). */
    public const BATCH = 20;

    public function index(Request $request, BlindScans $blind): View
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);

        return view('ui.dms.blind', [
            'root' => 'storage/EDMS/'.EdmsFolders::root(EdmsFolders::BLIND_SCAN),
            'folders' => $blind->folders($search),
            'summary' => $blind->summary((int) config('kamma.edms.summary_days', 30)),
            'history' => $blind->history(),
            'stats' => $blind->stats(),
            'search' => $search,
            'batch' => self::BATCH,
        ]);
    }

    public function migrate(Request $request, BlindScans $blind): JsonResponse
    {
        $max = (int) config('kamma.dms_page_max_kb');
        $data = $request->validate([
            'file_indexing_id' => ['required', 'integer'],
            'upload_type' => ['required', Rule::in([BlindScans::FULL, BlindScans::LF, BlindScans::IC])],
            'batch' => ['required', 'string', 'max:40'],
            'files' => ['required', 'array', 'min:1', 'max:'.self::BATCH],
            'files.*' => ['file', 'max:'.$max],
            'paths' => ['nullable', 'array'],
            'paths.*' => ['nullable', 'string', 'max:500'],
        ], ['files.required' => 'Pick the scanned folder (or the images) first.', 'file_indexing_id.required' => 'Select the file number first.']);

        $indexing = FileIndexing::query()->live()->find($data['file_indexing_id']);
        if ($indexing === null) {
            return response()->json(['message' => 'That file is not in the File Indexing Assistant. Match it there first.', 'errors' => ['file_indexing_id' => ['Pick the file again.']]], 422);
        }

        $result = $blind->store($indexing, $request->file('files'), array_values($data['paths'] ?? []), $data['upload_type'], $data['batch'], $request->user());

        return response()->json([
            'ok' => $result['saved'] !== [] || $result['skipped'] !== [],
            'folder' => 'storage/EDMS/'.EdmsFolders::root(EdmsFolders::BLIND_SCAN).'/'.EdmsFolders::folderName($indexing->file_number),
        ] + array_map('count', $result) + ['rejected_list' => array_slice($result['rejected'], 0, 10), 'failed_list' => array_slice($result['failed'], 0, 10)]);
    }

    /** Server Browser: the pages of one file folder. Scan Upload's Blind Scan Documents uses ?pending=1. */
    public function folder(Request $request, FileIndexing $indexing): JsonResponse
    {
        $pages = BlindScan::query()->where('file_indexing_id', $indexing->getKey())
            ->when($request->boolean('pending'), fn ($q) => $q->where('status', BlindScan::PENDING), fn ($q) => $q->where('status', '!=', 'deleted'))
            ->orderBy('relative_path')->get();

        return response()->json([
            'file_number' => $indexing->file_number,
            'folder' => 'storage/EDMS/'.EdmsFolders::root(EdmsFolders::BLIND_SCAN).'/'.EdmsFolders::folderName($indexing->file_number),
            'pages' => $pages->map(fn (BlindScan $p) => [
                'id' => $p->id, 'name' => basename($p->relative_path), 'path' => $p->relative_path, 'paper' => $p->paper_size,
                'type' => match ($p->paper_size) { 'IC' => 'Index card', 'A3' => 'Large format', default => 'Page' },
                'size' => (int) $p->file_size, 'modified' => $p->created_at?->format('d M Y H:i'), 'by' => $p->uploaded_by_name,
                'status' => $p->status, 'url' => route('dms.blind.image', $p), 'pdf' => str_contains((string) $p->mime, 'pdf'),
                'delete_url' => $p->status === BlindScan::PENDING ? route('dms.blind.destroy', $p) : null,
            ])->values(),
        ]);
    }

    public function image(BlindScan $scan, BlindScans $blind): StreamedResponse
    {
        abort_if($scan->status === 'deleted', 404);
        $disk = $blind->disk();
        abort_unless($disk->exists($scan->path()), 404, 'The blind-scan page is missing on disk.');

        return $disk->response($scan->path(), basename($scan->relative_path), ['Cache-Control' => 'private, max-age=86400']);
    }

    public function destroy(Request $request, BlindScan $scan, BlindScans $blind): JsonResponse
    {
        $blind->delete($scan, $request->user());

        return response()->json(['message' => basename($scan->getOriginal('relative_path')).' was moved to the EDMS trash.']);
    }

    /** Scan Upload, Upload Method = Blind scan, "Add": the picked pages become the file's scanned pages. */
    public function take(Request $request, ScanUploads $uploads): JsonResponse
    {
        $data = $request->validate([
            'file_indexing_id' => ['required', 'integer'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Tick the pages to add.']);
        $indexing = FileIndexing::query()->live()->findOrFail($data['file_indexing_id']);

        $rows = $uploads->fromBlindScans($indexing, array_map('intval', $data['ids']), $request->user());
        $indexing->touch();

        return response()->json(['message' => count($rows).' blind-scan page(s) added to '.$indexing->file_number.'.', 'count' => count($rows)]);
    }
}
