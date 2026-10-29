<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasFactory;

    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip_address',
        'reason',
        'attempts_count',
        'blocked_until',
        'is_permanent',
        'blocked_by',
    ];

    protected $casts = [
        'blocked_until' => 'datetime',
        'is_permanent' => 'boolean',
        'attempts_count' => 'integer',
    ];

    public function isBlockedNow(): bool
    {
        if ($this->is_permanent) {
            return true;
        }

        if ($this->blocked_until && $this->blocked_until->isFuture()) {
            return true;
        }

        return false;
    }
}

