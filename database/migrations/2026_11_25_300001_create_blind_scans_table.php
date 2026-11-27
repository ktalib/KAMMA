<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KAMMA Blind Scanning (user 2026-11-25, copied from KLAES blind_scannings): one row per page uploaded into
 * EDMS/BLIND_SCAN/KAMMA_Raw/<file no.>/{A4,A3,INDEX_CARDS}. A page stays "pending" until Scan Upload (Upload Method =
 * Blind scan) takes it into the file, which makes its kamma_scannings row and marks it "moved".
 * Shadow files only for now (BP files on hold). Own table, no foreign keys onto KLAES.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blind_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_indexing_id')->constrained('file_indexings'); // the KAMMA shadow file picked on upload
            $table->string('file_number', 100)->index();
            $table->string('folder', 300)->index();            // BLIND_SCAN/KAMMA_Raw/<file no.>
            $table->string('relative_path', 500);              // A4/page_001.jpg, A3/plan.jpg, INDEX_CARDS/card.jpg
            $table->string('original_filename', 300);
            $table->string('paper_size', 10);                  // A4 | A3 | IC (index card)
            $table->string('upload_type', 10);                 // full | lf | ic
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('upload_batch', 40)->index();       // one Migrate to Server
            $table->string('status', 20)->default('pending');  // pending | moved
            $table->unsignedBigInteger('scanning_id')->nullable();
            $table->dateTime('moved_at')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();   // KLAES dbo.users.id
            $table->string('uploaded_by_name', 300)->nullable();
            $table->unsignedBigInteger('moved_by')->nullable();
            $table->string('moved_by_name', 300)->nullable();
            $table->timestamps();
            $table->index(['file_indexing_id', 'status']);
            $table->unique(['folder', 'relative_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blind_scans');
    }
};
