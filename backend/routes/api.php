<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilterOptionsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchSuggestionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

// Store and search
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product:slug}', [ProductController::class, 'show']);
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/filters', FilterOptionsController::class);
Route::get('/search/suggest', SearchSuggestionController::class);

// Auth. Login and registration are rate-limited per IP address, against password guessing.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

// Homepage: the layout, then each block's data in its own request
Route::get('/homepage', [HomepageController::class, 'layout']);
Route::get('/homepage/blocks/{block}', [HomepageController::class, 'block']);
