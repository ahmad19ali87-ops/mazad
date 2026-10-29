<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'login_attempts';

    protected $fillable = [
        'ip_address',
        'email',
        'is_successful',
        'user_agent',
        'attempted_at',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];
}

