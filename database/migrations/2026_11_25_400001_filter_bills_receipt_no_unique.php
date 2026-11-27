<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * kamma_bills.receipt_no was a plain unique index (2026_11_23_000003). On SQL Server that index holds only ONE NULL, so
 * the second unpaid bill could not be saved. Replace it with the filtered form the BPA bills already use
 * (WHERE receipt_no IS NOT NULL): receipt numbers stay unique, unpaid bills may all be NULL.
 * SQLite (tests) already treats NULLs as distinct, so it is left alone there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }
        $bills = DB::getTablePrefix().'bills';
        DB::statement("DROP INDEX {$bills}_receipt_no_unique ON {$bills}");
        DB::statement("CREATE UNIQUE INDEX {$bills}_receipt_no_unique ON {$bills} (receipt_no) WHERE receipt_no IS NOT NULL");
    }

    public function down(): void
    {
        // Forward-only (the plain index is the bug this fixes).
    }
};
