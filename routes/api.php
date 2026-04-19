<?php

use App\Http\Controllers\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->post('/tokens/create', [TokenController::class, 'create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::delete('/tokens/current', [TokenController::class, 'destroy']);
});
