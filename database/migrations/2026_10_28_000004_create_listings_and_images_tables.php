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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->noActionOnDelete();
            
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            
            $table->decimal('price', 15, 2);
            $table->string('currency')->default('SYP'); // SYP, USD
            $table->boolean('is_negotiable')->default(false);
            
            // Condition: 'new' or 'used' (الجديد من القديم)
            $table->string('condition')->default('used'); // new, used
            
            // Location fields
            $table->foreignId('governorate_id')->constrained('governorates')->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->noActionOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->noActionOnDelete();
            $table->string('district_name')->nullable();
            $table->string('street_address')->nullable(); // اسم الشارع، جانب كذا، إلخ
            
            // Specifications (e.g. for car: model, make, year, mileage, transmission, fuel)
            $table->json('specs')->nullable();
            
            // Contact & settings
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->boolean('allow_comments')->default(true);
            
            // Moderation & status
            $table->string('status')->default('active'); // active, pending, sold, rejected
            $table->boolean('is_featured')->default(false);
            
            // Metrics
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category_id', 'status']);
            $table->index(['governorate_id', 'status']);
            $table->index(['condition', 'status']);
            $table->index('price');
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};
