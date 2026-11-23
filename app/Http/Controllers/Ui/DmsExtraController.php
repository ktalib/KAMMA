<?php

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\PageTyping;
use App\Services\Dms\DmsFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

/**
 * DMS screens copied in layout from KLAES (SerialNo Grouping, Print Files Label, Print Sign In & Out Sheet, Blind Scanning,
 * PT Quality Control) over KAMMA's own shadow files (kamma_file_indexings / _scannings / _page_typings). Reads only;
 * grouping, blind-scan uploads and QC decisions post to the preview stub until their backend is built.
 */
class DmsExtraController extends Controller
{
    public function grouping(Request $request): View
    {
        [$files, $error] = $this->read(fn () => FileIndexing::query()->live()->latest('id')->get(), collect());
        $batches = $files->groupBy(fn ($f) => $f->batch_no ?: '')->map(fn (Collection $g, $batch) => [
            'batch' => $batch, 'files' => $g->count(), 'registry' => $g->pluck('registry')->filter()->unique()->implode(', '),
            'serials' => $g->pluck('serial_no')->filter()->sort()->values(), 'last' => $g->max('created_at'),
        ])->sortByDesc('last')->values();

        return view('ui.dms.grouping', ['files' => $files, 'batches' => $batches, 'error' => $error,
            'ungrouped' => $files->filter(fn ($f) => blank($f->batch_no) || blank($f->serial_no))->values()]);
    }

    public function labels(Request $request): View
    {
        $batch = trim((string) $request->query('batch', ''));
        $q = trim((string) $request->query('q', ''));
        [$files, $error] = $this->read(fn () => FileIndexing::query()->live()
            ->when($batch !== '', fn ($w) => $w->where('batch_no', $batch))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('file_number', 'like', "%{$q}%")->orWhere('file_title', 'like', "%{$q}%")))
            ->latest('id')->limit(200)->get(), collect());

        return view('ui.dms.labels', ['files' => $files, 'batch' => $batch, 'q' => $q, 'error' => $error, 'batches' => $this->batches()]);
    }

    public function labelSheet(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))->map(fn ($i) => (int) $i)->filter()->take(120)->all();
        [$files] = $this->read(fn () => FileIndexing::query()->live()->whereIn('id', $ids)->orderBy('id')->get(), collect());
        $format = $request->query('format') === 'a4' ? 'a4' : 'single';

        return view('ui.dms.label-sheet', ['files' => $files, 'format' => $format]);
    }

    public function signin(Request $request): View
    {
        $batch = trim((string) $request->query('batch', ''));
        $date = (string) $request->query('date', '');
        $files = collect();
        $error = null;
        if ($batch !== '' || $date !== '') {
            [$files, $error] = $this->read(fn () => FileIndexing::query()->live()
                ->when($batch !== '', fn ($w) => $w->where('batch_no', $batch))
                ->when($date !== '', fn ($w) => $w->whereDate('date_matched', $date))
                ->orderBy('serial_no')->orderBy('id')->get(), collect());
        }

        return view('ui.dms.signin', ['files' => $files, 'batch' => $batch, 'date' => $date, 'error' => $error, 'batches' => $this->batches()]);
    }

    public function blind(): View
    {
        return view('ui.dms.blind', ['registries' => $this->batches('registry')]);
    }

    public function qualityControl(Request $request, DmsFiles $dms): View
    {
        $status = in_array($request->query('status'), ['pending', 'passed', 'failed'], true) ? $request->query('status') : 'pending';
        [$counts, $error] = $this->read(fn () => PageTyping::query()->selectRaw('qc_status, COUNT(*) as n')->groupBy('qc_status')->pluck('n', 'qc_status')->all(), []);
        [$pages] = $this->read(fn () => PageTyping::query()->with(['indexing', 'scanning'])->where('qc_status', $status)->latest('id')->paginate(24)->withQueryString(), null);

        return view('ui.dms.qc', ['counts' => $counts, 'pages' => $pages, 'status' => $status, 'error' => $error]);
    }

    private function batches(string $column = 'batch_no'): array
    {
        [$list] = $this->read(fn () => FileIndexing::query()->live()->whereNotNull($column)->where($column, '<>', '')
            ->distinct()->orderBy($column)->pluck($column)->all(), []);

        return $list;
    }

    /** DMS tables may not exist yet on a database where the DMS migration has not run: show an empty screen, not a 500. */
    private function read(callable $fn, mixed $fallback): array
    {
        try {
            return [$fn(), null];
        } catch (Throwable $e) {
            report($e);

            return [$fallback, 'The KAMMA DMS tables could not be read ('.class_basename($e).').'];
        }
    }
}
