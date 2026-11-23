<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KAMMA's own workflow tables (KAMMA Workflow v1.0 + briefing 2026-11-22), for the screens built UI-first on
 * 2026-11-23. Copied in shape from KLAES Physical Planning, renamed for KAMMA:
 *
 *  - desk_movements: the land file's route through KAMMA (PS → MD KAMMA → Department → Director → DD → Zonal
 *    Officer → Field Officer → KSI → Planning Recommendation → back to PS), with the current desk on the shadow
 *    file (file_indexings.stage).
 *  - ksi_reports: KSI, KAMMA Site Inspection (KLAES joint_site_inspection_reports, same fields; "it's not JSI in
 *    KAMMA"), one table for Conversion / ST / SLTR / Master KSI told apart by `kind` (KLAES keeps SLTR in its own
 *    table; the user may prefer that here too: open question). `building_exists` + `bpa_approved` drive the
 *    4× no-BPA betterment penalty.
 *  - ksi_photos: geocoded site photographs (GPS + time from the field app), on the shadow file.
 *  - memos: the KAMMA Memo (KLAES memos), per kind.
 *  - bills: processing fee and betterment bills (KLAES billing PP_BETTERMENT_BILL), kept in KAMMA until KAMMA bills go
 *    into KLAES `billing` with source KAMMA (needs user approval + a grant).
 *  - title_status_updates: KAMMA's Title Status Update records (KLAES title_status_applications shape).
 *  - portal_applicants / portal_applications: the applicant portal (/portal), session-only until this runs.
 *
 * No foreign keys onto KLAES tables. NOT RUN (user 2026-11-23: UI first); run later with approval, with --path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_indexings', function (Blueprint $table) {
            $table->string('stage', 40)->nullable()->index();         // App\Support\KammaDesk
            $table->string('application_type', 40)->nullable();       // conversion | st | sltr | master (post-CofO)
            $table->dateTime('received_from_ps_at')->nullable();
            $table->dateTime('returned_to_ps_at')->nullable();        // phase 4: land file detached and returned
        });

        Schema::create('desk_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_indexing_id')->constrained('file_indexings');
            $table->string('from_stage', 40)->nullable();
            $table->string('to_stage', 40);
            $table->string('decision', 30)->nullable();               // forwarded | recommended | not_recommended | returned
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();       // KLAES dbo.users.id
            $table->string('acted_by_name', 300)->nullable();
            $table->dateTime('acted_at');
            $table->timestamps();
        });

        Schema::create('ksi_reports', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();                      // conversion | st | sltr | master
            $table->string('post_cofo_type', 40)->nullable();         // master: Sub-Division | Merger | Extension | Change of Purpose
            $table->unsignedBigInteger('file_indexing_id')->nullable()->index(); // the KAMMA shadow file
            $table->unsignedBigInteger('klaes_file_indexing_id')->nullable();    // dbo.file_indexings.id (no FK)
            $table->string('file_number', 100)->index();
            $table->date('inspection_date')->nullable();
            $table->string('applicant_name', 300)->nullable();
            $table->string('applicant_address', 500)->nullable();
            $table->string('lpkn_no', 100)->nullable();
            $table->string('tp_no', 100)->nullable();
            $table->string('scheme_no', 100)->nullable();
            $table->string('plot_number', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 500)->nullable();              // never includes the plot number (KLAES rule)
            $table->string('boundary_north', 300)->nullable();
            $table->string('boundary_east', 300)->nullable();
            $table->string('boundary_south', 300)->nullable();
            $table->string('boundary_west', 300)->nullable();
            $table->boolean('existing_structure')->nullable();
            $table->string('existing_structure_dims', 200)->nullable();
            $table->boolean('building_exists')->nullable();           // penalty input (Site Assessment: Building_Exists)
            $table->boolean('bpa_approved')->nullable();              // penalty input (Land_File_Status = BPA_Approved)
            $table->string('service', 191)->nullable();
            $table->string('private_layout_no', 100)->nullable();
            $table->string('detailed_land_use', 191)->nullable();
            $table->string('purpose', 191)->nullable();
            $table->string('road_category', 191)->nullable();
            $table->boolean('conformity')->nullable();
            $table->boolean('accessibility')->nullable();
            $table->boolean('reservation_required')->nullable();
            $table->string('existing_reservation', 1000)->nullable();
            $table->string('recommended_reservation', 1000)->nullable();
            $table->string('reservation_comment', 191)->nullable();
            $table->string('reservation_meters', 50)->nullable();
            $table->boolean('adequate_size_requirement')->nullable();
            $table->boolean('land_traversing_utility')->nullable();
            $table->decimal('existing_site_length', 12, 2)->nullable();
            $table->decimal('existing_site_width', 12, 2)->nullable();
            $table->decimal('existing_site_area', 14, 2)->nullable();
            $table->decimal('recommended_site_length', 12, 2)->nullable();
            $table->decimal('recommended_site_width', 12, 2)->nullable();
            $table->decimal('recommended_site_area', 14, 2)->nullable();
            $table->boolean('has_extension')->nullable();
            $table->string('extension_dims', 100)->nullable();
            $table->decimal('extension_site_area', 14, 2)->nullable();
            $table->unsignedInteger('units_count')->nullable();       // ST
            $table->boolean('need_for_eia')->nullable();
            $table->boolean('available_on_ground')->nullable();
            $table->string('prevailing_land_use', 191)->nullable();
            $table->string('applied_land_use', 191)->nullable();
            $table->string('compliance_status', 191)->nullable();     // obtainable | not_obtainable | other text
            $table->string('inspection_officer', 300)->nullable();
            $table->string('officer_rank', 150)->nullable();
            $table->text('additional_observations')->nullable();
            $table->string('status', 20)->default('draft');           // draft | generated | submitted | approved | declined
            $table->string('md_decision', 20)->nullable();            // MD KAMMA: approved | declined
            $table->text('md_remarks')->nullable();
            $table->unsignedBigInteger('md_decided_by')->nullable();
            $table->string('md_decided_by_name', 300)->nullable();
            $table->dateTime('md_decided_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_name', 300)->nullable();
            $table->timestamps();
            $table->index(['kind', 'status']);
        });

        Schema::create('ksi_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_indexing_id')->nullable()->index();
            $table->unsignedBigInteger('ksi_report_id')->nullable()->index();
            $table->string('stage', 30);                              // initial_site_visit | site_assessment
            $table->string('path', 500);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->dateTime('taken_at')->nullable();
            $table->string('device', 200)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('uploaded_by_name', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();                      // conversion | st | sltr
            $table->string('memo_no', 60)->unique();
            $table->unsignedBigInteger('ksi_report_id')->nullable()->index();
            $table->string('file_number', 100)->index();
            $table->string('applicant_name', 300)->nullable();
            $table->string('property_location', 500)->nullable();
            $table->text('planner_recommendation')->nullable();
            $table->date('planner_recommendation_date')->nullable();
            $table->text('md_recommendation')->nullable();             // MD KAMMA (KLAES: Director of Physical Planning)
            $table->date('md_recommendation_date')->nullable();
            $table->string('status', 20)->default('draft');           // draft | submitted | approved | declined
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 60)->unique();
            $table->string('bill_type', 20);                          // processing | betterment
            $table->string('source', 30)->index();                    // conversion | st | sltr | master_ksi
            $table->unsignedBigInteger('file_indexing_id')->nullable()->index();
            $table->unsignedBigInteger('ksi_report_id')->nullable()->index();
            $table->string('file_number', 100)->index();
            $table->string('payer_name', 300)->nullable();
            $table->string('category', 100)->nullable();               // betterment rate category
            $table->decimal('area', 14, 2)->nullable();                // recommended site measurement (m²)
            $table->decimal('base_fee', 14, 2)->nullable();
            $table->decimal('base_area', 14, 2)->nullable();
            $table->decimal('rate_per_extra_sqm', 12, 2)->nullable();
            $table->decimal('standard_amount', 14, 2);
            $table->boolean('building_exists')->default(false);
            $table->boolean('bpa_approved')->default(false);
            $table->unsignedTinyInteger('penalty_multiplier')->default(1); // 4 = no-BPA penalty
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('unpaid');          // unpaid | partial | paid
            $table->string('receipt_no', 100)->nullable()->unique();
            $table->decimal('amount_paid', 14, 2)->nullable();
            $table->string('channel', 30)->nullable();                // Bank | POS | Interswitch | USSD | Other
            $table->string('channel_other', 100)->nullable();
            $table->date('paid_on')->nullable();
            $table->string('evidence_path', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('title_status_updates', function (Blueprint $table) {
            $table->id();
            $table->string('file_number', 100)->index();
            $table->unsignedBigInteger('klaes_file_indexing_id')->nullable();
            $table->string('file_title', 500)->nullable();
            $table->string('title_type', 100);                         // Litigation, Revoke (CofO), … (KLAES TitleStatusApplication types)
            $table->string('title_type_other', 200)->nullable();
            $table->string('initiated_by', 100)->nullable();
            $table->string('initiated_by_other', 200)->nullable();
            $table->string('plot_number', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 500)->nullable();
            $table->text('reason');
            $table->text('remark')->nullable();
            $table->string('status', 20)->default('pending');         // pending | approved | rejected
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('portal_applicants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 300);
            $table->string('username', 100)->unique();
            $table->string('email', 191)->nullable();
            $table->string('phone', 30);
            $table->string('password');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('portal_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_applicant_id')->constrained('portal_applicants');
            $table->string('reference', 60)->unique();
            $table->string('application_type', 40);                   // Sub-Division | Merger | Extension | Change of Purpose | Conversion | ST | SLTR
            $table->string('file_number', 100)->nullable();
            $table->unsignedBigInteger('file_indexing_id')->nullable();
            $table->string('stage', 40)->default('ps');                // App\Support\KammaDesk
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_applications');
        Schema::dropIfExists('portal_applicants');
        Schema::dropIfExists('title_status_updates');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('memos');
        Schema::dropIfExists('ksi_photos');
        Schema::dropIfExists('ksi_reports');
        Schema::dropIfExists('desk_movements');
        Schema::table('file_indexings', fn (Blueprint $t) => $t->dropColumn(['stage', 'application_type', 'received_from_ps_at', 'returned_to_ps_at']));
    }
};
