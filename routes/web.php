<?php

use App\Http\Controllers\Archive\DocWareActionController;
use App\Http\Controllers\Archive\DocWareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dms\BlindScanController;
use App\Http\Controllers\Dms\IndexingController;
use App\Http\Controllers\Dms\PageTypingController;
use App\Http\Controllers\Dms\ScanUploadController;
use App\Http\Controllers\Dms\PrintLabelController;
use App\Http\Controllers\Kamma\ApprovalController;
use App\Http\Controllers\Kamma\BillController;
use App\Http\Controllers\Kamma\EregistryController;
use App\Http\Controllers\Kamma\KammaDashboardController;
use App\Http\Controllers\Kamma\KsiController;
use App\Http\Controllers\Kamma\MemoController;
use App\Http\Controllers\Kamma\PaymentController;
use App\Http\Controllers\Kamma\ReportController;
use App\Http\Controllers\Kamma\ShadowFileController;
use App\Http\Controllers\Kamma\SiteVisitController;
use App\Http\Controllers\Kamma\TitleStatusController;
use App\Http\Controllers\Klaes\IndexedFileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RevmController;
use App\Http\Controllers\Records\RecordsController;
use App\Http\Controllers\Tracking\DepartmentMovementController;
use App\Http\Controllers\Tracking\FileTrackingController;
use App\Http\Controllers\Tracking\LogFileController;
use App\Http\Controllers\Tracking\QuickSearchController;
use App\Http\Controllers\Tracking\UnitMovementController;
use App\Http\Controllers\Ui\ArchiveExtraController;
use Illuminate\Support\Facades\Route;

/*
| KAMMA (KLAES-METRO) routes. The sidebar is config/kamma_menu.php; UI-first screens (CRM, DMS registers, GIS, HR,
| System Admin …) are in routes/ui.php.
|
| UI first (user 2026-11-23): KAMMA's own tables are not created yet. Screens over them carry
| `kamma.preview:<tables>` (App\Http\Middleware\PreviewWhenNotMigrated): until those tables exist the screen renders
| from an in-memory preview schema with empty lists, and saves answer "Saved (preview)".
*/

$dmsTables = 'kamma.preview:file_indexings,scannings,page_typings';
$trackingTables = 'kamma.preview:file_indexings,serials,units,file_trackers,file_movements,file_requests';
$allTables = 'kamma.preview:file_indexings,scannings,page_typings,serials,units,file_trackers,file_movements,file_requests,ksi_reports,ksi_photos,memos,bills,title_status_updates,desk_movements';

Route::get('/', function () {
    return view('welcome');
});

// The KLAES-METRO dashboard (the agency's own name on it, briefing 2026-11-22) and the MD's View.
Route::middleware(['auth', $allTables])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/md-view', [DashboardController::class, 'md'])->name('md.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/records/dashboard/{group}', [RecordsController::class, 'dashboard'])
        ->whereIn('group', ['products', 'land'])
        ->name('records.dashboard');
    Route::get('/records/{group}/{dataset}', [RecordsController::class, 'index'])
        ->where('group', '[a-z-]+')
        ->where('dataset', '[a-z-]+')
        ->name('records.index');
});

// My account: read-only, the details are KLAES's (dbo.users) and are changed in KLAES.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// Menu items with no screen at all land here instead of a dead link.
Route::get('/not-built', fn (\Illuminate\Http\Request $request) => view('not-built', [
    'item' => mb_substr((string) $request->query('item', ''), 0, 200),
]))->middleware('auth')->name('not-built');


