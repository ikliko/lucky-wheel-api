<?php

use App\Http\Controllers\GameSession\CreateHandler as CreateGameSessionHandler;
use App\Http\Controllers\Spin\CreateHandler as CreateSpinHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/game/session', CreateGameSessionHandler::class)
    ->middleware('throttle:20,1');

Route::post('/game/spin', CreateSpinHandler::class)
    ->middleware(['auth:sanctum', 'throttle:60,1']);
