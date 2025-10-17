<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', \App\Http\Controllers\Api\HealthController::class);

Route::post('/folders', [\App\Http\Controllers\Api\FolderController::class, 'store']);

// Auth (Bearer token via Sanctum Personal Access Tokens)
Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [\App\Http\Controllers\Api\AuthController::class, 'logoutAll']);
});


