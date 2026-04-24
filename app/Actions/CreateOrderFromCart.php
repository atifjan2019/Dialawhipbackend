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
     * @param  array<int, array{product_id: string, quantity: int, variant_id?: ?string, options?: array<mixed>}>  $items
     */
    public function execute(
        User $customer,
        array $items,
        Address $address,
        ?Carbon $scheduledFor = null,
        ?string $customerNotes = null,
        string $deliveryTier = 'standard',
        bool $statementOfUseAccepted = false,
        bool $n2oAgreementAccepted = false,
    ): Order {
        $priced = $this->pricing->priceCart($items, $address->postcode, $deliveryTier);

        $hasAgeRestricted = false;
        foreach ($priced['lines'] as $line) {
            if (($line['product']->is_age_restricted ?? false)) {
                $hasAgeRestricted = true;
                break;
            }
        }

        if ($hasAgeRestricted && (! $statementOfUseAccepted || ! $n2oAgreementAccepted)) {
            throw new \InvalidArgumentException('Age-restricted items require the statement of use and N2O agreement to be accepted.');
        }

        if ($hasAgeRestricted && ! $customer->isVerified()) {
            throw new \InvalidArgumentException('Your account must be ID-verified before ordering age-restricted items.');
        }

        return DB::transaction(function () use ($customer, $address, $scheduledFor, $customerNotes, $priced, $deliveryTier, $statementOfUseAccepted, $n2oAgreementAccepted) {
            $order = Order::create([
                'reference' => $this->nextReference(),
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'status' => Order::STATUS_PENDING,
                'subtotal_pence' => $priced['subtotal']->pence,
                'delivery_fee_pence' => $priced['delivery_fee']->pence,
                'vat_pence' => $priced['vat']->pence,
                'total_pence' => $priced['total']->pence,
                'delivery_tier' => $deliveryTier,
                'statement_of_use_accepted' => $statementOfUseAccepted,
                'n2o_agreement_accepted' => $n2oAgreementAccepted,
                'scheduled_for' => $scheduledFor,
                'customer_notes' => $customerNotes,
            ]);

            foreach ($priced['lines'] as $line) {
                /** @var \App\Models\Product $product */
                $product = $line['product'];
                /** @var \App\Models\ProductVariant|null $variant */
                $variant = $line['variant'] ?? null;

                $snapshot = $product->toOrderSnapshot();
                if ($variant) {
                    $snapshot['variant'] = $variant->toSnapshot();
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'variant_label' => $variant?->label,
                    'product_snapshot_json' => $snapshot,
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
