<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\ParkingDashboard;
use App\Livewire\FloorDetail;
use App\Livewire\FeedbackManager;
use App\Livewire\VehicleManager;
use App\Livewire\UserManager;
use App\Livewire\PendingAccountManager;
use App\Livewire\SensorManager;
use App\Livewire\PublicParkingDisplay;
use App\Livewire\RfidManagement;
use App\Livewire\GuardParkingDisplay;
use App\Livewire\ParkingLog;

// Public routes - no authentication required
Route::get('/parking-display', PublicParkingDisplay::class)->name('parking.display.public');
Route::get('/guard', GuardParkingDisplay::class)->name('guard.display');

Route::get('/', fn() => auth()->check() ? redirect('/dashboard') : redirect('/login'));

// Public route to seed parking spaces (protected by secret key)
Route::get('/seed-parking-spaces/{secret}', function ($secret) {
    if ($secret !== 'valet2025secret') {
        abort(403, 'Unauthorized');
    }

    \Artisan::call('parking:seed');
    $output = \Artisan::output();

    return response('<pre>' . $output . '</pre><br><a href="/parking-display">Go to Parking Display</a>');
})->name('public.seed-parking');

// Public route to clear all caches (protected by secret key)
Route::get('/clear-cache/{secret}', function ($secret) {
    if ($secret !== 'valet2025secret') {
        abort(403, 'Unauthorized');
    }

    $output = '';

    \Artisan::call('cache:clear');
    $output .= "Cache cleared\n";

    \Artisan::call('config:clear');
    $output .= "Config cleared\n";

    \Artisan::call('view:clear');
    $output .= "Views cleared\n";

    \Artisan::call('route:clear');
    $output .= "Routes cleared\n";

    return response('<pre>' . $output . '</pre><br><strong>All caches cleared!</strong><br><a href="/parking-display">Go to Parking Display</a>');
})->name('public.clear-cache');

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

    // Admin-only route to seed parking spaces
    Route::get('/admin/seed-parking', function () {
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        \Artisan::call('parking:seed');
        $output = \Artisan::output();

        return response('<pre>' . $output . '</pre><br><a href="/parking-display">Go to Parking Display</a>');
    })->name('admin.seed-parking');
    
    Route::middleware('role:user')->group(function () {
        Route::get('/dashboard', ParkingDashboard::class)->name('dashboard');
        Route::get('/parking-display', PublicParkingDisplay::class)->name('parking-display');
        Route::get('/floor/{floor}', FloorDetail::class)->name('floor.detail');
        Route::get('/feedback', FeedbackManager::class)->name('feedback.index');
    });
    
    Route::middleware('role:security')->group(function () {
        Route::get('/cars', VehicleManager::class)->name('cars.index');
        Route::get('/parking-log', ParkingLog::class)->name('parking-log');
    });
    
    Route::middleware('role:ssd')->group(function () {
        Route::get('/admin/users', UserManager::class)->name('admin.users');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pending-accounts', PendingAccountManager::class)->name('pending-accounts');
        Route::get('/sensors', SensorManager::class)->name('sensors');
        Route::get('/rfid', RfidManagement::class)->name('rfid');
        Route::get('/tools', App\Livewire\Tools::class)->name('tools');
    });
    
});