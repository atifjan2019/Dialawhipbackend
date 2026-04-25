<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PREP = 'in_prep';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const TIER_STANDARD = 'standard';
    public const TIER_PRIORITY = 'priority';
    public const TIER_SUPER = 'super';

    protected $fillable = [
        'reference', 'customer_id', 'address_id', 'assigned_driver_id',
        'status', 'subtotal_pence', 'delivery_fee_pence', 'vat_pence', 'total_pence',
        'delivery_tier', 'statement_of_use_accepted', 'n2o_agreement_accepted',
        'stripe_session_id', 'stripe_payment_intent_id',
        'paid_at', 'amount_paid_pence', 'payment_currency',
        'card_brand', 'card_last4', 'payment_method_type', 'receipt_url',
        'refund_id', 'refunded_at', 'amount_refunded_pence',
        'scheduled_for', 'customer_notes', 'driver_notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_pence' => 'integer',
            'delivery_fee_pence' => 'integer',
            'vat_pence' => 'integer',
            'total_pence' => 'integer',
            'amount_paid_pence' => 'integer',
            'amount_refunded_pence' => 'integer',
            'statement_of_use_accepted' => 'boolean',
            'n2o_agreement_accepted' => 'boolean',
            'scheduled_for' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Returns true when Stripe has confirmed payment for this order.
     * The webhook sets `paid_at` once `checkout.session.completed` arrives.
     */
    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
