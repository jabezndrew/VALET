<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\SysUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;

Route::get('/health', fn() => response()->json(['status' => 'ok', 'service' => 'VALET API']));

// Authentication endpoint with rate limiting (max 5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Public endpoints with rate limiting (max 60 requests per minute)
Route::prefix('public')->middleware('throttle:60,1')->group(function () {
    Route::get('/parking', [ParkingController::class, 'index']);
    Route::post('/parking', [ParkingController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/profile', 'profile');
        Route::get('/validate', 'validate');
        Route::post('/reset-password', 'resetPassword');
        Route::post('/set-default-passwords', 'setDefaultPasswords');
    });
    
    Route::controller(FeedbackController::class)->prefix('feedbacks')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/stats', 'stats');
        Route::post('/bulk-status', 'bulkUpdateStatus');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
    
    Route::controller(ParkingController::class)->prefix('parking')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/stats', 'stats');
        Route::get('/floor/{floorLevel}', 'getByFloor');
    });
    
    Route::controller(SysUserController::class)->prefix('users')->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
    });
    
});