<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use Stripe\StripeClient;

/**
 * Pulls payment details (card brand/last4, receipt URL, paid timestamp,
 * refund info) from Stripe and persists them onto the Order. The Stripe
 * webhook calls this when checkout.session.completed arrives, and the
 * admin "refresh" endpoint calls it on demand.
 */
class OrderPaymentSync
{
    public function __construct(private StripeClient $stripe) {}

    public function syncFromOrder(Order $order): Order
    {
        $update = [];

        // 1. Resolve the payment intent id, expanding the checkout session
        //    if we only have the session id.
        $paymentIntentId = $order->stripe_payment_intent_id;

        if (! $paymentIntentId && $order->stripe_session_id) {
            try {
                $session = $this->stripe->checkout->sessions->retrieve($order->stripe_session_id);
                $paymentIntentId = is_string($session->payment_intent)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null);
                if ($paymentIntentId) {
                    $update['stripe_payment_intent_id'] = $paymentIntentId;
                }
            } catch (\Throwable $e) {
                // ignore: leave the order untouched if Stripe is unreachable
            }
        }

        if (! $paymentIntentId) {
            return $order;
        }

        // 2. Pull the payment intent + latest charge for full payment detail.
        try {
            $intent = $this->stripe->paymentIntents->retrieve(
                $paymentIntentId,
                ['expand' => ['latest_charge', 'latest_charge.payment_method_details', 'latest_charge.refunds']],
            );
        } catch (\Throwable) {
            return $order;
        }

        if ($intent->status === 'succeeded') {
            $update['paid_at'] = $update['paid_at'] ?? ($order->paid_at ?? now());
            $update['amount_paid_pence'] = $intent->amount_received ?? $intent->amount;
            $update['payment_currency'] = strtoupper((string) $intent->currency);
        }

        $charge = $intent->latest_charge;
        if ($charge && is_object($charge)) {
            $details = $charge->payment_method_details ?? null;
            if ($details && isset($details->card)) {
                $update['card_brand'] = $details->card->brand ?? null;
                $update['card_last4'] = $details->card->last4 ?? null;
                $update['payment_method_type'] = 'card';
            } elseif ($details && isset($details->type)) {
                $update['payment_method_type'] = (string) $details->type;
            }
            $update['receipt_url'] = $charge->receipt_url ?? null;

            // Refunds
            $refunds = $charge->refunds->data ?? [];
            if (! empty($refunds)) {
                $latest = $refunds[0];
                $update['refund_id'] = $latest->id ?? null;
                $update['refunded_at'] = isset($latest->created)
                    ? \Illuminate\Support\Carbon::createFromTimestamp((int) $latest->created)
                    : now();
                $totalRefunded = 0;
                foreach ($refunds as $r) {
                    $totalRefunded += (int) ($r->amount ?? 0);
                }
                $update['amount_refunded_pence'] = $totalRefunded;
            }
        }

        if (! empty($update)) {
            $order->fill($update)->save();
        }

        return $order->fresh() ?? $order;
    }
}
