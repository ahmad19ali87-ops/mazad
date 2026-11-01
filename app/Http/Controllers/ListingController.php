<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Governorate;
use App\Models\Listing;
use App\Models\Region;
use App\Models\Subcategory;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ListingController extends Controller
{
    protected ListingService $listingService;

    public function __construct(ListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    public function index(Request $request): View
    {
        $query = Listing::with(['category', 'subcategory', 'governorate', 'region', 'images', 'user'])
            ->where('status', 'active');

        // Search text
        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('street_address', 'like', $search);
            });
        }

        // Category & Subcategory (Safe for SQL Server: numeric id vs string slug)
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

        if ($request->filled('subcategory')) {
            $subVal = $request->subcategory;
            $query->whereHas('subcategory', function ($q) use ($subVal) {
                if (is_numeric($subVal)) {
                    $q->where('id', (int)$subVal);
                } else {
                    $q->where('slug', $subVal);
                }
            });
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

        // Condition: 'new' or 'used' (الجديد من القديم)
        if ($request->filled('condition') && in_array($request->condition, ['new', 'used'])) {
            $query->where('condition', $request->condition);
        }

        // Governorate
        if ($request->filled('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }

        // Region
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float)$request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float)$request->max_price);
        }

        // Currency
        if ($request->filled('currency') && in_array($request->currency, ['SYP', 'USD'])) {
            $query->where('currency', $request->currency);
        }

        // Sorting (Featured VIP listings take precedence in default latest view)
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            default:
                $query->orderByDesc('is_featured')->latest();
                break;
        }

        $listings = $query->paginate(16)->withQueryString();

        $categories = Category::with('subcategories')->where('is_active', true)->orderBy('sort_order')->get();
        $governorates = Governorate::where('is_active', true)->orderBy('sort_order')->get();

        return view('listings.index', compact('listings', 'categories', 'governorates'));
    }

    public function category(string $slug, Request $request): View
    {
        $category = is_numeric($slug)
            ? Category::where('id', (int)$slug)->firstOrFail()
            : Category::where('slug', $slug)->firstOrFail();

        $request->merge(['category' => $category->slug]);
        return $this->index($request);
    }

    public function subcategory(string $categorySlug, string $subcategorySlug, Request $request): View
    {
        $category = is_numeric($categorySlug)
            ? Category::where('id', (int)$categorySlug)->firstOrFail()
            : Category::where('slug', $categorySlug)->firstOrFail();

        $subcategory = is_numeric($subcategorySlug)
            ? Subcategory::where('id', (int)$subcategorySlug)->where('category_id', $category->id)->firstOrFail()
            : Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->firstOrFail();

        $request->merge(['category' => $category->slug, 'subcategory' => $subcategory->slug]);
        return $this->index($request);
    }

    public function show(string $slug): View
    {
        $listing = Listing::with([
            'category',
            'subcategory',
            'governorate',
            'region',
            'district',
            'images',
            'user.followers',
            'comments.user',
            'comments.replies.user',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment views count safely without modifying updated_at
        $listing->timestamps = false;
        $listing->increment('views_count');
        $listing->timestamps = true;

        // Related listings in the same category
        $relatedListings = Listing::with(['category', 'governorate', 'region', 'images'])
            ->where('category_id', $listing->category_id)
            ->where('id', '!=', $listing->id)
            ->where('status', 'active')
            ->latest()
            ->take(4)
            ->get();

        $isFollowingSeller = Auth::check() && Auth::user()->isFollowing($listing->user_id);
        $isFavorited = Auth::check() && Auth::user()->savedListings()->where('listing_id', $listing->id)->exists();

        return view('listings.show', compact('listing', 'relatedListings', 'isFollowingSeller', 'isFavorited'));
    }

    public function create(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('info', 'يرجى تسجيل الدخول أو إنشاء حساب لعرض سلعتك للبيع.');
        }

        $categories = Category::with('subcategories')->where('is_active', true)->orderBy('sort_order')->get();
        $governorates = Governorate::with('regions')->where('is_active', true)->orderBy('sort_order')->get();

        return view('listings.create', compact('categories', 'governorates'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
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
            'description' => 'required|string|min:15|max:10000',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'district_name' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'allow_comments' => 'nullable|boolean',
            'specs_keys' => 'nullable|array',
            'specs_values' => 'nullable|array',
            'images' => ($request->input('listing_type') === 'wanted' || $request->filled('job_type')) ? 'nullable|array|max:10' : 'required|array|min:1|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB each
        ], [
            'title.required' => 'يرجى إدخال عنوان الإعلان أو السلعة',
            'category_id.required' => 'يرجى اختيار القسم الرئيسي',
            'condition.required' => 'يرجى تحديد حالة السلعة (جديد أو مستعمل)',
            'price.required' => 'يرجى تحديد السعر',
            'description.required' => 'يرجى كتابة وصف تفصيلي للسلعة ومواصفاتها',
            'governorate_id.required' => 'يرجى اختيار المحافظة',
            'images.required' => 'يرجى رفع صورة واحدة على الأقل للسلعة المعروضة للبيع',
            'images.*.image' => 'يجب أن تكون الملفات المرفوعة صوراً صالحة',
            'images.*.max' => 'حجم الصورة لا يجب أن يتجاوز 5 ميجابايت',
        ]);

        // Sanitize string inputs to eliminate XSS
        $validated['title'] = strip_tags($validated['title']);
        $validated['description'] = strip_tags($validated['description'], '<p><br><b><strong><ul><li><ol>');
        if (!empty($validated['district_name'])) {
            $validated['district_name'] = strip_tags($validated['district_name']);
        }
        if (!empty($validated['street_address'])) {
            $validated['street_address'] = strip_tags($validated['street_address']);
        }

        // Build specs dictionary from form keys/values
        $validated['title'] = strip_tags($validated['title']);
        $validated['description'] = strip_tags($validated['description'], '<p><br><b><strong><ul><li><ol>');
        if (!empty($validated['district_name'])) {
            $validated['district_name'] = strip_tags($validated['district_name']);
        }
        if (!empty($validated['street_address'])) {
            $validated['street_address'] = strip_tags($validated['street_address']);
        }

        $specs = [];
        if (!empty($request->specs_keys) && is_array($request->specs_keys)) {
            foreach ($request->specs_keys as $idx => $key) {
                $val = $request->specs_values[$idx] ?? '';
                if (!empty(trim($key)) && !empty(trim($val))) {
                    $specs[trim($key)] = trim($val);
                }
            }
        }
        $validated['specs'] = !empty($specs) ? $specs : null;

        $images = $request->file('images', []);
        $listing = $this->listingService->createListing(Auth::user(), $validated, $images);

        return redirect()->route('listings.show', $listing->slug)
            ->with('success', 'تم نشر إعلانك بنجاح! تم إشعار المتابعين أيضاً.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $listing = Listing::with(['images'])->findOrFail($id);

        if (!Auth::check() || (Auth::id() !== $listing->user_id && !Auth::user()->isAdmin())) {
            abort(403, 'غير مصرح لك بتعديل هذا الإعلان');
        }

        $categories = Category::with('subcategories')->where('is_active', true)->orderBy('sort_order')->get();
        $governorates = Governorate::with('regions')->where('is_active', true)->orderBy('sort_order')->get();

        return view('listings.edit', compact('listing', 'categories', 'governorates'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);

        if (!Auth::check() || (Auth::id() !== $listing->user_id && !Auth::user()->isAdmin())) {
            abort(403, 'غير مصرح لك بتعديل هذا الإعلان');
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
            'description' => 'required|string|min:15|max:10000',
            'governorate_id' => 'required|exists:governorates,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'district_name' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'allow_comments' => 'nullable|boolean',
            'status' => 'required|in:active,sold,pending',
            'specs_keys' => 'nullable|array',
            'specs_values' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $validated['title'] = strip_tags($validated['title']);
        $validated['description'] = strip_tags($validated['description'], '<p><br><b><strong><ul><li><ol>');
        if (!empty($validated['district_name'])) {
            $validated['district_name'] = strip_tags($validated['district_name']);
        }
        if (!empty($validated['street_address'])) {
            $validated['street_address'] = strip_tags($validated['street_address']);
        }

        $specs = [];
        if (!empty($request->specs_keys) && is_array($request->specs_keys)) {
            foreach ($request->specs_keys as $idx => $key) {
                $val = $request->specs_values[$idx] ?? '';
                if (!empty(trim($key)) && !empty(trim($val))) {
                    $specs[trim($key)] = trim($val);
                }
            }
        }
        $validated['specs'] = !empty($specs) ? $specs : null;

        $images = $request->file('images', []);
        $this->listingService->updateListing($listing, $validated, $images);

        return redirect()->route('listings.show', $listing->slug)->with('success', 'تم حفظ تعديلات الإعلان بنجاح.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);

        if (!Auth::check() || (Auth::id() !== $listing->user_id && !Auth::user()->isAdmin())) {
            abort(403);
        }

        $listing->delete();

        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/listings/' . $listing->slug) || str_contains($referer, '/listings/' . $listing->id)) {
            return redirect()->route('dashboard')->with('success', 'تم حذف الإعلان بنجاح.');
        }

        return redirect()->back()->with('success', 'تم حذف الإعلان بنجاح.');
    }

    public function toggleFavorite(int $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if ($user->savedListings()->where('listing_id', $id)->exists()) {
            $user->savedListings()->detach($id);
            $msg = 'تمت إزالة السلعة من المفضلة.';
        } else {
            $user->savedListings()->attach($id);
            $msg = 'تمت إضافة السلعة إلى قائمة المفضلة بنجاح.';
        }

        return back()->with('info', $msg);
    }

    public function toggleSold(int $id): RedirectResponse
    {
        $listing = Listing::findOrFail($id);

        if (!Auth::check() || (Auth::id() !== $listing->user_id && !Auth::user()->isAdmin())) {
            abort(403);
        }

        $listing->is_sold = !$listing->is_sold;
        $listing->status = $listing->is_sold ? 'sold' : 'active';
        $listing->save();

        $msg = $listing->is_sold ? 'تم تمييز السلعة كـ "تم البيع" بنجاح.' : 'تمت إعادة تنشيط السلعة في السوق.';
        return back()->with('success', $msg);
    }
}

