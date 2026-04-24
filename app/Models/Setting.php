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
            if (! $row) {
                return $default;
            }
            $value = $row->value;
            return $value ?? $default;
        });
    }

    /**
     * Write a setting. Nulls are stored as a JSON "null" literal so the
     * column (which is JSON-typed) always has a valid payload — this keeps
     * the write working whether or not the value column is nullable.
     */
    public static function put(string $key, mixed $value): void
    {
        // Bypass the array cast for null: Eloquent's setAttribute() skips
        // json_encode when the value is null, which would then try to write
        // a raw SQL NULL. Store a JSON "null" literal instead.
        if ($value === null) {
            self::query()->updateOrInsert(
                ['key' => $key],
                ['key' => $key, 'value' => 'null', 'updated_at' => now()],
            );
        } else {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()],
            );
        }

        Cache::forget("setting:$key");
    }
}
