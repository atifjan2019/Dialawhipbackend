<?php

namespace App\Listeners;

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInventory implements ShouldQueue
{
    public int $tries = 3;

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->toStatus !== Order::STATUS_CONFIRMED) {
            return;
        }

        foreach ($event->order->items()->whereNotNull('product_id')->get() as $item) {
            Product::where('id', $item->product_id)
                ->whereNotNull('stock_count')
                ->decrement('stock_count', $item->quantity);
        }
    }
}
