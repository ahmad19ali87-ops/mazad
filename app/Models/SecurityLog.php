<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $table = 'security_logs';

    protected $fillable = [
        'ip_address',
        'user_id',
        'event_type',
        'severity',
        'url',
        'method',
        'user_agent',
        'payload',
        'action_taken',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

