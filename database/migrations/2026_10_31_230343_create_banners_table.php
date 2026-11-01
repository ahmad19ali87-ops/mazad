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
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('image_path');
                $table->string('link_type', 30)->default('none'); // 'listing', 'external', 'none'
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->string('external_url', 500)->nullable();
                $table->string('badge_text', 50)->nullable()->default('إعلان ممول ⭐');
                $table->string('background_color', 30)->nullable()->default('#8b1d30');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('clicks_count')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
