<?php

namespace App\Actions;

use App\Domain\Orders\Services\OrderPricingService;
use App\Domain\Orders\Services\OrderStatusService;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCart
{
    public function __construct(
        private OrderPricingService $pricing,
        private OrderStatusService $status,
    ) {}

    /**
     * @param  array<int, array{product_id: string, quantity: int, options?: array<mixed>}>  $items
     */
    public function execute(
        User $customer,
        array $items,
        Address $address,
        ?Carbon $scheduledFor = null,
        ?string $customerNotes = null,
    ): Order {
        $priced = $this->pricing->priceCart($items, $address->postcode);

        return DB::transaction(function () use ($customer, $address, $scheduledFor, $customerNotes, $priced) {
            $order = Order::create([
                'reference' => $this->nextReference(),
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'status' => Order::STATUS_PENDING,
                'subtotal_pence' => $priced['subtotal']->pence,
                'delivery_fee_pence' => $priced['delivery_fee']->pence,
                'vat_pence' => $priced['vat']->pence,
                'total_pence' => $priced['total']->pence,
                'scheduled_for' => $scheduledFor,
                'customer_notes' => $customerNotes,
            ]);

            foreach ($priced['lines'] as $line) {
                /** @var \App\Models\Product $product */
                $product = $line['product'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_snapshot_json' => $product->toOrderSnapshot(),
                    'quantity' => $line['quantity'],
                    'unit_price_pence' => $line['unit_price']->pence,
                    'line_total_pence' => $line['line_total']->pence,
                    'options_json' => $line['options'] ?: null,
                ]);
            }

            $this->status->recordInitial($order, $customer);

            return $order->fresh(['items']);
        });
    }

    private function nextReference(): string
    {
        $prefix = env('ORDER_REFERENCE_PREFIX', 'CAT');
        $year = now()->format('Y');

        return DB::transaction(function () use ($prefix, $year) {
            $lastRef = Order::query()
                ->where('reference', 'like', "$prefix-$year-%")
                ->lockForUpdate()
                ->orderByDesc('reference')
                ->value('reference');

            $next = $lastRef
                ? ((int) substr($lastRef, strrpos($lastRef, '-') + 1)) + 1
                : 1;

            return sprintf('%s-%s-%04d', $prefix, $year, $next);
        });
    }
}
