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
            'items.*.variant_id' => ['nullable', 'string', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'items.*.options' => ['nullable', 'array'],
            'address_id' => ['required', 'string', 'exists:addresses,id'],
            'delivery_tier' => ['nullable', 'string', 'in:standard,priority,super'],
            'scheduled_for' => ['nullable', 'date'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'statement_of_use_accepted' => ['nullable', 'boolean'],
            'n2o_agreement_accepted' => ['nullable', 'boolean'],
        ];
    }
}
