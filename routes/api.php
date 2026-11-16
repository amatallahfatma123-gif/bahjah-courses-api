<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);

    // ============================================
    // مسارات القراءة (تحتاج طالب معتمد أو أدمن)
    // ============================================
    Route::middleware('student.approved')->group(function (): void {
        Route::get('courses', [CourseController::class, 'index']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
    });

    // ============================================
    // مسارات الكتابة (تحتاج أدمن فقط)
    // ============================================
    Route::post('courses', [CourseController::class, 'store']);
    Route::put('courses/{course}', [CourseController::class, 'update']);
    Route::patch('courses/{course}', [CourseController::class, 'update']);
    Route::delete('courses/{course}', [CourseController::class, 'destroy']);

    // ============================================
    // مسارات إدارة الطلاب (تحتاج أدمن فقط)
    // ============================================
    Route::middleware('admin')->group(function (): void {
        Route::get('students', [StudentController::class, 'index']);
        Route::patch('students/{student}/approve', [StudentController::class, 'approve']);
    });
});
