<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Yearly serial numbers (file search requests FR-KMA-YYYY-NNNNN, bills and memos later).
 * Each (name, year) has its own counter, so numbering restarts by itself on 1 January
 * without anyone having to reset it (briefing 2026-11-22). Call inside a transaction.
 */
class Serials
{
    public function next(string $name, ?int $year = null): int
    {
        $year ??= (int) now()->format('Y');

        $row = DB::table('serials')->where('name', $name)->where('year', $year)->lockForUpdate()->first();

        if ($row === null) {
            DB::table('serials')->insert([
                'name' => $name, 'year' => $year, 'last_value' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return 1;
        }

        $value = $row->last_value + 1;
        DB::table('serials')->where('id', $row->id)->update(['last_value' => $value, 'updated_at' => now()]);

        return $value;
    }
}
