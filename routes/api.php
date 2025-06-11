<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaggingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/test', function () {
        return 'test';
    });

    Route::get('/businesses-in-box', [TaggingController::class, 'getBusinessInBoundingBox']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
