<?php

namespace App\Domain\Orders;

use App\Models\Order;

final class StateMachine
{
    /** @var array<string, array<int, string>> */
    public const TRANSITIONS = [
        Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
        Order::STATUS_CONFIRMED => [Order::STATUS_IN_PREP, Order::STATUS_CANCELLED],
        Order::STATUS_IN_PREP => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_CANCELLED],
        Order::STATUS_OUT_FOR_DELIVERY => [Order::STATUS_DELIVERED, Order::STATUS_FAILED],
        Order::STATUS_FAILED => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_CANCELLED],
        Order::STATUS_DELIVERED => [Order::STATUS_REFUNDED],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_REFUNDED => [],
    ];

    public static function allowed(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** @return array<int, string> */
    public static function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function isTerminal(string $status): bool
    {
        return self::TRANSITIONS[$status] === [];
    }
}
