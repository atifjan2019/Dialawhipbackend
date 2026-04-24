<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeService
{
    public function __construct(private StripeClient $stripe) {}

    public static function make(): self
    {
        return new self(new StripeClient(config('services.stripe.secret')));
    }

    public function createCheckoutSession(Order $order): Session
    {
        $order->loadMissing(['items', 'customer']);

        $lineItems = $order->items->map(function ($item) {
            $snapshot = $item->product_snapshot_json ?? [];
            $name = $snapshot['name'] ?? 'Catering item';
            if (! empty($item->variant_label)) {
                $name .= ' — ' . $item->variant_label;
            }
            return [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $item->unit_price_pence,
                    'product_data' => ['name' => $name],
                ],
            ];
        })->all();

        if ($order->delivery_fee_pence > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $order->delivery_fee_pence,
                    'product_data' => ['name' => 'Delivery fee (' . ($order->delivery_tier ?? 'standard') . ')'],
                ],
            ];
        }

        if ($order->vat_pence > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $order->vat_pence,
                    'product_data' => ['name' => 'VAT'],
                ],
            ];
        }

        // Prefer explicit STRIPE_* URLs; otherwise fall back to FRONTEND_URL.
        $frontend = rtrim((string) env('FRONTEND_URL', env('APP_FRONTEND_URL', 'http://localhost:3000')), '/');
        $successUrl = (string) env('STRIPE_SUCCESS_URL', "$frontend/checkout/success?session_id={CHECKOUT_SESSION_ID}");
        $cancelUrl = (string) env('STRIPE_CANCEL_URL', "$frontend/checkout?cancelled=1");

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->customer->email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => $order->id,
                    'order_reference' => $order->reference,
                ],
            ],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    public function refund(Order $order): void
    {
        if (! $order->stripe_payment_intent_id) {
            throw new \RuntimeException('Order has no payment intent to refund.');
        }

        $this->stripe->refunds->create(['payment_intent' => $order->stripe_payment_intent_id]);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }
}
