<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Domain\Orders\Exceptions\BelowMinimumOrderException;
use App\Domain\Orders\Exceptions\PostcodeOutOfAreaException;
use App\Domain\Orders\Services\OrderPricingService;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function preview(Request $request, OrderPricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'delivery_tier' => ['nullable', 'string', 'in:standard,priority,super'],
        ]);

        try {
            $result = $pricing->priceCart($data['items'], $data['postcode'] ?? null, $data['delivery_tier'] ?? 'standard');
        } catch (BelowMinimumOrderException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'below_minimum',
                'minimum_pence' => (int) Setting::get('order.minimum_pence', 2000),
            ], 422);
        } catch (PostcodeOutOfAreaException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'out_of_area',
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $hasAgeRestricted = false;
        foreach ($result['lines'] as $line) {
            if (($line['product']->is_age_restricted ?? false)) {
                $hasAgeRestricted = true;
                break;
            }
        }

        return response()->json([
            'data' => [
                'subtotal_pence' => $result['subtotal']->pence,
                'delivery_fee_pence' => $result['delivery_fee']->pence,
                'vat_pence' => $result['vat']->pence,
                'total_pence' => $result['total']->pence,
                'minimum_pence' => (int) Setting::get('order.minimum_pence', 2000),
                'delivery_tier' => $data['delivery_tier'] ?? 'standard',
                'requires_id_verification' => $hasAgeRestricted,
                'service_area' => $result['service_area'] ? [
                    'postcode_prefix' => $result['service_area']->postcode_prefix,
                    'eta_standard_minutes' => $result['service_area']->eta_standard_minutes,
                    'eta_priority_minutes' => $result['service_area']->eta_priority_minutes,
                    'priority_fee_pence' => $result['service_area']->priority_fee_pence,
                    'super_fee_pence' => $result['service_area']->super_fee_pence,
                ] : null,
                'lines' => array_map(fn ($l) => [
                    'product_id' => $l['product']->id,
                    'name' => $l['product']->name,
                    'brand' => $l['product']->brand,
                    'quantity' => $l['quantity'],
                    'is_age_restricted' => (bool) $l['product']->is_age_restricted,
                    'unit_price_pence' => $l['unit_price']->pence,
                    'line_total_pence' => $l['line_total']->pence,
                ], $result['lines']),
            ],
        ]);
    }
}
