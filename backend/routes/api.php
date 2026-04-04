<?php

use App\Http\Controllers\Admin\CategoryManageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaManageController;
use App\Http\Controllers\Admin\OrderManageController;
use App\Http\Controllers\Admin\UserManageController;
use App\Http\Controllers\Admin\VideoManageController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
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
Route::post('/quick-register', [AuthController::class, 'quickRegister']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/latest', [VideoController::class, 'latest']);
Route::get('/videos/recommended', [VideoController::class, 'recommended']);
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

    // 收藏
    Route::post('/favorites/{videoId}', [FavoriteController::class, 'toggle']);
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::get('/favorites/check/{videoId}', [FavoriteController::class, 'check']);
    Route::post('/favorites/batch-check', [FavoriteController::class, 'batchCheck']);

    // 媒体资源管理
    Route::get('/media', [MediaResourceController::class, 'index']);
    Route::get('/media/{id}', [MediaResourceController::class, 'show']);
    Route::post('/media/{id}/sync', [MediaResourceController::class, 'syncToVideo']);
    Route::delete('/media/{id}', [MediaResourceController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| 后台管理接口（需登录 + 管理员权限）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/videos', [VideoManageController::class, 'index']);
    Route::post('/videos', [VideoManageController::class, 'store']);
    Route::put('/videos/{id}', [VideoManageController::class, 'update']);
    Route::delete('/videos/{id}', [VideoManageController::class, 'destroy']);

    Route::get('/categories', [CategoryManageController::class, 'index']);
    Route::post('/categories', [CategoryManageController::class, 'store']);
    Route::put('/categories/{id}', [CategoryManageController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryManageController::class, 'destroy']);

    Route::get('/users', [UserManageController::class, 'index']);
    Route::put('/users/{id}', [UserManageController::class, 'update']);
    Route::delete('/users/{id}', [UserManageController::class, 'destroy']);

    Route::get('/orders', [OrderManageController::class, 'index']);

    Route::get('/media', [MediaManageController::class, 'index']);
    Route::post('/media/{id}/sync', [MediaManageController::class, 'syncToVideo']);
    Route::delete('/media/{id}', [MediaManageController::class, 'destroy']);
});