// File Tracking, copied from KADCA (KLAES's File Tracker concept) over KAMMA's shadow files: KMA-TRK tracking IDs.
Route::middleware(['auth', $trackingTables])->prefix('file-tracking')->name('tracking.')->group(function () {
    Route::get('/', [FileTrackingController::class, 'dashboard'])->name('dashboard');
    Route::get('/search', [QuickSearchController::class, 'index'])->name('search');
    Route::get('/search/resolve', [QuickSearchController::class, 'resolve'])->name('quick-search.resolve');
    Route::post('/search/requests', [QuickSearchController::class, 'store'])->name('quick-search.request');
    Route::post('/search/requests/{fileRequest}/respond', [QuickSearchController::class, 'respond'])->name('quick-search.respond');
    Route::get('/search/log', [QuickSearchController::class, 'log'])->name('quick-search.log');
    Route::get('/search/feedback', [QuickSearchController::class, 'feedback'])->name('quick-search.feedback');
    Route::get('/search/report', [QuickSearchController::class, 'report'])->name('quick-search.report');
    Route::get('/search/report.csv', [QuickSearchController::class, 'csv'])->name('quick-search.csv');
    Route::get('/find', [FileTrackingController::class, 'find'])->name('find');
    Route::get('/find/lookup', [FileTrackingController::class, 'archiveLookup'])->name('find.lookup');
    Route::get('/log', [LogFileController::class, 'create'])->name('create');
    Route::post('/log', [LogFileController::class, 'store'])->name('store');
    Route::get('/log/file', [LogFileController::class, 'file'])->name('log.file');
    Route::get('/log/officers', [LogFileController::class, 'officers'])->name('log.officers');
    Route::get('/log/list', [LogFileController::class, 'list'])->name('log.list');
    Route::get('/lookup/files', [FileTrackingController::class, 'lookup'])->name('lookup');
    Route::get('/movement', [DepartmentMovementController::class, 'index'])->name('movement');
    Route::get('/movement/data', [DepartmentMovementController::class, 'data'])->name('movement.data');
    Route::get('/movement/scan', [DepartmentMovementController::class, 'scan'])->name('movement.scan');
    Route::post('/movement/log', [DepartmentMovementController::class, 'log'])->name('movement.log');
    Route::get('/units', [UnitMovementController::class, 'units'])->name('units');
    Route::post('/units', [UnitMovementController::class, 'storeUnit'])->name('units.store');
    Route::patch('/units/{unit}', [UnitMovementController::class, 'updateUnit'])->name('units.update');
    Route::get('/{tracker}', [FileTrackingController::class, 'show'])->name('show');
    Route::get('/{tracker}/label', [FileTrackingController::class, 'label'])->name('label');
    Route::post('/{tracker}/send', [FileTrackingController::class, 'send'])->name('send');
    Route::post('/{tracker}/receive', [FileTrackingController::class, 'receive'])->name('receive');
});

// DMS (the briefing's MDC exercise), copied from KADCA/KLAES: File Indexing Assistant = matching an existing KLAES file
// (its indexing record copied into KAMMA's indexing table and flagged kamma_matching on KLAES's dbo.file_indexings +
// dbo.fileNumber, the only KLAES columns KAMMA may write), then Scan Upload and Page Typing.
Route::middleware(['auth', $dmsTables])->prefix('dms')->name('dms.')->group(function () {
    Route::get('/indexing', [IndexingController::class, 'index'])->name('indexing.index');
    Route::get('/indexing/lookup', [IndexingController::class, 'lookup'])->name('indexing.lookup');
    Route::get('/indexing/klaes/{id}', [IndexingController::class, 'klaes'])->whereNumber('id')->name('indexing.klaes');
    Route::post('/indexing', [IndexingController::class, 'store'])->name('indexing.store');
    Route::get('/indexing/{indexing}', [IndexingController::class, 'show'])->whereNumber('indexing')->name('indexing.show');
    Route::get('/scanning', [ScanUploadController::class, 'index'])->name('scanning.index');
    Route::get('/scanning/files', [ScanUploadController::class, 'files'])->name('scanning.files');
    Route::post('/scanning', [ScanUploadController::class, 'upload'])->name('scanning.upload');
    Route::get('/files/{indexing}/pages', [ScanUploadController::class, 'pages'])->whereNumber('indexing')->name('scanning.pages');
    Route::get('/scans/{scan}', [ScanUploadController::class, 'image'])->whereNumber('scan')->name('scans.image');
    Route::delete('/scans/{scan}', [ScanUploadController::class, 'destroy'])->whereNumber('scan')->name('scans.destroy');
    Route::get('/pagetyping', [PageTypingController::class, 'index'])->name('pagetyping.index');
    Route::get('/pagetyping/{indexing}', [PageTypingController::class, 'type'])->whereNumber('indexing')->name('pagetyping.type');
    Route::post('/pagetyping/scans/{scan}', [PageTypingController::class, 'save'])->whereNumber('scan')->name('pagetyping.save');
});

