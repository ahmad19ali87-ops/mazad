<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Governorate;
use App\Models\InAppNotification;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    protected ListingService $listingService;

    public function __construct(ListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    // Auth: Register
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:9|max:25|unique:users,phone',
            'password' => 'required|string|min:6',
            'governorate' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'role' => 'nullable|in:user,seller',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'governorate' => $validated['governorate'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
            'role' => $validated['role'] ?? 'user',
            'is_verified' => false,
        ]);

        $token = $user->createToken('mazad_mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // Auth: Login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        $token = $user->createToken('mazad_mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // Auth: Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }

    // Current User Profile
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['listings', 'followers', 'following']);
        return response()->json(['success' => true, 'user' => $user]);
    }

    // Active Sponsored & Promotional Banners
    public function banners(): JsonResponse
    {
        $banners = Banner::active()
            ->with(['listing' => function ($q) {
                $q->select('id', 'title', 'slug', 'price', 'currency', 'status');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    // Categories and Subcategories
    public function categories(): JsonResponse
    {
        $categories = Category::with(['subcategories' => function ($q) {
            $q->where('is_active', true);
        }])
            ->withCount(['listings' => function ($q) {
                $q->where('status', 'active');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    // Locations (Governorates with Regions & Districts)
    public function locations(): JsonResponse
    {
        $governorates = Governorate::with(['regions.districts'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $governorates]);
    }

    // Listings index with filtering (Category, Subcategory, Condition, Governorate, Price, Search)
    public function listings(Request $request): JsonResponse
    {
        $query = Listing::with(['category', 'subcategory', 'governorate', 'region', 'district', 'images', 'user'])
            ->where('status', 'active');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('category')) {
            $catVal = $request->category;
            $query->whereHas('category', function ($q) use ($catVal) {
                if (is_numeric($catVal)) {
                    $q->where('id', (int)$catVal);
                } else {
                    $q->where('slug', $catVal);
                }
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        // Condition: 'new' vs 'used' (الجديد من القديم)
        if ($request->filled('condition') && in_array($request->condition, ['new', 'used'])) {
            $query->where('condition', $request->condition);
        }

        // Listing Type: 'sale' vs 'wanted' vs 'rent' vs 'rent_wanted'
        if ($request->filled('listing_type') && in_array($request->listing_type, ['sale', 'wanted', 'rent', 'rent_wanted'])) {
            $query->where('listing_type', $request->listing_type);
        }

        // Featured VIP Listings
        if ($request->boolean('featured') || $request->get('is_featured') == '1') {
            $query->where('is_featured', true);
        }

        // Job Type: 'vacancy' (مطلوب موظف) vs 'seeker' (باحث عن عمل)
        if ($request->filled('job_type') && in_array($request->job_type, ['vacancy', 'seeker'])) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float)$request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float)$request->max_price);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('street_address', 'like', $search);
            });
        }

        $sort = $request->get('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'popular') {
            $query->orderBy('views_count', 'desc');
        } else {
            $query->orderByDesc('is_featured')->latest();
        }

        $perPage = min((int)$request->get('per_page', 15), 50);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
            ],
        ]);
    }

    // Single Listing Details
    public function showListing(Request $request, $idOrSlug): JsonResponse
    {
        $listing = Listing::with([
            'category',
            'subcategory',
            'governorate',
            'region',
            'district',
            'images',
            'user' => function ($q) {
                $q->select('id', 'name', 'phone', 'whatsapp', 'avatar', 'role', 'is_verified', 'created_at')
                  ->withCount('followers');
            },
            'comments.user:id,name,avatar',
            'comments.replies.user:id,name,avatar',
        ])
            ->where(is_numeric($idOrSlug) ? 'id' : 'slug', $idOrSlug)
            ->firstOrFail();

        $listing->timestamps = false;
        $listing->increment('views_count');
        $listing->timestamps = true;

        $isFollowing = false;
        $isFavorited = false;
        if ($request->user('sanctum')) {
            $isFollowing = $request->user('sanctum')->isFollowing($listing->user_id);
            $isFavorited = $request->user('sanctum')->savedListings()->where('listing_id', $listing->id)->exists();
        }

        return response()->json([
            'success' => true,
            'data' => $listing,
            'is_following_seller' => $isFollowing,
            'is_favorited' => $isFavorited,
        ]);
    }

    // Store listing via API
    public function storeListing(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:190',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'condition' => 'required|in:new,used',
            'listing_type' => 'nullable|in:sale,wanted,rent,rent_wanted',
            'job_type' => 'nullable|in:vacancy,seeker',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|in:SYP,USD',
            'is_negotiable' => 'nullable|boolean',
            'description' => 'required|string|min:15',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'district_name' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'allow_comments' => 'nullable|boolean',
            'specs' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable',
        ]);

        $images = $request->file('images', []);
        $listing = $this->listingService->createListing($user, $validated, $images);

        return response()->json([
            'success' => true,
            'message' => 'تم نشر الإعلان بنجاح وتم إشعار المتابعين',
            'data' => $listing->load(['images', 'category', 'governorate']),
        ], 201);
    }

    // Update listing via API
    public function updateListing(Request $request, int $id): JsonResponse
    {
        $listing = Listing::findOrFail($id);
        $user = $request->user();

        if ($user->id !== $listing->user_id && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل هذا الإعلان'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:190',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'condition' => 'required|in:new,used',
            'listing_type' => 'nullable|in:sale,wanted,rent,rent_wanted',
            'job_type' => 'nullable|in:vacancy,seeker',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|in:SYP,USD',
            'is_negotiable' => 'nullable|boolean',
            'description' => 'required|string|min:15',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'district_name' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'allow_comments' => 'nullable|boolean',
            'status' => 'nullable|in:active,sold,pending',
            'specs' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer',
        ]);

        if (!empty($validated['delete_image_ids'])) {
            ListingImage::where('listing_id', $listing->id)
                ->whereIn('id', $validated['delete_image_ids'])
                ->delete();
        }

        $images = $request->file('images', []);
        $updatedListing = $this->listingService->updateListing($listing, $validated, $images);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تعديلات الإعلان بنجاح',
            'data' => $updatedListing->load(['images', 'category', 'governorate']),
        ]);
    }

    // Delete listing via API
    public function destroyListing(Request $request, int $id): JsonResponse
    {
        $listing = Listing::findOrFail($id);
        $user = $request->user();

        if ($user->id !== $listing->user_id && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح بحذف هذا الإعلان'], 403);
        }

        $listing->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإعلان بنجاح',
        ]);
    }

    // Add Comment on Listing
    public function addComment(Request $request, int $listingId): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'comment' => 'required|string|min:2|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $listing = Listing::findOrFail($listingId);

        if (!$listing->allow_comments) {
            return response()->json(['success' => false, 'message' => 'التعليقات مغلقة على هذه السلعة'], 403);
        }

        $comment = Comment::create([
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'comment' => strip_tags($validated['comment']),
            'is_approved' => true,
        ]);

        if ($listing->user_id !== $user->id) {
            InAppNotification::create([
                'user_id' => $listing->user_id,
                'title' => 'تعليق جديد على سلعتك',
                'message' => 'علق ' . $user->name . ' على إعلانك: ' . $listing->title,
                'type' => 'new_comment',
                'action_url' => '/listings/' . $listing->slug,
                'data' => ['listing_id' => $listing->id, 'comment_id' => $comment->id],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة التعليق بنجاح',
            'data' => $comment->load('user:id,name,avatar'),
        ], 201);
    }

    // Toggle Follow Seller
    public function toggleFollow(Request $request, int $sellerId): JsonResponse
    {
        $user = $request->user();

        if ($user->id === $sellerId) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك متابعة نفسك'], 422);
        }

        $seller = User::findOrFail($sellerId);

        $follow = Follow::where('follower_id', $user->id)->where('seller_id', $sellerId)->first();

        if ($follow) {
            $follow->delete();
            $isFollowing = false;
            $msg = 'تم إلغاء متابعة ' . $seller->name;
        } else {
            Follow::create([
                'follower_id' => $user->id,
                'seller_id' => $sellerId,
            ]);
            $isFollowing = true;
            $msg = 'تمت متابعة ' . $seller->name . ' بنجاح! ستصلك إشعارات عند إضافة سلع جديدة.';

            InAppNotification::create([
                'user_id' => $seller->id,
                'title' => 'متابع جديد!',
                'message' => 'بدأ ' . $user->name . ' بمتابعتك.',
                'type' => 'new_follower',
                'action_url' => '/sellers/' . $seller->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'is_following' => $isFollowing,
            'followers_count' => $seller->followers()->count(),
            'message' => $msg,
        ]);
    }

    // User's listings
    public function myListings(Request $request): JsonResponse
    {
        $listings = Listing::with(['category', 'governorate', 'images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $listings->items(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    // In-app Notifications
    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()->inAppNotifications()->paginate(20);
        $request->user()->inAppNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    // Public Site & App Appearance Settings
    public function settings(): JsonResponse
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    // Google Sign-In for Mobile & Web API
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        $googleEmail = $request->email;
        $googleName = $request->name;
        $googleId = $request->input('google_id', 'gid_' . Str::random(10));
        $avatarUrl = $request->input('avatar_url', 'https://lh3.googleusercontent.com/a/default-user');

        $user = User::where('email', $googleEmail)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $user->google_id ?: $googleId,
                'avatar_url' => $user->avatar_url ?: $avatarUrl,
            ]);
        } else {
            $user = User::create([
                'name' => $googleName,
                'email' => $googleEmail,
                'phone' => '09' . rand(11111111, 99999999),
                'password' => Hash::make(Str::random(16)),
                'google_id' => $googleId,
                'avatar_url' => $avatarUrl,
                'role' => 'user',
                'is_verified' => true,
                'remember_contact_info' => true,
            ]);
        }

        $token = $user->createToken('mazad-mobile-google')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بحساب Google بنجاح',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // Toggle Sold status
    public function toggleSold(Request $request, int $id): JsonResponse
    {
        $listing = Listing::findOrFail($id);

        if ($request->user()->id !== $listing->user_id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $listing->is_sold = !$listing->is_sold;
        $listing->status = $listing->is_sold ? 'sold' : 'active';
        $listing->save();

        return response()->json([
            'success' => true,
            'is_sold' => $listing->is_sold,
            'status' => $listing->status,
            'message' => $listing->is_sold ? 'تم تمييز السلعة كـ "تم البيع"' : 'تمت إعادة عرض السلعة',
        ]);
    }

    // Toggle Favorite status
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $listing = Listing::findOrFail($id);

        if ($user->savedListings()->where('listing_id', $listing->id)->exists()) {
            $user->savedListings()->detach($listing->id);
            $isFavorited = false;
            $msg = 'تمت إزالة السلعة من المفضلة';
        } else {
            $user->savedListings()->attach($listing->id);
            $isFavorited = true;
            $msg = 'تمت إضافة السلعة إلى المفضلة';
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $msg,
        ]);
    }

    // List User Favorites
    public function favorites(Request $request): JsonResponse
    {
        $user = $request->user();
        $favorites = $user->savedListings()
            ->with(['category', 'governorate', 'images', 'user'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $favorites->items(),
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    // User Mobile Dashboard (Stats, Purchases, Sales, Following)
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $counts = [
            'total_listings' => Listing::where('user_id', $user->id)->count(),
            'active_listings' => Listing::where('user_id', $user->id)->where('is_sold', false)->where('status', 'active')->count(),
            'sold_listings' => Listing::where('user_id', $user->id)->where('is_sold', true)->count(),
            'wanted_listings' => Listing::where('user_id', $user->id)->where('listing_type', 'wanted')->count(),
            'views_count' => (int) Listing::where('user_id', $user->id)->sum('views_count'),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ];

        $savedListings = $user->savedListings()->with(['category', 'images', 'governorate'])->take(10)->get();
        $followingSellers = $user->following()->withCount('listings')->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'counts' => $counts,
                'saved_listings' => $savedListings,
                'following_sellers' => $followingSellers,
            ],
        ]);
    }

    // Update Profile via API
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|min:9|max:25|unique:users,phone,' . $user->id,
            'whatsapp' => 'nullable|string|max:30',
            'governorate' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'remember_contact_info' => 'nullable|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'user' => $user,
        ]);
    }
}

