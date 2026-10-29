<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'seller_id',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}

