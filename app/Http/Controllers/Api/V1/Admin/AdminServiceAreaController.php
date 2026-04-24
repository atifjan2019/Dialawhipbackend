<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manage delivery service areas (postcode-based delivery charges and ETAs).
 * Admin-only.
 */
class AdminServiceAreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceArea::query();

        if ($request->filled('filter.active')) {
            $query->where('is_active', (bool) $request->query('filter.active'));
        }
        if ($search = $request->query('filter.search')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], strtoupper((string) $search)).'%';
            $query->where('postcode_prefix', 'like', $term);
        }

        $areas = $query->orderBy('postcode_prefix')->get();

        return response()->json([
            'data' => $areas->map(fn (ServiceArea $a) => $this->toArray($a))->all(),
        ]);
    }

    public function show(ServiceArea $serviceArea): JsonResponse
    {
        return response()->json(['data' => $this->toArray($serviceArea)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $area = ServiceArea::create($data);

        return response()->json(['data' => $this->toArray($area)], 201);
    }

    public function update(Request $request, ServiceArea $serviceArea): JsonResponse
    {
        $data = $this->validated($request, $serviceArea);
        $serviceArea->update($data);

        return response()->json(['data' => $this->toArray($serviceArea->fresh())]);
    }

    public function destroy(ServiceArea $serviceArea): JsonResponse
    {
        $serviceArea->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /**
     * Bulk upsert — POST an array of areas; existing postcode_prefixes are updated.
     *
     * Body: { "areas": [ { postcode_prefix, delivery_fee_pence, ... }, ... ] }
     */
    public function bulkUpsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'areas' => ['required', 'array', 'min:1', 'max:500'],
            'areas.*.postcode_prefix' => ['required', 'string', 'max:8'],
            'areas.*.delivery_fee_pence' => ['required', 'integer', 'min:0', 'max:100000'],
            'areas.*.priority_fee_pence' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'areas.*.super_fee_pence' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'areas.*.eta_standard_minutes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'areas.*.eta_priority_minutes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'areas.*.is_active' => ['nullable', 'boolean'],
        ]);

        $updated = [];
        foreach ($data['areas'] as $row) {
            $prefix = strtoupper(preg_replace('/\s+/', '', (string) $row['postcode_prefix']));
            $area = ServiceArea::updateOrCreate(
                ['postcode_prefix' => $prefix],
                array_filter([
                    'delivery_fee_pence' => $row['delivery_fee_pence'],
                    'priority_fee_pence' => $row['priority_fee_pence'] ?? null,
                    'super_fee_pence' => $row['super_fee_pence'] ?? null,
                    'eta_standard_minutes' => $row['eta_standard_minutes'] ?? null,
                    'eta_priority_minutes' => $row['eta_priority_minutes'] ?? null,
                    'is_active' => $row['is_active'] ?? true,
                ], fn ($v) => $v !== null),
            );
            $updated[] = $this->toArray($area);
        }

        return response()->json(['data' => $updated]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ServiceArea $existing = null): array
    {
        $uniqueRule = Rule::unique('service_areas', 'postcode_prefix');
        if ($existing) {
            $uniqueRule = $uniqueRule->ignore($existing->id);
        }

        return $request->validate([
            'postcode_prefix' => ['required', 'string', 'max:8', $uniqueRule],
            'delivery_fee_pence' => ['required', 'integer', 'min:0', 'max:100000'],
            'priority_fee_pence' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'super_fee_pence' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'eta_standard_minutes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'eta_priority_minutes' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ServiceArea $a): array
    {
        return [
            'id' => $a->id,
            'postcode_prefix' => $a->postcode_prefix,
            'delivery_fee_pence' => (int) $a->delivery_fee_pence,
            'priority_fee_pence' => $a->priority_fee_pence !== null ? (int) $a->priority_fee_pence : null,
            'super_fee_pence' => $a->super_fee_pence !== null ? (int) $a->super_fee_pence : null,
            'eta_standard_minutes' => $a->eta_standard_minutes !== null ? (int) $a->eta_standard_minutes : null,
            'eta_priority_minutes' => $a->eta_priority_minutes !== null ? (int) $a->eta_priority_minutes : null,
            'is_active' => (bool) $a->is_active,
            'created_at' => $a->created_at,
            'updated_at' => $a->updated_at,
        ];
    }
}
