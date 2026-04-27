<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceRuleController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\ClassTeacherController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GradeLevelSubjectController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\TermController;
use Illuminate\Support\Facades\Route;

Route::prefix('classes')->group(function () {
    Route::get('/', [ClassesController::class, 'index']);
    Route::post('/create', [ClassesController::class, 'store']);
    Route::get('/{class}', [ClassesController::class, 'show']);
    Route::put('/update/{class}', [ClassesController::class, 'update']);
    Route::delete('/{class}', [ClassesController::class, 'destroy']);
});

Route::prefix('grade-levels')->group(function () {
    Route::get('/', [GradeLevelController::class, 'index']);
    Route::post('/create', [GradeLevelController::class, 'store']);
    Route::get('/{id}', [GradeLevelController::class, 'show']);
    Route::put('/update/{id}', [GradeLevelController::class, 'update']);
    Route::delete('/{id}', [GradeLevelController::class, 'destroy']);
});

Route::prefix('grade-level-subjects')->group(function () {
    Route::get('/', [GradeLevelSubjectController::class, 'index']);
    Route::post('/create', [GradeLevelSubjectController::class, 'store']);
    Route::get('/{id}', [GradeLevelSubjectController::class, 'show']);
    Route::put('/update/{id}', [GradeLevelSubjectController::class, 'update']);
    Route::delete('/{id}', [GradeLevelSubjectController::class, 'destroy']);
});

Route::prefix('class-teachers')->group(function () {
    Route::get('/', [ClassTeacherController::class, 'index']);
    Route::post('/create', [ClassTeacherController::class, 'store']);
    Route::get('/{classTeacher}', [ClassTeacherController::class, 'show']);
    Route::put('/update/{classTeacher}', [ClassTeacherController::class, 'update']);
    Route::delete('/{classTeacher}', [ClassTeacherController::class, 'destroy']);
});

Route::prefix('enrollments')->group(function () {
    Route::get('/', [EnrollmentController::class, 'index']);
    Route::post('/create', [EnrollmentController::class, 'store']);
    Route::get('/classes/{class}/students', [EnrollmentController::class, 'listClassStudents']);
    Route::get('/{enrollment}', [EnrollmentController::class, 'show']);
    Route::put('/update/{enrollment}', [EnrollmentController::class, 'update']);
    Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy']);
});

Route::prefix('academic-year')->group(function () {
    Route::get('/', [AcademicYearController::class, 'index']);
    Route::post('/', [AcademicYearController::class, 'store']);
    Route::get('/{id}', [AcademicYearController::class, 'show']);
    Route::put('/{academicYear}', [AcademicYearController::class, 'update']);
    Route::delete('/all', [AcademicYearController::class, 'destroyAll']);
    Route::delete('/', [AcademicYearController::class, 'destroyMulti']);
    Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy']);
});

Route::prefix('term')->group(function () {
    Route::get('/', [TermController::class, 'index']);
    Route::post('/', [TermController::class, 'store']);
    Route::get('/{id}', [TermController::class, 'show']);
    Route::put('/{term}', [TermController::class, 'update']);
    Route::delete('/all', [TermController::class, 'destroyAll']);
    Route::delete('/', [TermController::class, 'destroyMulti']);
    Route::delete('/{idTerm}', [TermController::class, 'destroy']);
});

Route::prefix('class-session')->group(function () {
    Route::get('/', [ClassSessionController::class, 'index']);
    Route::post('/', [ClassSessionController::class, 'store']);
    Route::get('/{id}', [ClassSessionController::class, 'show']);
    Route::put('/{classSession}', [ClassSessionController::class, 'update']);
    Route::delete('/all', [ClassSessionController::class, 'destroyAll']);
    Route::delete('/', [ClassSessionController::class, 'destroyMulti']);
    Route::delete('/{classSession}', [ClassSessionController::class, 'destroy']);
});

Route::prefix('settings')->group(function () {
    Route::get('/school', [SchoolSettingController::class, 'show']);
    Route::put('/school', [SchoolSettingController::class, 'update']);

    Route::get('/attendance-rules', [AttendanceRuleController::class, 'show']);
    Route::put('/attendance-rules', [AttendanceRuleController::class, 'update']);
});
