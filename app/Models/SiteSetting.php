<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'label_ar',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, string $group = 'general', string $type = 'text', ?string $label = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type' => $type,
                'label_ar' => $label,
            ]
        );

        Cache::forget("site_setting_{$key}");
        Cache::forget("site_settings_all");
    }

    public static function allGrouped(): array
    {
        return Cache::remember("site_settings_all", 3600, function () {
            $all = static::all();
            $grouped = [];
            foreach ($all as $item) {
                $grouped[$item->group][$item->key] = $item->value;
            }
            return $grouped;
        });
    }
}

