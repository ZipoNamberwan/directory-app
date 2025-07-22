<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PolygonController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaggingController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\WilkerstatController;
use App\Http\Controllers\MajapahitLoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/wilkerstat', [AuthController::class, 'loginWilkerstat']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/test', function () {
        return 'test';
    });

    Route::get('/assignments/wilkerstat', [WilkerstatController::class, 'getAssignments']);
    Route::get('/assignments/wilkerstat/village/{villageId}', [WilkerstatController::class, 'getBusinessByVillage']);
    Route::get('/assignments/wilkerstat/sls/{slsId}', [WilkerstatController::class, 'getBusinessBySls']);

    Route::get('/business-in-box', [TaggingController::class, 'getBusinessInBoundingBox']);
    Route::get('/business/project/{projectId}', [TaggingController::class, 'getBusinessByProject']);
    Route::post('/business', [TaggingController::class, 'storeSupplementBusiness']);
    Route::post('/business/upload-multiple', [TaggingController::class, 'uploadMultipleTags']);
    Route::put('/business/{id}', [TaggingController::class, 'updateSupplementBusiness']);
    Route::delete('/business/delete-multiple', [TaggingController::class, 'deleteMultipleTags']);
    Route::delete('/business/{id}', [TaggingController::class, 'deleteSupplementBusiness']);

    Route::get('users/{user}/projects', [ProjectController::class, 'getProjectsByUser']);
    Route::post('mobile-projects', [ProjectController::class, 'storeMobileProject']);
    Route::get('mobile-projects/{id}', [ProjectController::class, 'show']);
    Route::put('mobile-projects/{id}', [ProjectController::class, 'updateMobileProject']);
    Route::delete('mobile-projects/{id}', [ProjectController::class, 'destroyMobileProject']);

    Route::get('/version/check', [VersionController::class, 'shouldUpdateKendedes']);
    Route::get('/version/check/leres-pak', [VersionController::class, 'shouldUpdateLeresPak']);

    Route::get('/villages/{subdistrict}', [PolygonController::class, 'getVillagesBySubdistrict']);
    Route::get('/sls/{village}', [PolygonController::class, 'getSlsByVillage']);
    Route::post('/polygon/download', [PolygonController::class, 'downloadPolygonData']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
