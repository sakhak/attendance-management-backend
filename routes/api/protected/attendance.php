<?php

use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceAnalyticsController;
use App\Http\Controllers\AttendanceReportExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('attendance-records')->group(function () {
    Route::get('/filter', [AttendanceRecordController::class, 'filter']);
    Route::get('/', [AttendanceRecordController::class, 'index']);
    Route::get('/{id}', [AttendanceRecordController::class, 'show']);
    Route::post('/', [AttendanceRecordController::class, 'store']);
    Route::put('/', [AttendanceRecordController::class, 'update']);
    Route::delete('/', [AttendanceRecordController::class, 'destroy']);
});

Route::prefix('report-export')->group(function () {
    Route::get('/history', [AttendanceReportExportController::class, 'history']);
    Route::get('/{id}/download', [AttendanceReportExportController::class, 'download'])
        ->whereNumber('id');
    Route::post('/{format}', [AttendanceReportExportController::class, 'export'])
        ->where('format', 'pdf|xlsx');
});

Route::prefix('attendance-analytics')->group(function () {
    Route::get('/report-summary', [AttendanceAnalyticsController::class, 'reportSummary']);
    Route::get('/blacklist-overview', [AttendanceAnalyticsController::class, 'blacklistOverview']);
});
