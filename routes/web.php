<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\ParkingDashboard;
use App\Livewire\ParkingMapLayout;
use App\Livewire\FloorDetail;
use App\Livewire\FeedbackManager;
use App\Livewire\VehicleManager;
use App\Livewire\UserManager;
use App\Livewire\PendingAccountManager;
use App\Livewire\SensorManager;
use App\Livewire\PublicParkingDisplay;

// Public route - no authentication required
Route::get('/parking-display', PublicParkingDisplay::class)->name('parking.display.public');

Route::get('/', fn() => auth()->check() ? redirect('/dashboard') : redirect('/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function () {
    
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
    
    Route::middleware('role:user')->group(function () {
        Route::get('/dashboard', ParkingDashboard::class)->name('dashboard');
        Route::get('/parking-map/{floor?}', ParkingMapLayout::class)->name('parking.map');
        Route::get('/floor/{floor}', FloorDetail::class)->name('floor.detail');
        Route::get('/feedback', FeedbackManager::class)->name('feedback.index');
    });
    
    Route::middleware('role:security')->group(function () {
        Route::get('/cars', VehicleManager::class)->name('cars.index');
    });
    
    Route::middleware('role:ssd')->group(function () {
        Route::get('/admin/users', UserManager::class)->name('admin.users');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pending-accounts', PendingAccountManager::class)->name('pending-accounts');
        Route::get('/sensors', SensorManager::class)->name('sensors');
    });
    
});