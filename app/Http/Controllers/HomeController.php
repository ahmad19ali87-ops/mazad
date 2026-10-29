<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Governorate;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::withCount(['listings' => function ($q) {
            $q->where('status', 'active');
        }])
            ->with(['subcategories' => function ($q) {
                $q->where('is_active', true);
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $featuredListings = Listing::with(['category', 'governorate', 'region', 'images', 'user'])
            ->where('status', 'active')
            ->where('is_featured', true)
            ->latest()
            ->take(8)
            ->get();

        $latestListings = Listing::with(['category', 'governorate', 'region', 'images', 'user'])
            ->where('status', 'active')
            ->latest()
            ->take(12)
            ->get();

        $governorates = Governorate::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Stats
        $counts = [
            'total' => Listing::where('status', 'active')->count(),
            'new' => Listing::where('status', 'active')->where('condition', 'new')->count(),
            'used' => Listing::where('status', 'active')->where('condition', 'used')->count(),
            'sellers' => User::where('role', 'seller')->count(),
        ];

        return view('home', compact('categories', 'featuredListings', 'latestListings', 'governorates', 'counts'));
    }
}

