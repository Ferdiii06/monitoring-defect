<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReworkController;
use App\Support\RoleRouteHelper;

// Login view (guest redirect checked inline)
Route::get('/', function (\Illuminate\Http\Request $request) {
    if (session('logged_in')) {
        if (in_array(session('user_role'), ['Administrator', 'User', 'Rework'], true)) {
            return RoleRouteHelper::redirectForRole();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    return view('login');
})->name('login');

// Handle GET /login to prevent 404 or method not allowed
Route::get('/login', function (\Illuminate\Http\Request $request) {
    if (session('logged_in')) {
        if (in_array(session('user_role'), ['Administrator', 'User', 'Rework'], true)) {
            return RoleRouteHelper::redirectForRole();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return view('login');
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

    // Protected Rework routes (accessible by Rework role and Administrator)
    Route::middleware(['rework'])->prefix('rework')->name('rework.')->group(function () {
        Route::get('/', [ReworkController::class, 'index'])->name('index');
        Route::post('/in', [ReworkController::class, 'storeIn'])->name('store_in');
        Route::post('/out/{groupId}', [ReworkController::class, 'storeOut'])->name('store_out');
        Route::get('/edit/{groupId}', [ReworkController::class, 'edit'])->name('edit');
        Route::put('/update/{groupId}', [ReworkController::class, 'update'])->name('update');
        Route::get('/lookup-assy', [\App\Http\Controllers\AssyLookupController::class, 'lookup'])->name('lookup_assy');
        Route::get('/lookup', [\App\Http\Controllers\AssyLookupController::class, 'lookup'])->name('lookup');
    });


    // Protected Admin routes (only accessible by Administrators)
    Route::middleware(['admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('admin.dashboard.analytics');
        Route::get('/dashboard/pareto-drilldown', [DashboardController::class, 'paretoDrilldown'])->name('admin.dashboard.pareto_drilldown');

        // Add Account routes
        Route::get('/add-account', [\App\Http\Controllers\AccountController::class, 'index'])->name('account.create');
        Route::post('/add-account', [\App\Http\Controllers\AccountController::class, 'store'])->name('account.store');
        Route::put('/add-account/{id}', [\App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
        Route::delete('/add-account/{id}', [\App\Http\Controllers\AccountController::class, 'destroy'])->name('account.destroy');

        // Final Assy Report routes
        Route::prefix('final-assy')->name('final_assy.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
            Route::get('/internal', [\App\Http\Controllers\ReportController::class, 'finalAssyInternal'])->name('internal');
            Route::get('/assembly', [\App\Http\Controllers\ReportController::class, 'finalAssyAssembly'])->name('assembly');
            Route::get('/rework', [\App\Http\Controllers\ReportController::class, 'finalAssyRework'])->name('rework');
            Route::get('/export', [\App\Http\Controllers\ReportController::class, 'exportFinalAssy'])->name('export');
            Route::get('/chart-data', [\App\Http\Controllers\ReportController::class, 'finalAssyChartData'])->name('chart_data');
        });

        // Pre Assy Report routes
        Route::prefix('pre-assy')->name('pre_assy.')->group(function () {
            Route::get('/', [\App\Http\Controllers\ReportController::class, 'preAssy'])->name('index');
            Route::get('/internal', [\App\Http\Controllers\ReportController::class, 'preAssyInternalCategory'])->name('internal');
            Route::get('/assembly', [\App\Http\Controllers\ReportController::class, 'preAssyAssemblyCategory'])->name('assembly');
            Route::get('/export', [\App\Http\Controllers\ReportController::class, 'exportPreAssy'])->name('export');
        });

        // Log System route
        Route::get('/log-system', [\App\Http\Controllers\ReportController::class, 'logSystem'])->name('log_system.index');
        Route::get('/log-system/export', [\App\Http\Controllers\ReportController::class, 'exportLogSystem'])->name('log_system.export');

        // Recent Defect route
        Route::get('/recent-defects', [\App\Http\Controllers\ReportController::class, 'recentDefects'])->name('recent_defects.index');
        Route::get('/recent-defects/export', [\App\Http\Controllers\ReportController::class, 'exportRecentDefects'])->name('recent_defects.export');

        // Riwayat Rework route (Admin)
        Route::get('/admin/rework-history', [\App\Http\Controllers\Admin\ReworkHistoryController::class, 'index'])->name('admin.rework_history.index');
        Route::get('/admin/rework-history/export', [\App\Http\Controllers\Admin\ReworkHistoryController::class, 'export'])->name('admin.rework_history.export');
        Route::delete('/admin/rework-history/{groupId}', [\App\Http\Controllers\Admin\ReworkHistoryController::class, 'destroyGroup'])->name('admin.rework_history.destroy');

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

            // Assy Numbers
            Route::get('/assy-numbers', [\App\Http\Controllers\AssyNumberController::class, 'index'])->name('assy_numbers.index');
            Route::post('/assy-numbers', [\App\Http\Controllers\AssyNumberController::class, 'store'])->name('assy_numbers.store');
            Route::post('/assy-numbers/import', [\App\Http\Controllers\AssyNumberController::class, 'import'])->name('assy_numbers.import');
            Route::put('/assy-numbers/{id}', [\App\Http\Controllers\AssyNumberController::class, 'update'])->name('assy_numbers.update');
            Route::delete('/assy-numbers/{id}', [\App\Http\Controllers\AssyNumberController::class, 'destroy'])->name('assy_numbers.destroy');

            // Defect Types & Sub Defect Types
            Route::get('/defect-types', [\App\Http\Controllers\DefectTypeController::class, 'index'])->name('defect_types.index');
            Route::post('/defect-types', [\App\Http\Controllers\DefectTypeController::class, 'store'])->name('defect_types.store');
            Route::put('/defect-types/{id}', [\App\Http\Controllers\DefectTypeController::class, 'update'])->name('defect_types.update');
            Route::delete('/defect-types/{id}', [\App\Http\Controllers\DefectTypeController::class, 'destroy'])->name('defect_types.destroy');
            Route::post('/defect-types/{defectTypeId}/sub', [\App\Http\Controllers\DefectTypeController::class, 'storeSub'])->name('defect_types.sub.store');
            Route::put('/defect-types/sub/{id}', [\App\Http\Controllers\DefectTypeController::class, 'updateSub'])->name('defect_types.sub.update');
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

            // Kanban Format (Urutan Format Kanban)
            Route::prefix('kanban-format')->name('kanban_format.')->group(function () {
                Route::get('/', [\App\Http\Controllers\KanbanFormatController::class, 'index'])->name('index');
                Route::put('/', [\App\Http\Controllers\KanbanFormatController::class, 'update'])->name('update');
                Route::post('/reset', [\App\Http\Controllers\KanbanFormatController::class, 'reset'])->name('reset');
            });

            // Nameplates (Master Nameplate HTScan JAI)
            Route::prefix('nameplates')->name('nameplates.')->group(function () {
                Route::get('/', [\App\Http\Controllers\NameplateController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\NameplateController::class, 'store'])->name('store');
                Route::post('/import', [\App\Http\Controllers\NameplateController::class, 'import'])->name('import');
                Route::put('/{id}', [\App\Http\Controllers\NameplateController::class, 'update'])->name('update');
                Route::delete('/{id}', [\App\Http\Controllers\NameplateController::class, 'destroy'])->name('destroy');
            });

            // Nameplate Settings (Carline Configuration)
            Route::prefix('nameplate-settings')->name('nameplate_settings.')->group(function () {
                Route::get('/', [\App\Http\Controllers\NameplateSettingController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\NameplateSettingController::class, 'store'])->name('store');
                Route::get('/export', [\App\Http\Controllers\NameplateSettingController::class, 'export'])->name('export');
                Route::get('/template', [\App\Http\Controllers\NameplateSettingController::class, 'template'])->name('template');
                Route::post('/import', [\App\Http\Controllers\NameplateSettingController::class, 'import'])->name('import');
                Route::post('/test-scan', [\App\Http\Controllers\NameplateSettingController::class, 'testScan'])->name('test_scan');
                Route::put('/{id}', [\App\Http\Controllers\NameplateSettingController::class, 'update'])->name('update');
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
