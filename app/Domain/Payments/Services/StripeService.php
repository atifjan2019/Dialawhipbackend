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
            $snapshot = $item->product_snapshot_json;
            return [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $item->unit_price_pence,
                    'product_data' => [
                        'name' => $snapshot['name'] ?? 'Catering item',
                    ],
                ],
            ];
        })->all();

        if ($order->delivery_fee_pence > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $order->delivery_fee_pence,
                    'product_data' => ['name' => 'Delivery fee'],
                ],
            ];
        }

        $frontend = rtrim((string) env('APP_FRONTEND_URL', 'http://localhost:3000'), '/');

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->customer->email,
            'success_url' => "$frontend/checkout/success?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => "$frontend/cart",
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
