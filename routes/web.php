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
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('admin.dashboard.analytics');

        // Add Account routes
        Route::get('/add-account', [\App\Http\Controllers\AccountController::class, 'index'])->name('account.create');
        Route::post('/add-account', [\App\Http\Controllers\AccountController::class, 'store'])->name('account.store');
        Route::put('/add-account/{id}', [\App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
        Route::delete('/add-account/{id}', [\App\Http\Controllers\AccountController::class, 'destroy'])->name('account.destroy');

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
        Route::delete('/report/{id}', [\App\Http\Controllers\ReportController::class, 'adminDestroyReport'])->name('admin.report.destroy');

        // Master Data routes
        Route::prefix('master')->name('admin.master.')->group(function () {
            // Car Types (Jenis Mobil)
            Route::get('/car-types', [\App\Http\Controllers\CarTypeController::class, 'index'])->name('car_types.index');
            Route::post('/car-types', [\App\Http\Controllers\CarTypeController::class, 'store'])->name('car_types.store');
            Route::put('/car-types/{id}', [\App\Http\Controllers\CarTypeController::class, 'update'])->name('car_types.update');
            Route::delete('/car-types/{id}', [\App\Http\Controllers\CarTypeController::class, 'destroy'])->name('car_types.destroy');

            // Carlines (Carline / Konveyor)
            Route::get('/carlines', [\App\Http\Controllers\CarlineController::class, 'index'])->name('carlines.index');
            Route::post('/carlines', [\App\Http\Controllers\CarlineController::class, 'store'])->name('carlines.store');
            Route::put('/carlines/{id}', [\App\Http\Controllers\CarlineController::class, 'update'])->name('carlines.update');
            Route::delete('/carlines/{id}', [\App\Http\Controllers\CarlineController::class, 'destroy'])->name('carlines.destroy');

            // Defect Types & Sub Defect Types
            Route::get('/defect-types', [\App\Http\Controllers\DefectTypeController::class, 'index'])->name('defect_types.index');
            Route::post('/defect-types', [\App\Http\Controllers\DefectTypeController::class, 'store'])->name('defect_types.store');
            Route::put('/defect-types/{id}', [\App\Http\Controllers\DefectTypeController::class, 'update'])->name('defect_types.update');
            Route::delete('/defect-types/{id}', [\App\Http\Controllers\DefectTypeController::class, 'destroy'])->name('defect_types.destroy');
            Route::post('/defect-types/{defectTypeId}/sub', [\App\Http\Controllers\DefectTypeController::class, 'storeSub'])->name('defect_types.sub.store');
            Route::delete('/defect-types/sub/{id}', [\App\Http\Controllers\DefectTypeController::class, 'destroySub'])->name('defect_types.sub.destroy');

            // Inspect Process Types
            Route::get('/inspect-process-types', [\App\Http\Controllers\InspectProcessTypeController::class, 'index'])->name('inspect_process_types.index');
            Route::post('/inspect-process-types', [\App\Http\Controllers\InspectProcessTypeController::class, 'store'])->name('inspect_process_types.store');
            Route::put('/inspect-process-types/{id}', [\App\Http\Controllers\InspectProcessTypeController::class, 'update'])->name('inspect_process_types.update');
            Route::delete('/inspect-process-types/{id}', [\App\Http\Controllers\InspectProcessTypeController::class, 'destroy'])->name('inspect_process_types.destroy');

            // Final Assy Inspect Types
            Route::get('/final-assy-inspect-types', [\App\Http\Controllers\FinalAssyInspectTypeController::class, 'index'])->name('final_assy_inspect_types.index');
            Route::post('/final-assy-inspect-types', [\App\Http\Controllers\FinalAssyInspectTypeController::class, 'store'])->name('final_assy_inspect_types.store');
            Route::put('/final-assy-inspect-types/{id}', [\App\Http\Controllers\FinalAssyInspectTypeController::class, 'update'])->name('final_assy_inspect_types.update');
            Route::delete('/final-assy-inspect-types/{id}', [\App\Http\Controllers\FinalAssyInspectTypeController::class, 'destroy'])->name('final_assy_inspect_types.destroy');

            // Area Ditemukan
            Route::get('/area-ditemukans', [\App\Http\Controllers\AreaDitemukanController::class, 'index'])->name('area_ditemukans.index');
            Route::post('/area-ditemukans', [\App\Http\Controllers\AreaDitemukanController::class, 'store'])->name('area_ditemukans.store');
            Route::put('/area-ditemukans/{id}', [\App\Http\Controllers\AreaDitemukanController::class, 'update'])->name('area_ditemukans.update');
            Route::delete('/area-ditemukans/{id}', [\App\Http\Controllers\AreaDitemukanController::class, 'destroy'])->name('area_ditemukans.destroy');

            // Target DPM (DPM Targets)
            Route::get('/dpm-targets', [\App\Http\Controllers\DpmTargetController::class, 'index'])->name('dpm_targets.index');
            Route::post('/dpm-targets', [\App\Http\Controllers\DpmTargetController::class, 'store'])->name('dpm_targets.store');
            Route::put('/dpm-targets/{id}', [\App\Http\Controllers\DpmTargetController::class, 'update'])->name('dpm_targets.update');
            Route::delete('/dpm-targets/{id}', [\App\Http\Controllers\DpmTargetController::class, 'destroy'])->name('dpm_targets.destroy');

            // Route name alias for kebab-case
            Route::name('dpm-targets.')->group(function () {
                Route::get('/dpm_targets', [\App\Http\Controllers\DpmTargetController::class, 'index'])->name('index');
                Route::post('/dpm_targets', [\App\Http\Controllers\DpmTargetController::class, 'store'])->name('store');
                Route::put('/dpm_targets/{id}', [\App\Http\Controllers\DpmTargetController::class, 'update'])->name('update');
                Route::delete('/dpm_targets/{id}', [\App\Http\Controllers\DpmTargetController::class, 'destroy'])->name('destroy');
            });
        });

        // Alias for master.dpm-targets.* (without admin. prefix)
        Route::prefix('master')->name('master.dpm-targets.')->group(function () {
            Route::get('/dpm-targets/alias', [\App\Http\Controllers\DpmTargetController::class, 'index'])->name('index');
            Route::post('/dpm-targets/alias', [\App\Http\Controllers\DpmTargetController::class, 'store'])->name('store');
            Route::put('/dpm-targets/alias/{id}', [\App\Http\Controllers\DpmTargetController::class, 'update'])->name('update');
            Route::delete('/dpm-targets/alias/{id}', [\App\Http\Controllers\DpmTargetController::class, 'destroy'])->name('destroy');
        });
    });
});
