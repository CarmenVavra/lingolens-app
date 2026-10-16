<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/scans', [ScanController::class, 'index'])->name('scans.index');
    Route::get('/scans/create', [ScanController::class, 'create'])->name('scans.create');
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:10,1')->name('scans.store');
    Route::get('/scans/{scan}/processing', [ScanController::class, 'processing'])->name('scans.processing');
    Route::get('/scans/{scan}/status', [ScanController::class, 'status'])->name('scans.status');
    Route::get('/scans/{scan}/preview', [ScanController::class, 'preview'])->name('scans.preview');
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');

    Route::patch('/corrections/{correction}', [CorrectionController::class, 'update'])->name('corrections.update');
});
