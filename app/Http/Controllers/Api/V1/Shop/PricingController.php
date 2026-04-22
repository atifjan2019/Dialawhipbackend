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
        ]);

        try {
            $result = $pricing->priceCart($data['items'], $data['postcode'] ?? null);
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

        return response()->json([
            'data' => [
                'subtotal_pence' => $result['subtotal']->pence,
                'delivery_fee_pence' => $result['delivery_fee']->pence,
                'vat_pence' => $result['vat']->pence,
                'total_pence' => $result['total']->pence,
                'minimum_pence' => (int) Setting::get('order.minimum_pence', 2000),
                'lines' => array_map(fn ($l) => [
                    'product_id' => $l['product']->id,
                    'name' => $l['product']->name,
                    'quantity' => $l['quantity'],
                    'unit_price_pence' => $l['unit_price']->pence,
                    'line_total_pence' => $l['line_total']->pence,
                ], $result['lines']),
            ],
        ]);
    }
}
