<?php

namespace App\Domain\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Return all settings grouped for the admin panel.
     * Each entry keeps schema metadata alongside the current value.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function allGrouped(): array
    {
        $schema = SettingsRegistry::schema();
        $stored = $this->loadStored();

        $groups = [];
        foreach ($schema as $key => $meta) {
            $value = $stored[$key] ?? $meta['default'] ?? null;

            $groups[$meta['group']][] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'type' => $meta['type'] ?? 'string',
                'public' => (bool) ($meta['public'] ?? false),
                'value' => $value,
            ];
        }

        return $groups;
    }

    /**
     * Flat key => value map of all known settings, using defaults when missing.
     *
     * @return array<string, mixed>
     */
    public function allFlat(): array
    {
        $schema = SettingsRegistry::schema();
        $stored = $this->loadStored();
        $out = [];
        foreach ($schema as $key => $meta) {
            $out[$key] = $stored[$key] ?? $meta['default'] ?? null;
        }
        return $out;
    }

    /**
     * Load every setting row and map key => decoded value.
     * Uses ->get() so Eloquent casts apply (pluck() skips casts).
     *
     * @return array<string, mixed>
     */
    private function loadStored(): array
    {
        $out = [];
        foreach (Setting::query()->get() as $row) {
            $out[$row->key] = $row->value;
        }
        return $out;
    }

    /**
     * Bulk update settings from an associative array. Unknown keys are ignored.
     *
     * @param  array<string, mixed>  $pairs
     * @return array<string>   list of updated keys
     */
    public function updateMany(array $pairs): array
    {
        $updated = [];
        foreach ($pairs as $key => $value) {
            if (! SettingsRegistry::exists($key)) {
                continue;
            }
            $value = $this->coerce($key, $value);
            Setting::put($key, $value);
            $updated[] = $key;
        }
        // Warm-up: clear all cached public keys in one go
        Cache::forget('settings:public');
        return $updated;
    }

    /**
     * Public settings only — intended for /v1/settings/public.
     * Result is cached briefly.
     *
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        return Cache::remember('settings:public', 60, function () {
            $keys = SettingsRegistry::publicKeys();
            $stored = [];
            foreach (Setting::query()->whereIn('key', $keys)->get() as $row) {
                $stored[$row->key] = $row->value;
            }
            $out = [];
            foreach ($keys as $key) {
                $meta = SettingsRegistry::meta($key);
                $out[$key] = $stored[$key] ?? ($meta['default'] ?? null);
            }
            return $out;
        });
    }

    /**
     * Coerce the incoming value to the declared type so the stored shape is consistent.
     */
    private function coerce(string $key, mixed $value): mixed
    {
        $type = SettingsRegistry::meta($key)['type'] ?? 'string';

        if ($value === '' || $value === null) {
            return null;
        }

        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value,
            'int' => (int) $value,
            'json', 'array' => is_array($value) ? $value : json_decode((string) $value, true),
            default => is_scalar($value) ? (string) $value : $value,
        };
    }
}
