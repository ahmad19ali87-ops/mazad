<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Governorate;
use App\Models\Listing;
use App\Models\User;
use Tests\TestCase;

class MazadPlatformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->artisan('migrate');
        $this->seed();
    }

    /**
     * Test home page returns 200 and displays categories and listings.
     */
    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('مزاد');
        $response->assertSee('تصفح حسب الأقسام');
    }

    /**
     * Test API categories endpoint.
     */
    public function test_api_categories_returns_json(): void
    {
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name_ar', 'slug', 'subcategories'],
            ],
        ]);
    }

    /**
     * Test API locations endpoint returns Syrian governorates.
     */
    public function test_api_locations_returns_syrian_governorates(): void
    {
        $response = $this->getJson('/api/locations');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name_ar' => 'دمشق']);
        $response->assertJsonFragment(['name_ar' => 'حلب']);
    }

    /**
     * Test API listings returns items with condition and price.
     */
    public function test_api_listings_returns_items(): void
    {
        $response = $this->getJson('/api/listings');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'title', 'condition', 'price', 'currency', 'governorate'],
            ],
        ]);
    }

    /**
     * Test filtering listings by condition (new / used).
     */
    public function test_filter_by_condition(): void
    {
        $responseNew = $this->getJson('/api/listings?condition=new');
        $responseNew->assertStatus(200);
        foreach ($responseNew->json('data') as $item) {
            $this->assertEquals('new', $item['condition']);
        }

        $responseUsed = $this->getJson('/api/listings?condition=used');
        $responseUsed->assertStatus(200);
        foreach ($responseUsed->json('data') as $item) {
            $this->assertEquals('used', $item['condition']);
        }
    }

    /**
     * Test posting comment on a listing.
     */
    public function test_user_can_comment_on_listing(): void
    {
        $user = User::where('email', 'ahmad.buyer@mazad.sy')->first();
        $listing = Listing::first();

        $response = $this->actingAs($user)->post("/listings/{$listing->id}/comments", [
            'comment' => 'هل السلعة متوفرة حالياً بالمعرض؟',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'comment' => 'هل السلعة متوفرة حالياً بالمعرض؟',
        ]);
    }

    /**
     * Test following a seller and notification creation.
     */
    public function test_user_can_follow_seller(): void
    {
        $buyer = User::where('email', 'nour.user@mazad.sy')->first();
        $seller = User::where('role', 'seller')->first();

        $response = $this->actingAs($buyer)->post("/sellers/{$seller->id}/follow");
        $response->assertRedirect();

        $this->assertTrue($buyer->isFollowing($seller->id));
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $seller->id,
            'type' => 'new_follower',
        ]);
    }

    /**
     * Test admin dashboard protection and access.
     */
    public function test_admin_dashboard_access(): void
    {
        $regularUser = User::where('email', 'ahmad.buyer@mazad.sy')->first();
        $adminUser = User::where('email', 'admin@mazad.sy')->first();

        // Regular user should get 403 Forbidden
        $responseBlocked = $this->actingAs($regularUser)->get('/admin');
        $responseBlocked->assertStatus(403);

        // Admin user should get 200 OK
        $responseAdmin = $this->actingAs($adminUser)->get('/admin');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('لوحة المعلومات والإحصائيات');
    }

    /**
     * Test Category Routes and Safe Querying
     */
    public function test_category_routes_load_cleanly(): void
    {
        $response = $this->get('/category/cars-vehicles');
        $response->assertStatus(200);

        $responseListings = $this->get('/listings?category=cars-vehicles');
        $responseListings->assertStatus(200);

        $responseWanted = $this->get('/wanted');
        $responseWanted->assertStatus(200);

        $responseJobs = $this->get('/jobs');
        $responseJobs->assertStatus(200);
    }

    /**
     * Test Custom 404 Error Page
     */
    public function test_custom_404_error_page_renders_with_support_notice(): void
    {
        $response = $this->get('/non-existent-page-url-xyz');
        $response->assertStatus(404);
        $response->assertSee('ERROR 404');
        $response->assertSee('الصفحة غير موجودة');
        $response->assertSee('support@mazad.sy');
    }

    /**
     * Test Brute-Force Login Detection & Auto IP Banning
     */
    public function test_brute_force_login_auto_bans_ip(): void
    {
        $fakeIp = '198.51.100.99';

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $fakeIp])
                ->post('/login', [
                    'email' => 'victim@example.com',
                    'password' => 'wrong-password-' . $i,
                ]);
        }

        // Verify IP is in blocked_ips
        $this->assertDatabaseHas('blocked_ips', [
            'ip_address' => $fakeIp,
        ]);

        // Attempting to access any route from that IP now returns 403
        $response = $this->withServerVariables(['REMOTE_ADDR' => $fakeIp])->get('/');
        $response->assertStatus(403);
    }

    /**
     * Test Admin Security Panel and Manual Unblock
     */
    public function test_admin_security_panel_and_unblock(): void
    {
        $adminUser = User::where('email', 'admin@mazad.sy')->first();
        $targetIp = '203.0.113.55';

        // Admin accesses security dashboard
        $response = $this->actingAs($adminUser)->get('/admin/security');
        $response->assertStatus(200);
        $response->assertSee('لوحة مراقبة النظام وصد الهجمات');

        // Admin manually blocks an IP
        $this->actingAs($adminUser)->post('/admin/security/block', [
            'ip_address' => $targetIp,
            'reason' => 'اختبار حظر يدوي من الإدارة',
            'hours' => 24,
        ]);

        $this->assertDatabaseHas('blocked_ips', ['ip_address' => $targetIp]);

        // Admin unblocks the IP
        $this->actingAs($adminUser)->post("/admin/security/unblock/{$targetIp}");
        $this->assertDatabaseMissing('blocked_ips', ['ip_address' => $targetIp]);
    }

    /**
     * Test User Dashboard
     */
    public function test_user_dashboard_access(): void
    {
        $user = User::where('email', 'ahmad.buyer@mazad.sy')->first();

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('لوحة تحكم المستخدم');
        $response->assertSee('إعلاناتي ومبيعاتي');
        $response->assertSee('تذكر معلوماتي');
    }

    /**
     * Test Android APK download route serves the file.
     */
    public function test_android_apk_download_route(): void
    {
        $response = $this->get('/download/android');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.android.package-archive');
    }

    /**
     * Test notifications strict isolation (only the owner receives the comment notification).
     */
    public function test_notifications_strict_isolation(): void
    {
        $seller = User::where('role', 'seller')->first();
        $buyer = User::where('email', 'ahmad.buyer@mazad.sy')->first();
        $listing = Listing::where('user_id', $seller->id)->first();

        $sellerInitialNotificationCount = \App\Models\InAppNotification::where('user_id', $seller->id)->count();
        $buyerInitialNotificationCount = \App\Models\InAppNotification::where('user_id', $buyer->id)->count();

        // Buyer comments on seller's listing
        $this->actingAs($buyer)->post("/listings/{$listing->id}/comments", [
            'comment' => 'إشعار فحص العزل الأمني',
        ]);

        // Seller receives notification
        $this->assertEquals($sellerInitialNotificationCount + 1, \App\Models\InAppNotification::where('user_id', $seller->id)->count());
        // Buyer does NOT receive notification
        $this->assertEquals($buyerInitialNotificationCount, \App\Models\InAppNotification::where('user_id', $buyer->id)->count());
        // Notification belongs strictly to seller
        $lastNotif = \App\Models\InAppNotification::where('user_id', $seller->id)->latest()->first();
        $this->assertEquals($seller->id, $lastNotif->user_id);
    }

    /**
     * Test listing moderation toggle and admin approval flow.
     */
    public function test_listing_moderation_toggle_and_approval(): void
    {
        $admin = User::where('email', 'admin@mazad.sy')->first();
        $seller = User::where('role', 'seller')->first();

        // Admin toggles moderation ON
        $response = $this->actingAs($admin)->post('/admin/listings/toggle-moderation');
        $response->assertRedirect();
        $this->assertEquals('1', \App\Models\SiteSetting::get('listings_require_approval'));

        // Seller posts an ad with an image while moderation is enabled
        $category = \App\Models\Category::first();
        $gov = \App\Models\Governorate::first();
        $response = $this->actingAs($seller)->post('/listings', [
            'title' => 'سلعة تجريبية للمراجعة الإدارية',
            'category_id' => $category->id,
            'governorate_id' => $gov->id,
            'city' => 'دمشق',
            'condition' => 'new',
            'type' => 'offer',
            'price' => 500000,
            'currency' => 'SYP',
            'description' => 'وصف تفصيلي للسلعة المفحوصة في نظام المراجعة والتأكد من إرسال الإشعار',
            'images' => [
                \Illuminate\Http\UploadedFile::fake()->image('listing.jpg'),
            ],
        ]);
        $response->assertRedirect('/dashboard');

        // Verify the listing is created as pending
        $listing = Listing::where('title', 'سلعة تجريبية للمراجعة الإدارية')->first();
        $this->assertNotNull($listing);
        $this->assertEquals('pending', $listing->status);

        // Public user cannot view pending listing (redirected to home with info message)
        $publicUser = User::where('email', 'ahmad.buyer@mazad.sy')->first();
        $this->actingAs($publicUser)->get("/listings/{$listing->id}")->assertRedirect('/');

        // Author can view pending listing
        $this->actingAs($seller)->get("/listings/{$listing->id}")->assertStatus(200);

        // Admin approves the listing
        $this->actingAs($admin)->post("/admin/listings/{$listing->id}/status", [
            'status' => 'active',
        ]);
        $this->assertEquals('active', $listing->fresh()->status);

        // Now public can view
        $this->actingAs($publicUser)->get("/listings/{$listing->id}")->assertStatus(200);

        // Turn moderation off again for default open state
        $this->actingAs($admin)->post('/admin/listings/toggle-moderation');
        $this->assertEquals('0', \App\Models\SiteSetting::get('listings_require_approval'));
    }
}

