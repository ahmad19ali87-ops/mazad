<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_code', 6)->nullable()->after('is_verified');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            $table->string('password_reset_code', 6)->nullable()->after('verification_code_expires_at');
            $table->string('password_reset_token', 100)->nullable()->after('password_reset_code');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_code',
                'verification_code_expires_at',
                'password_reset_code',
                'password_reset_token',
                'password_reset_expires_at',
            ]);
        });
    }
};
