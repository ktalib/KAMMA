<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Services\Dms\DmsFiles;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/** Digital File Archive > Dashboard and Virtual File/Folder System (Folio), over KAMMA's own DMS tables. */
class ArchiveExtraController extends Controller
{
    public function dashboard(DmsFiles $dms): View
    {
        try {
            $data = [
                'indexing' => $dms->indexingStats(),
                'scan' => $dms->scanStats(),
                'pt' => $dms->ptStats(),
                'archive' => $dms->archiveStats(),
                'types' => PageTyping::query()->selectRaw('page_type, COUNT(*) as n')->groupBy('page_type')->orderByDesc('n')->limit(8)->pluck('n', 'page_type')->all(),
                'byRegistry' => FileIndexing::query()->live()->selectRaw("COALESCE(registry, 'Not recorded') as k, COUNT(*) as n")->groupBy('registry')->pluck('n', 'k')->all(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            report($e);
            $data = ['indexing' => [], 'scan' => [], 'pt' => [], 'archive' => [], 'types' => [], 'byRegistry' => [], 'error' => 'The KAMMA DMS tables could not be read ('.class_basename($e).').'];
        }

        return view('ui.archive.dashboard', $data);
    }

    /** Folio: Registry > File > Document type > Pages, like KLAES's VFS. */
    public function folio(Request $request): View
    {
        $fileId = (int) $request->query('file');
        try {
            $tree = FileIndexing::query()->live()->whereHas('pageTypings')->orderBy('registry')->orderBy('file_number')
                ->get(['id', 'file_number', 'file_title', 'registry', 'plot_number', 'district', 'lga', 'location'])
                ->groupBy(fn ($f) => $f->registry ?: 'No registry');
            $file = $fileId ? FileIndexing::query()->live()->find($fileId) : null;
            $folders = $file ? PageTyping::query()->with('scanning')->where('file_indexing_id', $file->id)->orderBy('serial_number')->get()->groupBy('page_type') : collect();
            $error = null;
        } catch (Throwable $e) {
            report($e);
            [$tree, $file, $folders, $error] = [collect(), null, collect(), 'The KAMMA DMS tables could not be read ('.class_basename($e).').'];
        }

        return view('ui.archive.folio', compact('tree', 'file', 'folders', 'error'));
    }
}
