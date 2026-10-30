<?php

use App\Http\Controllers\Admin\AdminAppearanceController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminListingController;
use App\Http\Controllers\Admin\AdminSecurityController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SellerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Locations AJAX
Route::get('/locations/regions/{governorateId}', [LocationController::class, 'getRegions'])->name('locations.regions');
Route::get('/locations/districts/{regionId}', [LocationController::class, 'getDistricts'])->name('locations.districts');

// Dedicated Quick Shortcut Routes (Direct execution without redirect)
Route::get('/jobs', function (Request $request, ListingController $controller) {
    $request->merge(['category' => 'jobs-employment']);
    return $controller->index($request);
})->name('jobs.index');

Route::get('/services', function (Request $request, ListingController $controller) {
    $request->merge(['category' => 'services-jobs']);
    return $controller->index($request);
})->name('services.index');

Route::get('/wanted', function (Request $request, ListingController $controller) {
    $request->merge(['listing_type' => 'wanted']);
    return $controller->index($request);
})->name('wanted.index');

// Public Listings & Direct Category Routes
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listings/create', [ListingController::class, 'create'])->name('listings.create');
Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
Route::get('/listings/{slug}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/listings/{id}/edit', [ListingController::class, 'edit'])->name('listings.edit');
Route::put('/listings/{id}', [ListingController::class, 'update'])->name('listings.update');
Route::delete('/listings/{id}', [ListingController::class, 'destroy'])->name('listings.destroy');
Route::post('/listings/{id}/favorite', [ListingController::class, 'toggleFavorite'])->name('listings.favorite');
Route::post('/listings/{id}/sold', [ListingController::class, 'toggleSold'])->name('listings.sold');

// Explicit Category Routes
Route::get('/category/{slug}', [ListingController::class, 'category'])->name('categories.show');
Route::get('/categories/{slug}', [ListingController::class, 'category']);
Route::get('/categories/{category}/{subcategory}', [ListingController::class, 'subcategory'])->name('categories.subcategory');

// Comments on listings
Route::post('/listings/{id}/comments', [CommentController::class, 'store'])->name('comments.store');
Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');

// Sellers & Follows
Route::get('/sellers/{id}', [SellerController::class, 'show'])->name('sellers.show');
Route::post('/sellers/{id}/follow', [SellerController::class, 'toggleFollow'])->name('sellers.follow');

// Authentication & Google Sign-In
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::match(['get', 'post'], '/auth/google', [AuthController::class, 'googleLogin'])->name('auth.google');

// User Dashboard & Profile
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/my-listings', [AuthController::class, 'dashboard'])->name('my-listings');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/notifications', [AuthController::class, 'notifications'])->name('notifications');
});

// Admin Panel Routes
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Appearance & Theme Management (إدارة الواجهة والمظهر للموقع والتطبيق)
    Route::get('/appearance', [AdminAppearanceController::class, 'index'])->name('appearance.index');
    Route::post('/appearance', [AdminAppearanceController::class, 'update'])->name('appearance.update');

    // Security & WAF Defense (لوحة مراقبة النظام من الهجمات وصد هجمات تلقائي وحظر IP)
    Route::get('/security', [AdminSecurityController::class, 'index'])->name('security.index');
    Route::post('/security/block', [AdminSecurityController::class, 'blockIp'])->name('security.block');
    Route::post('/security/unblock/{ip}', [AdminSecurityController::class, 'unblockIp'])->name('security.unblock');
    Route::post('/security/clear-logs', [AdminSecurityController::class, 'clearOldLogs'])->name('security.clear-logs');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::post('/subcategories', [AdminCategoryController::class, 'storeSubcategory'])->name('subcategories.store');
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    Route::delete('/subcategories/{id}', [AdminCategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');

    // Listings
    Route::get('/listings', [AdminListingController::class, 'index'])->name('listings.index');
    Route::post('/listings/{id}/featured', [AdminListingController::class, 'toggleFeatured'])->name('listings.featured');
    Route::post('/listings/{id}/status', [AdminListingController::class, 'updateStatus'])->name('listings.status');
    Route::delete('/listings/{id}', [AdminListingController::class, 'destroy'])->name('listings.destroy');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{id}/verify', [AdminUserController::class, 'toggleVerification'])->name('users.verify');
    Route::post('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Comments
    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/{id}/approve', [AdminCommentController::class, 'toggleApproval'])->name('comments.approve');
    Route::delete('/comments/{id}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
});

// Hostinger Storage Symlink Fallback: Safely serves images if symlink() is disabled on shared hosting
Route::get('/storage/{path}', [MediaController::class, 'servePublicStorage'])->where('path', '.*')->name('media.fallback');
