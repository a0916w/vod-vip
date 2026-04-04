<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MediaResourceController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\VipController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公开接口（无需登录）
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/{id}', [VideoController::class, 'show']);

Route::get('/vip/plans', [VipController::class, 'plans']);

// 支付回调（第三方调用，不需要用户 Token）
Route::post('/payment/callback', [VipController::class, 'paymentCallback']);

// Telegram Bot Webhook（TG 服务器回调，不需要用户 Token）
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| 需要登录的接口
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/vip/order', [VipController::class, 'createOrder']);
    Route::get('/vip/orders', [VipController::class, 'myOrders']);

    // 媒体资源管理
    Route::get('/media', [MediaResourceController::class, 'index']);
    Route::get('/media/{id}', [MediaResourceController::class, 'show']);
    Route::post('/media/{id}/sync', [MediaResourceController::class, 'syncToVideo']);
    Route::delete('/media/{id}', [MediaResourceController::class, 'destroy']);
});
