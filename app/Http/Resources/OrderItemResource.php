<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $snapshot = $this->product_snapshot_json ?? [];

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'variant_label' => $this->variant_label,
            'name' => $snapshot['name'] ?? 'Item',
            'image_url' => $snapshot['image_url'] ?? null,
            'quantity' => (int) $this->quantity,
            'unit_price_pence' => (int) $this->unit_price_pence,
            'line_total_pence' => (int) $this->line_total_pence,
            'options' => $this->options_json,
        ];
    }
}