// DMS > Blind Scanning (copied from KLAES, user 2026-11-25): EDMS/BLIND_SCAN/KAMMA_Raw on KAMMA's own storage, then
// Scan Upload (Upload Method = Blind scan) takes the pages into the file.
Route::middleware(['auth', 'kamma.preview:file_indexings,scannings,page_typings,blind_scans'])->prefix('dms')->name('dms.')->group(function () {
    Route::get('/blind-scanning', [BlindScanController::class, 'index'])->name('blind');
    Route::post('/blind-scanning', [BlindScanController::class, 'migrate'])->name('blind.migrate');
    Route::get('/blind-scanning/files/{indexing}', [BlindScanController::class, 'folder'])->whereNumber('indexing')->name('blind.folder');
    Route::get('/blind-scanning/pages/{scan}', [BlindScanController::class, 'image'])->whereNumber('scan')->name('blind.image');
    Route::delete('/blind-scanning/pages/{scan}', [BlindScanController::class, 'destroy'])->whereNumber('scan')->name('blind.destroy');
    Route::post('/scanning/blind', [BlindScanController::class, 'take'])->name('scanning.blind');
});

// DMS > Indexing > File SerialNo Grouping > Print Files Label, COPIED from KLAES's printlabel (user 2026-11-25): the KLAES
// page + JS, answered from KAMMA's shadow files and kamma_print_label_batches / _items (Dms\PrintLabelController).
Route::middleware(['auth', 'kamma.preview:file_indexings,file_trackers,serials,print_label_batches,print_label_batch_items'])->prefix('dms/print-labels')->name('dms.labels')->group(function () {
    Route::get('/', [PrintLabelController::class, 'index']);
    Route::get('/print-template', [PrintLabelController::class, 'printTemplate'])->name('.print-template');
    Route::prefix('api')->name('.api.')->group(function () {
        Route::get('/files', [PrintLabelController::class, 'files'])->name('files');
        Route::post('/batch', [PrintLabelController::class, 'createBatch'])->name('create-batch');
        Route::get('/batches', [PrintLabelController::class, 'batches'])->name('batches');
        Route::get('/batch/{id}', [PrintLabelController::class, 'batch'])->whereNumber('id')->name('batch');
        Route::get('/batch/{id}/print', [PrintLabelController::class, 'batchForPrinting'])->whereNumber('id')->name('batch-for-printing');
        Route::patch('/batch/{id}/print', [PrintLabelController::class, 'markPrinted'])->whereNumber('id')->name('mark-printed');
        Route::delete('/batch/{id}', [PrintLabelController::class, 'deleteBatch'])->whereNumber('id')->name('delete-batch');
        Route::get('/statistics', [PrintLabelController::class, 'statistics'])->name('statistics');
        Route::get('/grouping/preview', [PrintLabelController::class, 'groupingPreview'])->name('grouping-preview');
        Route::get('/registry-batches', [PrintLabelController::class, 'registryBatches'])->name('registry-batches');
        Route::get('/rack-label/status', [PrintLabelController::class, 'rackLabelStatus'])->name('rack-label-status');
        Route::post('/override/lookup', [PrintLabelController::class, 'overrideLookup'])->name('override-lookup');
    });
});

