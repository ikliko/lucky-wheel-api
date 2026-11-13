<?php

use App\Http\Controllers\GameSession\CreateHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/game/session', CreateHandler::class)
    ->middleware('throttle:20,1');
