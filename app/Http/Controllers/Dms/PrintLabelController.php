<?php

namespace App\Http\Controllers\Dms;

use App\Http\Controllers\Controller;
use App\Models\Dms\FileIndexing;
use App\Models\Dms\PrintLabelBatch;
use App\Models\Dms\PrintLabelBatchItem;
use App\Services\Serials;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * DMS > Indexing > File SerialNo Grouping > Print Files Label, COPIED from KLAES (PrintLabelController + views/printlabel,
 * user 2026-11-25: "it's not the same as the one we had before, check the KLAES and copy it"). The page and its JS are
 * KLAES's; this controller answers the same JSON contracts from KAMMA's own data:
 *
 *  - files are KAMMA shadow files (kamma_file_indexings), never KLAES land files;
 *  - KLAES's grouping table / registry batches → the shadow file's batch_no (copied from the KLAES indexing record);
 *    KAMMA has one registry, so KLAES's Registry 1/2/3 is shown as "KAMMA";
 *  - KLAES's Rack_Shelf_Labels counters → a count of shadow files on that shelf/rack (kamma_file_indexings.shelf_location);
 *  - batches + items → kamma_print_label_batches / kamma_print_label_batch_items;
 *  - the QR value is the KAMMA tracking ID (KMA-TRK-…, File Tracking), else the file number.
 *
 * A shadow file on any batch is "labelled" and leaves the Select Files list (KLAES rule). The shelf/rack a batch assigns
 * is written to the shadow file only (KAMMA's table); KLAES is never written.
 */
class PrintLabelController extends Controller
{
    private const MAX_BATCH_SELECTION = 500;

    private const RACK_SHELF_CAPACITY = 100;

    private const FORMATS = ['standard', 'compact', 'qr_code', '30-in-1'];

    public function index(): View
    {
        return view('dms.printlabel.index', ['availableFilesCount' => $this->unlabelled()->count()]);
    }

    public function printTemplate(): View
    {
        return view('dms.printlabel.print-file-lab');
    }

    /** Select Files tab (KLAES getAvailableFiles): unlabelled shadow files, newest first, with search. */
    public function files(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->get('per_page', 30), 100));
        $search = mb_substr(trim((string) $request->get('search', '')), 0, 100);

        $files = $this->unlabelled()->with('tracker')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('file_number', 'like', "%{$search}%")
                ->orWhere('file_title', 'like', "%{$search}%")->orWhere('plot_number', 'like', "%{$search}%")
                ->orWhere('district', 'like', "%{$search}%")->orWhere('lga', 'like', "%{$search}%")
                ->orWhere('batch_no', 'like', "%{$search}%")))
            ->orderByDesc('id')->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => $files->map(fn (FileIndexing $f) => $this->present($f))->values(),
            'pagination' => [
                'current_page' => 1, 'per_page' => $perPage, 'has_more_pages' => $files->count() >= $perPage,
                'next_page_url' => null, 'prev_page_url' => null, 'last_page' => 1, 'total' => null,
            ],
        ]);
    }

    /** Batch Mode (KLAES previewGroupingBatch): shadow files of the typed batch number(s), by serial no., 100 per batch. */
    public function groupingPreview(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'registry_batch_no' => 'required|string|max:600',
            'start' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:'.self::MAX_BATCH_SELECTION,
            'per_batch_limit' => 'nullable|integer|min:1|max:'.self::MAX_BATCH_SELECTION,
            'exclude_assigned' => 'nullable|in:true,false,1,0',
            'year' => 'nullable|string|max:4',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        $batches = $this->splitList($d['registry_batch_no']);
        if ($batches->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Provide at least one batch number.'], 422);
        }
        if ($batches->count() > 25) {
            return response()->json(['success' => false, 'message' => 'Please limit the request to 25 batch numbers at a time.'], 422);
        }

        $perBatch = (int) ($d['per_batch_limit'] ?? min((int) ($d['limit'] ?? 30), self::RACK_SHELF_CAPACITY));
        $perBatch = max(1, min($perBatch, self::MAX_BATCH_SELECTION));
        $start = (int) ($d['start'] ?? 1);
        $year = trim((string) ($d['year'] ?? ''));
        $excludeAssigned = in_array($d['exclude_assigned'] ?? null, ['true', '1'], true);
        $single = $batches->count() === 1;

        // KLAES auto-advances a single batch past what it has already printed.
        $progress = $single ? $this->batchProgress($batches->first()) : null;
        if ($progress !== null) {
            $start = max($start, (int) $progress['next_start']);
        }

        $records = collect();
        foreach ($batches as $batchNo) {
            $rows = FileIndexing::query()->live()->with('tracker')->where('batch_no', $batchNo)
                ->when($year !== '', fn ($q) => $q->where('file_number', 'like', "%-{$year}-%"))
                ->get()
                ->sortBy(fn (FileIndexing $f) => [is_numeric($f->serial_no) ? (int) $f->serial_no : PHP_INT_MAX, $f->id])
                ->values()
                ->slice($start - 1, $perBatch);
            $labelled = $this->labelledIds($rows->pluck('id'));
            $records = $records->concat($rows
                ->reject(fn (FileIndexing $f) => in_array($f->id, $labelled, true))
                ->reject(fn (FileIndexing $f) => $excludeAssigned && filled($f->shelf_location))
                ->map(fn (FileIndexing $f) => $this->present($f)));
        }

        $message = null;
        if ($records->isEmpty()) {
            $message = $year !== ''
                ? sprintf('No unlabelled shadow files were found for year %s in batch%s %s.', $year, $single ? '' : 'es', $batches->implode(', '))
                : 'No unlabelled shadow files were found for the selected batch.';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $records->values(),
                'requested' => [
                    'registry_batch_no' => $batches->implode(', '),
                    'registry_batch_list' => $batches->all(),
                    'registry' => '1',
                    'year' => $year !== '' ? $year : null,
                    'start' => $start,
                    'per_batch_limit' => $perBatch,
                    'total_requested' => $perBatch * $batches->count(),
                ],
                'label_statistics' => $single ? $this->shelfStatistics($batches->first()) : null,
                'available_years' => [],
                'message' => $message,
                'registry_progress' => $progress,
            ],
        ]);
    }

    /** Batch number suggestions (KLAES searchRegistryBatches). */
    public function registryBatches(Request $request): JsonResponse
    {
        $term = mb_substr(trim((string) $request->get('q', $request->get('term', ''))), 0, 50);
        $rows = FileIndexing::query()->live()->whereNotNull('batch_no')->where('batch_no', '<>', '')
            ->when($term !== '', fn ($q) => $q->where('batch_no', 'like', "%{$term}%"))
            ->select('batch_no')->distinct()->orderBy('batch_no')->limit(50)->pluck('batch_no');

        return response()->json(['success' => true, 'data' => $rows->map(fn ($b) => ['id' => $b, 'text' => $b, 'registry_batch_no' => $b])->values()]);
    }

    /** Label Capacity card (KLAES getRackLabelStatus): how many shadow files sit on this shelf/rack. */
    public function rackLabelStatus(Request $request): JsonResponse
    {
        $label = strtoupper(trim((string) $request->get('full_label', '')));
        if ($label === '') {
            return response()->json(['success' => false, 'message' => 'Please provide a rack/shelf label.'], 422);
        }
        $used = FileIndexing::query()->live()->whereRaw('UPPER(shelf_location) = ?', [$label])->count();
        $batch = FileIndexing::query()->live()->whereRaw('UPPER(shelf_location) = ?', [$label])->whereNotNull('batch_no')->value('batch_no');
        preg_match('/^([A-Z]+)(\d+)$/', $label, $m);

        return response()->json(['success' => true, 'data' => [
            'label_id' => null, 'full_label' => $label, 'rack' => $m[1] ?? null, 'shelf' => $m[2] ?? null,
            'counter' => $used, 'raw_counter' => $used, 'capacity' => null, 'remaining' => null,
            'next_start' => $used + 1, 'next_range_key' => $used + 1 <= self::RACK_SHELF_CAPACITY ? '1-100' : null,
            'assigned' => $batch, 'assigned_registry' => $batch ? '1' : null, 'assigned_batch' => $batch,
            'is_full' => false, 'registry_conflict' => false, 'reserved_by' => null, 'reserved_at' => null,
            'registry_progress' => $batch ? $this->batchProgress($batch) : null,
        ]]);
    }

    /** Manual Registry Override (KLAES lookupFileNumbers): typed file numbers → shadow files, in the typed order. */
    public function overrideLookup(Request $request): JsonResponse
    {
        $raw = $request->input('file_numbers', []);
        $numbers = collect(is_array($raw) ? $raw : preg_split('/[\s,;]+/', (string) $raw))
            ->map(fn ($n) => trim((string) $n))->filter()->unique(fn ($n) => strtoupper($n))->take(100)->values();
        if ($numbers->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Enter at least one file number.'], 422);
        }

        $found = FileIndexing::query()->live()->with('tracker')
            ->where(function (Builder $q) use ($numbers) {
                foreach ($numbers as $n) {
                    $q->orWhereRaw('UPPER(file_number) = ?', [strtoupper($n)]);
                }
            })->get()->keyBy(fn (FileIndexing $f) => strtoupper(trim($f->file_number)));

        $ordered = $numbers->map(fn ($n) => $found->get(strtoupper($n)))->filter()->unique('id')->values();
        $missing = $numbers->reject(fn ($n) => $found->has(strtoupper($n)))->values();

        return response()->json([
            'success' => true,
            'data' => $ordered->map(fn (FileIndexing $f) => $this->present($f) + ['override_match' => 'file_number'])->values(),
            'meta' => [
                'requested_count' => $numbers->count(), 'found_count' => $ordered->count(),
                'not_found' => $missing->all(), 'grouping_matches' => 0,
            ],
        ]);
    }

    /** Save the batch before printing (KLAES createBatch). */
    public function createBatch(Request $request, Serials $serials): JsonResponse
    {
        $source = $request->input('source') === 'grouping' ? 'grouping' : 'file_indexings';
        $rules = [
            'label_format' => 'required|in:'.implode(',', self::FORMATS),
            'orientation' => 'required|in:portrait,landscape',
            'batch_size' => 'nullable|integer|min:1|max:'.self::MAX_BATCH_SELECTION,
        ];
        $rules += $source === 'grouping' ? [
            'registry_batch_no' => 'nullable|string|max:600',
            'rack_primary' => 'required|alpha|size:1',
            'rack_secondary' => 'nullable|alpha|size:1',
            'shelf' => 'required|integer|min:1|max:100',
            'records' => 'required|array|min:1|max:'.self::MAX_BATCH_SELECTION,
            'records.*.id' => 'required|integer',
            'sub_groups' => 'nullable|array',
            'sub_groups.*.record_ids' => 'required_with:sub_groups|array|min:1',
            'sub_groups.*.record_ids.*' => 'integer',
            'sub_groups.*.rack_primary' => 'required_with:sub_groups|alpha|size:1',
            'sub_groups.*.rack_secondary' => 'nullable|alpha|size:1',
            'sub_groups.*.shelf' => 'required_with:sub_groups|integer|min:1|max:100',
        ] : [
            'file_ids' => 'required|array|min:1|max:'.self::MAX_BATCH_SELECTION,
            'file_ids.*' => 'integer',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        // Which shelf/rack each file gets: Batch Mode / override assigns the selected label (or its sub group's).
        $ids = collect($source === 'grouping' ? array_column($d['records'], 'id') : $d['file_ids'])->map(fn ($i) => (int) $i)->unique()->values();
        $labelFor = [];
        $fullLabel = null;
        if ($source === 'grouping') {
            $fullLabel = self::fullLabel($d['rack_primary'], $d['rack_secondary'] ?? null, (int) $d['shelf']);
            foreach ($ids as $id) {
                $labelFor[$id] = $fullLabel;
            }
            foreach ($d['sub_groups'] ?? [] as $g) {
                $l = self::fullLabel($g['rack_primary'], $g['rack_secondary'] ?? null, (int) $g['shelf']);
                foreach ($g['record_ids'] as $id) {
                    $labelFor[(int) $id] = $l;
                }
            }
        }

        $files = FileIndexing::query()->live()->with('tracker')->whereIn('id', $ids)->get()->keyBy('id');
        if ($files->count() !== $ids->count()) {
            return response()->json(['success' => false, 'message' => 'Some selected files were not found (removed or not a KAMMA shadow file).'], 422);
        }

        $user = $request->user();
        $batch = DB::transaction(function () use ($ids, $files, $labelFor, $source, $d, $fullLabel, $serials, $user) {
            $ym = now()->format('Ym');
            $batch = PrintLabelBatch::create([
                'batch_number' => sprintf('PLB-KMA-%s-%s-%03d', now()->format('Y'), now()->format('m'), $serials->next("print_label_batch_{$ym}")),
                'batch_size' => $ids->count(),
                'label_format' => $d['label_format'],
                'orientation' => $d['orientation'],
                'source' => $source === 'grouping' ? 'batch' : 'file_indexings',
                'batch_filter' => $source === 'grouping' ? (($d['registry_batch_no'] ?? null) ?: null) : null,
                'full_label' => $fullLabel,
                'status' => PrintLabelBatch::STATUS_PENDING,
                'created_by' => $user->getKey(),
                'created_by_name' => $user->name,
            ]);

            foreach ($ids->values() as $i => $id) {
                $f = $files[$id];
                $shelf = $labelFor[$id] ?? $f->shelf_location;
                if (isset($labelFor[$id]) && $f->shelf_location !== $shelf) {
                    $f->update(['shelf_location' => $shelf]);   // KAMMA's shadow file only
                }
                $tracking = $f->tracker?->tracking_id;
                $batch->batchItems()->create([
                    'file_indexing_id' => $f->id,
                    'file_number' => $f->file_number,
                    'file_title' => $f->file_title,
                    'plot_number' => $f->plot_number,
                    'district' => $f->district,
                    'lga' => $f->lga,
                    'land_use_type' => $f->land_use_type,
                    'shelf_location' => $shelf,
                    'tracking_id' => $tracking,
                    'qr_code_data' => [
                        'file_number' => $f->file_number, 'tracking_id' => $tracking, 'plot_number' => $f->plot_number,
                        'district' => $f->district, 'lga' => $f->lga, 'land_use_type' => $f->land_use_type,
                        'shelf_location' => $shelf, 'generated_at' => now()->toIso8601String(),
                    ],
                    'label_position' => $i + 1,
                ]);
            }

            $batch->update(['status' => PrintLabelBatch::STATUS_GENERATED, 'generated_count' => $ids->count(), 'updated_by' => $user->getKey()]);

            return $batch->fresh('batchItems');
        });

        $single = $source === 'grouping' ? $this->splitList((string) ($d['registry_batch_no'] ?? ''))->first() : null;

        return response()->json([
            'success' => true,
            'message' => 'Label batch created successfully',
            'data' => [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'file_count' => $batch->generated_count,
                'source' => $source,
                'registry_batch_no' => $batch->batch_filter,
                'registry_progress' => $single ? $this->batchProgress($single) : null,
                'rack_label_status' => null,
                'warnings' => [],
                'label_items' => $batch->batchItems->map(fn (PrintLabelBatchItem $i) => $this->presentItem($i))->values(),
                'label_statistics' => $single ? $this->shelfStatistics($single) : null,
            ],
        ]);
    }

    /** Generated Batches tab (KLAES getGeneratedBatches). */
    public function batches(Request $request): JsonResponse
    {
        $status = (string) $request->get('status', '');
        $search = mb_substr(trim((string) $request->get('search', '')), 0, 60);
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));

        $page = PrintLabelBatch::query()->withCount('batchItems')->where('status', '<>', PrintLabelBatch::STATUS_PENDING)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('batch_number', 'like', "%{$search}%"))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('date_to')))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (PrintLabelBatch $b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'created_at' => $b->created_at?->toISOString(),
                'batch_size' => (int) $b->batch_size,
                'batch_items_count' => (int) $b->batch_items_count,
                'label_format' => $b->label_format,
                'status' => $b->status,
                'file_year' => $this->fileYear($b),
                'creator' => $b->created_by ? ['id' => $b->created_by, 'name' => $b->created_by_name ?: 'Unknown'] : null,
            ])->values(),
            'pagination' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()],
        ]);
    }

    public function batch(int $id): JsonResponse
    {
        $b = PrintLabelBatch::query()->with('batchItems')->findOrFail($id);
        $data = $b->toArray();
        $data['creator'] = $b->created_by ? ['id' => $b->created_by, 'name' => $b->created_by_name ?: 'Unknown'] : null;

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Reprint a batch (KLAES getBatchForPrinting). */
    public function batchForPrinting(int $id): JsonResponse
    {
        $b = PrintLabelBatch::query()->with('batchItems')->findOrFail($id);
        $files = $b->batchItems->map(fn (PrintLabelBatchItem $i) => ['id' => $i->file_indexing_id] + $this->presentItem($i) + ['batch_no' => $i->file_number])->values();
        $batch = $b->toArray();
        $batch['registry_batch_no'] = $b->batch_filter;

        return response()->json(['success' => true, 'data' => [
            'batch' => $batch,
            'files' => $files,
            'label_statistics' => $b->batch_filter ? $this->shelfStatistics($this->splitList($b->batch_filter)->first()) : null,
        ]]);
    }

    public function markPrinted(Request $request, int $id): JsonResponse
    {
        $b = PrintLabelBatch::query()->findOrFail($id);
        DB::transaction(function () use ($b, $request) {
            $b->update(['status' => PrintLabelBatch::STATUS_PRINTED, 'printed_at' => now(), 'updated_by' => $request->user()->getKey()]);
            $b->batchItems()->update(['is_printed' => true, 'printed_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Batch marked as printed successfully']);
    }

    /** KLAES rule: printed or completed batches cannot be deleted. Deleting frees its files for labelling again. */
    public function deleteBatch(int $id): JsonResponse
    {
        $b = PrintLabelBatch::query()->findOrFail($id);
        if (in_array($b->status, [PrintLabelBatch::STATUS_PRINTED, PrintLabelBatch::STATUS_COMPLETED], true)) {
            return response()->json(['success' => false, 'message' => 'Cannot delete printed or completed batches'], 400);
        }
        DB::transaction(function () use ($b) {
            $b->batchItems()->delete();
            $b->delete();
        });

        return response()->json(['success' => true, 'message' => 'Batch deleted successfully']);
    }

    public function statistics(): JsonResponse
    {
        $count = fn (string $s) => PrintLabelBatch::query()->where('status', $s)->count();

        return response()->json(['success' => true, 'data' => [
            'available_files' => $this->unlabelled()->count(),
            'total_batches' => PrintLabelBatch::query()->count(),
            'pending_batches' => $count(PrintLabelBatch::STATUS_PENDING),
            'generated_batches' => $count(PrintLabelBatch::STATUS_GENERATED),
            'printed_batches' => $count(PrintLabelBatch::STATUS_PRINTED),
            'completed_batches' => $count(PrintLabelBatch::STATUS_COMPLETED),
            'total_labels_printed' => PrintLabelBatchItem::query()->where('is_printed', true)->count(),
        ]]);
    }

    public static function fullLabel(string $rackPrimary, ?string $rackSecondary, int $shelf): string
    {
        return strtoupper(trim($rackPrimary).trim((string) $rackSecondary).$shelf);
    }

    /** Live shadow files not on any label batch. */
    private function unlabelled(): Builder
    {
        return FileIndexing::query()->live()
            ->whereNotIn('id', PrintLabelBatchItem::query()->select('file_indexing_id'));
    }

    /** @return list<int> */
    private function labelledIds(Collection $ids): array
    {
        return $ids->isEmpty() ? [] : PrintLabelBatchItem::query()->whereIn('file_indexing_id', $ids)->distinct()->pluck('file_indexing_id')->map(fn ($i) => (int) $i)->all();
    }

    /** KLAES getRegistryBatchProgress: how far a batch number has been labelled (next start = labelled + 1). */
    private function batchProgress(string $batchNo): array
    {
        $ids = FileIndexing::query()->live()->where('batch_no', $batchNo)->pluck('id');
        $printed = count($this->labelledIds($ids));

        return [
            'registry_batch_no' => $batchNo, 'printed' => $printed, 'printed_within_capacity' => $printed, 'remaining' => null,
            'next_start' => $printed + 1, 'next_range_key' => '1-100', 'next_range_start' => 1, 'next_range_end' => 100,
            'is_complete' => false, 'auto_advance' => false, 'capacity' => null, 'registry' => '1',
        ];
    }

    /** KLAES buildShelfStatistics: the shelves/racks the files of a batch number sit on. */
    private function shelfStatistics(?string $batchNo): ?array
    {
        if ($batchNo === null || $batchNo === '') {
            return null;
        }
        $labels = FileIndexing::query()->live()->where('batch_no', $batchNo)->whereNotNull('shelf_location')->where('shelf_location', '<>', '')
            ->selectRaw('shelf_location, COUNT(*) AS used')->groupBy('shelf_location')->orderBy('shelf_location')->get()
            ->map(fn ($r) => [
                'label_id' => null, 'label' => $r->shelf_location, 'used' => (int) $r->used,
                'remaining' => max(0, self::RACK_SHELF_CAPACITY - (int) $r->used), 'capacity' => self::RACK_SHELF_CAPACITY,
                'status' => 'active', 'reserved_at' => null, 'reserved_by' => null, 'assigned_registry' => '1', 'assigned_batch' => $batchNo,
            ])->values();
        $used = (int) $labels->sum('used');
        $capacity = max($labels->count(), 1) * self::RACK_SHELF_CAPACITY;

        return [
            'labels' => $labels->all(), 'label_capacity' => self::RACK_SHELF_CAPACITY, 'total_used' => $used,
            'total_remaining' => max(0, $capacity - $used), 'total_capacity' => $capacity, 'registry' => '1', 'next_label' => null,
        ];
    }

    private function fileYear(PrintLabelBatch $b): ?string
    {
        $years = $b->batchItems()->pluck('file_number')
            ->map(fn ($n) => preg_match('/-(\d{4})-/', (string) $n, $m) ? $m[1] : null)->filter()->unique()->values();

        return $years->isEmpty() ? null : $years->implode(', ');
    }

    private function splitList(string $value): Collection
    {
        return collect(preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY))->map(fn ($v) => trim($v))->filter()->unique()->values();
    }

    /** One shadow file in KLAES's record shape. */
    private function present(FileIndexing $f): array
    {
        $shelf = filled($f->shelf_location) ? strtoupper(trim($f->shelf_location)) : null;

        return [
            'id' => $f->id,
            'file_number' => $f->file_number,
            'file_title' => $f->file_title,
            'plot_number' => $f->plot_number,
            'district' => $f->district,
            'lga' => $f->lga,
            'land_use_type' => $f->land_use_type,
            'shelf_location' => $shelf,
            'shelf_label' => $shelf,
            'shelf_full_label' => $shelf,
            'shelf_value' => $shelf,
            'batch_no' => $f->batch_no,
            'registry_batch_no' => $f->batch_no,
            'registry' => '1',
            'serial_no' => $f->serial_no,
            'tracking_id' => $f->tracker?->tracking_id,
        ];
    }

    private function presentItem(PrintLabelBatchItem $i): array
    {
        $qr = is_array($i->qr_code_data) ? $i->qr_code_data : [];

        return [
            'batch_item_id' => $i->id,
            'file_indexing_id' => $i->file_indexing_id,
            'file_number' => $i->file_number,
            'file_title' => $i->file_title,
            'plot_number' => $i->plot_number,
            'district' => $i->district,
            'lga' => $i->lga,
            'land_use_type' => $i->land_use_type,
            'shelf_location' => $i->shelf_location,
            'shelf_value' => $i->shelf_location,
            'shelf_label' => $i->shelf_location,
            'tracking_id' => $i->tracking_id,
            'qr_code_data' => $qr,
            'qr_value' => $i->tracking_id ?: $i->file_number,
            'label_position' => $i->label_position,
        ];
    }
}
