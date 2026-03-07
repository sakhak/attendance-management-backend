<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceReportExportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\ClassTeacherController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GradeLevelSubjectController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'store']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('index', [AuthController::class, 'index']);
        Route::put('update', [AuthController::class, 'update']);
        Route::get('show/{id}', [AuthController::class, 'show']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user-profile')->group(function () {
        Route::post('/create', [UserProfileController::class, 'store']);
        Route::get('/show', [UserProfileController::class, 'show']);
        Route::put('/update', [UserProfileController::class, 'update']);
        Route::delete('/delete', [UserProfileController::class, 'destroy']);
    });
});

Route::prefix('permissions')->group(function () {
    Route::post('/', [PermissionController::class, 'store']);
    Route::put('/{permission}', [PermissionController::class, 'update']);
    Route::delete('/{permission}', [PermissionController::class, 'destroy']);
    Route::get('/{permission}', [PermissionController::class, 'show']);
    Route::get('/', [PermissionController::class, 'index']);
});

// Roles CRUD
Route::prefix('roles')->group(function () {
    Route::post('/create', [RoleController::class, 'store']);
    Route::get('/', [RoleController::class, 'index']);
    Route::get('/{role}', [RoleController::class, 'show']);
    Route::put('/update/{role}', [RoleController::class, 'update']);
    Route::delete('/{role}', [RoleController::class, 'destroy']);
});

// User-Role Management
Route::prefix('user-roles')->group(function () {
    Route::post('/create', [UserRoleController::class, 'store']);    // attach
    Route::post('/update', [UserRoleController::class, 'update']);     // update
    Route::post('/delete', [UserRoleController::class, 'destroy']); // detach
    Route::get('/', [UserRoleController::class, 'index']);
    Route::get('/{id}', [UserRoleController::class, 'show']);
});

// Role-Permission Management
Route::prefix('roles/{role}/permissions')->group(function () {
    Route::get('/', [RolePermissionController::class, 'index']);
    Route::post('/', [RolePermissionController::class, 'store']);     // attach
    Route::put('/', [RolePermissionController::class, 'update']);     // update
    Route::delete('/', [RolePermissionController::class, 'destroy']); // detach
});

// Classes CRUD
Route::prefix('classes')->group(function () {
    Route::get('/', [ClassesController::class, 'index']);
    Route::post('/create', [ClassesController::class, 'store']);
    Route::get('/{class}', [ClassesController::class, 'show']);
    Route::put('/update/{class}', [ClassesController::class, 'update']);
    Route::delete('/{class}', [ClassesController::class, 'destroy']);
});

//Grade level
Route::prefix('grade-levels')->group(function () {
    Route::get('/', [GradeLevelController::class, 'index']);
    Route::post('/create', [GradeLevelController::class, 'store']);
    Route::get('/{id}', [GradeLevelController::class, 'show']);
    Route::put('/update/{id}', [GradeLevelController::class, 'update']);
    Route::delete('/{id}', [GradeLevelController::class, 'destroy']);
});
//Grade level subject
Route::prefix('grade-level-subjects')->group(function () {
    Route::post('/create', [GradeLevelSubjectController::class, 'store']);
    Route::put('/update/{id}', [GradeLevelSubjectController::class, 'update']);
    Route::delete('/{id}', [GradeLevelSubjectController::class, 'destroy']);
    Route::get('/{id}', [GradeLevelSubjectController::class, 'show']);
    Route::get('/', [GradeLevelSubjectController::class, 'index']);
});

// Blacklist CRUD
Route::prefix('blacklists')->group(function () {
    Route::get('/', [BlacklistController::class, 'index']);
    Route::post('/create', [BlacklistController::class, 'store']);
    Route::get('/{blacklist}', [BlacklistController::class, 'show']);
    Route::put('/update/{blacklist}', [BlacklistController::class, 'update']);
    Route::delete('/{blacklist}', [BlacklistController::class, 'destroy']);
});

