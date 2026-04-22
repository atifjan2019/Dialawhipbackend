<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Exceptions\InvalidStateTransitionException;
use App\Domain\Orders\StateMachine;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function transition(Order $order, string $toStatus, ?User $actor = null, ?string $note = null): Order
    {
        $fromStatus = $order->status;

        if (! StateMachine::allowed($fromStatus, $toStatus)) {
            throw new InvalidStateTransitionException($fromStatus, $toStatus);
        }

        return DB::transaction(function () use ($order, $fromStatus, $toStatus, $actor, $note) {
            $order->update(['status' => $toStatus]);

            OrderEvent::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_user_id' => $actor?->id,
                'note' => $note,
            ]);

            OrderStatusChanged::dispatch($order->fresh(), $fromStatus, $toStatus, $actor, $note);

            return $order;
        });
    }

    public function recordInitial(Order $order, ?User $actor = null): void
    {
        OrderEvent::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => $order->status,
            'actor_user_id' => $actor?->id,
            'note' => 'Order created',
        ]);
    }
}
