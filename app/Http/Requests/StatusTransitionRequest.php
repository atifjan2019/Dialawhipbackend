<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'to_status' => ['required', Rule::in([
                Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_IN_PREP,
                Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED,
                Order::STATUS_FAILED, Order::STATUS_CANCELLED, Order::STATUS_REFUNDED,
            ])],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
