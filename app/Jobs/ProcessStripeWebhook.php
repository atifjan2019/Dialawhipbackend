<?php

namespace App\Jobs;

use App\Domain\Orders\Services\OrderStatusService;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public string $stripeEventId) {}

    public function handle(OrderStatusService $orders): void
    {
        $event = StripeEvent::find($this->stripeEventId);
        if (! $event || $event->processed_at) {
            return;
        }

        $payload = $event->payload;
        $object = $payload['data']['object'] ?? [];

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object, $orders),
            'charge.refunded' => $this->handleRefunded($object, $orders),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($object, $orders),
            default => Log::info('Unhandled Stripe event', ['type' => $event->type, 'id' => $this->stripeEventId]),
        };

        $event->update(['processed_at' => now()]);
    }

    private function handleCheckoutCompleted(array $session, OrderStatusService $orders): void
    {
        $orderId = $session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null;
        if (! $orderId) {
            return;
        }

        $order = Order::find($orderId);
        if (! $order) {
            return;
        }

        if ($pi = $session['payment_intent'] ?? null) {
            $order->update(['stripe_payment_intent_id' => $pi]);
        }

        if ($order->status === Order::STATUS_PENDING) {
            $orders->transition($order, Order::STATUS_CONFIRMED, null, 'Stripe payment confirmed');
        }
    }

    private function handleRefunded(array $charge, OrderStatusService $orders): void
    {
        $paymentIntent = $charge['payment_intent'] ?? null;
        if (! $paymentIntent) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntent)->first();
        if (! $order || $order->status !== Order::STATUS_DELIVERED) {
            return;
        }

        $orders->transition($order, Order::STATUS_REFUNDED, null, 'Stripe refund processed');
    }

    private function handlePaymentFailed(array $intent, OrderStatusService $orders): void
    {
        $orderId = $intent['metadata']['order_id'] ?? null;
        if (! $orderId) {
            return;
        }

        $order = Order::find($orderId);
        if ($order && $order->status === Order::STATUS_PENDING) {
            $orders->transition($order, Order::STATUS_CANCELLED, null, 'Stripe payment failed');
        }
    }
}
