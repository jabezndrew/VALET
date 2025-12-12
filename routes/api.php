<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\ParkingConfigController;
use App\Http\Controllers\Api\SysUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;

Route::get('/health', fn() => response()->json(['status' => 'ok', 'service' => 'VALET API']));

// Authentication endpoint
Route::post('/login', [AuthController::class, 'login']);

// Public endpoints
Route::prefix('public')->group(function () {
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

});