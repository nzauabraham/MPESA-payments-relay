<?php

use App\Http\Controllers\API\V1\Controllers\AuthController;
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

Route::get('/', function () {
    //return view('welcome');
    return redirect('https://www.google.com/');
});

Route::match(array('GET','POST'),'auth_check', [AuthController::class, 'auth_failed'])->name('auth.failed');


Route::group(['prefix' => 'admin'], function () {
});
Voyager::routes();Voyager::routes();
