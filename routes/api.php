<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));

Route::middleware('auth:sanctum')->group(function () {
    require base_path('routes/api/protected/access-control.php');
    require base_path('routes/api/protected/school-structure.php');
    require base_path('routes/api/protected/people.php');
    require base_path('routes/api/protected/attendance.php');
});
