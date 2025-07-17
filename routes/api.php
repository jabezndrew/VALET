<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\SysUserController;

// === PARKING SYSTEM ROUTES ===
Route::prefix('parking')->group(function () {
    Route::get('/', [ParkingController::class, 'index']);           // Get all parking spaces
    Route::post('/', [ParkingController::class, 'store']);          // ESP32 sends sensor data here
    Route::get('/stats', [ParkingController::class, 'stats']);      // Statistics for dashboard
    Route::get('/floor/{floorLevel}', [ParkingController::class, 'getByFloor']); // Floor-specific data
});

// === USER SYSTEM ROUTES (No Auth Required) ===
Route::prefix('users')->group(function () {
    Route::get('/', [SysUserController::class, 'index']);           // Get all registered users
    Route::get('/stats', [SysUserController::class, 'stats']);      // User statistics
});

// === HEALTH CHECK ===
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'VALET Smart Parking API',
        'timestamp' => now(),
        'features' => [
            'parking_sensors' => true,
            'floor_tracking' => true,
            'real_time_updates' => true,
            'livewire_dashboard' => true,
            'user_listing' => true,
        ]
    ]);
});