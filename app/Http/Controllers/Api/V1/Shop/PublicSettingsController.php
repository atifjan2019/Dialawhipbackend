<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    private const PUBLIC_KEYS = [
        'business.name',
        'business.hours',
        'business.phone',
        'business.email',
        'business.address',
        'order.minimum_pence',
        'order.lead_time_hours',
        'order.is_open',
    ];

    public function index(): JsonResponse
    {
        $data = [];
        foreach (self::PUBLIC_KEYS as $key) {
            $data[$key] = Setting::get($key);
        }

        return response()->json(['data' => $data]);
    }
}
