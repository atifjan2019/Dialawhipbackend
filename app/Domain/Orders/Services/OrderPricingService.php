<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Exceptions\BelowMinimumOrderException;
use App\Domain\Orders\Exceptions\PostcodeOutOfAreaException;
use App\Domain\Orders\Exceptions\ShopClosedException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Support\Money;

/**
 * @phpstan-type CartLine array{product_id: string, quantity: int, variant_id?: ?string, options?: array<mixed>}
 * @phpstan-type PricedLine array{product: Product, variant: ?ProductVariant, quantity: int, unit_price: Money, line_total: Money, options: array<mixed>}
 * @phpstan-type PriceResult array{lines: array<int, PricedLine>, subtotal: Money, delivery_fee: Money, vat: Money, total: Money, service_area: ?ServiceArea}
 */
class OrderPricingService
{
    /**
     * @param  array<int, CartLine>  $items
     */
    public function priceCart(array $items, ?string $postcode = null, string $tier = 'standard'): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        // Honour the admin's "Shop accepting orders" toggle. When the admin
        // turns this off, customers can still browse but cannot price or
        // place orders.
        $isOpen = Setting::get('order.is_open', true);
        if ($isOpen === false || $isOpen === 0 || $isOpen === '0') {
            throw new ShopClosedException();
        }

        $productIds = array_column($items, 'product_id');
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $variantIds = array_filter(array_column($items, 'variant_id'));
        $variants = $variantIds
            ? ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $lines = [];
        $subtotal = Money::zero();

        foreach ($items as $line) {
            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $product = $products->get($line['product_id'] ?? '');

            if (! $product) {
                throw new \InvalidArgumentException("Product {$line['product_id']} unavailable.");
            }

            $variant = null;
            if (! empty($line['variant_id'])) {
                $variant = $variants->get($line['variant_id']);
                if (! $variant || $variant->product_id !== $product->id || ! $variant->is_active) {
                    throw new \InvalidArgumentException("Variant {$line['variant_id']} unavailable for product {$product->id}.");
                }
            }

            $unit = Money::fromPence($variant ? (int) $variant->price_pence : (int) $product->price_pence);
            $lineTotal = $unit->times($quantity);
            $subtotal = $subtotal->plus($lineTotal);

            $lines[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
                'options' => $line['options'] ?? [],
            ];
        }

        $minimum = Money::fromPence((int) Setting::get('order.minimum_pence', (int) env('ORDER_MIN_TOTAL_PENCE', 2000)));
        if ($subtotal->pence < $minimum->pence) {
            throw new BelowMinimumOrderException($subtotal, $minimum);
        }

        $deliveryFee = Money::zero();
        $serviceArea = null;

        if ($postcode !== null) {
            $serviceArea = ServiceArea::findForPostcode($postcode);
            if (! $serviceArea) {
                throw new PostcodeOutOfAreaException($postcode);
            }
            $base = (int) $serviceArea->delivery_fee_pence;
            $surcharge = match ($tier) {
                'priority' => (int) ($serviceArea->priority_fee_pence ?? 500),
                'super' => (int) ($serviceArea->super_fee_pence ?? 1500),
                default => 0,
            };
            $deliveryFee = Money::fromPence($base + $surcharge);
        }

        // VAT is zero-rated for most cold catered food in the UK.
        // If the client needs to charge VAT, set 'vat.rate_bps' (basis points, e.g. 2000 = 20%) in settings.
        $vatRateBps = (int) Setting::get('vat.rate_bps', 0);
        $vat = Money::fromPence(intdiv($subtotal->pence * $vatRateBps, 10000));

        $total = $subtotal->plus($deliveryFee)->plus($vat);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'vat' => $vat,
            'total' => $total,
            'service_area' => $serviceArea,
        ];
    }
}
