<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Parking API routes
Route::prefix('parking')->group(function () {
    Route::get('/', [ParkingController::class, 'index']);
    Route::post('/', [ParkingController::class, 'store']);
    Route::get('/stats', [ParkingController::class, 'stats']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});