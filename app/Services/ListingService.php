<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingService
{
    protected SecureFileUploadService $fileUploadService;

    public function __construct(SecureFileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Store a new listing and handle multiple images + notifications.
     */
    public function createListing(User $user, array $validatedData, array $uploadedImages = []): Listing
    {
        return DB::transaction(function () use ($user, $validatedData, $uploadedImages) {
            $validatedData['user_id'] = $user->id;

            // Check if site admin enabled pre-approval requirement
            $requireApproval = (bool)\App\Models\SiteSetting::get('listings_require_approval', false);
            $status = ($requireApproval && !$user->isAdmin()) ? 'pending' : 'active';
            $validatedData['status'] = $status;

            $validatedData['currency'] = $validatedData['currency'] ?? 'SYP';
            $validatedData['condition'] = $validatedData['condition'] ?? 'used';
            $validatedData['listing_type'] = $validatedData['listing_type'] ?? 'sale';
            $validatedData['job_type'] = $validatedData['job_type'] ?? null;
            $validatedData['is_negotiable'] = !empty($validatedData['is_negotiable']);
            $validatedData['allow_comments'] = isset($validatedData['allow_comments']) ? (bool)$validatedData['allow_comments'] : true;

            // Generate clean unique slug
            $baseSlug = Str::slug($validatedData['title']);
            if (empty($baseSlug)) {
                $baseSlug = 'ad-' . time();
            }
            $validatedData['slug'] = $baseSlug . '-' . Str::random(6);

            $listing = Listing::create($validatedData);

            // Handle image uploads
            $this->saveImages($listing, $uploadedImages);

            // If active: notify followers. If pending: notify user that ad is under review
            if ($status === 'active') {
                $this->notifyFollowers($user, $listing);
            } else {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'title' => 'تم استلام إعلانك وهو قيد المراجعة ⏳',
                    'message' => 'تم استلام إعلانك "' . $listing->title . '" بنجاح وهو الآن قيد المراجعة والتدقيق من قبل إدارة الموقع وسيتم نشره للجميع فور اعتماده.',
                    'type' => 'listing_pending',
                    'action_url' => '/dashboard',
                    'is_read' => false,
                ]);
            }

            return $listing;
        });
    }

    /**
     * Update an existing listing.
     */
    public function updateListing(Listing $listing, array $validatedData, array $uploadedImages = []): Listing
    {
        return DB::transaction(function () use ($listing, $validatedData, $uploadedImages) {
            $validatedData['is_negotiable'] = !empty($validatedData['is_negotiable']);
            $validatedData['allow_comments'] = isset($validatedData['allow_comments']) ? (bool)$validatedData['allow_comments'] : true;

            $listing->update($validatedData);

            if (!empty($uploadedImages)) {
                $this->saveImages($listing, $uploadedImages);
            }

            return $listing;
        });
    }

    /**
     * Upload, strictly scan, and attach images to a listing.
     */
    public function saveImages(Listing $listing, array $images): void
    {
        $hasPrimary = $listing->images()->where('is_primary', true)->exists();
        $currentCount = $listing->images()->count();

        foreach ($images as $index => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                // Strict Deep Security Inspection (MIME, Magic Bytes, Double Extension, Shell Code Scan)
                $inspection = $this->fileUploadService->inspect($file);
                if (!$inspection['is_safe']) {
                    \Illuminate\Support\Facades\Log::warning("Blocked unsafe file upload for listing {$listing->id}: " . $inspection['reason']);
                    continue;
                }

                // Safely store with UUID and sanitization
                $path = $this->fileUploadService->storeSafely(
                    file: $file,
                    destinationDir: 'listings/' . $listing->id,
                    safeExt: $inspection['safe_extension']
                );

                ListingImage::create([
                    'listing_id' => $listing->id,
                    'image_path' => $path,
                    'is_primary' => !$hasPrimary && $index === 0,
                    'sort_order' => $currentCount + $index + 1,
                ]);

                if (!$hasPrimary && $index === 0) {
                    $hasPrimary = true;
                }
            }
        }
    }

    /**
     * Notify followers when a seller publishes a new listing.
     */
    public function notifyFollowers(User $seller, Listing $listing): void
    {
        $followers = $seller->followers()->get();

        foreach ($followers as $follower) {
            InAppNotification::create([
                'user_id' => $follower->id,
                'title' => 'سلعة جديدة من بائع تتابعه',
                'message' => 'قام "' . ($seller->name) . '" بنشر سلعة جديدة: ' . $listing->title,
                'type' => 'new_listing',
                'action_url' => '/listings/' . $listing->slug,
                'data' => [
                    'listing_id' => $listing->id,
                    'seller_id' => $seller->id,
                    'slug' => $listing->slug,
                ],
                'is_read' => false,
            ]);
        }
    }
}

