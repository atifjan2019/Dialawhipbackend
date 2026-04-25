<?php

namespace App\Jobs;

use App\Domain\Orders\Services\OrderStatusService;
use App\Domain\Payments\Services\OrderPaymentSync;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public string $stripeEventId) {}

    public function handle(OrderStatusService $orders, OrderPaymentSync $payments): void
    {
        $event = StripeEvent::find($this->stripeEventId);
        if (! $event || $event->processed_at) {
            return;
        }

        $payload = $event->payload;
        $object = $payload['data']['object'] ?? [];

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object, $orders, $payments),
            'charge.refunded' => $this->handleRefunded($object, $orders, $payments),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($object, $orders),
            default => Log::info('Unhandled Stripe event', ['type' => $event->type, 'id' => $this->stripeEventId]),
        };

        $event->update(['processed_at' => now()]);
    }

    private function handleCheckoutCompleted(array $session, OrderStatusService $orders, OrderPaymentSync $payments): void
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

        // Mark paid immediately from the webhook payload (don't wait for the
        // live API call). The live sync below will fill in card brand/last4/receipt.
        if (($session['payment_status'] ?? null) === 'paid') {
            $order->update([
                'paid_at' => $order->paid_at ?? now(),
                'amount_paid_pence' => $session['amount_total'] ?? $order->total_pence,
                'payment_currency' => strtoupper((string) ($session['currency'] ?? 'gbp')),
            ]);
        }

        // Hydrate full payment details (card brand, last4, receipt URL).
        try {
            $payments->syncFromOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::warning('Stripe payment sync failed', ['order' => $order->id, 'err' => $e->getMessage()]);
        }

        if ($order->fresh()->status === Order::STATUS_PENDING) {
            $orders->transition($order->fresh(), Order::STATUS_CONFIRMED, null, 'Stripe payment confirmed');
        }
    }

    private function handleRefunded(array $charge, OrderStatusService $orders, OrderPaymentSync $payments): void
    {
        $paymentIntent = $charge['payment_intent'] ?? null;
        if (! $paymentIntent) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntent)->first();
        if (! $order) {
            return;
        }

        // Capture refund details onto the order.
        $order->update([
            'refunded_at' => isset($charge['created'])
                ? Carbon::createFromTimestamp((int) $charge['created'])
                : now(),
            'amount_refunded_pence' => $charge['amount_refunded'] ?? null,
        ]);

        try {
            $payments->syncFromOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::warning('Stripe refund sync failed', ['order' => $order->id, 'err' => $e->getMessage()]);
        }

        if ($order->fresh()->status === Order::STATUS_DELIVERED) {
            $orders->transition($order->fresh(), Order::STATUS_REFUNDED, null, 'Stripe refund processed');
        }
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
