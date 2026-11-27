<?php

namespace App\Http\Controllers\Archive;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\Scanning;
use App\Services\Dms\DocWareActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Doc-WARE actions (KLAES File Digital Archive, user 2026-11-25): Delete File from the card menu (Super Admin only, as
 * in KLAES), and the viewer's Edit Mode: Delete page, Rotate (saved), Replace, QC Pass / Fail. Edit Type posts to
 * Page Typing (dms.pagetyping.save). Move to NR stays off until KAMMA has its own registries; Crop and Reassign are
 * still preview.
 */
class DocWareActionController extends Controller
{
    public function deleteFile(Request $request, FileIndexing $indexing, DocWareActions $actions): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Only a Super Admin can delete a file from Doc-WARE.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], ['reason.required' => 'Give the reason for deleting the file.']);

        $done = $actions->deleteFile($indexing, trim($data['reason']), $request->user());

        return redirect()->route('archive.index')->with('status', "{$indexing->file_number}: {$done['pages']} page(s) moved to the EDMS trash ({$done['typed']} typed). The indexing record is kept.");
    }

    public function deletePage(Request $request, Scanning $scan, DocWareActions $actions): RedirectResponse
    {
        $actions->deletePage($scan, $request->user());
        $left = Scanning::query()->where('file_indexing_id', $scan->file_indexing_id)->where('is_deleted', false)->exists();

        return ($left ? redirect()->route('archive.show', $scan->file_indexing_id) : redirect()->route('archive.index'))
            ->with('status', 'The page was moved to the EDMS trash.'.($left ? '' : ' The file has no pages left, so it is no longer in Doc-WARE.'));
    }

    public function rotate(Request $request, Scanning $scan, DocWareActions $actions): RedirectResponse
    {
        $data = $request->validate(['degrees' => ['required', 'integer']]);
        $actions->rotate($scan, (int) $data['degrees'], $request->user());

        return redirect()->route('archive.show', [$scan->file_indexing_id, 'page' => $scan->id])->with('status', 'The page was turned and saved. The original is kept in the EDMS trash.');
    }

    public function replace(Request $request, Scanning $scan, DocWareActions $actions): RedirectResponse
    {
        $request->validate(['image' => ['required', 'file', 'max:'.(int) config('kamma.dms_page_max_kb'), 'mimes:jpg,jpeg,png,gif,bmp,tif,tiff,webp,pdf']],
            ['image.required' => 'Choose the new image.']);
        $actions->replace($scan, $request->file('image'), $request->user());

        return redirect()->route('archive.show', [$scan->file_indexing_id, 'page' => $scan->id])->with('status', 'The page was replaced. The old image is kept in the EDMS trash.');
    }

    public function qc(Request $request, Scanning $scan, DocWareActions $actions): RedirectResponse
    {
        $data = $request->validate(['qc' => ['required', 'in:pass,fail']]);
        $actions->qc($scan, $data['qc']);

        return redirect()->route('archive.show', [$scan->file_indexing_id, 'page' => $scan->id])->with('status', 'Quality control: '.($data['qc'] === 'pass' ? 'Passed' : 'Failed').'.');
    }
}
