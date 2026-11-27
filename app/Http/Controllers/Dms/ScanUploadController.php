<?php

namespace App\Http\Controllers\Dms;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\Scanning;
use App\Services\Dms\DmsFiles;
use App\Services\Dms\ScanUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DMS > Scanning > Upload Indexed Files, copied from KLAES Scan Uploads ("Document Upload"): stats, Upload
 * Documents / Uploaded Documents tabs, Select Indexed File, PDF → JPG in the browser, drag-and-drop.
 * DMS Update > Scan More is the same screen limited to files that are already page typed (?url=scmore, as KLAES).
 */
class ScanUploadController extends Controller
{
    public function index(Request $request, DmsFiles $dms): View
    {
        $scanMore = $request->query('url') === 'scmore';
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        // KLAES Uploaded Documents: paper-size filter and the source tabs (Scanned Files / Module Uploads).
        $paper = in_array($request->query('paper'), ['A4', 'A5', 'A3', 'Letter', 'Legal', 'Custom'], true) ? (string) $request->query('paper') : '';
        $source = $request->query('source') === 'uploads' ? 'uploads' : 'scanned';
        $uploaded = $dms->search($dms->query()->whereHas('scannings', fn ($s) => $paper !== '' ? $s->where('paper_size', $paper) : $s), $search)
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $selected = null;
        if ($id = (int) $request->query('file')) {
            $selected = $dms->query()->find($id);
        }

        return view('dms.scanning.index', [
            'stats' => $dms->scanStats(), 'uploaded' => $uploaded, 'search' => $search, 'scanMore' => $scanMore, 'paper' => $paper, 'source' => $source,
            'selected' => $selected, 'dms' => $dms, 'tab' => $request->query('tab') === 'uploaded' ? 'uploaded' : 'upload',
        ]);
    }

    /** The Select Indexed File picker: KAMMA indexed files (Scan More: typed files only). */
    public function files(Request $request, DmsFiles $dms): JsonResponse
    {
        $q = $dms->search($dms->query(), (string) $request->query('q', ''));
        if ($request->boolean('typed')) {
            $q->whereHas('pageTypings');
        }

        return response()->json(['files' => $q->orderByDesc('id')->limit(20)->get()->map(fn (FileIndexing $f) => [
            'id' => $f->getKey(), 'file_number' => $f->file_number, 'file_title' => $f->file_title,
            'plot_number' => $f->plot_number, 'district' => $f->district, 'lga' => $f->lga,
            // The shared file selector (<x-ui.file-picker source="shadow">) reads plot_no and location.
            'plot_no' => $f->plot_number, 'location' => $f->location,
            'pages' => $f->pages_count, 'status' => $dms->status($f),
        ])]);
    }

    public function upload(Request $request, ScanUploads $uploads): JsonResponse
    {
        $max = (int) config('kamma.dms_page_max_kb');
        $data = $request->validate([
            'file_indexing_id' => ['required', 'integer'],
            'paper_size' => ['required', 'in:A4,A3'],
            'converted' => ['nullable', 'boolean'],
            'documents' => ['required', 'array', 'min:1', 'max:200'],
            'documents.*' => ['file', 'max:'.$max, 'mimes:jpg,jpeg,png,gif,bmp,tif,tiff,webp,pdf'],
        ], ['documents.required' => 'Choose at least one scanned page.']);

        $indexing = FileIndexing::query()->live()->findOrFail($data['file_indexing_id']);
        $rows = $uploads->store($indexing, $request->file('documents'), $data['paper_size'], (bool) ($data['converted'] ?? false), $request->user());
        $indexing->touch(); // Uploaded Documents lists the latest uploads first

        return response()->json(['message' => count($rows).' page(s) uploaded to '.$indexing->file_number.'.', 'count' => count($rows)]);
    }

    /** The pages of one file, for Uploaded Documents and Page Typing. */
    public function pages(FileIndexing $indexing): JsonResponse
    {
        return response()->json(['pages' => $indexing->scannings()->with('pageTyping')->get()->values()->map(fn (Scanning $s, $i) => [
            'id' => $s->getKey(), 'no' => $i + 1, 'name' => $s->original_filename, 'size' => $s->file_size,
            'paper' => $s->paper_size, 'more' => $s->is_scan_more, 'pdf' => str_contains((string) $s->mime, 'pdf'),
            'url' => route('dms.scans.image', $s), 'uploaded' => $s->created_at?->format('jS M Y, H:i'), 'by' => $s->uploaded_by_name,
            'delete_url' => route('dms.scans.destroy', $s),
            'type' => $s->pageTyping ? [
                'page_type' => $s->pageTyping->page_type, 'page_type_others' => $s->pageTyping->page_type_others,
                'page_subtype' => $s->pageTyping->page_subtype, 'page_subtype_others' => $s->pageTyping->page_subtype_others,
                'label' => $s->pageTyping->typeLabel(), 'sub' => $s->pageTyping->subtypeLabel(),
                'serial' => $s->pageTyping->serial_number, 'code' => $s->pageTyping->page_code,
            ] : null,
        ])]);
    }

    public function image(Scanning $scan): StreamedResponse
    {
        abort_if($scan->is_deleted, 404);
        $disk = Storage::disk(config('kamma.dms_disk'));
        abort_unless($disk->exists($scan->document_path), 404, 'The scanned page is missing on disk.');

        return $disk->response($scan->document_path, $scan->original_filename, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function destroy(Scanning $scan, ScanUploads $uploads): JsonResponse
    {
        $uploads->remove($scan);

        return response()->json(['message' => 'Page removed.']);
    }
}
