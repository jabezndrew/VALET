<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;

Route::prefix('parking')->group(function () {
    Route::get('/', [ParkingController::class, 'index']);           // Get all parking spaces
    Route::post('/', [ParkingController::class, 'store']);          // ESP32 sends sensor data here
    Route::get('/stats', [ParkingController::class, 'stats']);      // Statistics for dashboard
    Route::get('/floor/{floorLevel}', [ParkingController::class, 'getByFloor']); // Floor-specific data
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'VALET Smart Parking API',
        'timestamp' => now(),
        'features' => [
            'parking_sensors' => true,
            'floor_tracking' => true,
            'real_time_updates' => true,
            'livewire_dashboard' => true
        ]
    ]);
});