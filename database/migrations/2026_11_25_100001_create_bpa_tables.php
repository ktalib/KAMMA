<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Building Plan Approval moved from the KADCA app into KAMMA (user 2026-11-25, option A: "it's coming under KAMMA").
 * The KADCA tables as they stand after its migrations 2026_11_22_000001 .. 2026_11_25_000001, created here in one go.
 * Tables whose names KAMMA already uses get a bpa_ prefix: bills -> bpa_bills, memos -> bpa_memos, and the Zonal Office
 * tables follow (bpa_zonal_assignments, bpa_site_inspections, bpa_site_inspection_photos, bpa_conveyances).
 * KAMMA's serials table (2026_11_23_000002) is shared. BPA files join KAMMA file tracking through
 * file_trackers.bpa_application_id, and the BPA desks get their units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpa_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 40)->unique();
            $table->string('status', 30)->default('Submitted');
            $table->string('stage', 40)->default('assessment')->index();
            $table->string('application_type', 30)->nullable();           // Fresh Application | Registration
            $table->unsignedBigInteger('bp_file_id')->nullable()->index();
            // The KLAES land file and its details at the time of application (empty id = land file typed on the BP file).
            $table->unsignedBigInteger('klaes_file_indexing_id')->nullable()->index();
            $table->string('file_number', 100)->index();
            $table->string('file_title', 500)->nullable();
            $table->string('plot_no', 100)->nullable();
            $table->string('district', 200)->nullable();
            $table->string('lga', 100)->nullable();
            $table->string('location', 500)->nullable();
            $table->string('plot_size', 50)->nullable();
            $table->string('file_land_use', 100)->nullable();
            $table->string('file_land_use_code', 10)->nullable();
            $table->string('title_status', 100)->nullable();
            $table->string('proposed_use', 60);
            $table->string('proposed_use_other', 200)->nullable();
            $table->string('land_use_check', 20);
            $table->string('land_use_note', 500)->nullable();
            $table->string('developer_name', 300);
            $table->string('cadastral_zone', 100)->nullable();
            $table->string('cadastral_zone_plot_no', 150)->nullable();
            $table->string('street_name', 200)->nullable();
            $table->string('dwelling_plot_floor', 100)->nullable();
            $table->string('dwelling_unit', 100)->nullable();
            $table->string('contact_address', 500)->nullable();
            $table->string('developer_phone', 40)->nullable();
            $table->string('developer_email', 150)->nullable();
            $table->string('representative_phone', 40)->nullable();
            $table->string('representative_email', 150)->nullable();
            $table->string('submission_type', 30);
            $table->string('submission_type_other', 200)->nullable();
            $table->text('documents_submitted')->nullable();
            $table->string('documents_other', 300)->nullable();
            $table->text('drawings_submitted')->nullable();
            $table->string('plan_received_as', 40);
            $table->unsignedSmallInteger('number_of_plots')->default(1);
            $table->string('submitted_by_name', 300)->nullable();
            $table->date('submitted_on')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->string('received_by_name', 300)->nullable();
            $table->string('received_by_rank', 150)->nullable();
            $table->string('bp_file_no', 40)->nullable();
            $table->dateTime('commissioned_at')->nullable();
            $table->unsignedBigInteger('commissioned_by')->nullable();
            $table->string('commissioned_by_name', 300)->nullable();
            $table->dateTime('bpa_approved_at')->nullable();
            $table->timestamps();
        });
        $apps = DB::getTablePrefix().'bpa_applications';
        // One BP number per file. Filtered: SQL Server lets a plain unique index hold only one NULL.
        DB::statement("CREATE UNIQUE INDEX {$apps}_bp_file_no_unique ON {$apps} (bp_file_no) WHERE bp_file_no IS NOT NULL");

        Schema::create('bpa_application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->constrained('bpa_applications')->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('original_name', 300);
            $table->string('stored_path', 500);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bpa_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_no', 40)->unique();
            $table->foreignId('bpa_application_id')->nullable()->constrained('bpa_applications')->cascadeOnDelete();
            $table->string('source', 20)->default('initial')->index();
            $table->string('bill_type', 60);
            $table->decimal('rate', 14, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('Unpaid');
            $table->unsignedBigInteger('klaes_file_indexing_id')->nullable();
            $table->string('file_number', 100)->nullable()->index();
            $table->string('payer_name', 300)->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->string('plot_no', 100)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 300)->nullable();
            $table->json('details')->nullable();
            $table->text('remarks')->nullable();
            $table->date('due_on')->nullable();
            $table->string('receipt_no', 100)->nullable();
            $table->date('paid_on')->nullable();
            $table->decimal('amount_paid', 14, 2)->nullable();
            $table->string('payment_channel', 60)->nullable();
            $table->string('payment_channel_other', 100)->nullable();
            $table->unsignedBigInteger('payment_recorded_by')->nullable();
            $table->string('payment_recorded_by_name', 300)->nullable();
            $table->dateTime('payment_recorded_at')->nullable();
            $table->string('official_receipt_no', 40)->nullable();
            $table->dateTime('receipted_at')->nullable();
            $table->unsignedBigInteger('receipted_by')->nullable();
            $table->string('receipted_by_name', 300)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 300)->nullable();
            $table->timestamps();
        });
        $bills = DB::getTablePrefix().'bpa_bills';
        DB::statement("CREATE UNIQUE INDEX {$bills}_receipt_no_unique ON {$bills} (receipt_no) WHERE receipt_no IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX {$bills}_official_receipt_no_unique ON {$bills} (official_receipt_no) WHERE official_receipt_no IS NOT NULL");

        Schema::create('bpa_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->constrained('bpa_applications')->cascadeOnDelete();
            $table->string('from_stage', 40)->nullable();
            $table->string('to_stage', 40)->nullable();
            $table->string('action', 40);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->string('acted_by_name', 300)->nullable();
            $table->string('acted_by_rank', 150)->nullable();
            $table->dateTime('acted_at');
            $table->timestamps();
            $table->index(['from_stage', 'action']);
        });

        Schema::create('bpa_zonal_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->constrained('bpa_applications');
            $table->string('instruction_no', 40)->unique();
            $table->unsignedBigInteger('officer_id');
            $table->string('officer_name', 300);
            $table->string('officer_rank', 150)->nullable();
            $table->json('scope');
            $table->date('due_date');
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('issued_by');
            $table->string('issued_by_name', 300);
            $table->dateTime('issued_at');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bpa_site_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->constrained('bpa_applications');
            $table->foreignId('zonal_assignment_id')->nullable()->constrained('bpa_zonal_assignments');
            $table->date('visit_date');
            $table->string('visit_time', 5)->nullable();
            $table->string('person_met', 200)->nullable();
            $table->text('visit_notes')->nullable();
            $table->string('plot_no', 100);
            $table->string('district', 150);
            $table->string('cadastral_zone', 150)->nullable();
            $table->string('land_use', 150);
            $table->string('purpose_clause', 300)->nullable();
            $table->string('plot_size', 100)->nullable();
            $table->string('density', 100)->nullable();
            $table->string('street_name', 200)->nullable();
            $table->string('nature_of_plot', 60);
            $table->string('nature_of_plot_other', 200)->nullable();
            $table->string('slope', 60);
            $table->string('vacancy', 20);
            $table->text('existing_development')->nullable();
            $table->string('approved_earlier', 5)->nullable();
            $table->string('conformity', 30)->nullable();
            $table->text('non_conformity')->nullable();
            $table->string('level_of_service', 30);
            $table->string('level_of_service_details', 300)->nullable();
            foreach (['front_setback', 'rear_setback', 'left_setback', 'right_setback', 'plot_frontage', 'plot_depth', 'building_footprint', 'building_height'] as $m) {
                $table->decimal($m, 10, 2)->nullable();
            }
            $table->unsignedSmallInteger('floors')->nullable();
            $table->string('recommendation', 60);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('officer_id');
            $table->string('officer_name', 300);
            $table->string('officer_rank', 150)->nullable();
            $table->dateTime('filed_at');
            $table->string('review_decision', 20)->nullable();
            $table->text('review_remarks')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('reviewed_by_name', 300)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bpa_site_inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_inspection_id')->constrained('bpa_site_inspections')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('original_name', 300)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('taken_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bpa_conveyances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->unique()->constrained('bpa_applications');
            $table->string('permit_no', 40)->unique();
            $table->string('applicant', 300);
            $table->date('date_of_approval');
            $table->date('commencement_date');
            $table->date('expiry_date');
            $table->json('conditions');
            $table->text('additional_conditions')->nullable();
            $table->string('declarant_name', 300);
            $table->date('declarant_date')->nullable();
            $table->string('authorized_officer', 300);
            $table->date('officer_date')->nullable();
            $table->unsignedBigInteger('raised_by');
            $table->string('raised_by_name', 300);
            $table->timestamps();
        });

        Schema::create('bpa_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bpa_application_id')->constrained('bpa_applications');
            $table->string('memo_no', 40)->unique();
            $table->string('to', 300);
            $table->string('through', 300)->nullable();
            $table->string('from', 300);
            $table->date('memo_date');
            $table->string('subject', 300);
            $table->text('body');
            $table->text('recommendation')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('created_by_name', 300);
            $table->timestamps();
        });

        // BP files (indexing / commissioning, user briefing 2026-11-25).
        Schema::create('bp_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 500)->nullable();
            $table->boolean('is_other')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        $now = now();
        foreach ([
            ['Zone I', 'City centre: the inner circle around Gidan Murtala.', false],
            ['Zone II', 'North: above the Dawanau/Katsina road – Zaria road line, both sides of the Hadejia road.', false],
            ['Zone III', 'South-west: between the Katsina road and the BUK – Gwarzo road.', false],
            ['Zone IV', 'South-east: between the BUK – Gwarzo road and the Zaria road.', false],
            ['UC', 'Urban Centre.', false],
            ['Other', 'Zone not known yet: pick this and set the zone later.', true],
        ] as $i => [$name, $description, $isOther]) {
            DB::table('bp_zones')->insert([
                'name' => $name, 'description' => $description, 'is_other' => $isOther, 'is_active' => true,
                'sort_order' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::create('bp_groupings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('serial');
            $table->string('bp_file_no', 30)->unique();
            $table->string('status', 15)->default('Available');
            $table->unsignedBigInteger('bp_file_id')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->unique(['year', 'serial']);
            $table->index(['year', 'status']);
        });

        Schema::create('bp_files', function (Blueprint $table) {
            $table->id();
            $table->string('bp_file_no', 30);
            $table->unsignedSmallInteger('bp_year');
            $table->unsignedInteger('bp_serial');
            $table->string('origin', 15);
            $table->unsignedBigInteger('bp_grouping_id')->nullable();
            $table->boolean('land_in_klaes')->default(true);
            $table->unsignedBigInteger('klaes_file_indexing_id')->nullable()->index();
            $table->string('land_file_number', 100)->index();
            $table->string('land_file_title', 500)->nullable();
            $table->string('plot_no', 150)->nullable();
            $table->string('district', 150)->nullable();
            $table->string('lga', 150)->nullable();
            $table->string('location', 500)->nullable();
            $table->string('land_use', 100)->nullable();
            $table->string('land_use_code', 10)->nullable();
            $table->string('plot_size', 100)->nullable();
            $table->string('street_name', 200)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('title_status', 150)->nullable();
            $table->unsignedBigInteger('bp_zone_id')->nullable()->index();
            $table->string('zone_name', 60)->nullable();
            $table->string('bp_file_title', 500)->nullable();
            $table->string('registry', 150)->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->string('shelf_location', 150)->nullable();
            $table->string('serial_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('indexed_by')->nullable();
            $table->string('indexed_by_name', 300)->nullable();
            $table->dateTime('indexed_at');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_name', 300)->nullable();
            $table->unsignedBigInteger('bpa_application_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->index(['bp_year', 'bp_serial']);
        });
        $bp = DB::getTablePrefix().'bp_files';
        DB::statement("CREATE UNIQUE INDEX {$bp}_bp_file_no_unique ON {$bp} (bp_file_no) WHERE is_deleted = 0");

        // BPA files in KAMMA file tracking: one tracker per application.
        Schema::table('file_trackers', function (Blueprint $table) {
            $table->unsignedBigInteger('bpa_application_id')->nullable();
        });
        $trk = DB::getTablePrefix().'file_trackers';
        DB::statement("CREATE UNIQUE INDEX {$trk}_bpa_application_unique ON {$trk} (bpa_application_id) WHERE bpa_application_id IS NOT NULL");

        // The BPA desks, as units files move between (KADCA's seed). Added only when the stage has no unit yet.
        $sort = (int) DB::table('units')->max('sort_order');
        foreach ([['Architecture', 'architecture'], ['Director (Arch.)', 'director_arch'], ['Deputy Director (Arch.)', 'dd_arch'], ['Zonal Office (BPA)', 'zonal_office']] as [$name, $stage]) {
            if (! DB::table('units')->where('stage', $stage)->exists()) {
                $sort += 10;
                DB::table('units')->insert(['name' => $name, 'stage' => $stage, 'is_active' => true, 'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('units')->whereIn('stage', ['architecture', 'director_arch', 'dd_arch', 'zonal_office'])->delete();
        $trk = DB::getTablePrefix().'file_trackers';
        DB::statement("DROP INDEX {$trk}_bpa_application_unique ON {$trk}");
        Schema::table('file_trackers', fn (Blueprint $t) => $t->dropColumn('bpa_application_id'));
        foreach (['bp_files', 'bp_groupings', 'bp_zones', 'bpa_memos', 'bpa_conveyances', 'bpa_site_inspection_photos', 'bpa_site_inspections',
            'bpa_zonal_assignments', 'bpa_movements', 'bpa_bills', 'bpa_application_files', 'bpa_applications'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
