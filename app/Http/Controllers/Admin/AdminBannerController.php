<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Listing;
use App\Services\SecureFileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBannerController extends Controller
{
    protected SecureFileUploadService $fileUploadService;

    public function __construct(SecureFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(): View
    {
        $banners = Banner::with('listing')->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();
        $featuredListings = Listing::where('status', 'active')->latest()->take(50)->get();

        return view('admin.banners.index', compact('banners', 'featuredListings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:190',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'link_type' => 'required|in:listing,external,none',
            'listing_id' => 'nullable|exists:listings,id',
            'external_url' => 'nullable|url|max:500',
            'background_color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
        ]);

        $imagePath = $validated['image_url'] ?? '';

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $inspection = $this->fileUploadService->inspect($file);
            if ($inspection['is_safe']) {
                $imagePath = $this->fileUploadService->storeSafely(
                    file: $file,
                    destinationDir: 'banners',
                    safeExt: $inspection['safe_extension']
                );
            }
        }

        if (empty($imagePath)) {
            // Default placeholder if none provided
            $imagePath = 'https://images.unsplash.com/photo-1555529771-835f59fc5efe?auto=format&fit=crop&w=1200&q=80';
        }

        Banner::create([
            'title' => strip_tags($validated['title']),
            'subtitle' => !empty($validated['subtitle']) ? strip_tags($validated['subtitle']) : null,
            'badge_text' => $validated['badge_text'] ?? 'إعلان ممول ⭐',
            'image_path' => $imagePath,
            'link_type' => $validated['link_type'],
            'listing_id' => $validated['link_type'] === 'listing' ? $validated['listing_id'] : null,
            'external_url' => $validated['link_type'] === 'external' ? $validated['external_url'] : null,
            'background_color' => $validated['background_color'] ?? '#8b1d30',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'تمت إضافة البنر الإعلاني بنجاح.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:190',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'link_type' => 'required|in:listing,external,none',
            'listing_id' => 'nullable|exists:listings,id',
            'external_url' => 'nullable|url|max:500',
            'background_color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'title' => strip_tags($validated['title']),
            'subtitle' => !empty($validated['subtitle']) ? strip_tags($validated['subtitle']) : null,
            'badge_text' => $validated['badge_text'] ?? 'إعلان ممول ⭐',
            'link_type' => $validated['link_type'],
            'listing_id' => $validated['link_type'] === 'listing' ? $validated['listing_id'] : null,
            'external_url' => $validated['link_type'] === 'external' ? $validated['external_url'] : null,
            'background_color' => $validated['background_color'] ?? '#8b1d30',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool)$request->input('is_active', true),
        ];

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $inspection = $this->fileUploadService->inspect($file);
            if ($inspection['is_safe']) {
                $data['image_path'] = $this->fileUploadService->storeSafely(
                    file: $file,
                    destinationDir: 'banners',
                    safeExt: $inspection['safe_extension']
                );
            }
        } elseif (!empty($validated['image_url'])) {
            $data['image_path'] = $validated['image_url'];
        }

        $banner->update($data);

        return back()->with('success', 'تم تعديل البنر الإعلاني بنجاح.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->is_active = !$banner->is_active;
        $banner->save();

        return back()->with('success', $banner->is_active ? 'تم تفعيل البنر الإعلاني' : 'تم تعطيل البنر');
    }

    public function destroy(int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();

        return back()->with('success', 'تم حذف البنر الإعلاني بنجاح.');
    }
}

