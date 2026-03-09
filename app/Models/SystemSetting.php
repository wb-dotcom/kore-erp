<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    public $timestamps = false;

    protected $table = 'system_settings';

    protected $fillable = ['setting_key', 'setting_value'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $row = static::where('setting_key', $key)->first();
            return $row ? $row->setting_value : $default;
        });
    }

    /**
     * Set a setting value, clearing cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );
        Cache::forget("setting:{$key}");
    }
}
