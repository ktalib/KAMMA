<?php

namespace App\Services\Bpa;

use App\Models\Bpa\BpFile;
use App\Models\Bpa\BpGrouping;
use App\Services\Serials;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * BP file numbers, BP-[Year]-[Serial] (user 2026-11-22: "it should be BP-2026-1"), unpadded.
 *
 *  - An existing BP file is indexed under its own number, which must be in the grouping (kadca_bp_groupings,
 *    1981 onwards, 10,000 a year for now) and still Available.
 *  - A new BP file is commissioned with the next serial of its year: one above the highest number already used
 *    that year (indexed or commissioned), so new numbers never land on an old file's number.
 *
 * Call inside a transaction.
 */
class BpNumbers
{
    public function __construct(private Serials $serials)
    {
    }

    public static function format(int $year, int $serial): string
    {
        return sprintf('BP-%d-%d', $year, $serial);
    }

    /** "BP-1985-12", "bp/1985/12", "BP 1985 12" → [1985, 12]; null when it isn't a BP number. */
    public static function parse(string $value): ?array
    {
        if (! preg_match('/^\s*BP\s*[-\/ ]\s*(\d{4})\s*[-\/ ]\s*0*(\d{1,6})\s*$/i', $value, $m) || (int) $m[2] < 1) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    /** Locks and returns the grouping row an existing BP file is indexed under. */
    public function claimForIndexing(int $year, int $serial): BpGrouping
    {
        $number = self::format($year, $serial);
        $row = BpGrouping::query()->where('year', $year)->where('serial', $serial)->lockForUpdate()->first();

        if ($row === null) {
            $has = BpGrouping::query()->where('year', $year)->exists();
            throw ValidationException::withMessages(['bp_serial' => $has
                ? "{$number} is outside the {$year} grouping. Ask the System Admin to extend that year."
                : "There is no grouping for {$year}. Existing BP files are indexed from the grouping; a new file is commissioned instead."]);
        }
        if ($row->status !== BpGrouping::AVAILABLE) {
            $file = BpFile::query()->live()->find($row->bp_file_id);
            throw ValidationException::withMessages(['bp_serial' => "{$number} is already {$row->status}"
                .($file ? " (land file {$file->land_file_number})" : '').'.']);
        }
        if ($row->tracking_id === null) {
            $row->update(['tracking_id' => BpGrouping::newTrackingId()]);
        }

        return $row;
    }

    /** The number the next commissioning would get (shown on the form; given only on save). */
    public function peekCommissioning(int $year): string
    {
        $last = (int) DB::table('serials')->where('name', 'bp_file')->where('year', $year)->value('last_value');

        return self::format($year, max($last, $this->highestUsed($year)) + 1);
    }

    /** @return array{0: int, 1: ?BpGrouping} the serial and, when the year has a grouping, its row (locked) */
    public function nextForCommissioning(int $year): array
    {
        $used = $this->highestUsed($year);

        $serial = $this->serials->next('bp_file', $year);
        if ($serial <= $used) {
            $serial = $used + 1;
            $this->serials->raiseTo('bp_file', $year, $serial);
        }

        $row = BpGrouping::query()->where('year', $year)->where('serial', $serial)->lockForUpdate()->first();
        // A year past the grouping (2027+ is commissioning only): the number gets its grouping row here, so every BP
        // number, and its tracking ID, is in the grouping (user 2026-11-26).
        $row ??= BpGrouping::create(['year' => $year, 'serial' => $serial, 'bp_file_no' => self::format($year, $serial),
            'status' => BpGrouping::AVAILABLE, 'tracking_id' => BpGrouping::newTrackingId()]);
        if ($row->tracking_id === null) {
            $row->update(['tracking_id' => BpGrouping::newTrackingId()]);
        }

        return [$serial, $row];
    }

    private function highestUsed(int $year): int
    {
        return (int) max(
            BpGrouping::query()->where('year', $year)->where('status', '!=', BpGrouping::AVAILABLE)->max('serial'),
            BpFile::query()->live()->where('bp_year', $year)->max('bp_serial'),
        );
    }

    public function markUsed(?BpGrouping $row, BpFile $file, string $status): void
    {
        $row?->update(['status' => $status, 'bp_file_id' => $file->id, 'used_at' => now()]);
        if ($row) {
            $file->update(['bp_grouping_id' => $row->id]);
        }
    }
}
