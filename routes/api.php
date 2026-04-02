<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\BookingApiController;
use App\Http\Controllers\User\MoviesController;
use App\Http\Controllers\User\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('movies')->group(function () {
    Route::get('/', [MoviesController::class, 'allMovies']);
    Route::get('/now-showing', [MoviesController::class, 'dangChieu']);
    Route::get('/coming-soon', [MoviesController::class, 'sapChieu']);
    Route::get('/{slug}', [MoviesController::class, 'show']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/showtimes/{showtime}', [BookingApiController::class, 'showShowtime']);
Route::get('/products', [BookingApiController::class, 'indexProducts']);
Route::post('/payments', [PaymentController::class, 'createPayment']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/showtimes/{showtime}/seat-holds', [BookingApiController::class, 'storeSeatHold']);
    Route::get('/customers/me/vouchers', [BookingApiController::class, 'indexMyVouchers']);
    Route::get('/customers/me/loyalty-points', [BookingApiController::class, 'showMyLoyaltyPoints']);
    Route::post('/promotions/validate', [BookingApiController::class, 'validatePromotion']);
});
