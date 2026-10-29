<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminListingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Listing::with(['user', 'category', 'governorate', 'images']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where('title', 'like', $q);
        }

        $listings = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name_ar')->get();

        return view('admin.listings.index', compact('listings', 'categories'));
    }

    public function toggleFeatured(int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);
        $listing->is_featured = !$listing->is_featured;
        $listing->save();

        $status = $listing->is_featured ? 'تم تمييز الإعلان بنجاح' : 'تمت إزالة التمييز عن الإعلان';
        return back()->with('success', $status);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $request->validate(['status' => 'required|in:active,pending,sold,rejected']);
        $listing = Listing::findOrFail($id);
        $listing->status = $request->status;
        $listing->save();

        return back()->with('success', 'تم تعديل حالة الإعلان إلى ' . $request->status);
    }

    public function destroy(int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);
        $listing->delete();

        return back()->with('success', 'تم حذف الإعلان بنجاح.');
    }
}

