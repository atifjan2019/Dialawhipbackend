<?php

namespace App\Http\Resources;

use App\Domain\Orders\StateMachine;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'allowed_transitions' => StateMachine::allowedFrom($this->status),
            'subtotal_pence' => (int) $this->subtotal_pence,
            'delivery_fee_pence' => (int) $this->delivery_fee_pence,
            'vat_pence' => (int) $this->vat_pence,
            'total_pence' => (int) $this->total_pence,
            'delivery_tier' => $this->delivery_tier,
            'statement_of_use_accepted' => (bool) $this->statement_of_use_accepted,
            'n2o_agreement_accepted' => (bool) $this->n2o_agreement_accepted,
            'scheduled_for' => $this->scheduled_for,
            'customer_notes' => $this->customer_notes,
            'driver_notes' => $this->driver_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'payment' => $this->paymentBlock(),
            'customer' => $this->whenLoaded('customer', fn () => new UserResource($this->customer)),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver ? new UserResource($this->driver) : null),
            'address' => $this->whenLoaded('address', fn () => $this->address ? new AddressResource($this->address) : null),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'events' => OrderEventResource::collection($this->whenLoaded('events')),
        ];
    }

    /**
     * Build the payment summary surfaced to the admin / customer panels.
     *
     * @return array<string, mixed>
     */
    private function paymentBlock(): array
    {
        $isRefunded = $this->refunded_at !== null;
        $isPaid = $this->paid_at !== null;

        return [
            'status' => $isRefunded ? 'refunded' : ($isPaid ? 'paid' : 'unpaid'),
            'is_paid' => $isPaid,
            'is_refunded' => $isRefunded,
            'paid_at' => $this->paid_at,
            'amount_paid_pence' => $this->amount_paid_pence !== null ? (int) $this->amount_paid_pence : null,
            'currency' => $this->payment_currency,
            'card_brand' => $this->card_brand,
            'card_last4' => $this->card_last4,
            'method' => $this->payment_method_type,
            'receipt_url' => $this->receipt_url,
            'stripe_session_id' => $this->stripe_session_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'refund_id' => $this->refund_id,
            'refunded_at' => $this->refunded_at,
            'amount_refunded_pence' => $this->amount_refunded_pence !== null ? (int) $this->amount_refunded_pence : null,
        ];
    }
}
