<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SecureFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HostingerSecurityAndUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->artisan('migrate');
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\BlockedIp::truncate();
    }

    /**
     * Test genuine image uploads pass validation and scanning.
     */
    public function test_safe_image_upload_passes_inspection(): void
    {
        Storage::fake('public');
        $fileUploadService = app(SecureFileUploadService::class);

        // Generate a valid real JPEG in memory
        $file = UploadedFile::fake()->image('clean_car.jpg', 640, 480);

        $result = $fileUploadService->inspect($file);

        $this->assertTrue($result['is_safe']);
        $this->assertNull($result['reason']);
        $this->assertEquals('jpg', $result['safe_extension']);
    }

    /**
     * Test double extension attack (e.g. exploit.php.jpg) is blocked immediately.
     */
    public function test_double_extension_attack_is_blocked(): void
    {
        $fileUploadService = app(SecureFileUploadService::class);

        $maliciousFile = UploadedFile::fake()->createWithContent(
            'exploit.php.jpg',
            "\xFF\xD8\xFF\xE0" . "fake jpg image content"
        );

        $result = $fileUploadService->inspect($maliciousFile);

        $this->assertFalse($result['is_safe']);
        $this->assertStringContainsString('امتداد مزدوج', $result['reason']);
    }

    /**
     * Test dangerous extension (e.g. shell.phtml, test.php) is blocked immediately.
     */
    public function test_dangerous_extension_is_blocked(): void
    {
        $fileUploadService = app(SecureFileUploadService::class);

        $phpFile = UploadedFile::fake()->createWithContent(
            'shell.phtml',
            "<?php phpinfo(); ?>"
        );

        $result = $fileUploadService->inspect($phpFile);

        $this->assertFalse($result['is_safe']);
    }

    /**
     * Test fake extension / mismatched MIME type (e.g. text file renamed to .jpg) is blocked.
     */
    public function test_fake_mime_type_is_blocked(): void
    {
        $fileUploadService = app(SecureFileUploadService::class);

        // A plain text file claiming to be a .jpg
        $fakeFile = UploadedFile::fake()->createWithContent(
            'readme.jpg',
            "This is just plain text masquerading as a jpeg file."
        );

        $result = $fileUploadService->inspect($fakeFile);

        $this->assertFalse($result['is_safe']);
        $this->assertStringContainsString('نوع الملف الفعلي لا يتطابق', $result['reason']);
    }

    /**
     * Test embedded PHP code / Webshell inside an image binary is detected and blocked.
     */
    public function test_polyglot_webshell_inside_image_is_blocked(): void
    {
        $fileUploadService = app(SecureFileUploadService::class);

        // Valid JPEG magic header with embedded PHP eval webshell
        $polyglotContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00`\x00`\x00\x00" .
            "<?php eval(base64_decode('cGhwaW5mbygpOw==')); ?>" .
            "\xFF\xD9";

        $polyglotFile = UploadedFile::fake()->createWithContent('innocent.jpg', $polyglotContent);

        $result = $fileUploadService->inspect($polyglotFile);

        $this->assertFalse($result['is_safe']);
        $this->assertStringContainsString('شيفرات أو نصوص برمجية مخفية', $result['reason']);
    }

    /**
     * Test client filename is completely discarded and replaced with random UUID.
     */
    public function test_file_storage_discards_client_filename(): void
    {
        Storage::fake('public');
        $fileUploadService = app(SecureFileUploadService::class);

        $file = UploadedFile::fake()->image('my_sensitive_name_123.png', 100, 100);
        $path = $fileUploadService->storeSafely($file, 'listings/99', 'png');

        $this->assertStringNotContainsString('my_sensitive_name_123', $path);
        $this->assertStringStartsWith('listings/99/', $path);
        $this->assertStringEndsWith('.png', $path);
    }

    /**
     * Test dynamic media serving route (Hostinger symlink fallback) serves file cleanly.
     */
    public function test_media_fallback_serves_valid_images(): void
    {
        $testDir = storage_path('app/public/test_media');
        if (!is_dir($testDir)) {
            @mkdir($testDir, 0775, true);
        }

        $dummyImagePath = $testDir . '/sample_test.png';
        // 1x1 transparent PNG binary
        file_put_contents($dummyImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $response = $this->get('/storage/test_media/sample_test.png');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        @unlink($dummyImagePath);
        @rmdir($testDir);
    }

    /**
     * Test media fallback blocks directory traversal attempts.
     */
    public function test_media_fallback_blocks_directory_traversal(): void
    {
        $response = $this->get('/storage/../.env');
        $this->assertContains($response->getStatusCode(), [400, 403, 404]);
    }

    /**
     * Test Hostinger Artisan command executes successfully.
     */
    public function test_hostinger_setup_artisan_command_succeeds(): void
    {
        $this->artisan('hostinger:setup')
            ->expectsOutputToContain('فحص توافق منصة مزاد سوريا مع استضافة هوستنجر')
            ->assertExitCode(0);
    }
}
