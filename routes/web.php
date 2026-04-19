<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/two-factor/setup', function () {
    return view('auth.two-factor-setup');
})->middleware('auth')->name('two-factor.setup');

Route::get('/home', function () {
    return view('home');
})->middleware(['auth', 'two-factor-setup'])->name('home');
