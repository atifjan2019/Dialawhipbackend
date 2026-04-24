<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Domain\Settings\SettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->publicPayload(),
        ]);
    }
}
