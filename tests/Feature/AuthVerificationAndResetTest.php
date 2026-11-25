<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthVerificationAndResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->artisan('migrate');
        $this->seed();
    }
    public function test_user_registration_requires_email_verification_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'سامر الأحمد',
            'email' => 'samer.test@example.com',
            'phone' => '0988776655',
            'governorate' => 'دمشق',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('verification.notice', ['email' => 'samer.test@example.com']));

        $user = User::where('email', 'samer.test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool)$user->is_verified);
        $this->assertNotNull($user->verification_code);
        $this->assertEquals(6, strlen($user->verification_code));
        $this->assertNotNull($user->verification_code_expires_at);
    }

    public function test_unverified_user_cannot_log_in_and_is_redirected_to_verify(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'is_verified' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice', ['email' => 'unverified@example.com']));
        $this->assertGuest();
    }

    public function test_valid_6_digit_code_activates_user_account(): void
    {
        $user = User::factory()->create([
            'email' => 'verify.me@example.com',
            'is_verified' => false,
            'verification_code' => '456789',
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-email', [
            'email' => 'verify.me@example.com',
            'code' => '456789',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue((bool)$user->is_verified);
        $this->assertNull($user->verification_code);
    }

    public function test_invalid_verification_code_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid.code@example.com',
            'is_verified' => false,
            'verification_code' => '112233',
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->from('/verify-email')->post('/verify-email', [
            'email' => 'invalid.code@example.com',
            'code' => '999999',
        ]);

        $response->assertRedirect('/verify-email');
        $response->assertSessionHasErrors(['code']);

        $user->refresh();
        $this->assertFalse((bool)$user->is_verified);
    }

    public function test_forgot_password_generates_code_and_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.user@example.com',
            'is_verified' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'reset.user@example.com',
        ]);

        $response->assertRedirect(route('password.reset', ['email' => 'reset.user@example.com']));

        $user->refresh();
        $this->assertNotNull($user->password_reset_code);
        $this->assertEquals(6, strlen($user->password_reset_code));
        $this->assertNotNull($user->password_reset_token);
        $this->assertNotNull($user->password_reset_expires_at);
    }

    public function test_reset_password_using_6_digit_code(): void
    {
        $user = User::factory()->create([
            'email' => 'reset.bycode@example.com',
            'password_reset_code' => '654321',
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/reset-password', [
            'email' => 'reset.bycode@example.com',
            'code' => '654321',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertNull($user->password_reset_code);
    }

    public function test_reset_password_using_token_link(): void
    {
        $token = Str::random(60);
        $user = User::factory()->create([
            'email' => 'reset.bytoken@example.com',
            'password_reset_token' => $token,
            'password_reset_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/reset-password', [
            'email' => 'reset.bytoken@example.com',
            'token' => $token,
            'password' => 'brandnewpass456',
            'password_confirmation' => 'brandnewpass456',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue(Hash::check('brandnewpass456', $user->password));
        $this->assertNull($user->password_reset_token);
    }

    public function test_api_registration_and_verification_flow(): void
    {
        $registerRes = $this->postJson('/api/register', [
            'name' => 'تطبيقي مسجل',
            'email' => 'api.user@example.com',
            'phone' => '0933112233',
            'password' => 'secret123',
            'governorate' => 'حلب',
        ]);

        $registerRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'requires_verification' => true,
                'email' => 'api.user@example.com',
            ]);

        $user = User::where('email', 'api.user@example.com')->first();
        $this->assertNotNull($user->verification_code);

        // Verify with code via API
        $verifyRes = $this->postJson('/api/verify-email', [
            'email' => 'api.user@example.com',
            'code' => $user->verification_code,
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_api_in_app_google_authentication(): void
    {
        $res = $this->postJson('/api/auth/google', [
            'email' => 'google.inapp@gmail.com',
            'name' => 'طارق السوري',
            'google_id' => 'google_unique_id_9988',
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['token', 'user']);

        $user = User::where('email', 'google.inapp@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue((bool)$user->is_verified);
    }

    public function test_web_google_oauth_redirect_uses_configured_credentials(): void
    {
        config([
            'services.google.client_id' => '467934742059-tume7aasai0pco63di846e3fcca054sg.apps.googleusercontent.com',
            'services.google.client_secret' => 'GOCSPX-test-secret',
            'services.google.redirect' => 'https://mazad.ahmadinsights.online/auth/google/callback',
        ]);

        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('467934742059-tume7aasai0pco63di846e3fcca054sg.apps.googleusercontent.com', $location);
        $this->assertStringContainsString(urlencode('https://mazad.ahmadinsights.online/auth/google/callback'), $location);
    }

    public function test_api_google_id_token_verification_accepts_valid_and_rejects_mismatched_client(): void
    {
        config([
            'services.google.client_id' => '467934742059-tume7aasai0pco63di846e3fcca054sg.apps.googleusercontent.com',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'oauth2.googleapis.com/tokeninfo*valid_token*' => \Illuminate\Support\Facades\Http::response([
                'aud' => '467934742059-tume7aasai0pco63di846e3fcca054sg.apps.googleusercontent.com',
                'sub' => 'sub_google_12345',
                'email' => 'verified.google@gmail.com',
                'email_verified' => 'true',
                'name' => 'مستخدم جوجل موثق',
                'picture' => 'https://lh3.googleusercontent.com/a/test',
            ], 200),
            'oauth2.googleapis.com/tokeninfo*wrong_aud*' => \Illuminate\Support\Facades\Http::response([
                'aud' => 'other-client-id.apps.googleusercontent.com',
                'sub' => 'sub_wrong',
                'email' => 'hacker@gmail.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $badRes = $this->postJson('/api/auth/google', ['id_token' => 'wrong_aud']);
        $badRes->assertStatus(401);

        $goodRes = $this->postJson('/api/auth/google', ['id_token' => 'valid_token']);
        $goodRes->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', [
            'email' => 'verified.google@gmail.com',
            'google_id' => 'sub_google_12345',
        ]);
    }
}
