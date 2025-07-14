<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SysUserController;

// Redirect root to login or dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [SysUserController::class, 'showLogin'])->name('login');
    Route::post('/login', [SysUserController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SysUserController::class, 'logout'])->name('logout');
});

// Protected routes - require authentication
Route::middleware(['auth', 'role:user'])->group(function () {
    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', function () {
        return view('dashboard.livewire-index');
    })->name('dashboard');
    
    // Floor details - accessible to all authenticated users
    Route::get('/floor/{floor}', function ($floor) {
        return view('dashboard.floor-detail', ['floor' => urldecode($floor)]);
    })->name('floor.detail');
});

// Security routes - accessible to security, ssd, and admin
Route::middleware(['auth', 'role:security'])->group(function () {
    Route::get('/cars', function () {
        return view('cars.index');
    })->name('cars.index');
});

// SSD routes - accessible to ssd and admin
Route::middleware(['auth', 'role:ssd'])->group(function () {
    Route::get('/cars/create', function () {
        return view('cars.create');
    })->name('cars.create');
    
    Route::post('/cars', function () {
        // Car creation logic will go here
    })->name('cars.store');
    
    Route::get('/cars/{car}/edit', function ($car) {
        return view('cars.edit', ['car' => $car]);
    })->name('cars.edit');
    
    Route::put('/cars/{car}', function ($car) {
        // Car update logic will go here
    })->name('cars.update');
    
    Route::delete('/cars/{car}', function ($car) {
        // Car deletion logic will go here
    })->name('cars.destroy');
});

// Admin routes - accessible to admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    // User management
    Route::get('/admin/users', function () {
        return view('admin.users.index');
    })->name('admin.users');
    
    Route::get('/admin/users/{user}/edit', function ($user) {
        return view('admin.users.edit', ['user' => $user]);
    })->name('admin.users.edit');
    
    Route::put('/admin/users/{user}', function ($user) {
        // User update logic will go here
    })->name('admin.users.update');
    
    Route::delete('/admin/users/{user}', function ($user) {
        // User deletion logic will go here
    })->name('admin.users.destroy');
    
    // System settings
    Route::get('/admin/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');
});