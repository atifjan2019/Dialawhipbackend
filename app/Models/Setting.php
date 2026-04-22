<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array', 'updated_at' => 'datetime'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:$key", 60, function () use ($key, $default) {
            $row = self::find($key);
            return $row?->value ?? $default;
        });
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        Cache::forget("setting:$key");
    }
}
