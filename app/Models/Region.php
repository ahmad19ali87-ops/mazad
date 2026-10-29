<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'governorate_id',
        'name_ar',
        'name_en',
        'sort_order',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}

