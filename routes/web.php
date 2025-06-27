<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "VALET Parking System - Laravel is working! ✅";
});

Route::get('/dashboard', function () {
    return "Dashboard test - Route is working! ✅";
});

Route::get('/test-db', function () {
    try {
        $result = \DB::select('SELECT 1 as test');
        return "Database connection works! ✅ Result: " . json_encode($result);
    } catch (\Exception $e) {
        return "Database error: " . $e->getMessage();
    }
});