<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Jobs\SendOrderEmail;
use App\Jobs\SendOrderSms;
use App\Models\Order;

class SendOrderStatusNotifications
{
    private const TEMPLATE_BY_STATUS = [
        Order::STATUS_CONFIRMED => 'order_confirmed',
        Order::STATUS_OUT_FOR_DELIVERY => 'out_for_delivery',
        Order::STATUS_DELIVERED => 'delivered',
        Order::STATUS_CANCELLED => 'cancelled',
        Order::STATUS_REFUNDED => 'refunded',
    ];

    private const SMS_STATUSES = [
        Order::STATUS_CONFIRMED,
        Order::STATUS_OUT_FOR_DELIVERY,
        Order::STATUS_DELIVERED,
    ];

    public function handle(OrderStatusChanged $event): void
    {
        $template = self::TEMPLATE_BY_STATUS[$event->toStatus] ?? null;
        if (! $template) {
            return;
        }

        SendOrderEmail::dispatch($event->order->id, $template)->onQueue('notifications');

        if (in_array($event->toStatus, self::SMS_STATUSES, true)) {
            SendOrderSms::dispatch($event->order->id, $template)->onQueue('notifications');
        }
    }
}
