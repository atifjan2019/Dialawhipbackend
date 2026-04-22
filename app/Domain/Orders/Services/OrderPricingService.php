<?php

namespace App\Domain\Orders\Services;

use App\Domain\Orders\Exceptions\BelowMinimumOrderException;
use App\Domain\Orders\Exceptions\PostcodeOutOfAreaException;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Support\Money;

/**
 * @phpstan-type CartLine array{product_id: string, quantity: int, options?: array<mixed>}
 * @phpstan-type PricedLine array{product: Product, quantity: int, unit_price: Money, line_total: Money, options: array<mixed>}
 * @phpstan-type PriceResult array{lines: array<int, PricedLine>, subtotal: Money, delivery_fee: Money, vat: Money, total: Money, service_area: ?ServiceArea}
 */
class OrderPricingService
{
    /**
     * @param  array<int, CartLine>  $items
     */
    public function priceCart(array $items, ?string $postcode = null): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        $productIds = array_column($items, 'product_id');
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $lines = [];
        $subtotal = Money::zero();

        foreach ($items as $line) {
            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $product = $products->get($line['product_id'] ?? '');

            if (! $product) {
                throw new \InvalidArgumentException("Product {$line['product_id']} unavailable.");
            }

            $unit = Money::fromPence($product->price_pence);
            $lineTotal = $unit->times($quantity);
            $subtotal = $subtotal->plus($lineTotal);

            $lines[] = [
                'product' => $product,
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
            $deliveryFee = Money::fromPence($serviceArea->delivery_fee_pence);
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
