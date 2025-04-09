<?php

use App\Http\Controllers\AdminKabController;
use App\Http\Controllers\AdminProvController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\ChangePassword;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PclController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

// Route::get('/info', function () {
// 	return view('pages.info');
// })->middleware('guest')->name('register');

Route::get('/register', [RegisterController::class, 'create'])->middleware('guest')->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware('guest')->name('register.perform');
Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest')->name('login.perform');
Route::get('/reset-password', [ResetPassword::class, 'show'])->middleware('guest')->name('reset-password');
Route::post('/reset-password', [ResetPassword::class, 'send'])->middleware('guest')->name('reset.perform');

Route::group(['middleware' => 'auth'], function () {

	Route::impersonate();

	Route::get('/change-password', [ChangePassword::class, 'show'])->name('change-password');
	Route::post('/change-password', [ChangePassword::class, 'update'])->name('change.perform');

	Route::get('/sls-directory/data', [HomeController::class, 'getSlsDirectoryTables']);
	Route::get('/non-sls-directory/data', [HomeController::class, 'getNonSlsDirectoryTables']);

	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/kec/{regency_id}', [HomeController::class, 'getSubdistrict']);
	Route::get('/desa/{subdistrict_id}', [HomeController::class, 'getVillage']);
	Route::get('/sls/{village_id}', [HomeController::class, 'getSls']);
	Route::get('/sls-directory/{id_sls}', [HomeController::class, 'getSlsDirectory']);

	Route::post('/sls-directory', [HomeController::class, 'addSlsDirectory']);
	Route::post('/non-sls-directory', [HomeController::class, 'addNonSlsDirectory']);
	Route::delete('/sls-directory/{id}', [HomeController::class, 'deleteSlsDirectory']);
	Route::delete('/non-sls-directory/{id}', [HomeController::class, 'deleteNonSlsDirectory']);
	Route::patch('/directory/edit/sls/{id}', [HomeController::class, 'updateSlsDirectory']);
	Route::patch('/directory/edit/non-sls/{id}', [HomeController::class, 'updateNonSlsDirectory']);

	Route::group(['middleware' => ['role:pcl|adminkab|adminprov']], function () {
		Route::get('/pemutakhiran-sls', [PclController::class, 'updatePage'])->name('updating-sls');
	});

	Route::group(['middleware' => ['role:pml|operator|adminkab|adminprov']], function () {
		Route::get('/pemutakhiran-non-sls', [AdminKabController::class, 'updatePage'])->name('updating-non-sls');
		Route::get('/tambah-direktori', [AdminKabController::class, 'showAddition'])->name('tambah-direktori');

		Route::get('/pasar-upload', [MarketController::class, 'show'])->name('market-upload');
		Route::post('/pasar-upload', [MarketController::class, 'upload']);
		Route::get('/pasar-upload/data', [MarketController::class, 'getUploadStatusData']);

		Route::post('/download-file-pasar', [MarketController::class, 'download']);

		Route::get('/pasar', [MarketController::class, 'index'])->name('market');
		Route::get('/pasar/data', [MarketController::class, 'getMarketData']);
		Route::get('/pasar/kab/{regency}', [MarketController::class, 'getMarketByRegency']);
	});

	Route::group(['middleware' => ['role:adminkab']], function () {
		Route::get('/assignment', [AdminKabController::class, 'showAssignment'])->name('assignment');
		Route::get('/download', [AdminKabController::class, 'showDownload'])->name('download');
		Route::get('/report', [ReportController::class, 'index'])->name('report');

		Route::get('/pasar-assignment', [MarketController::class, 'assignment'])->name('market-assignment');
		Route::get('/pasar-assignment/pivot', [MarketController::class, 'getUserMarketMappings']);
		Route::get('/pasar-assignment/data', [MarketController::class, 'getAssignmentStatusData']);
		Route::get('/pasar-assignment/list', [MarketController::class, 'showAssignmentList']);
		Route::get('/pasar-assignment/create', [MarketController::class, 'assignmentCreate']);
		Route::post('/pasar-assignment/store', [MarketController::class, 'assignmentStore']);
		Route::post('/pasar-assignment/upload', [MarketController::class, 'assignmentUpload']);
		Route::post('/pasar-assignment/download', [MarketController::class, 'assignmentDownload']);
		Route::delete('/pasar-assignment/{id}', [MarketController::class, 'assignmentDelete']);

		Route::post('/download-file-assignment', [MarketController::class, 'downloadUploadedAssignment']);
	});

	Route::group(['middleware' => ['role:adminprov']], function () {
		Route::get('/personifikasi', [AdminProvController::class, 'showPersonification']);
		Route::get('/users/search', [UserController::class, 'searchUser']);
	});

	Route::group(['middleware' => ['role:adminkab|adminprov']], function () {
		Route::get('/report/{date}/{type}/{level}/{id}', [ReportController::class, 'getReport']);
		Route::get('/users/data', [UserController::class, 'getUserData']);
		Route::resource('users', UserController::class);
	});

	Route::get('/{page}', [PageController::class, 'index'])->name('page');

	Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
