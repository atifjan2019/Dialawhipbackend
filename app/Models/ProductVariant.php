<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasUlids;

    protected $fillable = [
        'product_id', 'label', 'price_pence', 'qty_multiplier',
        'stock_count', 'sku', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'qty_multiplier' => 'integer',
            'stock_count' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Snapshot fields copied onto order_items.options_json so the variant is preserved
     * even if it gets deleted or edited later.
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'variant_id' => $this->id,
            'label' => $this->label,
            'price_pence' => (int) $this->price_pence,
            'qty_multiplier' => (int) $this->qty_multiplier,
            'sku' => $this->sku,
        ];
    }
}
