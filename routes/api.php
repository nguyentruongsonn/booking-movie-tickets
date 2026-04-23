<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\BookingApiController;
use App\Http\Controllers\User\MoviesController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Middleware\VerifyPayOSWebhookSource;
use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
 
    // Public routes (Movies)
    Route::prefix('movies')->middleware('throttle:60,1')->group(function () {
        Route::get('/now-showing',  [MoviesController::class, 'nowShowing']);
        Route::get('/coming-soon',  [MoviesController::class, 'comingSoon']);
        Route::get('/{slug}',       [MoviesController::class, 'show']);
    });
 
    // Public routes (Showtimes, Products)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/showtimes/{showtime}', [BookingApiController::class, 'showShowtime']);
        Route::get('/products',             [BookingApiController::class, 'indexProducts']);
    });
 
    // Authentication routes
    Route::prefix('auth')->middleware('throttle:60,1')->group(function () {
        Route::post('/login',         [AuthController::class, 'login']);
        Route::post('/register',      [AuthController::class, 'register']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
 
        Route::middleware('auth:api')->group(function () {
            Route::get('/me',       [AuthController::class, 'me']);
            Route::post('/logout',  [AuthController::class, 'logout']);
        });
    });
 
    // Payment Webhook
    Route::post('/payos/webhook', [PaymentController::class, 'handleWebhook'])
        ->middleware(VerifyPayOSWebhookSource::class);
 
    // Customer protected routes
    Route::middleware(['auth:api', 'role:customer'])->group(function () {
        Route::post('/showtimes/{showtime}/seat-holds', [BookingApiController::class, 'storeSeatHold']);
        Route::get('/customers/me/loyalty-points', [BookingApiController::class, 'showMyLoyaltyPoints']);
 
        Route::post('/promotions/validate',           [BookingApiController::class, 'validatePromotion']);
        Route::post('/customer/register-promotion',   [BookingApiController::class, 'registerPromotion']);
        Route::get('/customer/registered-promotions', [BookingApiController::class, 'registeredPromotions']);
 
        Route::post('/payments',                          [PaymentController::class, 'createPayment']);
        Route::get('/payments/orders/{orderCode}',        [PaymentController::class, 'showOrderSummary']);
    });
});

