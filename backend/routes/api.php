<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FilterOptionsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
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

    // Checkout and order history
    Route::post('/checkout', CheckoutController::class);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
});

// Admin: only for the emails in ADMIN_EMAILS (Gate 'admin' in AppServiceProvider)
Route::middleware(['auth:sanctum', 'can:admin'])->prefix('admin')->group(function () {
    Route::get('/homepage-blocks', [Admin\HomepageBlockController::class, 'index']);
    Route::post('/homepage-blocks', [Admin\HomepageBlockController::class, 'store']);
    Route::post('/homepage-blocks/order', [Admin\HomepageBlockController::class, 'reorder']);
    Route::patch('/homepage-blocks/{block}', [Admin\HomepageBlockController::class, 'update']);
    Route::delete('/homepage-blocks/{block}', [Admin\HomepageBlockController::class, 'destroy']);
    Route::get('/banners', [Admin\BannerController::class, 'index']);
    Route::patch('/banners/{banner}', [Admin\BannerController::class, 'update']);
    Route::post('/offers/{offer}/keys', [Admin\OfferKeyController::class, 'store']);
});

// Homepage: the layout, then each block's data in its own request
Route::get('/homepage', [HomepageController::class, 'layout']);
Route::get('/homepage/blocks/{block}', [HomepageController::class, 'block']);