// Class Teacher CRUD
Route::prefix('class-teachers')->group(function () {
    Route::get('/', [ClassTeacherController::class, 'index']);
    Route::post('/create', [ClassTeacherController::class, 'store']);
    Route::get('/{classTeacher}', [ClassTeacherController::class, 'show']);
    Route::put('/update/{classTeacher}', [ClassTeacherController::class, 'update']);
    Route::delete('/{classTeacher}', [ClassTeacherController::class, 'destroy']);
});

// Students (CRUD + link to users)
Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/create', [StudentController::class, 'store']);
    Route::get('/{student}', [StudentController::class, 'show']);
    Route::put('/update/{student}', [StudentController::class, 'update']);
    Route::delete('/{student}', [StudentController::class, 'destroy']);
});
// Teachers (CRUD + link to users)
Route::middleware(['role:admin'])->prefix('teachers')->group(function () {
    Route::get('/', [TeacherController::class, 'index']);
    Route::get('/{teacher}', [TeacherController::class, 'show']);
    Route::put('/update/{teacher}', [TeacherController::class, 'update']);
    // Route::delete('/{teacher}', [TeacherController::class, 'destroy']);
});

// Enrollment CRUD
Route::prefix('enrollments')->group(function () {
    Route::get('/', [EnrollmentController::class, 'index']);
    Route::post('/create', [EnrollmentController::class, 'store']);
    Route::get('/{enrollment}', [EnrollmentController::class, 'show']);
    Route::put('/update/{enrollment}', [EnrollmentController::class, 'update']);
    Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy']);
    Route::get('/classes/{class}/students', [EnrollmentController::class, 'listClassStudents']);
});


// Academic year Crud
Route::prefix('academic-year')->group(function () {
    Route::get('/', [AcademicYearController::class, 'index']);
    Route::post('/', [AcademicYearController::class, 'store']);
    Route::get('/{id}', [AcademicYearController::class, 'show']);
    Route::put('/{academicYear}', [AcademicYearController::class, 'update']);
    Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy']);
    Route::delete('/', [AcademicYearController::class, 'destroyMulti']);
    Route::delete('/all', [AcademicYearController::class, 'destroyAll']);
});

// Terms Crud
Route::prefix('term')->group(function () {
    Route::get('/', [TermController::class, 'index']);
    Route::post('/', [TermController::class, 'store']);
    Route::get('/{id}', [TermController::class, 'show']);
    Route::put('/{term}', [TermController::class, 'update']);
    Route::delete('/{idTerm}', [TermController::class, 'destroy']);
    Route::delete('/', [TermController::class, 'destroyMulti']);
    Route::delete('/all', [TermController::class, 'destroyAll']);
});


// Class Session Crud
Route::prefix('class-session')->group(function () {
    Route::get('/', [ClassSessionController::class, 'index']);
    Route::post('/', [ClassSessionController::class, 'store']);
    Route::get('/{id}', [ClassSessionController::class, 'show']);
    Route::put('/{classSession}', [ClassSessionController::class, 'update']);
    Route::delete('/{classSession}', [ClassSessionController::class, 'destroy']);
    Route::delete('/', [ClassSessionController::class, 'destroyMulti']);
    Route::delete('/all', [ClassSessionController::class, 'destroyAll']);
});


// Generate Report

Route::prefix('report-export')->group(function () {
    Route::post('/{format}', [AttendanceReportExportController::class, 'export'])
        ->where('format', 'pdf|xlsx');
});


// Attendance Record
Route::prefix('attendance-records')->group(function () {
    // Filter attendance records
    Route::get('/filter', [AttendanceRecordController::class, 'filter']);
    // List + show
    Route::get('/', [AttendanceRecordController::class, 'index']);
    Route::get('/{id}', [AttendanceRecordController::class, 'show']);

    // create (many students for one class_session_id)
    Route::post('/', [AttendanceRecordController::class, 'store']);

    // update (many students for one class_session_id)
    Route::put('/', [AttendanceRecordController::class, 'update']);

    // delete (many students for one class_session_id)
    Route::delete('/', [AttendanceRecordController::class, 'destroy']);
});
