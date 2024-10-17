<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountDepositController;
use App\Http\Controllers\AccountWithdrawalController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TumblrAuthController;
use App\Http\Controllers\TumblrPostController;
use App\Http\Controllers\WeiboController;
use App\Http\Controllers\WeChatController;
use App\Http\Controllers\DouyinController;
use App\Http\Controllers\MeetupAuthController;
use App\Http\Controllers\MeetupDashboardController;
use App\Http\Controllers\DailyPostController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\RumbleController;
use App\Http\Controllers\RumblePostController;
use App\Http\Controllers\SnapchatController;
use App\Http\Controllers\QPostController;
use App\Http\Controllers\TTSController;
use App\Http\Controllers\VideoController;
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
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::prefix('auth')->group(function () {
//    dd(\request()->isProduction());
    Route::post('register', [AuthenticationController::class, 'register']);
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::middleware("auth:sanctum")->group(function () {
        Route::get("user", [AuthenticationController::class, 'user']);
        Route::get('logout', [AuthenticationController::class, 'logout']);
    });
});

Route::middleware("auth:sanctum")->group(function () {
    Route::prefix('onboarding')->group(function () {
        Route::post('setup/pin', [PinController::class, 'setupPin']);
        Route::middleware('has.set.pin')->group(function () {
            Route::post('validate/pin', [PinController::class, 'validatePin']);
            Route::post('generate/account-number', [AccountController::class, 'store']);
        });
    });

    Route::middleware('has.set.pin')->group(function () {
        Route::prefix('account')->group(function () {
            Route::post('deposit', [AccountDepositController::class, 'store']);
            Route::post('withdraw', [AccountWithdrawalController::class, 'store']);
            Route::post('transfer', [TransferController::class, 'store']);
        });
        Route::prefix('transactions')->group(function () {
            Route::get('history', [TransactionController::class, 'index']);
        });
    });


});

Route::get('/auth/tumblr', [TumblrAuthController::class, 'redirect']);
Route::get('/auth/tumblr/callback', [TumblrAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/tumblr-info', [TumblrAuthController::class, 'getUserInfo']);
    Route::get('/auth/tumblr/refresh', [TumblrAuthController::class, 'refreshToken']);
    
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tumblr/post', [TumblrPostController::class, 'create']);
    Route::get('/tumblr/connection', [TumblrAuthController::class, 'checkTumblrConnection']);
});

Route::get('/connect-weibo', [WeiboController::class, 'connect'])->name('weibo.connect');
Route::get('/oauth-callback', [WeiboController::class, 'callback'])->name('weibo.callback');
Route::get('/compose', [WeiboController::class, 'compose'])->name('compose');
Route::post('/publish', [WeiboController::class, 'publish'])->name('publish');



Route::get('/connect/wechat', [WeChatController::class, 'connect'])->name('wechat.connect');
Route::get('/connect/wechat/callback', [WeChatController::class, 'callback'])->name('wechat.callback');



Route::get('/douyin/connect', [DouyinController::class, 'connect'])->name('douyin.connect');
Route::get('/douyin/callback', [DouyinController::class, 'callback'])->name('douyin.callback');
Route::get('/compose', [DouyinController::class, 'compose'])->name('douyin.compose');
Route::post('/publish', [DouyinController::class, 'publish'])->name('douyin.publish');







Route::get('/auth/meetup', [MeetupAuthController::class, 'redirectToMeetup'])->name('auth.meetup');
Route::get('/auth/meetup/callback', [MeetupAuthController::class, 'handleMeetupCallback']);
Route::get('/meetup/dashboard', [MeetupDashboardController::class, 'index'])->middleware('auth')->name('meetup.dashboard');
Route::post('/meetup/publish', [MeetupDashboardController::class, 'publish'])->middleware('auth')->name('meetup.publish');




Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/daily-post/create', [DailyPostController::class, 'create'])->name('daily-post.create');
    Route::post('/daily-post', [DailyPostController::class, 'store'])->name('daily-post.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/rumble/connect', [RumbleController::class, 'connect'])->name('rumble.connect');
    Route::get('/rumble/callback', [RumbleController::class, 'callback'])->name('rumble.callback');
    Route::post('/rumble/disconnect', [RumbleController::class, 'disconnect'])->name('rumble.disconnect');
    Route::get('/rumble/post/create', [RumblePostController::class, 'create'])->name('rumble.post.create');
    Route::post('/rumble/post', [RumblePostController::class, 'store'])->name('rumble.post.store');
});

Route::get('/connect/snapchat', [SnapchatController::class, 'connect'])->name('snapchat.connect');
Route::get('/connect/snapchat/callback', [SnapchatController::class, 'callback'])->name('snapchat.callback');
Route::post('/disconnect/snapchat', [SnapchatController::class, 'disconnect'])->name('snapchat.disconnect');

Route::get('/qcompose', [QPostController::class, 'create'])->name('post.create');
Route::post('/post', [QPostController::class, 'store'])->name('post.store');


Route::post('/text-to-speech', [TTSController::class, 'convertTextToSpeech']);

Route::post('/speech-to-video', [VideoController::class, 'convertSpeechToVideo']);

Route::get('/languages', [TTSController::class, 'getSupportedLanguages']);

