<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\MoviesController;
use App\Http\Controllers\Auth\AuthController;
// Route::apiResource('customers', CustomersController::class);
// Route::apiResource('invoicedetails', InvoiceDetailsController::class);
use App\Http\Controllers\User\BookingApiController;



Route::get('/movie/all', [MoviesController::class , 'allMovies']);
Route::get('/movie/dang-chieu', [MoviesController::class , 'dangChieu']);
Route::get('/movie/sap-chieu', [MoviesController::class , 'sapChieu']);
Route::get('/movie/{slug}', [MoviesController::class , 'show']);




Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class , 'login']);
    Route::post('/register', [AuthController::class , 'register']);
    Route::post('/refresh-token', [AuthController::class , 'refreshToken']);
    Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class , 'me']);
            Route::post('/logout', [AuthController::class , 'logout']);
        }
        );    });



Route::get('/showtime-info/{showtimeID}', [BookingApiController::class , 'getShowtimeInfo']);
Route::post('/hold-seat', [BookingApiController::class , 'holdSeat']);
Route::get('/products',[BookingApiController::class,'getCombos']);