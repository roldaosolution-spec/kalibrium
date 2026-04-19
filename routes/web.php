<?php

use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Instruments\InstrumentForm;
use App\Livewire\Instruments\InstrumentIndex;
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

Route::middleware(['auth', 'two-factor-setup'])->group(function (): void {
    // Clients CRUD
    Route::get('/clientes', ClientIndex::class)->name('clients.index');
    Route::get('/clientes/novo', ClientForm::class)->name('clients.create');
    Route::get('/clientes/{id}/editar', ClientForm::class)->name('clients.edit');

    // Instruments CRUD
    Route::get('/instrumentos', InstrumentIndex::class)->name('instruments.index');
    Route::get('/instrumentos/novo', InstrumentForm::class)->name('instruments.create');
    Route::get('/instrumentos/{id}/editar', InstrumentForm::class)->name('instruments.edit');
});
