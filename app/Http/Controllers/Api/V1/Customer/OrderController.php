<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with(['items', 'address'])
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->query('limit', 25), 100));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        return response()->json([
            'data' => new OrderResource($order->load(['items', 'address', 'events.actor', 'driver'])),
        ]);
    }
}
