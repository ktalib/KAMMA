<?php

namespace App\Http\Controllers\Dms;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\Scanning;
use App\Services\Dms\DmsFiles;
use App\Services\Dms\PageTypes;
use App\Services\Dms\PageTypingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DMS > Document Page Types > PageTyping, copied from KLAES Page Typing: the four counters, the Pending / In
 * Progress / Completed / PageType More tabs, then the Typing view for one file. DMS Update > More Pages opens the
 * PageType More tab (?url=ptmore, as KLAES).
 */
class PageTypingController extends Controller
{
    public function index(Request $request, DmsFiles $dms): View
    {
        $tab = $request->query('url') === 'ptmore' ? 'more' : (string) $request->query('tab', 'pending');
        $tab = array_key_exists($tab, DmsFiles::PT_TABS) ? $tab : 'pending';
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);

        $files = $dms->search($dms->ptTab($dms->query(), $tab), $search)->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('dms.pagetyping.index', ['stats' => $dms->ptStats(), 'files' => $files, 'tab' => $tab, 'search' => $search, 'dms' => $dms]);
    }

    public function type(FileIndexing $indexing, DmsFiles $dms, PageTypes $types): View
    {
        abort_if($indexing->is_deleted, 404);
        abort_unless($indexing->scannings()->exists(), 404, 'This file has no scanned pages yet. Upload them first.');

        return view('dms.pagetyping.type', ['file' => $dms->query()->find($indexing->getKey()), 'types' => $types->all(), 'dms' => $dms]);
    }

    public function save(Request $request, Scanning $scan, PageTypingService $typing): JsonResponse
    {
        $data = $request->validate([
            'page_type' => ['required', 'string', 'max:150'],
            'page_type_others' => ['nullable', 'string', 'max:200'],
            'page_subtype' => ['nullable', 'string', 'max:200'],
            'page_subtype_others' => ['nullable', 'string', 'max:200'],
        ]);
        $row = $typing->save($scan, $data, $request->user());
        $scan->indexing?->touch();

        return response()->json(['message' => 'Page '.$row->page_number.' typed as '.$row->typeLabel().' ('.$row->page_code.').', 'code' => $row->page_code, 'serial' => $row->serial_number]);
    }
}
