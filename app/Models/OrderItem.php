<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'variant_label', 'product_snapshot_json',
        'quantity', 'unit_price_pence', 'line_total_pence', 'options_json',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot_json' => 'array',
            'options_json' => 'array',
            'quantity' => 'integer',
            'unit_price_pence' => 'integer',
            'line_total_pence' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
