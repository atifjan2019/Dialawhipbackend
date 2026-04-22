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
            'scheduled_for' => $this->scheduled_for,
            'customer_notes' => $this->customer_notes,
            'driver_notes' => $this->driver_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'customer' => $this->whenLoaded('customer', fn () => new UserResource($this->customer)),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver ? new UserResource($this->driver) : null),
            'address' => $this->whenLoaded('address', fn () => $this->address ? new AddressResource($this->address) : null),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'events' => OrderEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
