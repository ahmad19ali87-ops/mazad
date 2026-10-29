<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'google_id',
        'role',
        'avatar',
        'avatar_url',
        'bio',
        'whatsapp',
        'is_verified',
        'governorate',
        'remember_contact_info',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'remember_contact_info' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // People who follow this user (when user is seller)
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'seller_id', 'follower_id')->withTimestamps();
    }

    // Sellers that this user follows
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'seller_id')->withTimestamps();
    }

    public function isFollowing(int $sellerId): bool
    {
        return $this->following()->where('seller_id', $sellerId)->exists();
    }

    public function savedListings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'saved_listings')->withTimestamps();
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class)->latest();
    }
}
