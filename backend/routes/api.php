<?php

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

// Homepage: the layout, then each block's data in its own request
Route::get('/homepage', [HomepageController::class, 'layout']);
Route::get('/homepage/blocks/{block}', [HomepageController::class, 'block']);
