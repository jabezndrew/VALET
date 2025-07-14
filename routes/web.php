<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard.livewire-index');
})->name('dashboard');

Route::get('/floor/{floor}', function ($floor) {
    return view('dashboard.floor-detail', ['floor' => urldecode($floor)]);
})->name('floor.detail');