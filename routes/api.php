<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register',                                    [AuthController::class, 'register']);
Route::post('/login',                                       [AuthController::class, 'login']);
Route::post('/reset-password',                              [AuthController::class, 'resetPassword']);
Route::post('/choose-new-password/{token}',                 [AuthController::class, 'chooseNewPassword']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);


});