// Digital File Archive: Dashboard and File Digital Library - Doc-WARE (copied from KADCA / KLAES's File Digital Archive).
Route::middleware(['auth', $dmsTables])->prefix('archive')->name('archive.')->group(function () {
    Route::get('/dashboard', [ArchiveExtraController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [DocWareController::class, 'index'])->name('index');
    Route::get('/{indexing}', [DocWareController::class, 'show'])->whereNumber('indexing')->name('show');
    // Doc-WARE actions (2026-11-25): Delete File (Super Admin), and the viewer's Edit Mode.
    Route::post('/{indexing}/delete', [DocWareActionController::class, 'deleteFile'])->whereNumber('indexing')->name('delete-file');
    Route::post('/pages/{scan}/delete', [DocWareActionController::class, 'deletePage'])->whereNumber('scan')->name('pages.delete');
    Route::post('/pages/{scan}/rotate', [DocWareActionController::class, 'rotate'])->whereNumber('scan')->name('pages.rotate');
    Route::post('/pages/{scan}/replace', [DocWareActionController::class, 'replace'])->whereNumber('scan')->name('pages.replace');
    Route::post('/pages/{scan}/qc', [DocWareActionController::class, 'qc'])->whereNumber('scan')->name('pages.qc');
});

// KLAES land files, read-only, for the file selector.
Route::middleware('auth')->prefix('klaes/files')->name('klaes.files.')->group(function () {
    Route::get('/search', [IndexedFileController::class, 'search'])->name('search');
    Route::get('/{id}', [IndexedFileController::class, 'show'])->whereNumber('id')->name('show');
});

// KLAES REV-M (KAMMA's bills): dashboard and Bill Master. Betterment / Initial Bill / receipts open the KAMMA bill screens.
Route::middleware(['auth', $allTables])->prefix('revm')->name('revm.')->group(function () {
    Route::get('/', [RevmController::class, 'dashboard'])->name('dashboard');
    Route::get('/bill-master', [RevmController::class, 'billMaster'])->name('bill-master');
});

// The KAMMA section: KLAES Physical Planning's screens for KAMMA (JSI → KSI, Director of Physical Planning → MD KAMMA),
// plus the KAMMA workflow (Shadow File Commissioning, site visit, desks, betterment with the 4× penalty, return to the PS).
Route::middleware(['auth', $allTables])->prefix('kamma')->name('kamma.')->group(function () {
    $kinds = ['conversion', 'st', 'sltr'];

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/title-status', [TitleStatusController::class, 'index'])->name('title-status.index');
    Route::post('/title-status', [TitleStatusController::class, 'store'])->name('title-status.store');

    // Phase 1: Shadow File Commissioning (the PS sends the land file; KAMMA commissions its shadow file → MD KAMMA).
    Route::get('/shadow-files', [ShadowFileController::class, 'index'])->name('shadow-files.index');
    Route::post('/shadow-files', [ShadowFileController::class, 'store'])->name('shadow-files.store');
    Route::get('/shadow-files/{file}', [ShadowFileController::class, 'show'])->whereNumber('file')->name('shadow-files.show');
    Route::post('/shadow-files/{file}/decide', [ShadowFileController::class, 'decide'])->whereNumber('file')->name('shadow-files.decide');
    Route::get('/file-return', [ShadowFileController::class, 'returns'])->name('returns.index');
    Route::post('/shadow-files/{file}/return', [ShadowFileController::class, 'returnToPs'])->whereNumber('file')->name('shadow-files.return');

    // Phase 2: Initial site visit, geocoded photographs (GPS + time) on the shadow file.
    Route::get('/site-visits', [SiteVisitController::class, 'index'])->name('site-visits.index');
    Route::post('/site-visits', [SiteVisitController::class, 'store'])->name('site-visits.store');
    Route::get('/site-visits/photos/{photo}', [SiteVisitController::class, 'photo'])->whereNumber('photo')->name('site-visits.photo');

    // Phase 3: KSI (KAMMA Site Inspection) + Planning Recommendation, per kind; Master KSI for post-CofO.
    Route::get('/{kind}/planning-recommendation', [KsiController::class, 'index'])->whereIn('kind', $kinds)->name('pr.index');
    Route::get('/{kind}/planning-recommendation/ksi', [KsiController::class, 'create'])->whereIn('kind', $kinds)->name('pr.create');
    Route::post('/{kind}/planning-recommendation/ksi', [KsiController::class, 'store'])->whereIn('kind', $kinds)->name('pr.store');
    Route::get('/{kind}/planning-recommendation/{report}', [KsiController::class, 'show'])->whereIn('kind', $kinds)->whereNumber('report')->name('pr.show');
    Route::get('/master-ksi', [KsiController::class, 'masterIndex'])->name('master-ksi.index');
    Route::get('/master-ksi/create', [KsiController::class, 'masterCreate'])->name('master-ksi.create');
    Route::post('/master-ksi', [KsiController::class, 'masterStore'])->name('master-ksi.store');
    Route::get('/master-ksi/{report}', [KsiController::class, 'masterShow'])->whereNumber('report')->name('master-ksi.show');

    Route::get('/{kind}/memos', [MemoController::class, 'index'])->whereIn('kind', $kinds)->name('memos.index');
    Route::post('/{kind}/memos', [MemoController::class, 'store'])->whereIn('kind', $kinds)->name('memos.store');
    Route::get('/{kind}/memos/{memo}', [MemoController::class, 'show'])->whereIn('kind', $kinds)->whereNumber('memo')->name('memos.show');

    Route::get('/{kind}/e-registry', [EregistryController::class, 'index'])->whereIn('kind', ['st', 'sltr'])->name('eregistry');

    // Bills & Payments: Application Processing Fee and Betterment (4× when a building stands without BPA).
    Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
    Route::get('/bills/generate', [BillController::class, 'create'])->name('bills.create');
    Route::post('/bills', [BillController::class, 'store'])->name('bills.store');
    Route::get('/bills/{bill}/print', [BillController::class, 'print'])->whereNumber('bill')->name('bills.print');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{bill}', [PaymentController::class, 'store'])->whereNumber('bill')->name('payments.store');
    Route::get('/payments/report', [PaymentController::class, 'report'])->name('payments.report');
    Route::get('/payments/report.csv', [PaymentController::class, 'csv'])->name('payments.report.csv');
    Route::get('/payments/receipts', [PaymentController::class, 'receipts'])->name('payments.receipts');
    Route::get('/payments/{bill}/receipt', [PaymentController::class, 'receipt'])->whereNumber('bill')->name('payments.receipt');

    // MD KAMMA (KLAES: Director of Physical Planning) approvals.
    Route::get('/approvals/master-ksi', [ApprovalController::class, 'masterKsi'])->name('approvals.master-ksi');
    Route::get('/approvals/{kind}/planning-recommendation', [ApprovalController::class, 'pr'])->whereIn('kind', $kinds)->name('approvals.pr');
    Route::get('/approvals/{kind}/memo', [ApprovalController::class, 'memo'])->whereIn('kind', $kinds)->name('approvals.memo');
    Route::post('/approvals/reports/{report}', [ApprovalController::class, 'decide'])->whereNumber('report')->name('approvals.decide');
    Route::post('/approvals/memos/{memo}', [ApprovalController::class, 'decideMemo'])->whereNumber('memo')->name('approvals.decide-memo');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/register.csv', [ReportController::class, 'csv'])->name('reports.csv');
});

require __DIR__.'/auth.php';
require __DIR__.'/ui.php';

// Development Permit = Building Plan Approval staff screens, moved in from the KADCA app 2026-11-25 (option A).
require __DIR__.'/bpa.php';

// Applicant portal (Planning Recommendation requests), switched on 2026-11-25 (user: "bring it, switch it on now").
require __DIR__.'/portal.php';
