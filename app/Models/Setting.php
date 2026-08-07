<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'key', 'value', 'type', 'group', 'description', 'is_public', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    /**
     * Get a setting value by key with optional default.
     * Cached for 60 minutes.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) return $default;

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value and flush the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : (string) $value]);
        Cache::forget("setting:{$key}");
    }
}
