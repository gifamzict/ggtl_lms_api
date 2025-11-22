<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonProgressController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PaymentController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public course browsing
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{slug}', [CourseController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/reviews/course/{courseId}', [ReviewController::class, 'index']);

// Public payment endpoints
Route::get('/payment/public-key', [PaymentController::class, 'getPublicKey']);
Route::post('/payment/webhook', [PaymentController::class, 'webhook']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);

    // Student routes
    Route::get('/my-courses', [EnrollmentController::class, 'index']);
    Route::post('/enroll', [EnrollmentController::class, 'store']);
    Route::get('/enrollment/{courseId}', [EnrollmentController::class, 'show']);
    Route::get('/learn/{slug}', [CourseController::class, 'learn']);
    
    // Lesson progress
    Route::put('/lessons/{lessonId}/progress', [LessonProgressController::class, 'update']);
    Route::get('/lessons/{lessonId}/progress', [LessonProgressController::class, 'show']);
    
    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // Payment
    Route::post('/payment/initialize', [PaymentController::class, 'initialize']);
    Route::post('/payment/verify', [PaymentController::class, 'verify']);

    // Admin routes (requires admin role)
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
        
        // Course management
        Route::get('/courses', [CourseController::class, 'adminIndex']);
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
        
        // Lesson management
        Route::get('/courses/{courseId}/lessons', [LessonController::class, 'index']);
        Route::post('/lessons', [LessonController::class, 'store']);
        Route::put('/lessons/{id}', [LessonController::class, 'update']);
        Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);
        Route::post('/courses/{courseId}/lessons/reorder', [LessonController::class, 'reorder']);
        
        // Category management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        
        // User management
        Route::get('/students', [AdminController::class, 'students']);
        Route::get('/admins', [AdminController::class, 'admins']);
        Route::put('/users/{userId}/promote', [AdminController::class, 'promoteToAdmin']);
        Route::put('/users/{userId}/demote', [AdminController::class, 'demoteAdmin']);
        
        // Orders
        Route::get('/orders', [AdminController::class, 'orders']);
        
        // Payment settings
        Route::get('/payment-settings', [PaymentController::class, 'getSettings']);
        Route::put('/payment-settings', [PaymentController::class, 'updateSettings']);
    });
});
