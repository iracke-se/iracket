<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ScraperSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("scraper_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return;
        }

        if ($type) {
            $setting->type = $type;
        }

        $setting->value = match ($setting->type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting->save();

        // Clear cache
        Cache::forget("scraper_setting_{$key}");
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => static::get($setting->key)];
            })
            ->toArray();
    }

    /**
     * Get URL for a specific scraper type.
     */
    public static function getUrl(string $type): ?string
    {
        return static::get("url_{$type}");
    }

    /**
     * Clear all cached settings.
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');

        foreach ($keys as $key) {
            Cache::forget("scraper_setting_{$key}");
        }
    }
}
