<?php

use App\Http\Controllers\API\V1\Controllers\DlrController;
use App\Http\Controllers\API\V1\Controllers\RequestOtpController;
use App\Http\Controllers\API\V1\Controllers\RequestTokenController;
use App\Http\Controllers\API\V1\Controllers\StkCallbackController;
use App\Http\Controllers\API\V1\Controllers\StkRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group(['prefix' => 'v1', 'namespace' => 'API\V1\Controllers'], function () {
    Route::post('stk_push',[StkRequestController::class, 'triggerStk'])->middleware('auth:sanctum');
    Route::post('send_otp',[RequestOtpController::class, 'sendOtp'])->middleware('throttle:2,5', 'is_exists'); //Limit request otp to 2 attempts per minute
    Route::post('token_generate',[RequestTokenController::class, 'fetchToken'])->middleware('throttle:2,5', 'is_exists'); //Limit verify otp to 2 attempts per minute
    Route::post('sms/dlr',[DlrController::class, 'update_dlr']);
    Route::post('stk_callback',[StkCallbackController::class, 'process_callback']);
});


//Route::controller(OrderController::class)->group(function () {
//    Route::get('/orders/{id}', 'show');
//    Route::post('/orders', 'store');
//});

