<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A tracking ID on every BP grouping number (user 2026-11-26), like KLAES dbo.grouping holds one per land file number.
 * It lives ONLY here and is fetched by BP number whenever needed ("if we are moving the tracking ID from one place to
 * another, there might be issue later"): never copied onto bp_files or anywhere else.
 * Additive. The IDs are filled by `php artisan kamma:bp-grouping --commit` (BP-TRK-XXXXXXXX).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bp_groupings', function (Blueprint $table) {
            $table->string('tracking_id', 30)->nullable();
        });

        $g = DB::getTablePrefix().'bp_groupings';
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement("CREATE UNIQUE INDEX {$g}_tracking_id_unique ON {$g} (tracking_id) WHERE tracking_id IS NOT NULL");
        } else {
            Schema::table('bp_groupings', fn (Blueprint $t) => $t->unique('tracking_id'));
        }
    }

    public function down(): void
    {
        $g = DB::getTablePrefix().'bp_groupings';
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement("DROP INDEX {$g}_tracking_id_unique ON {$g}");
        } else {
            Schema::table('bp_groupings', fn (Blueprint $t) => $t->dropUnique(['tracking_id']));
        }
        Schema::table('bp_groupings', fn (Blueprint $t) => $t->dropColumn('tracking_id'));
    }
};
