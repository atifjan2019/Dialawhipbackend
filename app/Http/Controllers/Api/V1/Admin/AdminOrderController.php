<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Orders\Services\OrderStatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatusTransitionRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()->with(['customer', 'driver', 'address']);

        if ($status = $request->query('filter.status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('filter.search')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', $term)
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        $sort = $request->query('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if (in_array($column, ['created_at', 'total_pence', 'status', 'reference'], true)) {
            $query->orderBy($column, $direction);
        }

        $orders = $query->paginate(min((int) $request->query('limit', 25), 100));

        return OrderResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => new OrderResource(
                $order->load(['items', 'address', 'events.actor', 'customer', 'driver'])
            ),
        ]);
    }

    public function transition(StatusTransitionRequest $request, Order $order, OrderStatusService $service): JsonResponse
    {
        $service->transition($order, $request->input('to_status'), $request->user(), $request->input('note'));

        return response()->json([
            'data' => new OrderResource($order->fresh(['items', 'events.actor'])),
        ]);
    }

    public function assign(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'driver_id' => ['required', 'string', 'exists:users,id'],
        ]);

        $driver = User::find($request->input('driver_id'));
        if (! $driver || ! $driver->isDriver()) {
            throw ValidationException::withMessages(['driver_id' => 'User is not a driver.']);
        }

        $order->update(['assigned_driver_id' => $driver->id]);

        return response()->json(['data' => new OrderResource($order->load('driver'))]);
    }
}
