<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Domain\Orders\Services\OrderStatusService;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    /**
     * Statuses an assigned driver is allowed to set on an order.
     *
     * Once admin assigns the rider, the rider takes the order through:
     *   confirmed  → in_prep         (start packing)
     *   in_prep    → out_for_delivery (start the run)
     *   out_for_delivery → delivered  (success)
     *                    → failed     (delivery failed)
     *   failed     → out_for_delivery (retry)
     *   any pre-delivery → cancelled  (rider cancels — e.g. customer not home)
     *
     * The OrderStatusService still enforces the StateMachine, so invalid
     * jumps (e.g. confirmed → delivered) are rejected.
     */
    private const DRIVER_ALLOWED_STATUSES = [
        Order::STATUS_IN_PREP,
        Order::STATUS_OUT_FOR_DELIVERY,
        Order::STATUS_DELIVERED,
        Order::STATUS_FAILED,
        Order::STATUS_CANCELLED,
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $deliveries = Order::where('assigned_driver_id', $request->user()->id)
            ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])
            ->with(['items', 'address', 'customer'])
            ->orderBy('scheduled_for')
            ->get();

        return OrderResource::collection($deliveries);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->assigned_driver_id === $request->user()->id, 403);

        return response()->json([
            'data' => new OrderResource($order->load(['items', 'address', 'customer', 'events.actor'])),
        ]);
    }

    public function transition(Request $request, Order $order, OrderStatusService $service): JsonResponse
    {
        abort_unless($order->assigned_driver_id === $request->user()->id, 403);

        $data = $request->validate([
            'to_status' => ['required', 'in:'.implode(',', self::DRIVER_ALLOWED_STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Cancellation requires the rider to record why — it lands on the order
        // event timeline so admins can see why the run didn't complete.
        if ($data['to_status'] === Order::STATUS_CANCELLED && trim((string) ($data['note'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'note' => 'Please provide a reason for cancelling this delivery.',
            ]);
        }

        // Same expectation for failed deliveries — the rider should explain.
        if ($data['to_status'] === Order::STATUS_FAILED && trim((string) ($data['note'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'note' => 'Please describe why the delivery failed.',
            ]);
        }

        $service->transition($order, $data['to_status'], $request->user(), $data['note'] ?? null);

        return response()->json([
            'data' => new OrderResource($order->fresh(['items', 'events.actor', 'address', 'customer'])),
        ]);
    }

    public function appendNote(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->assigned_driver_id === $request->user()->id, 403);

        $note = (string) $request->validate(['note' => ['required', 'string', 'max:500']])['note'];
        $existing = $order->driver_notes;
        $order->update([
            'driver_notes' => $existing
                ? $existing."\n[".now()->toIso8601String().'] '.$note
                : '['.now()->toIso8601String().'] '.$note,
        ]);

        return response()->json(['data' => new OrderResource($order)]);
    }
}
