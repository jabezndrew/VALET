<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', App\Livewire\Auth\Login::class)->name('login');
});

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/dashboard', App\Livewire\ParkingDashboard::class)->name('dashboard');
    Route::get('/floor/{floor}', App\Livewire\FloorDetail::class)->name('floor.detail');
    Route::get('/feedback', \App\Livewire\FeedbackManager::class)->name('feedback.index');
});

Route::middleware(['auth', 'role:security'])->group(function () {
    Route::get('/cars', \App\Livewire\VehicleManager::class)->name('cars.index');
});

Route::middleware(['auth', 'role:ssd'])->group(function () {
    Route::get('/admin/users', \App\Livewire\UserManager::class)->name('admin.users');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/pending-accounts', \App\Livewire\PendingAccountManager::class)->name('admin.pending-accounts');
    Route::get('/admin/settings', function () { return 'Settings - Coming Soon'; })->name('admin.settings');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});