<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */


Route::group(['middleware' => 'web'], function () {
    Route::group(['middleware' => 'auth:sanctum','prefix' => 'admin'], function () {
        Route::get('/', 'App\Http\Controllers\IndexController@Index');
        Route::get('/dashboard', 'App\Http\Controllers\IndexController@Index')->name("dashboard");
        Route::get('/add-new-user', 'App\Http\Controllers\IndexController@AddNewUser')->name("add-new-user");
        Route::post('/store-new-user', 'App\Http\Controllers\IndexController@StoreNewUser')->name("store-new-user");
        Route::get('/delete-user/{id}', 'App\Http\Controllers\IndexController@DeleteUser')->name("delete-user");
    });
});

Route::prefix('/bot')->group(function() {
    Route::get('/updateResponsePercentAndComplete', 'App\Http\Controllers\CronController@updateResponsePercentAndComplete');
});


