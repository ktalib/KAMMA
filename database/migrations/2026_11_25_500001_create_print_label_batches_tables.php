<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DMS > Print Files Label, copied from KLAES (printlabel: print_label_batches + print_label_batch_items), for KAMMA's
 * shadow files. A batch is one print run (30-in-1 A4 QR labels: file number + shelf/rack); its items are the files on it.
 * A shadow file that is on a batch is "labelled" and leaves the Select Files list (KLAES rule). The rack/shelf a batch
 * assigns is also written to the shadow file's own shelf_location (kamma_file_indexings, never KLAES).
 * No foreign keys onto KLAES tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_label_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 40)->unique();           // PLB-KMA-YYYY-MM-NNN
            $table->unsignedInteger('batch_size')->default(0);
            $table->unsignedInteger('generated_count')->default(0);
            $table->string('label_format', 30);                     // 30-in-1 (KLAES template)
            $table->string('orientation', 20)->default('portrait');
            $table->string('source', 30)->default('file_indexings'); // file_indexings (manual) | batch (Batch Mode / override)
            $table->string('batch_filter', 600)->nullable();        // the KAMMA batch number(s) loaded in Batch Mode
            $table->string('full_label', 20)->nullable();           // rack + shelf, e.g. A1
            $table->string('status', 20)->default('pending');       // pending | generated | printed | completed
            $table->unsignedBigInteger('created_by')->nullable();   // KLAES dbo.users.id
            $table->string('created_by_name', 300)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('printed_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('print_label_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('print_label_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('file_indexing_id')->index(); // kamma_file_indexings.id (the shadow file)
            $table->string('file_number', 100);
            $table->string('file_title', 500)->nullable();
            $table->string('plot_number', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('land_use_type', 150)->nullable();
            $table->string('shelf_location', 150)->nullable();
            $table->string('tracking_id', 100)->nullable();          // KAMMA tracker (KMA-TRK-…), the QR value
            $table->text('qr_code_data')->nullable();
            $table->unsignedInteger('label_position')->default(1);
            $table->boolean('is_printed')->default(false);
            $table->dateTime('printed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_label_batch_items');
        Schema::dropIfExists('print_label_batches');
    }
};
