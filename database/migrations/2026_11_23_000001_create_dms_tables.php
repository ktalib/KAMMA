<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KAMMA DMS (the briefing's MDC exercise), copied in concept from KLAES:
 *
 *  - file_indexings: KAMMA's own indexing table. Each row is a KAMMA shadow file of a KLAES land file: the
 *    KLAES indexing record is copied in when the file is matched (KLAES's own record only gets the
 *    kamma_matching flags, see database/manual/2026_11_23_klaes_shadow_file_flags.sql).
 *  - scannings: scanned pages uploaded onto an indexed file (KLAES scannings).
 *  - page_typings: one row per typed page (KLAES pagetypings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_indexings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('klaes_file_indexing_id');    // dbo.file_indexings.id (no FK onto KLAES)
            $table->string('file_number', 100)->index();
            $table->string('file_title', 500)->nullable();
            $table->string('plot_number', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 500)->nullable();
            $table->string('land_use_type', 100)->nullable();
            $table->string('plot_size', 100)->nullable();
            $table->string('registry', 150)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->string('shelf_location', 150)->nullable();
            $table->string('serial_no', 100)->nullable();
            $table->string('klaes_tracking_id', 100)->nullable();
            $table->text('klaes_record')->nullable();                 // the KLAES row as copied (JSON)
            $table->date('date_matched');
            $table->time('time_matched');
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->string('matched_by_name', 300)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        $idx = DB::getTablePrefix().'file_indexings';
        // One live KAMMA shadow file per KLAES land file.
        DB::statement("CREATE UNIQUE INDEX {$idx}_klaes_id_unique ON {$idx} (klaes_file_indexing_id) WHERE is_deleted = 0");

        Schema::create('scannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_indexing_id')->constrained('file_indexings');
            $table->string('document_path', 500);
            $table->string('original_filename', 300);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('paper_size', 10)->default('A4');           // A4 | A3 (KLAES)
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_pdf_converted')->default(false);
            $table->string('upload_batch', 40);                        // one Start Upload; Scan More adds a new batch
            $table->boolean('is_scan_more')->default(false);           // added after the file was page typed
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_by_name', 300)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->index(['file_indexing_id', 'is_deleted']);
        });

        Schema::create('page_typings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_indexing_id')->constrained('file_indexings');
            $table->foreignId('scanning_id')->constrained('scannings');
            $table->unsignedInteger('page_number');
            $table->string('page_type', 150);
            $table->string('page_type_others', 200)->nullable();
            $table->string('page_subtype', 200)->nullable();
            $table->string('page_subtype_others', 200)->nullable();
            $table->unsignedInteger('serial_number');
            $table->string('page_code', 100)->nullable();
            $table->string('qc_status', 20)->default('pending');       // pending | passed | failed (PT Quality Control)
            $table->unsignedBigInteger('typed_by')->nullable();
            $table->string('typed_by_name', 300)->nullable();
            $table->timestamps();
            $table->index(['file_indexing_id', 'page_type']);
        });

        $pt = DB::getTablePrefix().'page_typings';
        DB::statement("CREATE UNIQUE INDEX {$pt}_scanning_unique ON {$pt} (scanning_id)");
    }

    public function down(): void
    {
        Schema::dropIfExists('page_typings');
        Schema::dropIfExists('scannings');
        Schema::dropIfExists('file_indexings');
    }
};
