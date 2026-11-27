<?php

use App\Http\Controllers\Portal\ApplicationController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\Bpa\ApplicationController as BpaApplicationController;
use App\Http\Controllers\Portal\Bpa\BuildingPlanController as BpaBuildingPlanController;
use App\Http\Controllers\Portal\Bpa\InspectionController as BpaInspectionController;
use App\Http\Controllers\Portal\Bpa\ProgressController as BpaProgressController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\LandingController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Middleware\PortalAuthenticate;
use App\Http\Middleware\PortalGuest;
use Illuminate\Support\Facades\Route;

/*
| KAMMA applicant portal (KLAES-METRO): applicants ask for a Planning Recommendation on a land application or a
| Post-CofO application (Sub-Division, Merger, Extension, Change of Purpose) inside the 10 km buffer, pay the processing
| fee and betterment bills, and follow the request to the MD KAMMA and back to the Ministry. Copied from the KADCA portal.
|
| Loaded by bootstrap/app.php (web middleware), outside the staff `auth` guard, like KLAES routes/laas.php. The applicant
| is signed in by App\Support\Portal\PortalSession (session only while the portal is UI first, user 2026-11-23).
| Saves answer "Saved (preview)" and store nothing.
*/

Route::prefix('portal')->name('portal.')->group(function () {
    // Public landing page with Sign in / Create account (user 2026-11-23); a signed-in applicant goes to the dashboard.
    Route::get('/', LandingController::class)->name('home');

    Route::middleware(PortalGuest::class)->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
        Route::post('demo', [AuthController::class, 'demo'])->middleware('throttle:10,1')->name('demo');
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.submit');
        Route::get('register/verify', [AuthController::class, 'showVerify'])->name('verify');
        Route::post('register/verify', [AuthController::class, 'verify'])->middleware('throttle:10,1')->name('verify.submit');
    });

    Route::middleware(PortalAuthenticate::class)->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('bills', [DashboardController::class, 'bills'])->name('bills');
        Route::get('notifications', [DashboardController::class, 'notifications'])->name('notifications');

        Route::get('applications/create', [ApplicationController::class, 'create'])->name('applications.create');
        Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/track', [ApplicationController::class, 'track'])->name('applications.track');
        // Request numbers carry slashes (DEMO-KPR/2026/00001), so the reference takes the rest of the path.
        Route::get('applications/{reference}', [ApplicationController::class, 'show'])
            ->where('reference', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('applications.show');

        // KLAES land files for the selector: file numbers only, no phone or address.
        Route::get('land-files/search', [ApplicationController::class, 'fileSearch'])->middleware('throttle:60,1')->name('files.search');
        Route::get('land-files/{id}', [ApplicationController::class, 'fileShow'])->whereNumber('id')->name('files.show');

        // Building Plan Approval (2026-11-25, recording: the building plan portal "is coming under KAMMA, not KADCA").
        // Copied from the KADCA app's portal; UI first like the rest: saves answer "Saved (preview)".
        Route::prefix('building-plans')->name('bpa.')->group(function () {
            Route::get('/', [BpaBuildingPlanController::class, 'index'])->name('index');
            Route::get('apply', [BpaApplicationController::class, 'create'])->name('applications.create');
            Route::post('apply', [BpaApplicationController::class, 'store'])->name('applications.store');
            Route::get('track', [BpaApplicationController::class, 'track'])->name('applications.track');
            Route::get('construction-progress', [BpaProgressController::class, 'index'])->name('progress.index');
            Route::post('construction-progress', [BpaProgressController::class, 'store'])->name('progress.store');
            Route::get('inspections', [BpaInspectionController::class, 'index'])->name('inspections.index');
            Route::post('inspections', [BpaInspectionController::class, 'store'])->name('inspections.store');
            // Application numbers carry slashes (DEMO-BPA/2026/00001), so the reference takes the rest of the path.
            Route::get('applications/{reference}', [BpaApplicationController::class, 'show'])
                ->where('reference', '[A-Za-z0-9][A-Za-z0-9\-/]*')->name('applications.show');
        });

        Route::get('profile', [ProfileController::class, 'show'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
    });
});
