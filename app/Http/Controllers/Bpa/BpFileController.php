<?php

namespace App\Http\Controllers\Bpa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bpa\StoreBpFileRequest;
use App\Models\Bpa\BpFile;
use App\Models\Bpa\BpGrouping;
use App\Models\Bpa\BpZone;
use App\Services\Bpa\BpFiles;
use App\Services\Bpa\BpNumbers;
use App\Services\Klaes\IndexedFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * KAMMA > Development Permit > Building Plan Files (user briefing 2026-11-25): index existing BP files (number from
 * the grouping), commission new ones (next serial), the register (one table, a Land File band and a Building Plan
 * band), and the grouping by year. From the register a BP file goes on to the Application section.
 */
class BpFileController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    /** DMS > Indexing (user 2026-11-25): the existing BP files already indexed, with their dashboard; the form is one click on. */
    public function indexed(Request $request): View
    {
        return $this->listing($request, BpFile::INDEXED);
    }

    /** Building Plan Files > Commissioning: the new BP files already commissioned, with their dashboard, before the form. */
    public function commissioned(Request $request): View
    {
        return $this->listing($request, BpFile::COMMISSIONED);
    }

    /** The register (scope null = every BP file) or one origin's list page. */
    private function listing(Request $request, ?string $scope): View
    {
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);
        $zone = (string) $request->query('zone', '');
        $origin = $scope ?? (string) $request->query('origin', '');
        $year = (int) $request->query('year', 0);
        $zones = BpZone::query()->active()->get();

        $files = BpFile::query()->live()->with('application:id,application_no,status,application_type')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $search).'%';
                if (DB::getDriverName() !== 'sqlsrv') {
                    $like = '%'.$search.'%';
                }
                $q->where(function ($q) use ($like) {
                    foreach (['bp_file_no', 'land_file_number', 'land_file_title', 'bp_file_title', 'plot_no', 'district', 'lga', 'location'] as $col) {
                        $q->orWhere($col, 'like', $like);
                    }
                });
            })
            ->when($zone === 'none', fn ($q) => $q->whereNull('bp_zone_id'))
            ->when(ctype_digit($zone), fn ($q) => $q->where('bp_zone_id', (int) $zone))
            ->when(in_array($origin, [BpFile::INDEXED, BpFile::COMMISSIONED], true), fn ($q) => $q->where('origin', $origin))
            ->when($year > 0, fn ($q) => $q->where('bp_year', $year))
            ->orderByDesc('indexed_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $live = BpFile::query()->live()->when($scope, fn ($q) => $q->where('origin', $scope));
        $otherIds = $zones->where('is_other', true)->pluck('id');
        $stats = [
            'today' => (clone $live)->where('indexed_at', '>=', today())->count(),
            'month' => (clone $live)->where('indexed_at', '>=', now()->startOfMonth())->count(),
            'total' => (clone $live)->count(),
            'indexed' => (clone $live)->where('origin', BpFile::INDEXED)->count(),
            'commissioned' => (clone $live)->where('origin', BpFile::COMMISSIONED)->count(),
            'zone_other' => (clone $live)->whereIn('bp_zone_id', $otherIds)->count(),
            'no_application' => (clone $live)->whereNull('bpa_application_id')->count(),
            'not_in_klaes' => (clone $live)->where('land_in_klaes', false)->count(),
        ];

        return view('bpa.bp-files.index', compact('files', 'search', 'zone', 'origin', 'year', 'zones', 'stats', 'otherIds', 'scope'));
    }

    public function createIndexed(): View
    {
        return $this->form('indexed');
    }

    public function createCommissioned(BpNumbers $numbers): View
    {
        return $this->form('commissioned', null, $numbers->peekCommissioning((int) now()->format('Y')));
    }

    public function edit(BpFile $bpFile): View
    {
        abort_if($bpFile->is_deleted, 404);

        return $this->form('edit', $bpFile);
    }

    public function storeIndexed(StoreBpFileRequest $request, BpFiles $files): RedirectResponse
    {
        $file = $files->index($request->validated(), $request->user());

        return redirect()->route('bpa.bp-files.indexed')->with('status', "{$file->bp_file_no} indexed on land file {$file->land_file_number}.")
            ->with('bp_file_saved', $file->id);
    }

    public function storeCommissioned(StoreBpFileRequest $request, BpFiles $files): RedirectResponse
    {
        $file = $files->commission($request->validated(), $request->user());

        return redirect()->route('bpa.bp-files.commissioned')->with('status', "{$file->bp_file_no} commissioned on land file {$file->land_file_number}.")
            ->with('bp_file_saved', $file->id);
    }

    public function update(StoreBpFileRequest $request, BpFile $bpFile, BpFiles $files): RedirectResponse
    {
        abort_if($bpFile->is_deleted, 404);
        $files->update($bpFile, $request->validated(), $request->user());

        $back = $bpFile->origin === BpFile::INDEXED ? 'bpa.bp-files.indexed' : 'bpa.bp-files.commissioned';

        return redirect()->route($back)->with('status', "{$bpFile->bp_file_no} updated.");
    }

    /**
     * BP file selector (DMS: Blind Scanning, Scan Upload, Doc-WARE): BP files on the register by BP number, land file number
     * or title. Same answer shape as the KLAES land-file search so <x-ui.file-picker source="bp"> reads it.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['files' => []]);
        }

        $files = BpFile::query()->live()
            ->where(fn ($w) => $w->where('bp_file_no', 'like', "%{$q}%")->orWhere('land_file_number', 'like', "%{$q}%")
                ->orWhere('bp_file_title', 'like', "%{$q}%")->orWhere('land_file_title', 'like', "%{$q}%"))
            ->orderByDesc('bp_year')->orderByDesc('bp_serial')
            ->limit(15)
            ->get()
            ->map(fn (BpFile $f) => [
                'id' => $f->id,
                'file_number' => $f->bp_file_no,
                'file_title' => $f->bp_file_title ?: $f->land_file_title,
                'land_file_number' => $f->land_file_number,
                'plot_no' => $f->plot_no,
                'district' => $f->district,
                'lga' => $f->lga,
                'location' => $f->location,
            ]);

        return response()->json(['files' => $files]);
    }

    /** The left half: a land file's KLAES indexing record, with the BP files already on it. */
    public function land(int $id, IndexedFiles $klaes): JsonResponse
    {
        $file = $klaes->find($id);
        if ($file === null) {
            return response()->json(['message' => 'Land file not found in KLAES.'], 404);
        }

        return response()->json(['file' => $file + [
            'tracking_id' => $klaes->trackingId($file['file_number']),
            'bp_files' => BpFile::query()->live()->where('klaes_file_indexing_id', $id)->orderBy('bp_year')->orderBy('bp_serial')->pluck('bp_file_no')->all(),
        ]]);
    }

    /** Live check of a BP number while it is typed on the indexing form. */
    public function checkNumber(Request $request): JsonResponse
    {
        $year = (int) $request->query('year');
        $serial = (int) $request->query('serial');
        if ($year < 1900 || $serial < 1) {
            return response()->json(['state' => 'incomplete']);
        }
        $number = BpNumbers::format($year, $serial);
        $row = BpGrouping::query()->where('year', $year)->where('serial', $serial)->first();
        if ($row === null) {
            $has = BpGrouping::query()->where('year', $year)->exists();

            return response()->json(['state' => 'missing', 'number' => $number, 'message' => $has
                ? "{$number} is outside the {$year} grouping."
                : "No grouping for {$year}: existing files are indexed from the grouping; a new file is commissioned."]);
        }
        if ($row->status !== BpGrouping::AVAILABLE) {
            $land = BpFile::query()->live()->whereKey($row->bp_file_id)->value('land_file_number');

            return response()->json(['state' => 'used', 'number' => $number, 'tracking_id' => $row->tracking_id, 'message' => "{$number} is already {$row->status}".($land ? " (land file {$land})" : '').'.']);
        }

        return response()->json(['state' => 'available', 'number' => $number, 'tracking_id' => $row->tracking_id, 'message' => "{$number} is available."]);
    }

    /** The grouping by year: how many numbers each year holds and how many are used. */
    public function grouping(): View
    {
        $years = BpGrouping::query()
            ->selectRaw('year, COUNT(*) AS total, MIN(serial) AS first_serial, MAX(serial) AS last_serial')
            ->selectRaw("SUM(CASE WHEN status = 'Indexed' THEN 1 ELSE 0 END) AS indexed")
            ->selectRaw("SUM(CASE WHEN status = 'Commissioned' THEN 1 ELSE 0 END) AS commissioned")
            ->groupBy('year')->orderByDesc('year')->get();

        return view('bpa.bp-files.grouping', ['years' => $years, 'current' => (int) now()->format('Y'), 'numbers' => app(BpNumbers::class)]);
    }

    private function form(string $mode, ?BpFile $file = null, ?string $next = null): View
    {
        return view('bpa.bp-files.form', [
            'mode' => $mode,
            'file' => $file,
            'next' => $next,
            // Edit: the land file's tracking ID, fetched from the KLAES grouping by file number (never stored here).
            // The BP number's tracking ID, fetched from the BP grouping (never stored on the BP file). Indexing: shown once the number is typed.
            'bpTrackingId' => BpGrouping::trackingIdFor($file?->bp_file_no ?? $next),
            'landTrackingId' => $file ? app(IndexedFiles::class)->trackingId($file->land_file_number) : null,
            'zones' => BpZone::query()->active()->get(['id', 'name', 'description', 'is_other']),
            'years' => BpGrouping::query()->select('year')->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }
}
