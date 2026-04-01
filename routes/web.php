<?php

use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Socialite\ProviderRedirectController;
use App\Http\Controllers\Socialite\ProviderCallbackController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/about', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/blog', 'blog')->name('blog');
    Route::get('/', 'index')->name('home');
    Route::get('/movie/{id}', 'detail')->name('movie.detail');
    Route::get('/bookings/{id}', 'bookings')->name('bookings');
});

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/{provider}/redirect', ProviderRedirectController::class)->name('redirect');
    Route::get('/{provider}/callback', ProviderCallbackController::class)->name('callback');
});
Route::get('/payment/success', function() {
    return view('payment.success'); // Trang báo cảm ơn/thành công
})->name('payment.success');

Route::get('/payment/cancel', function() {
    return view('payment.cancel'); // Trang báo hủy/lỗi
})->name('payment.cancel');
