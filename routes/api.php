<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HonorController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\MemberController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * 關於使用者登入的路由
 * AuthController
 */
Route::group([
    'prefix' => 'auth',
], function(){
    // Public Routes
    Route::post('login', [AuthController::class, 'login']);
    Route::get('member', [AuthController::class, 'member']); // For Frontend
    Route::get('account', [AuthController::class, 'account']); // For Backend
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/update/{id}', [AuthController::class, 'update']);
        Route::put('/ban/{id}', [AuthController::class, 'ban']);
        Route::delete('/delete/{id}', [AuthController::class, 'delete']);
        Route::post('/logout', [AuthController::class, 'logout']);
        // Member編輯
        Route::group(['prefix' => 'member'], function () {
            Route::post('/create/{id}', [MemberController::class, 'create']);
            Route::post('/update/{id}', [MemberController::class, 'update']);
            Route::delete('/delete/{id}', [MemberController::class, 'delete']);
        });
    });
});


/*
 * 關於使用者登入的路由
 * NewsController
 */
Route::group([
    'prefix' => 'news',
    'middleware' => 'cors',
], function(){
    // Public Routes
    Route::get('/', [NewsController::class, 'read']);
    Route::get('/all', [NewsController::class, 'all']);
    Route::get('/{id}', [NewsController::class, 'detail']);
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [NewsController::class, 'create']);
        Route::post('/update/{id}', [NewsController::class, 'update']);
        Route::put('/top/{id}', [NewsController::class, 'top']);
        Route::delete('/softdelete/{id}', [NewsController::class, 'softDelete']);
        Route::delete('/delete/{id}', [NewsController::class, 'delete']);
    });
});

// Albums
Route::group([
    'prefix' => 'album',
    'middleware' => 'cors',
], function(){
    // Public Routes
    Route::get('/', [AlbumController::class, 'read']);
    Route::get('/{id}', [AlbumController::class, 'detail']);
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [AlbumController::class, 'create']);
        Route::post('/update/{id}', [AlbumController::class, 'update']);
        Route::delete('/delete/{id}', [AlbumController::class, 'delete']);
    });
});

// Banners
Route::group([
    'prefix' => 'banner',
    'middleware' => 'cors'
], function(){
    // Public Routes
    Route::get('/', [BannerController::class, 'read']);
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [BannerController::class, 'create']);
        Route::post('/update/{id}', [BannerController::class, 'update']);
        Route::delete('/delete/{id}', [BannerController::class, 'delete']);
    });
});

// Files
Route::group([
    'prefix' => 'file',
    // 'middleware' => 'cors'
], function(){
    // Public Routes
    Route::get('/{id}', [FileUploadController::class, 'read']);
    Route::get('/path/{id}', [FileUploadController::class, 'readFile']);
    Route::get('/filter/{type_id}/{sheet_id}', [FileUploadController::class, 'filter']);
    /* 
     * type_id指的是資料表代號(詳見sheets資料表)
     * sheet_id指的是該資料表中的id欄位
     */

    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        // 資料正規化
        Route::group(['prefix' => 'link'], function(){
            Route::post('/create', [FileUploadController::class, 'createLink']);
            Route::post('/update/{id}', [FileUploadController::class, 'updateLink']);
            Route::delete('/delete/{id}', [FileUploadController::class, 'deleteLink']);
        });
        Route::post('/create', [FileUploadController::class, 'create']);
        Route::post('/update/{id}', [FileUploadController::class, 'update']);
        Route::delete('/delete/{id}', [FileUploadController::class, 'delete']);
    });
});

// Links
Route::group([
    'prefix' => 'link',
    // 'middleware' => 'cors'
],function(){
    // Public Routes
    Route::get('/', [LinkController::class, 'read']);
    Route::get('/{id}', [LinkController::class, 'detail']);
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [LinkController::class, 'create']);
        Route::post('/update/{id}', [LinkController::class, 'update']);
        Route::delete('/delete/{id}', [LinkController::class, 'delete']);
    });
});

// Honor
Route::group([
    'prefix' => 'honor',
    // 'middleware' => 'cors'
], function(){
    // Public Routes
    Route::get('/', [HonorController::class, 'read']);
    Route::get('/{id}', [HonorController::class, 'detail']);
    // Protectd Routes
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create', [HonorController::class, 'create']);
        Route::post('/update/{id}', [HonorController::class, 'update']);
        Route::delete('/delete/{id}', [HonorController::class, 'delete']);
    });
});

Route::post('search', [SearchController::class, 'index']);