<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Governorate;
use App\Models\InAppNotification;
use App\Models\Listing;
use App\Models\SiteSetting;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminListingController extends Controller
{
    protected ListingService $listingService;

    public function __construct(ListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    public function index(Request $request): View
    {
        $query = Listing::with(['user', 'category', 'governorate', 'images']);

        if ($request->filled('status')) {
            if ($request->status === 'rejected') {
                $query->whereIn('status', ['rejected', 'hidden']);
            } else {
                $query->where('status', $request->status);
            }
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

        $counts = [
            'total' => Listing::count(),
            'pending' => Listing::where('status', 'pending')->count(),
            'active' => Listing::where('status', 'active')->count(),
            'rejected' => Listing::whereIn('status', ['rejected', 'hidden'])->count(),
            'sold' => Listing::where('status', 'sold')->count(),
        ];

        $requireApproval = (bool)SiteSetting::get('listings_require_approval', false);

        return view('admin.listings.index', compact('listings', 'categories', 'counts', 'requireApproval'));
    }

    public function toggleModeration(Request $request): RedirectResponse
    {
        $current = (bool)SiteSetting::get('listings_require_approval', false);
        SiteSetting::set('listings_require_approval', $current ? '0' : '1', 'moderation', 'boolean', 'مراجعة الإعلانات قبل النشر');

        $statusMsg = !$current 
            ? 'تم تفعيل نظام المراجعة الإلزامية: أي إعلان جديد سيدخل أولاً في حالة "قيد المراجعة"!'
            : 'تم تعطيل نظام المراجعة: الآن أي إعلان جديد سيتم نشره وقبوله مباشرة وتلقائياً!';

        return back()->with('success', $statusMsg);
    }

    public function toggleFeatured(int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);
        $listing->is_featured = !$listing->is_featured;
        $listing->save();

        $status = $listing->is_featured ? 'تم تمييز الإعلان بنجاح (VIP)' : 'تمت إزالة التمييز عن الإعلان';
        return back()->with('success', $status);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $request->validate(['status' => 'required|in:active,pending,sold,rejected,hidden']);
        $listing = Listing::with('user')->findOrFail($id);
        $oldStatus = $listing->status;
        $newStatus = $request->status;

        $listing->status = $newStatus;
        if ($newStatus === 'sold') {
            $listing->is_sold = true;
        } elseif ($newStatus === 'active') {
            $listing->is_sold = false;
        }
        $listing->save();

        // 1. If approved and published
        if ($newStatus === 'active') {
            // Notify seller
            InAppNotification::create([
                'user_id' => $listing->user_id,
                'title' => 'تمت الموافقة على إعلانك ونشره بنجاح! 🎉',
                'message' => 'وافقت إدارة الموقع على إعلانك "' . $listing->title . '" وهو الآن معروض ونشط لجميع الزوار والمشترين.',
                'type' => 'listing_approved',
                'action_url' => '/listings/' . $listing->slug,
                'is_read' => false,
            ]);

            // If it was previously pending, notify followers now
            if ($oldStatus === 'pending' && $listing->user) {
                $followers = $listing->user->followers()->get();
                foreach ($followers as $follower) {
                    InAppNotification::create([
                        'user_id' => $follower->id,
                        'title' => 'سلعة جديدة من بائع تتابعه',
                        'message' => 'قام "' . ($listing->user->name) . '" بنشر سلعة جديدة: ' . $listing->title,
                        'type' => 'new_listing',
                        'action_url' => '/listings/' . $listing->slug,
                        'data' => [
                            'listing_id' => $listing->id,
                            'seller_id' => $listing->user_id,
                            'slug' => $listing->slug,
                        ],
                        'is_read' => false,
                    ]);
                }
            }

            return back()->with('success', 'تمت الموافقة على الإعلان بنجاح وهو الآن منشور للجميع، وتم إرسال إشعار للبائع!');
        }

        // 2. If rejected or hidden
        if (in_array($newStatus, ['rejected', 'hidden'])) {
            InAppNotification::create([
                'user_id' => $listing->user_id,
                'title' => 'تنبيه: تم إخفاء/رفض إعلانك من قبل الإدارة',
                'message' => 'تم إخفاء إعلانك "' . $listing->title . '" من قبل الإدارة. يرجى مراجعة تفاصيل الإعلان وشروط النشر.',
                'type' => 'listing_rejected',
                'action_url' => '/listings/' . $listing->id . '/edit',
                'is_read' => false,
            ]);

            return back()->with('info', 'تم إخفاء/رفض الإعلان وإرسال إشعار فوري للبائع.');
        }

        return back()->with('success', 'تم تعديل حالة الإعلان إلى ' . $newStatus);
    }

    public function destroy(int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);

        // Notify seller before deleting
        InAppNotification::create([
            'user_id' => $listing->user_id,
            'title' => 'تم حذف إعلانك من قبل إدارة الموقع',
            'message' => 'تم حذف إعلانك "' . $listing->title . '" من قبل الإدارة لمخالفته شروط الاستخدام والنشر.',
            'type' => 'listing_deleted',
            'action_url' => '/dashboard',
            'is_read' => false,
        ]);

        $listing->delete();

        return back()->with('success', 'تم حذف الإعلان بنجاح وإرسال إشعار للبائع.');
    }
}

