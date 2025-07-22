<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\SysUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;

// === HEALTH CHECK (Public) ===
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'VALET Smart Parking API',
        'timestamp' => now(),
        'version' => '1.0.0',
        'features' => [
            'authentication' => true,
            'feedback_crud' => true,
            'parking_sensors' => true,
            'floor_tracking' => true,
            'real_time_updates' => true,
            'admin_only_tokens' => true,
        ]
    ]);
});

// === AUTHENTICATION ROUTES (Public) ===
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected auth routes (require token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/revoke-all', [AuthController::class, 'revokeAllTokens']);
        Route::get('/check-token', [AuthController::class, 'checkToken']);
    });
});

// === PROTECTED ROUTES (Require Admin Token) ===
Route::middleware('auth:sanctum')->group(function () {
    
    // === FEEDBACK ROUTES (Primary focus for mobile devs) ===
    Route::prefix('feedbacks')->group(function () {
        Route::get('/', [FeedbackController::class, 'index']);           // List all feedbacks with filters
        Route::post('/', [FeedbackController::class, 'store']);          // Create new feedback
        Route::get('/stats', [FeedbackController::class, 'stats']);      // Feedback statistics
        Route::get('/{id}', [FeedbackController::class, 'show']);        // Get single feedback
        Route::put('/{id}', [FeedbackController::class, 'update']);      // Update feedback (admin response)
        Route::delete('/{id}', [FeedbackController::class, 'destroy']);  // Delete feedback
        Route::post('/bulk-status', [FeedbackController::class, 'bulkUpdateStatus']); // Bulk status update
    });
    
    // === PARKING SYSTEM ROUTES ===
    Route::prefix('parking')->group(function () {
        Route::get('/', [ParkingController::class, 'index']);           // Get all parking spaces
        Route::post('/', [ParkingController::class, 'store']);          // ESP32 sends sensor data here
        Route::get('/stats', [ParkingController::class, 'stats']);      // Statistics for dashboard
        Route::get('/floor/{floorLevel}', [ParkingController::class, 'getByFloor']); // Floor-specific data
    });
    
    // === USER SYSTEM ROUTES ===
    Route::prefix('users')->group(function () {
        Route::get('/', [SysUserController::class, 'index']);           // Get all registered users
        Route::get('/stats', [SysUserController::class, 'stats']);      // User statistics
    });
});

// === PUBLIC PARKING ROUTES (For ESP32 sensors - no auth needed) ===
Route::prefix('public')->group(function () {
    Route::post('/parking/update', [ParkingController::class, 'store']); // ESP32 sensor updates
    Route::get('/parking/status', [ParkingController::class, 'index']);  // Public parking status
});

// === API Documentation Route ===
Route::get('/docs', function () {
    return response()->json([
        'api_name' => 'VALET Smart Parking API',
        'version' => '1.0.0',
        'description' => 'API for VALET Smart Parking System - Feedback Management & Parking Data',
        'authentication' => [
            'type' => 'Bearer Token (Sanctum)',
            'restriction' => 'Admin accounts only',
            'login_endpoint' => '/api/auth/login',
            'token_expiry' => '7 days',
        ],
        'endpoints' => [
            'authentication' => [
                'POST /api/auth/login' => 'Login (admin only) - get API token',
                'POST /api/auth/logout' => 'Logout - revoke current token',
                'GET /api/auth/profile' => 'Get current user profile',
                'GET /api/auth/check-token' => 'Verify token validity',
                'POST /api/auth/revoke-all' => 'Revoke all tokens for user',
            ],
            'feedbacks' => [
                'GET /api/feedbacks' => 'List feedbacks (with filters: status, type, user_id, search, page, per_page)',
                'POST /api/feedbacks' => 'Create new feedback',
                'GET /api/feedbacks/{id}' => 'Get single feedback',
                'PUT /api/feedbacks/{id}' => 'Update feedback (admin response)',
                'DELETE /api/feedbacks/{id}' => 'Delete feedback',
                'GET /api/feedbacks/stats' => 'Feedback statistics',
                'POST /api/feedbacks/bulk-status' => 'Bulk update status',
            ],
            'parking' => [
                'GET /api/parking' => 'Get all parking spaces',
                'POST /api/parking' => 'Update parking space (from sensors)',
                'GET /api/parking/stats' => 'Parking statistics',
                'GET /api/parking/floor/{floor}' => 'Get spaces by floor',
            ],
            'users' => [
                'GET /api/users' => 'Get all users',
                'GET /api/users/stats' => 'User statistics',
            ],
            'public' => [
                'POST /api/public/parking/update' => 'ESP32 sensor updates (no auth)',
                'GET /api/public/parking/status' => 'Public parking status (no auth)',
            ]
        ],
        'example_requests' => [
            'login' => [
                'method' => 'POST',
                'url' => '/api/auth/login',
                'body' => [
                    'email' => 'admin@valet.com',
                    'password' => 'password123'
                ]
            ],
            'create_feedback' => [
                'method' => 'POST',
                'url' => '/api/feedbacks',
                'headers' => ['Authorization' => 'Bearer {token}'],
                'body' => [
                    'user_id' => 1,
                    'type' => 'bug',
                    'message' => 'The parking app crashes when I try to view floor 2.',
                    'email' => 'user@example.com',
                    'device_info' => [
                        'platform' => 'android',
                        'version' => '1.0.0',
                        'model' => 'Samsung Galaxy S21'
                    ]
                ]
            ]
        ]
    ]);
});