<?php

use App\Actions\User\ForgotPassword;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'store']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', function (Request $request) {
    return app(ForgotPassword::class)->execute($request);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('index', [AuthController::class, 'index']);
    Route::put('update', [AuthController::class, 'update']);
    Route::get('show/{id}', [AuthController::class, 'show']);
    Route::post('logout', [AuthController::class, 'logout']);
});
