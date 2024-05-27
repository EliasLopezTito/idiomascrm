<?php

use Illuminate\Http\Request;
use easyCRM\Cliente;

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

Route::middleware('auth:api')->group(function () {
    Route::prefix('cliente')->group(function () {
        Route::post('/create', 'Auth\Api\ClienteController@create');
        Route::post('/reingreso/create', 'Auth\Api\ClienteController@create_reingreso');
    });
});
