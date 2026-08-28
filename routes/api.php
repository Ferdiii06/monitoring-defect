<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DefectApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route API untuk menyimpan defect baru dari Flutter
Route::post('/defects', [DefectApiController::class, 'store']);
Route::post('/defects/delete-external', [DefectApiController::class, 'deleteExternal']);
Route::get('/dashboard/stats', [DefectApiController::class, 'getStats']);

// Route API Live Polling (AJAX)
Route::get('/dashboard/recent-defects', [\App\Http\Controllers\ReportController::class, 'dashboardRecentDefects']);
Route::get('/final-assy/live', [\App\Http\Controllers\ReportController::class, 'finalAssyLive']);
Route::get('/pre-assy/live', [\App\Http\Controllers\ReportController::class, 'preAssyLive']);
Route::get('/recent-defects/live', [\App\Http\Controllers\ReportController::class, 'recentDefectsLive']);
Route::get('/log-system/live', [\App\Http\Controllers\ReportController::class, 'logSystemLive']);

