<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

// Public Endpoints
Route::get('/settings', [ApiController::class, 'settings']);
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

    // Post ad with multi-images
    Route::post('/listings', [ApiController::class, 'storeListing']);
    Route::get('/my-listings', [ApiController::class, 'myListings']);
    Route::post('/listings/{id}/sold', [ApiController::class, 'toggleSold']);

    // Comments & Follows
    Route::post('/listings/{id}/comments', [ApiController::class, 'addComment']);
    Route::post('/sellers/{id}/toggle-follow', [ApiController::class, 'toggleFollow']);

    // Notifications
    Route::get('/notifications', [ApiController::class, 'notifications']);
});
