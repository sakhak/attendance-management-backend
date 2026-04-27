<?php

use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('blacklists')->group(function () {
    Route::get('/', [BlacklistController::class, 'index']);
    Route::post('/create', [BlacklistController::class, 'store']);
    Route::get('/{blacklist}', [BlacklistController::class, 'show']);
    Route::put('/update/{blacklist}', [BlacklistController::class, 'update']);
    Route::delete('/{blacklist}', [BlacklistController::class, 'destroy']);
});

Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/create', [StudentController::class, 'store']);
    Route::get('/{student}', [StudentController::class, 'show']);
    Route::put('/update/{student}', [StudentController::class, 'update']);
    Route::delete('/{student}', [StudentController::class, 'destroy']);
});

Route::middleware(['role:admin'])->prefix('teachers')->group(function () {
    Route::get('/', [TeacherController::class, 'index']);
    Route::get('/{teacher}', [TeacherController::class, 'show']);
    Route::put('/update/{teacher}', [TeacherController::class, 'update']);
});
