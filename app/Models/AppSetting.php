<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

// Konfigurasi aplikasi yang dapat diubah admin. @2026-Alifiandra
class AppSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('app_settings', function () {
            try {
                return Schema::hasTable('app_settings') ? static::query()->pluck('value', 'key')->all() : [];
            } catch (\Throwable) {
                return [];
            }
        });

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function put(string $key, mixed $value, ?int $userId = null): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
        Cache::forget('app_settings');
    }
}
