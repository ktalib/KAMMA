<?php

namespace App\Services\Dms;

use App\Models\Dms\FileIndexing;
use App\Models\User;
use App\Services\Tracking\FileTracking;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shadow File Commissioning (the File Indexing Assistant), from the briefing's DMS / MDC exercise:
 * "once we select the file number, it's going to bring the indexing record from our indexing, … update the
 * shadow file under our own indexing, and bring that indexing record to their own indexing table."
 *
 * Copied from KLAES LandsFileNoMatchingController::store: the KLAES record must already exist, a file is
 * matched once, and the match is flagged on KLAES's own rows (here kamma_matching / kamma_date_matched /
 * kamma_time_matched on dbo.file_indexings and dbo.fileNumber; the kamma_app login may set only those).
 * KLAES's file data is never changed.
 */
class ShadowFiles
{
    /** The KLAES columns copied into KAMMA's indexing table, KLAES name => KAMMA name. */
    private const COPY = [
        'file_number' => 'file_number', 'file_title' => 'file_title', 'plot_number' => 'plot_number',
        'district' => 'district', 'lga' => 'lga', 'location' => 'location', 'land_use_type' => 'land_use_type',
        'plot_size' => 'plot_size', 'registry' => 'registry', 'batch_no' => 'batch_no',
        'shelf_location' => 'shelf_location', 'serial_no' => 'serial_no', 'tracking_id' => 'klaes_tracking_id',
    ];

    /** fileNumber columns that can carry the same file number (KLAES: match across columns). */
    private const FILENUMBER_COLUMNS = ['mlsfNo', 'kangisFileNo', 'NewKANGISFileNo'];

    public function __construct(private FileTracking $tracking)
    {
    }

    public function commission(int $klaesFileIndexingId, User $officer, ?string $remarks = null): FileIndexing
    {
        $row = $this->klaesRow($klaesFileIndexingId);
        if ($row === null) {
            throw ValidationException::withMessages(['klaes_file_indexing_id' => 'That file is not in KLAES indexing (or it is deleted / decommissioned). Only indexed KLAES files can be matched.']);
        }

        $existing = FileIndexing::query()->live()->where('klaes_file_indexing_id', $klaesFileIndexingId)->first();
        if ($existing !== null) {
            throw ValidationException::withMessages(['klaes_file_indexing_id' => "File Number {$existing->file_number} has already been matched in KAMMA (".$existing->date_matched->format('jS M, Y').').']);
        }

        $now = now();

        return DB::transaction(function () use ($row, $officer, $remarks, $now) {
            $data = [];
            foreach (self::COPY as $from => $to) {
                $data[$to] = $this->clean($row[$from] ?? null);
            }

            $indexing = FileIndexing::query()->create($data + [
                'klaes_file_indexing_id' => (int) $row['id'],
                'klaes_record' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'date_matched' => $now->toDateString(),
                'time_matched' => $now->format('H:i:s'),
                'matched_by' => $officer->getKey(),
                'matched_by_name' => $officer->name,
                'remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null,
            ]);

            // Flag the match on KLAES's own rows. Separate connection (unprefixed); if it fails, the throw rolls
            // back KAMMA's row above, so the two never disagree about a new match.
            $this->flagKlaes((int) $row['id'], (string) $row['file_number'], $now);

            // KLAES gives a file its tracking ID when it is indexed; a KAMMA shadow file starts its KAMMA tracking
            // (KMA-TRK-…) in the Registry the same way.
            $this->tracking->startForShadowFile($indexing, $officer);

            return $indexing;
        });
    }

    /** The full KLAES indexing row, if live. */
    public function klaesRow(int $id): ?array
    {
        $row = $this->klaes()->table(DB::raw($this->nolock('file_indexings')))
            ->where('id', $id)
            ->where(fn (Builder $q) => $q->whereNull('is_deleted')->orWhere('is_deleted', 0))
            ->where(fn (Builder $q) => $q->whereNull('is_decommissioned')->orWhere('is_decommissioned', 0))
            ->first();

        return $row ? (array) $row : null;
    }

    private function flagKlaes(int $indexingId, string $fileNumber, $now): void
    {
        $flags = ['kamma_matching' => 1, 'kamma_date_matched' => $now->toDateString(), 'kamma_time_matched' => $now->format('H:i:s')];

        $this->klaes()->table('file_indexings')->where('id', $indexingId)->update($flags);

        // fileNumber: ids first with NOLOCK, one column at a time (a multi-column OR on fileNumber blocks on
        // locks in KLAES), then update by id.
        $fileNumber = trim($fileNumber);
        if ($fileNumber === '') {
            return;
        }
        $ids = [];
        foreach (self::FILENUMBER_COLUMNS as $col) {
            $ids = array_merge($ids, $this->klaes()->table(DB::raw($this->nolock('fileNumber')))->where($col, $fileNumber)->pluck('id')->all());
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids !== []) {
            $this->klaes()->table('fileNumber')->whereIn('id', $ids)->update($flags);
        }
    }

    private function nolock(string $table): string
    {
        return $this->klaes()->getDriverName() === 'sqlsrv' ? "{$table} with (nolock)" : $table;
    }

    private function klaes(): Connection
    {
        return DB::connection('klaes');
    }

    private function clean(mixed $value): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $v === '' ? null : trim($v, '"');
    }
}
