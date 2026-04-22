<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'items.*.options' => ['nullable', 'array'],
            'address_id' => ['required', 'string', 'exists:addresses,id'],
            'scheduled_for' => ['nullable', 'date', 'after:+'.(int) env('ORDER_LEAD_TIME_HOURS', 24).' hours'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
