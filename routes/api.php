<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

// Public Endpoints
Route::get('/settings', [ApiController::class, 'settings']);
Route::get('/banners', [ApiController::class, 'banners']);
Route::post('/register', [ApiController::class, 'register']);
Route::post('/login', [ApiController::class, 'login']);
Route::post('/auth/google', [ApiController::class, 'googleLogin']);

Route::get('/categories', [ApiController::class, 'categories']);
Route::get('/locations', [ApiController::class, 'locations']);
Route::get('/listings', [ApiController::class, 'listings']);
Route::get('/listings/{idOrSlug}', [ApiController::class, 'showListing']);

// Authenticated Endpoints (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiController::class, 'logout']);
    Route::get('/user', [ApiController::class, 'profile']);
    Route::put('/user/profile', [ApiController::class, 'updateProfile']);
    Route::get('/user/dashboard', [ApiController::class, 'dashboard']);

    // Post, Edit, and Manage Listings
    Route::post('/listings', [ApiController::class, 'storeListing']);
    Route::put('/listings/{id}', [ApiController::class, 'updateListing']);
    Route::delete('/listings/{id}', [ApiController::class, 'destroyListing']);
    Route::get('/my-listings', [ApiController::class, 'myListings']);
    Route::post('/listings/{id}/sold', [ApiController::class, 'toggleSold']);

    // Favorites
    Route::get('/favorites', [ApiController::class, 'favorites']);
    Route::post('/listings/{id}/favorite', [ApiController::class, 'toggleFavorite']);

    // Comments & Follows
    Route::post('/listings/{id}/comments', [ApiController::class, 'addComment']);
    Route::post('/sellers/{id}/toggle-follow', [ApiController::class, 'toggleFollow']);

    // Notifications
    Route::get('/notifications', [ApiController::class, 'notifications']);
});
