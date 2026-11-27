<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applicant portal backend, step 1 (user 2026-11-25: "Building plans first"): real applicant accounts and Building Plan
 * applications saved from the portal into the staff Applications list.
 *
 *  - portal_applicants (made by 2026_11_23_000003, empty until now): account switches. The username is the mobile number
 *    in local form (0XXXXXXXXXX), so one account per mobile.
 *  - bpa_applications.portal_applicant_id: the applicant who sent the application on the portal (null = entered by staff).
 *
 * Planning Recommendation requests on the portal stay in preview (step 2; number format and fee not decided).
 * Own tables only, no foreign keys onto KLAES.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_applicants', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->index('phone');
            $table->index('email');
        });

        Schema::table('bpa_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('portal_applicant_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('bpa_applications', function (Blueprint $table) {
            $table->dropIndex(['portal_applicant_id']);
            $table->dropColumn('portal_applicant_id');
        });
        Schema::table('portal_applicants', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['email']);
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
