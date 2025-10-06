<?php

use Illuminate\Support\Facades\Response;
use App\Http\Controllers\AdminKabController;
use App\Http\Controllers\AdminProvController;
use App\Http\Controllers\AnomalyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ResetPassword;
use App\Http\Controllers\ChangePassword;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MajapahitLoginController;
use App\Http\Controllers\MarketAssignmentController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MarketManagementController;
use App\Http\Controllers\PclController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplementController;
use App\Http\Controllers\UserController;

// Route::get('/info', function () {
// 	return view('pages.info');
// })->middleware('guest')->name('register');

Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest')->name('login.perform');
Route::get('/majapahit', [MajapahitLoginController::class, 'login']);
Route::get('/majapahit-mobile-login', [MajapahitLoginController::class, 'loginApi']);
Route::get('/login-redirect', [MajapahitLoginController::class, 'redirectApi']);

Route::get('/.well-known/assetlinks.json', function () {
	$path = public_path('.well-known/assetlinks.json');
	return Response::file($path, ['Content-Type' => 'application/json']);
});

Route::group(['middleware' => 'auth'], function () {

	Route::impersonate();

	Route::get('/change-password', [ChangePassword::class, 'show'])->name('change-password');
	Route::post('/change-password', [ChangePassword::class, 'update'])->name('change.perform');

	Route::get('/sls-directory/data', [HomeController::class, 'getSlsDirectoryTables']);
	Route::get('/non-sls-directory/data', [HomeController::class, 'getNonSlsDirectoryTables']);

	// Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/', [MarketController::class, 'homeRedirect'])->name('home');
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

	Route::get('/status/data/{type}', [HomeController::class, 'getAssignmentStatusData']);
	Route::post('/status/download/{type}', [HomeController::class, 'getAssigmentFile']);

	Route::group(['middleware' => ['role:pcl|pml|operator|adminkab|adminprov']], function () {
		Route::get('/pemutakhiran-non-sls', [AdminKabController::class, 'updatePage'])->name('updating-non-sls');
		Route::get('/tambah-direktori', [AdminKabController::class, 'showAddition'])->name('tambah-direktori');

		Route::get('/pasar/upload', [MarketController::class, 'showUploadPage'])->name('market-upload');
		Route::post('/pasar/upload', [MarketController::class, 'upload']);
		Route::get('/pasar/upload/data', [MarketController::class, 'getUploadStatusData']);

		Route::get('/pasar', [MarketController::class, 'index'])->name('market');
		Route::get('/pasar/muatan', [MarketController::class, 'showMarketDistributionPage']);
		Route::get('/pasar/data', [MarketController::class, 'getMarketData']);
		Route::delete('/pasar/{id}', [MarketController::class, 'deleteMarketBusiness']);
		Route::post('/pasar/download', [MarketController::class, 'downloadUploadedData']);
		Route::post('/pasar/download/swmap', [MarketController::class, 'downloadSwmapsExport']);
		Route::get('/pasar/kab/{regency}', [MarketController::class, 'getMarketByRegency']);
		Route::get('/pasar/filter', [MarketController::class, 'getMarketByFilter']);
		Route::get('/pasar/type', [MarketController::class, 'getMarketTypes']);

		Route::get('/pasar/manajemen', [MarketManagementController::class, 'showMarketManagementPage']);
		Route::get('/pasar/manajemen/data', [MarketManagementController::class, 'getMarketManagementData']);
		Route::post('/pasar/manajemen/download/{id}', [MarketManagementController::class, 'downloadMarketProject']);

		Route::get('/suplemen', [SupplementController::class, 'showSupplementIndexPage'])->name('suplemen');
		Route::get('/suplemen/data', [SupplementController::class, 'getSupplementData']);
		Route::get('/suplemen/upload', [SupplementController::class, 'showSupplementUploadPage']);
		Route::post('/suplemen/upload', [SupplementController::class, 'upload']);
		Route::get('/suplemen/download', [SupplementController::class, 'showSupplementDownloadPage']);
		Route::post('/suplemen/download/swmap', [SupplementController::class, 'downloadSwmapsExport']);
		Route::post('/suplemen/download/raw', [SupplementController::class, 'downloadSupplementBusiness']);
		Route::post('/suplemen/download-android', [SupplementController::class, 'downloadSupplementProjectAndroid']);
		Route::post('/suplemen/download-ios', [SupplementController::class, 'downloadSupplementProjectIos']);
		Route::delete('/suplemen/{id}', [SupplementController::class, 'deleteBusiness']);

		Route::get('/suplemen/upload/data', [SupplementController::class, 'getUploadStatusData']);

		Route::get('/pasar/peta', [MarketController::class, 'getMarketDistributionData']);
		Route::get('/pasar/muatan/{id}', [MarketController::class, 'getMarketBusinessDetail']);
		Route::get('/pasar/polygon/{id}', [MarketController::class, 'getMarketPolygon']);
	});

	Route::group(['middleware' => ['role:adminkab']], function () {
		Route::get('/assignment', [AdminKabController::class, 'showAssignment'])->name('assignment');
		Route::get('/download', [AdminKabController::class, 'showDownload'])->name('download');
		Route::get('/report', [ReportController::class, 'index'])->name('report');
	});

	Route::group(['middleware' => ['role:adminprov']], function () {
		Route::get('/personifikasi', [AdminProvController::class, 'showPersonification']);
		Route::get('/users/search', [UserController::class, 'searchUser']);

		Route::resource('users', UserController::class)->only(['create', 'store']);
	});

	Route::group(['middleware' => ['role:adminkab|adminprov']], function () {
		Route::get('/users/kab/{regency}', [UserController::class, 'getUserByRegency']);

		Route::get('/report/{date}/{type}/{level}/{id}', [ReportController::class, 'getReport']);
		Route::get('/users/data', [UserController::class, 'getUserData']);
		Route::resource('users', UserController::class)->except(['create', 'store']);

		Route::get('/pasar-assignment', [MarketAssignmentController::class, 'showMarketAssignmentForm'])->name('market-assignment');
		Route::get('/pasar-assignment/pivot', [MarketAssignmentController::class, 'getUserMarketPivot']);
		Route::get('/pasar-assignment/list', [MarketAssignmentController::class, 'showMarketAssignmentPage']);
		Route::get('/pasar-assignment/create', [MarketAssignmentController::class, 'showMarketAssignmentCreatePage']);
		Route::post('/pasar-assignment/store', [MarketAssignmentController::class, 'storeMarketAssignment']);
		Route::post('/pasar-assignment/upload', [MarketAssignmentController::class, 'uploadMarketAssignment']);
		Route::post('/pasar-assignment/download', [MarketAssignmentController::class, 'downloadMarketAssignment']);
		Route::delete('/pasar-assignment/{id}', [MarketAssignmentController::class, 'deleteMarketAssignment']);

		Route::patch('/pasar/manajemen/selesai/{id}', [MarketManagementController::class, 'changeMarketCompletionStatus']);
		Route::post('/pasar/manajemen/download', [MarketManagementController::class, 'downloadMarket']);
		Route::get('/pasar/manajemen/create', [MarketManagementController::class, 'showMarketCreatePage']);
		Route::get('/pasar/manajemen/{id}/edit', [MarketManagementController::class, 'showMarketEditPage']);
		Route::patch('/pasar/manajemen/kategori/{id}', [MarketManagementController::class, 'changeMarketTargetCategory']);
		Route::post('/pasar/manajemen', [MarketManagementController::class, 'storeMarket']);
		Route::put('/pasar/manajemen/{id}', [MarketManagementController::class, 'updateMarket']);
		Route::delete('/pasar/manajemen/{id}', [MarketManagementController::class, 'deleteMarket']);

		Route::get('/pasar-dashboard', [DashboardController::class, 'showDashboardPage'])->name('market-dashboard');
		Route::get('/kenarok-dashboard', [DashboardController::class, 'showDashboardKenarok']);
		Route::get('/pasar-dashboard/download', [DashboardController::class, 'showDownloadReportPage']);
		Route::post('/pasar-dashboard/download', [DashboardController::class, 'downloadReport']);
		Route::get('/pasar-dashboard/market/data/{date}', [DashboardController::class, 'getMarketReportData']);
		Route::get('/pasar-dashboard/user/data/{date}', [DashboardController::class, 'getUserReportData']);
		Route::get('/pasar-dashboard/regency/data/{date}', [DashboardController::class, 'getRegencyReportData']);
		Route::get('/pasar-dashboard/area', [DashboardController::class, 'getAreaReportData']);
		Route::get('/pasar-dashboard/graph/data/{regency}', [DashboardController::class, 'getGraphReportData']);

		Route::post('/pasar/savepolygon/{id}', [MarketManagementController::class, 'savePolygonMarket']);

		Route::get('/suplemen/restore', [SupplementController::class, 'showRestorePage']);
		Route::post('/suplemen/restore', [SupplementController::class, 'restoreBusinesses']);
	});

	Route::group(['middleware' => ['role:adminprov|adminkab|operator']], function () {
		Route::get('/anomali', [AnomalyController::class, 'index']);
		Route::get('/anomali/data', [AnomalyController::class, 'getAnomalyListData']);
		Route::post('/anomali/update', [AnomalyController::class, 'updateAnomaly']);
		Route::post('/anomali/download', [AnomalyController::class, 'downloadAnomaly']);
		Route::delete('/anomali/delete/{id}', [AnomalyController::class, 'deleteAnomalyBusiness']);

		Route::patch('/suplemen/{id}', [SupplementController::class, 'updateSupplement']);
		Route::delete('/suplemen/{id}/delete', [SupplementController::class, 'confirmDeleteBusiness']);

		Route::patch('/pasar/{id}', [MarketController::class, 'updateMarket']);
		Route::delete('/pasar/{id}/delete', [MarketController::class, 'confirmDeleteBusiness']);
	});

	Route::get('/{page}', [PageController::class, 'index'])->name('page');

	Route::post('acting-context/toggle', [UserController::class, 'toggleActingContext'])->name('acting-context.toggle');
	Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
