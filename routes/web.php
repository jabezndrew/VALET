<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Livewire Dashboard Route
Route::get('/dashboard', function () {
    return view('dashboard.livewire-index');
})->name('dashboard');