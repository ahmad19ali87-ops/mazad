<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'is_negotiable',
        'condition', // 'new' or 'used' (الجديد من القديم)
        'listing_type', // 'sale' (للبيع) or 'wanted' (مطلوب للشراء/للتوظيف)
        'is_sold', // هل بيعت السلعة
        'job_type', // 'vacancy' (مطلوب موظف), 'seeker' (باحث عن عمل)
        'governorate_id',
        'region_id',
        'district_id',
        'district_name',
        'street_address', // تفاصيل العنوان (الشارع، جانب كذا...)
        'specs',
        'phone',
        'whatsapp',
        'allow_comments',
        'status', // active, pending, sold, rejected
        'is_featured',
        'views_count',
        'likes_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'is_sold' => 'boolean',
        'allow_comments' => 'boolean',
        'is_featured' => 'boolean',
        'specs' => 'array',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($listing) {
            if (empty($listing->slug)) {
                $base = Str::slug($listing->title);
                if (empty($base)) {
                    $base = 'item-' . time();
                }
                $listing->slug = $base . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->where('is_approved', true)->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_listings')->withTimestamps();
    }

    public function getFormattedPriceAttribute(): string
    {
        $symbol = $this->currency === 'USD' ? '$' : 'ل.س';
        return number_format($this->price, 0) . ' ' . $symbol;
    }

    public function getConditionLabelAttribute(): string
    {
        return $this->condition === 'new' ? 'جديد' : 'مستعمل';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->governorate) {
            $parts[] = $this->governorate->name_ar;
        }
        if ($this->region) {
            $parts[] = $this->region->name_ar;
        }
        if ($this->district) {
            $parts[] = $this->district->name_ar;
        } elseif ($this->district_name) {
            $parts[] = $this->district_name;
        }
        if ($this->street_address) {
            $parts[] = $this->street_address;
        }
        return implode(' - ', $parts);
    }
}

