<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'image',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name_en ?: $category->name_ar) . '-' . rand(100, 999);
            }
        });
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}

