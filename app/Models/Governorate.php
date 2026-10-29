<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class)->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}

