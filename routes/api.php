<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParkingConfigController;
use App\Http\Controllers\Api\SensorAssignmentController;
use App\Http\Controllers\Api\SysUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\RfidController;

Route::get('/health', fn() => response()->json(['status' => 'ok', 'service' => 'VALET API']));

// Authentication endpoint
Route::post('/login', [AuthController::class, 'login']);

// Public endpoints
Route::prefix('public')->group(function () {
    Route::get('/parking', [ParkingController::class, 'index']);
    Route::post('/parking', [ParkingController::class, 'store']);

    // Mobile App Endpoints - Public access for parking map display
    Route::get('/parking/map', [ParkingController::class, 'getMapData']);
    Route::get('/parking/dashboard', [ParkingController::class, 'getDashboardStats']);

    // Sensor assignment endpoint (for Arduino to fetch its assignment)
    Route::post('/sensor/assignment', [SensorAssignmentController::class, 'getAssignment']);

    // Sensor registration endpoint (for Arduino to register all sensors on boot)
    Route::post('/sensor/register', [SensorAssignmentController::class, 'register']);

    // RFID endpoints (for ESP32 gate controllers)
    Route::post('/rfid/verify', [RfidController::class, 'verify']);
    Route::post('/rfid/exit', [RfidController::class, 'exit']);
    Route::get('/rfid/scans', [RfidController::class, 'recentScans']);
    Route::post('/guest/verify', [RfidController::class, 'verifyGuest']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/profile', 'profile');
        Route::get('/validate', 'validate');
        Route::post('/reset-password', 'resetPassword');
        Route::post('/set-default-passwords', 'setDefaultPasswords');
        Route::post('/user/push-token', 'updatePushToken');
        Route::delete('/user/push-token', 'removePushToken');
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

    Route::controller(ParkingConfigController::class)->prefix('parking-config')->group(function () {
        Route::get('/floors', 'getFloors');
        Route::post('/floors', 'createFloor');
        Route::get('/floors/{floorNumber}/columns', 'getColumns');
        Route::post('/columns', 'createColumn');
        Route::get('/floors/{floorNumber}/columns/{columnCode}/max-slots', 'getMaxSlots');
    });

    Route::controller(SysUserController::class)->prefix('users')->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
    });

    // Sensor Assignment Management Routes (Admin only)
    Route::controller(SensorAssignmentController::class)->prefix('sensors')->group(function () {
        Route::get('/', 'index');                          // Get all sensors
        Route::get('/unassigned', 'unassigned');           // Get unassigned sensors
        Route::post('/assign', 'assign');                  // Assign sensor to space
        Route::post('/unassign', 'unassign');              // Unassign sensor
        Route::post('/identify/start', 'startIdentify');   // Start identify mode (blue LED)
        Route::post('/identify/stop', 'stopIdentify');     // Stop identify mode
        Route::put('/{macAddress}', 'update');             // Update sensor details
        Route::delete('/{macAddress}', 'destroy');         // Delete sensor
    });

});
