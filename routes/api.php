<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaggingController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/test', function () {
        return 'test';
    });

    Route::get('/businesses-in-box', [TaggingController::class, 'getBusinessInBoundingBox']);


    Route::get('users/{user}/projects', [ProjectController::class, 'getProjectsByUser']);
    Route::post('mobile-projects', [ProjectController::class, 'storeMobileProject']);
    Route::get('mobile-projects/{id}', [ProjectController::class, 'show']);
    Route::put('mobile-projects/{id}', [ProjectController::class, 'updateMobileProject']);
    Route::delete('mobile-projects/{id}', [ProjectController::class, 'destroyMobileProject']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
