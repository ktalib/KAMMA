<?php

namespace App\Http\Controllers\Archive;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Services\Dms\DmsFiles;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Digital File Archive > File Digital Library – Doc-WARE, copied from KLAES's File Digital Archive: the four
 * counters, Search Archives filters, the Archived Files grid of cover cards, and a file viewer with its pages
 * grouped by page type.
 */
class DocWareController extends Controller
{
    public function index(Request $request, DmsFiles $dms): View
    {
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'land_use' => $request->query('land_use'), 'lga' => $request->query('lga'),
            'page_type' => $request->query('page_type'), 'year' => $request->query('year'),
            'sort' => $request->query('sort', 'recent'),
        ];
        $live = fn () => FileIndexing::query()->live()->whereHas('pageTypings');

        return view('archive.index', [
            'stats' => $dms->archiveStats(),
            'files' => $dms->archive($filters),
            'filters' => $filters,
            'landUses' => $live()->whereNotNull('land_use_type')->distinct()->orderBy('land_use_type')->pluck('land_use_type'),
            'lgas' => $live()->whereNotNull('lga')->distinct()->orderBy('lga')->pluck('lga'),
            'pageTypes' => PageTyping::query()->distinct()->orderBy('page_type')->pluck('page_type'),
            'years' => $live()->pluck('date_matched')->map(fn ($d) => substr((string) $d, 0, 4))->unique()->sortDesc()->values(),
            'dms' => $dms,
        ]);
    }

    public function show(FileIndexing $indexing, DmsFiles $dms): View
    {
        abort_if($indexing->is_deleted, 404);
        $file = $dms->query()->find($indexing->getKey());
        $pages = $indexing->scannings()->with('pageTyping')->get();
        abort_if($pages->isEmpty(), 404, 'This file has no scanned pages yet.');

        return view('archive.show', [
            'file' => $file, 'pages' => $pages, 'dms' => $dms,
            'groups' => $pages->groupBy(fn ($s) => $s->pageTyping?->typeLabel() ?? 'Not typed yet'),
            'tracker' => $indexing->tracker()->first(),
        ]);
    }
}
