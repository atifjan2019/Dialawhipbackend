<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Settings\SettingsRegistry;
use App\Domain\Settings\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    /**
     * List every setting (grouped) for the admin panel.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'groups' => $this->settings->allGrouped(),
                'flat' => $this->settings->allFlat(),
            ],
        ]);
    }

    /**
     * Bulk update. Accepts either:
     *   { "settings": { "key": value, ... } }
     * or a flat body of key/value pairs where every key is a known setting.
     */
    public function update(Request $request): JsonResponse
    {
        $payload = $request->input('settings');
        if (! is_array($payload)) {
            // Allow flat body — strip any non-registered keys
            $payload = array_intersect_key($request->all(), array_flip(SettingsRegistry::keys()));
        }

        // Validate each provided key against its declared rules
        $rules = [];
        foreach ($payload as $key => $_) {
            if (SettingsRegistry::exists($key)) {
                $rules["settings.$key"] = SettingsRegistry::validationRules()[$key] ?? ['nullable'];
            }
        }
        $request->merge(['settings' => $payload]);
        if ($rules !== []) {
            $request->validate($rules);
        }

        $updated = $this->settings->updateMany($payload);

        return response()->json([
            'data' => [
                'updated' => $updated,
                'groups' => $this->settings->allGrouped(),
            ],
        ]);
    }

    /**
     * Update a single setting.  Useful for simple toggles from the admin panel.
     */
    public function updateOne(Request $request, string $key): JsonResponse
    {
        if (! SettingsRegistry::exists($key)) {
            return response()->json(['message' => "Unknown setting: {$key}"], 404);
        }

        $data = $request->validate([
            'value' => SettingsRegistry::validationRules()[$key] ?? ['nullable'],
        ]);

        $this->settings->updateMany([$key => $data['value'] ?? null]);

        return response()->json([
            'data' => [
                'key' => $key,
                'value' => $this->settings->allFlat()[$key] ?? null,
            ],
        ]);
    }

    /**
     * Upload a file (logo, favicon, og-image, etc.) and optionally bind it
     * to a setting key. Returns the stored public URL.
     *
     * Body (multipart):
     *   file  : the uploaded file (required)
     *   key   : optional setting key to update (must be an image-type key)
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,svg,ico,gif'],
            'key' => ['nullable', 'string', Rule::in(SettingsRegistry::keys())],
        ]);

        $file = $request->file('file');
        $folder = 'settings';
        $path = $file->store($folder, 'public');
        $url = Storage::disk('public')->url($path);

        $updatedKey = null;
        if (! empty($validated['key']) && SettingsRegistry::exists($validated['key'])) {
            $meta = SettingsRegistry::meta($validated['key']);
            if (($meta['type'] ?? null) === 'image') {
                $this->settings->updateMany([$validated['key'] => $url]);
                $updatedKey = $validated['key'];
            }
        }

        return response()->json([
            'data' => [
                'url' => $url,
                'path' => $path,
                'disk' => 'public',
                'key' => $updatedKey,
            ],
        ], 201);
    }
}
