<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KAMMA File Tracking, copied from KADCA (which copied it in concept from KLAES's file_tracker +
 * file_movement_units + Quick Search file requests), in one migration because none of it has run in KAMMA yet.
 *
 * A KAMMA file is a shadow file (kamma_file_indexings, Shadow File Commissioning) and keeps the KLAES land-file
 * number (briefing 2026-11-22). The KAMMA marker is the tracking ID (KMA-TRK-…) and the request number
 * (FR-KMA-YYYY-NNNNN), and the rows live only in KAMMA's own tables, so they never mix with KLAES land-file tracking.
 *
 * NOT RUN (user 2026-11-23: UI first). Run later, with approval, with --path, one file at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Yearly counters (FR-KMA-2026-00001 …). Each (name, year) restarts by itself on 1 January.
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();
            $table->unique(['name', 'year']);
        });

        // Units files move between. No KAMMA department list yet, so the known desks (KammaDesk) are seeded and the
        // list is editable. `stage` links a unit to a desk so desk decisions can move the physical file too.
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('stage', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('file_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id', 30)->unique();    // KMA-TRK-XXXXXXXX
            $table->string('kind', 10);                      // file | mail
            $table->foreignId('file_indexing_id')->nullable()->constrained('file_indexings'); // the KAMMA shadow file
            $table->string('reference', 150);                // land file number / mail reference
            $table->string('title', 500)->nullable();        // file title / mail subject
            $table->string('mail_from', 300)->nullable();
            $table->string('priority', 20)->default('Medium'); // Low | Medium | High (KLAES Log a File)
            $table->string('status', 20);                    // in_unit | in_transit
            $table->foreignId('current_unit_id')->constrained('units');
            $table->foreignId('to_unit_id')->nullable()->constrained('units');
            $table->date('due_on')->nullable();
            $table->string('shelf_location', 150)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('holder_user_id')->nullable()->index(); // KLAES dbo.users.id (no FK onto KLAES)
            $table->string('holder_name', 300)->nullable();
            $table->unsignedInteger('num_pages')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
            $table->index(['current_unit_id', 'status']);
        });

        $units = DB::getTablePrefix().'units';
        DB::statement("CREATE UNIQUE INDEX {$units}_stage_unique ON {$units} (stage) WHERE stage IS NOT NULL");

        $trackers = DB::getTablePrefix().'file_trackers';
        // One tracker per shadow file. Filtered: SQL Server lets a plain unique index hold only one NULL.
        DB::statement("CREATE UNIQUE INDEX {$trackers}_file_indexing_id_unique ON {$trackers} (file_indexing_id) WHERE file_indexing_id IS NOT NULL");

        Schema::create('file_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_tracker_id')->constrained('file_trackers')->cascadeOnDelete();
            $table->string('action', 20); // logged | sent | received
            $table->unsignedBigInteger('from_unit_id')->nullable();
            $table->unsignedBigInteger('to_unit_id')->nullable();
            $table->string('purpose', 300)->nullable();
            $table->text('notes')->nullable();
            $table->string('log_id', 40)->nullable()->index();
            $table->string('priority', 20)->nullable();
            $table->unsignedSmallInteger('timeline_days')->nullable();
            $table->date('expected_return_on')->nullable();
            $table->unsignedInteger('num_pages')->nullable();
            $table->unsignedBigInteger('holder_user_id')->nullable();
            $table->string('holder_name', 300)->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->string('acted_by_name', 300)->nullable();
            $table->dateTime('acted_at');
            $table->timestamps();
        });

        // File search requests (KLAES Quick Search): awaiting, then Found or Not Found (Pending / NWK).
        Schema::create('file_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();     // FR-KMA-YYYY-NNNNN
            $table->foreignId('file_tracker_id')->nullable()->constrained('file_trackers');
            $table->unsignedBigInteger('file_indexing_id')->nullable()->index();
            $table->string('file_number', 150)->index();
            $table->string('file_title', 500)->nullable();
            $table->string('status_at_request', 40);
            $table->boolean('is_blind')->default(false);
            $table->unsignedBigInteger('requester_unit_id')->nullable();
            $table->string('requester_unit_name', 150)->nullable();
            $table->string('requester_name', 300);
            $table->string('purpose', 300);
            $table->date('needed_by')->nullable();
            $table->string('status', 20)->default('awaiting'); // awaiting | found | not_found
            $table->string('not_found_type', 20)->nullable();   // pending | nwk
            $table->text('response_note')->nullable();
            $table->string('responded_by_name', 300)->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        $now = now();
        $seed = [
            ['Registry', 'registry'], ['PS Office (Ministry of Land)', 'ps'], ["MD KAMMA's Office", 'md'], ['Department', 'department'], ['Director', 'director'],
            ['Deputy Director', 'deputy_director'], ['Zonal Office', 'zonal_officer'], ['Field Officers', 'field_officer'],
            ['Accounts', 'accounts'], ['Archive', 'archive'],
        ];
        foreach ($seed as $i => [$name, $stage]) {
            DB::table('units')->insert(['name' => $name, 'stage' => $stage, 'is_active' => true, 'sort_order' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('file_requests');
        Schema::dropIfExists('file_movements');
        Schema::dropIfExists('file_trackers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('serials');
    }
};
