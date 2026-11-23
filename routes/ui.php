<?php

/*
| KAMMA screens built in the UI-first phase (user 2026-11-23: "first finish the UI and frontend ... then generate the URLs,
| and next we can focus on the backend"), copied from KADCA's routes/ui.php. Required from routes/web.php.
| Read-only KLAES record screens read the real KLAES tables through the read-only 'klaes' connection; forms whose
| backend is not built post to ui.preview (stores nothing). Screens over KAMMA's own tables carry kamma.preview.
*/

use App\Http\Controllers\Records\RecordsController;
use App\Http\Controllers\Ui;
use Illuminate\Support\Facades\Route;

$kammaTables = 'kamma.preview:file_indexings,scannings,page_typings,serials,units,file_trackers,file_movements,file_requests,ksi_reports,ksi_photos,memos,bills,title_status_updates,desk_movements';

Route::middleware('auth')->group(function () {
    // Stub save for forms whose backend comes later.
    Route::post('/preview/{form}', [Ui\PreviewController::class, 'save'])->where('form', '[a-z0-9\-]+')->name('ui.preview');

    // Read-only KLAES record screens (Information Products, Land Records, DMS registers, GIS, CRM).
    Route::get('/information-products', [RecordsController::class, 'informationProducts'])->name('records.information-products');
    Route::get('/land-records', [RecordsController::class, 'landRecords'])->name('records.land');
    Route::get('/records/{dataset}', [RecordsController::class, 'show'])->where('dataset', '[a-z\-]+')->name('records.show');
    Route::get('/crm/customers/{type}', [RecordsController::class, 'crm'])->where('type', '[a-z\-]+')->name('crm.customers');

});

// Screens over KAMMA's own tables (preview schema until the migrations are run).
Route::middleware(['auth', $kammaTables])->group(function () {
    Route::get('/lookup/shadow-files', [Ui\PreviewController::class, 'shadowFiles'])->name('ui.shadow-files');

    Route::get('/crm', [Ui\CrmController::class, 'dashboard'])->name('crm.dashboard');
    Route::get('/crm/appointments', [Ui\CrmController::class, 'appointments'])->name('crm.appointments');
    Route::get('/crm/calendar', [Ui\CrmController::class, 'calendar'])->name('crm.calendar');

    Route::prefix('dms')->name('dms.')->group(function () {
        Route::get('/serial-grouping', [Ui\DmsExtraController::class, 'grouping'])->name('grouping');
        Route::get('/print-labels', [Ui\DmsExtraController::class, 'labels'])->name('labels');
        Route::get('/print-labels/sheet', [Ui\DmsExtraController::class, 'labelSheet'])->name('labels.sheet');
        Route::get('/sign-in-out-sheet', [Ui\DmsExtraController::class, 'signin'])->name('signin');
        Route::get('/blind-scanning', [Ui\DmsExtraController::class, 'blind'])->name('blind');
        Route::get('/quality-control', [Ui\DmsExtraController::class, 'qualityControl'])->name('qc');
    });

    Route::get('/activity-monitoring', [Ui\ActivityController::class, 'monitoring'])->name('activity.monitoring');
    Route::get('/system-admin/activity-logs', [Ui\ActivityController::class, 'logs'])->name('admin.activity-logs');
    Route::get('/folio', [Ui\ArchiveExtraController::class, 'folio'])->name('folio.index');

    Route::prefix('revm')->name('revm.')->group(function () {
        Route::get('/contravention', [Ui\RevenueController::class, 'contravention'])->name('contravention');
        Route::get('/legacy', [Ui\RevenueController::class, 'legacy'])->name('legacy');
        Route::get('/transaction-token-control', [Ui\RevenueController::class, 'tokens'])->name('tokens');
    });

});
