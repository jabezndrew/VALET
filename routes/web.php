<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    try {
        return "VALET Parking System - Laravel is working! ✅";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine();
    }
});

Route::get('/dashboard', function () {
    try {
        return "Dashboard test - Route is working! ✅";
    } catch (\Exception $e) {
        return "Dashboard Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine();
    }
});

Route::get('/test-db', function () {
    try {
        $result = \DB::select('SELECT 1 as test');
        return "Database connection works! ✅ Result: " . json_encode($result);
    } catch (\Exception $e) {
        return "Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine();
    }
});

Route::get('/test-simple', function () {
    return response()->json([
        'status' => 'working',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Simple route works'
    ]);
});