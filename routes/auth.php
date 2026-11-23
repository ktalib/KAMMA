<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LoginOtpController;
use Illuminate\Support\Facades\Route;

// Accounts are KLAES's (dbo.users): registration, password reset/change and email verification
// happen in KLAES, so only sign-in and sign-out live here.

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('login/verify', [LoginOtpController::class, 'show'])->name('login.otp');
    Route::post('login/verify', [LoginOtpController::class, 'verify'])->middleware('throttle:10,1')->name('login.otp.verify');
    Route::post('login/verify/resend', [LoginOtpController::class, 'resend'])->middleware('throttle:6,1')->name('login.otp.resend');
    Route::post('login/verify/cancel', [LoginOtpController::class, 'cancel'])->name('login.otp.cancel');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
