<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('user-profile')->group(function () {
    Route::post('/create', [UserProfileController::class, 'store']);
    Route::get('/show', [UserProfileController::class, 'show']);
    Route::put('/update', [UserProfileController::class, 'update']);
    Route::delete('/delete', [UserProfileController::class, 'destroy']);
});

Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::get('/{permission}', [PermissionController::class, 'show']);

    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{permission}', [PermissionController::class, 'update']);
        Route::delete('/{permission}', [PermissionController::class, 'destroy']);
    });
});

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::get('/{role}', [RoleController::class, 'show']);

    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::post('/create', [RoleController::class, 'store']);
        Route::put('/update/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);
    });
});

Route::middleware(['role:super_admin,admin'])->prefix('user-roles')->group(function () {
    Route::get('/', [UserRoleController::class, 'index']);
    Route::post('/create', [UserRoleController::class, 'store']);
    Route::post('/update', [UserRoleController::class, 'update']);
    Route::post('/delete', [UserRoleController::class, 'destroy']);
    Route::get('/{id}', [UserRoleController::class, 'show']);
});

Route::middleware(['role:super_admin,admin'])->prefix('rolespermissions')->group(function () {
    Route::get('/', [RolePermissionController::class, 'index']);
    Route::post('/', [RolePermissionController::class, 'store']);
    Route::put('/', [RolePermissionController::class, 'update']);
    Route::delete('/', [RolePermissionController::class, 'destroy']);
});
