<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Public legal pages (reachable with and without login)
Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    // Passwort vergessen → Link per E-Mail → neues Passwort setzen
    Route::get('/passwort-vergessen', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/passwort-vergessen', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/passwort-zuruecksetzen/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/passwort-zuruecksetzen', [NewPasswordController::class, 'store'])->middleware('throttle:10,1')->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/konto', [AccountController::class, 'show'])->name('account.show');
    Route::put('/konto/passwort', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password');

    Route::get('/scans', [ScanController::class, 'index'])->name('scans.index');
    Route::get('/scans/create', [ScanController::class, 'create'])->name('scans.create');
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:10,1')->name('scans.store');
    Route::get('/scans/{scan}/processing', [ScanController::class, 'processing'])->name('scans.processing');
    Route::get('/scans/{scan}/status', [ScanController::class, 'status'])->name('scans.status');
    Route::get('/scans/{scan}/preview', [ScanController::class, 'preview'])->name('scans.preview');
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');

    Route::patch('/corrections/{correction}', [CorrectionController::class, 'update'])->name('corrections.update');
});
