<?php

namespace App\Domain\Orders\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $fromStatus,
        public string $toStatus,
        public ?User $actor = null,
        public ?string $note = null,
    ) {}
}
