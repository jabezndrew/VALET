<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\SysUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;

// === PUBLIC ROUTES ===
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'VALET API']);
});

// === AUTHENTICATION ===
Route::post('/login', [AuthController::class, 'login']);

// === PROTECTED ROUTES ===
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/validate', [AuthController::class, 'validate']);
    
    // Feedbacks
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::post('/feedbacks', [FeedbackController::class, 'store']);
    Route::get('/feedbacks/stats', [FeedbackController::class, 'stats']);
    Route::get('/feedbacks/{id}', [FeedbackController::class, 'show']);
    Route::put('/feedbacks/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedbacks/{id}', [FeedbackController::class, 'destroy']);
    Route::post('/feedbacks/bulk-status', [FeedbackController::class, 'bulkUpdateStatus']);
    
    // Parking
    Route::get('/parking', [ParkingController::class, 'index']);
    Route::post('/parking', [ParkingController::class, 'store']);
    Route::get('/parking/stats', [ParkingController::class, 'stats']);
    Route::get('/parking/floor/{floorLevel}', [ParkingController::class, 'getByFloor']);
    
    // Users
    Route::get('/users', [SysUserController::class, 'index']);
    Route::get('/users/stats', [SysUserController::class, 'stats']);
});

// === PUBLIC SENSOR ROUTES (ESP32) ===
Route::post('/public/parking', [ParkingController::class, 'store']);
Route::get('/public/parking', [ParkingController::class, 'index']);