<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\ServiceArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAreaController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $postcode = $request->string('postcode');
        if ($postcode === '') {
            return response()->json(['message' => 'postcode required'], 422);
        }

        $area = ServiceArea::findForPostcode((string) $postcode);

        return response()->json([
            'data' => [
                'postcode' => ServiceArea::normalisePostcode((string) $postcode),
                'available' => $area !== null,
                'postcode_prefix' => $area?->postcode_prefix,
                'delivery_fee_pence' => $area?->delivery_fee_pence,
                'eta_standard_minutes' => $area?->eta_standard_minutes,
                'eta_priority_minutes' => $area?->eta_priority_minutes,
                'priority_fee_pence' => $area?->priority_fee_pence,
                'super_fee_pence' => $area?->super_fee_pence,
            ],
        ]);
    }
}
