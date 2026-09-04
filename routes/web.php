<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

// Login view (guest redirect checked inline)
Route::get('/', function () {
    if (session('logged_in')) {
        return session('user_role') === 'Administrator'
            ? redirect()->route('dashboard')
            : redirect()->route('operator.home');
    }
    return view('login');
})->name('login');

// Handle GET /login to prevent 404 or method not allowed
Route::get('/login', function () {
    if (session('logged_in')) {
        return session('user_role') === 'Administrator'
            ? redirect()->route('dashboard')
            : redirect()->route('operator.home');
    }
    return redirect()->route('login');
});

// Authenticate post request
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Protected routes wrapped in a login enforcement middleware group
Route::middleware([\App\Http\Middleware\EnsureUserIsLoggedIn::class])->group(function () {
    // Logout route (accessible by any logged in user)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected Operator routes (only accessible by Operators/Users)
    Route::middleware(['operator'])->group(function () {
        Route::get('/home', [\App\Http\Controllers\ReportController::class, 'operatorHome'])->name('operator.home');
        Route::get('/input-defect', [\App\Http\Controllers\ReportController::class, 'createInputDefect'])->name('input_defect.create');
        Route::post('/input-defect', [\App\Http\Controllers\ReportController::class, 'storeInputDefect'])->name('input_defect.store');
        Route::get('/input-defect/{id}/edit', [\App\Http\Controllers\ReportController::class, 'editInputDefect'])->name('input_defect.edit');
        Route::put('/input-defect/{id}', [\App\Http\Controllers\ReportController::class, 'updateInputDefect'])->name('input_defect.update');
        Route::delete('/input-defect/{id}', [\App\Http\Controllers\ReportController::class, 'destroyInputDefect'])->name('input_defect.destroy');
    });


    // Protected Admin routes (only accessible by Administrators)
    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Add Account routes
        Route::get('/add-account', [\App\Http\Controllers\AccountController::class, 'create'])->name('account.create');
        Route::post('/add-account', [\App\Http\Controllers\AccountController::class, 'store'])->name('account.store');

        // Final Assy Report route
        Route::get('/final-assy', [\App\Http\Controllers\ReportController::class, 'index'])->name('final_assy.index');
        Route::get('/final-assy/export', [\App\Http\Controllers\ReportController::class, 'exportFinalAssy'])->name('final_assy.export');

        // Pre Assy Report route
        Route::get('/pre-assy', [\App\Http\Controllers\ReportController::class, 'preAssy'])->name('pre_assy.index');
        Route::get('/pre-assy/export', [\App\Http\Controllers\ReportController::class, 'exportPreAssy'])->name('pre_assy.export');

        // Log System route
        Route::get('/log-system', [\App\Http\Controllers\ReportController::class, 'logSystem'])->name('log_system.index');
        Route::get('/log-system/export', [\App\Http\Controllers\ReportController::class, 'exportLogSystem'])->name('log_system.export');

        // Recent Defect route
        Route::get('/recent-defects', [\App\Http\Controllers\ReportController::class, 'recentDefects'])->name('recent_defects.index');
        Route::get('/recent-defects/export', [\App\Http\Controllers\ReportController::class, 'exportRecentDefects'])->name('recent_defects.export');

        Route::get('/report/{id}/edit', [\App\Http\Controllers\ReportController::class, 'adminEditReport'])->name('admin.report.edit');
        Route::put('/report/{id}', [\App\Http\Controllers\ReportController::class, 'adminUpdateReport'])->name('admin.report.update');
    });
});
