<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Login view (guest redirect checked inline)
Route::get('/', function () {
    if (session('logged_in')) {
        return redirect('/dashboard');
    }
    return view('login');
})->name('login');

// Authenticate post request
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (handled dynamically in controller index/logout)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Add Account routes
Route::get('/add-account', [\App\Http\Controllers\AccountController::class, 'create'])->name('account.create');
Route::post('/add-account', [\App\Http\Controllers\AccountController::class, 'store'])->name('account.store');

// Final Assy Report route
Route::get('/final-assy', [\App\Http\Controllers\ReportController::class, 'index'])->name('final_assy.index');

// Pre Assy Report route
Route::get('/pre-assy', [\App\Http\Controllers\ReportController::class, 'preAssy'])->name('pre_assy.index');

// Log System route
Route::get('/log-system', [\App\Http\Controllers\ReportController::class, 'logSystem'])->name('log_system.index');
