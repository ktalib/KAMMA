<?php

use App\Http\Controllers\Bpa\ApplicationController;
use App\Http\Controllers\Bpa\BillController;
use App\Http\Controllers\Bpa\BpaReportController;
use App\Http\Controllers\Bpa\BpaUiController;
use App\Http\Controllers\Bpa\BpFileController;
use App\Http\Controllers\Bpa\BuildingPlanController;
use App\Http\Controllers\Bpa\CommissioningController;
use App\Http\Controllers\Bpa\DeskController;
use App\Http\Controllers\Bpa\PaymentController;
use App\Http\Controllers\Bpa\PaymentReportController;
use App\Http\Controllers\Bpa\TemplateController;
use App\Http\Controllers\Bpa\ZonalController;
use Illuminate\Support\Facades\Route;

/*
| Development Permit = Building Plan Approval, staff side. Moved in from the KADCA app 2026-11-25 (user, option A: the
| building plan work "is coming under KAMMA"; the applicant side is the KAMMA portal's /portal/building-plans).
| Same routes and names as in KADCA (bpa.*; KADCA's kadca.* group is bpa.* here), all under /development-permit.
| UI first like the rest of KAMMA: until the BPA tables exist the screens run in preview (kamma.preview).
*/

$bpaTables = 'kamma.preview:serials,units,file_trackers,file_movements,bpa_applications,bpa_application_files,bpa_bills,bpa_movements,'
    .'bpa_zonal_assignments,bpa_site_inspections,bpa_site_inspection_photos,bpa_conveyances,bpa_memos,bp_zones,bp_groupings,bp_files';

Route::middleware(['auth', $bpaTables])->prefix('development-permit')->name('bpa.')->group(function () {
    // Dashboard, GIS
    Route::get('/', [BpaUiController::class, 'dashboard'])->name('dashboard');
    Route::get('/gis/basemap', [BpaUiController::class, 'basemap'])->name('basemap');
    Route::get('/gis/charting', [BpaUiController::class, 'charting'])->name('charting');

    // Assessment Stage: applications and their application bill.
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/files/{file}', [ApplicationController::class, 'download'])->name('applications.files.download');
    Route::get('/bills', [BillController::class, 'index'])->name('bills.index');

    // Architecture: payment, BP file commissioning, building plans.
    Route::get('/architecture/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/architecture/payments/{bill}', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/architecture/commissioning', [CommissioningController::class, 'index'])->name('commissioning.index');
    Route::post('/architecture/commissioning/{application}', [CommissioningController::class, 'store'])->name('commissioning.store');
    Route::get('/architecture/building-plans', [BuildingPlanController::class, 'index'])->name('building-plans.index');
    Route::post('/architecture/building-plans/{application}/resend', [BuildingPlanController::class, 'resend'])->name('building-plans.resend');

    // Building Plan Files (user 2026-11-25): index existing BP files from the grouping, commission new ones.
    Route::prefix('bp-files')->name('bp-files.')->group(function () {
        Route::get('/', [BpFileController::class, 'index'])->name('index');
        // Each list page (table + dashboard) comes first; its form is under /new (user 2026-11-25).
        Route::get('/index-existing', [BpFileController::class, 'indexed'])->name('indexed');
        Route::get('/index-existing/new', [BpFileController::class, 'createIndexed'])->name('create-indexed');
        Route::post('/index-existing', [BpFileController::class, 'storeIndexed'])->name('store-indexed');
        Route::get('/commission', [BpFileController::class, 'commissioned'])->name('commissioned');
        Route::get('/commission/new', [BpFileController::class, 'createCommissioned'])->name('create-commissioned');
        Route::post('/commission', [BpFileController::class, 'storeCommissioned'])->name('store-commissioned');
        Route::get('/grouping', [BpFileController::class, 'grouping'])->name('grouping');
        Route::get('/check-number', [BpFileController::class, 'checkNumber'])->name('check-number');
        Route::get('/search', [BpFileController::class, 'search'])->name('search');
        Route::get('/land/{id}', [BpFileController::class, 'land'])->whereNumber('id')->name('land');
        Route::get('/{bpFile}/edit', [BpFileController::class, 'edit'])->whereNumber('bpFile')->name('edit');
        Route::put('/{bpFile}', [BpFileController::class, 'update'])->whereNumber('bpFile')->name('update');
    });

    // Print templates (the KNUPDA paper forms, KAMMA's): preview only, not on the sidebar (user 2026-11-24).
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->where('template', '[a-z\-]+')->name('templates.show');

    // Bills & Payments report, Reports.
    Route::get('/payments-report', [PaymentReportController::class, 'index'])->name('payments-report');
    Route::get('/payments-report.csv', [PaymentReportController::class, 'csv'])->name('payments-report.csv');
    Route::get('/reports', [BpaReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/applications-register.csv', [BpaReportController::class, 'register'])->name('reports.register');

    // Approvals: Director (Arch.) and Deputy Director (Arch.).
    Route::get('/approvals/{desk}', [DeskController::class, 'index'])->whereIn('desk', ['director', 'deputy-director'])->name('desks.index');
    Route::post('/approvals/{desk}/{application}', [DeskController::class, 'decide'])->whereIn('desk', ['director', 'deputy-director'])->name('desks.decide');

    // Zonal Office, inspection, field data, memo, Final Conveyance.
    Route::get('/approvals/head-of-zonal-office', [ZonalController::class, 'approval'])->name('zonal-approval');
    Route::post('/approvals/head-of-zonal-office/{application}/assign', [ZonalController::class, 'assign'])->whereNumber('application')->name('zonal.assign');
    Route::post('/approvals/head-of-zonal-office/{application}/review', [ZonalController::class, 'review'])->whereNumber('application')->name('zonal.review');
    Route::get('/zonal/instructions/{assignment}', [ZonalController::class, 'instruction'])->whereNumber('assignment')->name('zonal.instruction');
    Route::get('/zonal/inspection', [ZonalController::class, 'inspection'])->name('inspection');
    Route::post('/zonal/inspection', [ZonalController::class, 'storeInspection'])->name('inspection.store');
    Route::get('/zonal/inspection/{inspection}', [ZonalController::class, 'showInspection'])->whereNumber('inspection')->name('inspection.show');
    Route::get('/zonal/photos/{photo}', [ZonalController::class, 'photo'])->whereNumber('photo')->name('inspection.photo');
    Route::get('/zonal/field-data', [ZonalController::class, 'fieldData'])->name('field-data');
    Route::get('/final-conveyance', [ZonalController::class, 'conveyance'])->name('conveyance');
    Route::post('/final-conveyance', [ZonalController::class, 'storeConveyance'])->name('conveyance.store');
    Route::get('/final-conveyance/{conveyance}', [ZonalController::class, 'showConveyance'])->whereNumber('conveyance')->name('conveyance.show');
    Route::get('/memo', [ZonalController::class, 'memo'])->name('memo');
    Route::post('/memo', [ZonalController::class, 'storeMemo'])->name('memo.store');
    Route::get('/memo/{memo}', [ZonalController::class, 'showMemo'])->whereNumber('memo')->name('memo.show');
});
