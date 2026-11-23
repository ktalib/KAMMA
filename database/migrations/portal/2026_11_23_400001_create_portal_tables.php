<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DRAFT, NOT RUN. KAMMA applicant portal backend (user 2026-11-23: UI first, backend next). Copied from the KADCA draft.
 *
 * Kept in database/migrations/portal/ so `php artisan migrate` and the test suite do not pick it up. When the user
 * approves the portal backend, review it, move it to database/migrations/ and run it on dev only, by itself:
 *   php artisan migrate --path=database/migrations/<file>.php
 *
 * Own tables only (kamma_ prefix added by the connection, kamma schema). No foreign keys onto KLAES dbo tables.
 * Applicants are members of the public, not KLAES staff, so they are NOT in dbo.users.
 * Open before running: the request number format (DEMO-KPR/... is a demo placeholder), whether a portal request becomes
 * the staff-side Shadow File request row directly, and the processing fee amount (config kamma.bills.processing_fee).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Applicant accounts (kamma_portal_applicants).
        Schema::create('portal_applicants', function (Blueprint $table) {
            $table->id();
            $table->string('username', 60)->unique();
            $table->string('name', 150);
            $table->string('phone', 20)->index();
            $table->string('email', 150)->nullable()->index();
            $table->string('password');
            $table->dateTime('phone_verified_at')->nullable();
            $table->boolean('is_demo')->default(false);       // demo login: no sign-in code (set by hand only)
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });

        // Planning Recommendation requests made on the portal (kamma_portal_requests). The land file is referenced by
        // its KLAES indexing id AND its file-number string (KLAES joins on the string), never altered.
        Schema::create('portal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 40)->unique();
            $table->unsignedBigInteger('portal_applicant_id')->index();
            $table->string('category', 20);                    // land | post_cofo
            $table->string('request_type', 40);                // Conversion, Sectional Titling, SLTR, Sub-Division, Merger, Extension, Change of Purpose
            $table->unsignedBigInteger('klaes_file_indexing_id')->index();
            $table->string('file_number', 100)->index();
            $table->string('plot_no', 100)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 300)->nullable();
            $table->string('file_land_use', 100)->nullable();
            $table->unsignedSmallInteger('proposed_plots')->nullable();     // Sub-Division
            $table->decimal('extension_area', 12, 2)->nullable();          // Extension, m²
            $table->string('proposed_use', 100)->nullable();               // Change of Purpose
            $table->string('proposed_use_other', 100)->nullable();
            $table->string('description', 1000)->nullable();
            $table->boolean('building_exists');
            $table->boolean('has_bpa')->nullable();
            $table->string('applicant_name', 300);
            $table->string('contact_address', 500);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->text('documents_submitted')->nullable();               // JSON list of ticked documents
            $table->string('documents_other', 200)->nullable();
            $table->string('stage', 30)->default('received');               // App\Support\Portal\PortalTimeline::STEPS keys | complete
            $table->unsignedBigInteger('shadow_file_id')->nullable()->index(); // the staff-side Shadow File once commissioned
            $table->dateTime('declared_at');
            $table->timestamps();
        });

        // Merger: the other land files (kamma_portal_request_merge_files).
        Schema::create('portal_request_merge_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('portal_request_id')->index();
            $table->unsignedBigInteger('klaes_file_indexing_id');
            $table->string('file_number', 100);
            $table->timestamps();
        });

        // Uploaded documents (kamma_portal_request_files); go into the DMS once the Shadow File is commissioned.
        Schema::create('portal_request_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('portal_request_id')->index();
            $table->string('original_name', 300);
            $table->string('stored_path', 500);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_request_files');
        Schema::dropIfExists('portal_request_merge_files');
        Schema::dropIfExists('portal_requests');
        Schema::dropIfExists('portal_applicants');
    }
};
