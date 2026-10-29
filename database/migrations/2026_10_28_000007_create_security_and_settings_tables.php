<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add listing_type and is_sold to listings table
        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'listing_type')) {
                $table->string('listing_type', 20)->default('sale')->after('condition'); // 'sale' (للبيع) or 'wanted' (مطلوب للشراء/للتوظيف)
            }
            if (!Schema::hasColumn('listings', 'is_sold')) {
                $table->boolean('is_sold')->default(false)->after('status');
            }
            if (!Schema::hasColumn('listings', 'job_type')) {
                $table->string('job_type', 30)->nullable()->after('listing_type'); // 'vacancy' (مطلوب موظف), 'seeker' (باحث عن عمل)
            }
        });

        // 2. Add google_id and remember profile flags to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url', 255)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'remember_contact_info')) {
                $table->boolean('remember_contact_info')->default(true)->after('bio');
            }
        });

        // 3. Blocked IPs Table
        if (!Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->string('reason', 255)->default('حظر تلقائي بسبب تجاوز محاولات الدخول الخاطئة أو نشاط مشبوه');
                $table->integer('attempts_count')->default(1);
                $table->dateTime('blocked_until')->nullable();
                $table->boolean('is_permanent')->default(false);
                $table->string('blocked_by', 100)->default('النظام التلقائي (WAF)');
                $table->timestamps();
            });
        }

        // 4. Security Logs Table
        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->noActionOnDelete();
                $table->string('event_type', 50)->index(); // brute_force, sql_injection, xss_attempt, scanner, path_traversal, unauthorized_access
                $table->string('severity', 20)->default('medium'); // info, low, medium, high, critical
                $table->string('url', 500)->nullable();
                $table->string('method', 10)->default('GET');
                $table->string('user_agent', 350)->nullable();
                $table->text('payload')->nullable();
                $table->string('action_taken', 50)->default('logged'); // logged, blocked, throttled
                $table->timestamps();
            });
        }

        // 5. Login Attempts Table (Brute-force tracking)
        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address', 45)->index();
                $table->string('email', 190)->nullable()->index();
                $table->boolean('is_successful')->default(false);
                $table->string('user_agent', 255)->nullable();
                $table->dateTime('attempted_at')->index();
            });
        }

        // 6. Site & Mobile Appearance Settings Table
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->string('group', 50)->default('general'); // general, appearance, mobile, hero, security, contact
                $table->string('type', 30)->default('text'); // text, textarea, color, boolean, image, json
                $table->string('label_ar', 150)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('blocked_ips');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url', 'remember_contact_info']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['listing_type', 'is_sold', 'job_type']);
        });
    }
};

