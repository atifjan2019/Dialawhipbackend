<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_pence' => (int) $this->price_pence,
            'image_url' => $this->image_url,
            'options' => $this->options_json,
            'short_spec' => $this->short_spec,
            'is_active' => (bool) $this->is_active,
            'is_age_restricted' => (bool) $this->is_age_restricted,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'stock_count' => $this->stock_count,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
        ];
    }
}
