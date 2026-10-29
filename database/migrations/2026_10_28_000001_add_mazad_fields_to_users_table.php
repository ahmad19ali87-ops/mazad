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
            $table->string('phone')->nullable()->unique();
            $table->string('role')->default('user'); // admin, seller, user
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('governorate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'avatar',
                'bio',
                'whatsapp',
                'is_verified',
                'governorate',
            ]);
        });
    }
};

