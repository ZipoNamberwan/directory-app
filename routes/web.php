<?php

use App\Http\Controllers\AdminKabController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\ChangePassword;
use App\Http\Controllers\PclController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

Route::get('/info', function () {
	return view('pages.info');
})->middleware('guest')->name('register');

Route::get('/register', [RegisterController::class, 'create'])->middleware('guest')->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware('guest')->name('register.perform');
Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest')->name('login.perform');
Route::get('/reset-password', [ResetPassword::class, 'show'])->middleware('guest')->name('reset-password');
Route::post('/reset-password', [ResetPassword::class, 'send'])->middleware('guest')->name('reset.perform');
Route::get('/change-password', [ChangePassword::class, 'show'])->middleware('guest')->name('change-password');
Route::post('/change-password', [ChangePassword::class, 'update'])->middleware('guest')->name('change.perform');

Route::group(['middleware' => 'auth'], function () {

	Route::get('/sls-directory/data', [HomeController::class, 'getSlsDirectoryTables']);
	Route::get('/non-sls-directory/data', [HomeController::class, 'getNonSlsDirectoryTables']);

	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/desa/{subdistrict_id}', [HomeController::class, 'getVillage']);
	Route::get('/sls/{village_id}', [HomeController::class, 'getSls']);
	Route::get('/sls-directory/{id_sls}', [HomeController::class, 'getSlsDirectory']);

	Route::post('/sls-directory', [HomeController::class, 'addDirectory']);
	Route::delete('/sls-directory/{id}', [HomeController::class, 'deleteDirectory']);
	Route::patch('/directory/edit/{type}/{id}', [HomeController::class, 'updateDirectory']);

	Route::group(['middleware' => ['role:pcl|adminkab']], function () {
		Route::get('/pemutakhiran-sls', [PclController::class, 'updatePage'])->name('updating-sls');
	});

	Route::group(['middleware' => ['role:adminkab|pml']], function () {
		Route::get('/pemutakhiran-non-sls', [AdminKabController::class, 'updatePage'])->name('updating-non-sls');
	});

	Route::group(['middleware' => ['role:adminkab']], function () {
		Route::get('/assignment', [AdminKabController::class, 'showAssignment'])->name('assignment');
		Route::get('/report', [ReportController::class, 'index'])->name('report');

		Route::get('/users/data', [UserController::class, 'getUserData']);
		Route::resource('users', UserController::class);
	});

	Route::get('/virtual-reality', [PageController::class, 'vr'])->name('virtual-reality');
	Route::get('/rtl', [PageController::class, 'rtl'])->name('rtl');
	Route::get('/profile', [UserProfileController::class, 'show'])->name('profile');
	Route::post('/profile', [UserProfileController::class, 'update'])->name('profile.update');
	Route::get('/profile-static', [PageController::class, 'profile'])->name('profile-static');
	Route::get('/sign-in-static', [PageController::class, 'signin'])->name('sign-in-static');
	Route::get('/sign-up-static', [PageController::class, 'signup'])->name('sign-up-static');
	Route::get('/{page}', [PageController::class, 'index'])->name('page');

	Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
