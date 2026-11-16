<?php

use App\Http\Controllers\FilterOptionsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchSuggestionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

// Store and search
Route::get('/products', [ProductController::class, 'index']);
Route::get('/filters', FilterOptionsController::class);
Route::get('/search/suggest', SearchSuggestionController::class);
