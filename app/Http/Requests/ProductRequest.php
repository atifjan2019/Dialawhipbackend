<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        // Admin routes pass the ULID as a string; customer-facing routes pass a model.
        $routeParam = $this->route('product');
        $productId = is_string($routeParam) ? $routeParam : $routeParam?->id;

        return [
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price_pence' => ['required', 'integer', 'min:0', 'max:1000000'],
            'image_url' => ['nullable', 'url'],
            'options' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'available_from' => ['nullable', 'date_format:H:i'],
            'available_until' => ['nullable', 'date_format:H:i'],
            'stock_count' => ['nullable', 'integer', 'min:0'],

            // Variants: optional pack-size / bundle pricing rows.
            'variants' => ['nullable', 'array', 'max:50'],
            'variants.*.id' => ['nullable', 'string', 'exists:product_variants,id'],
            'variants.*.label' => ['required_with:variants.*', 'string', 'max:120'],
            'variants.*.price_pence' => ['required_with:variants.*', 'integer', 'min:0', 'max:10000000'],
            'variants.*.qty_multiplier' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'variants.*.stock_count' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'],
            'variants.*.sort_order' => ['nullable', 'integer'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
