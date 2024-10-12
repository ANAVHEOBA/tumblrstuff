<?php

use App\Http\Controllers\TumblrAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {

    return view('welcome');
});


Route::get('/auth/tumblr', [TumblrAuthController::class, 'redirect']);
Route::get('/auth/tumblr/callback', [TumblrAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/tumblr-info', [TumblrAuthController::class, 'getUserInfo']);
    Route::get('/auth/tumblr/refresh', [TumblrAuthController::class, 'refreshToken']);
});