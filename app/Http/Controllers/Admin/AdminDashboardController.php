<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Listing;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_listings' => Listing::count(),
            'active_listings' => Listing::where('status', 'active')->count(),
            'featured_listings' => Listing::where('is_featured', true)->count(),
            'new_condition' => Listing::where('condition', 'new')->count(),
            'used_condition' => Listing::where('condition', 'used')->count(),
            'total_users' => User::count(),
            'sellers_count' => User::where('role', 'seller')->count(),
            'comments_count' => Comment::count(),
            'categories_count' => Category::count(),
        ];

        $recentListings = Listing::with(['user', 'category', 'governorate', 'images'])
            ->latest()
            ->take(8)
            ->get();

        $recentUsers = User::latest()->take(6)->get();

        $recentComments = Comment::with(['user', 'listing'])->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recentListings', 'recentUsers', 'recentComments'));
    }
}

