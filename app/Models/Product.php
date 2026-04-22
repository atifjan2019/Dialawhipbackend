<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price_pence',
        'image_url', 'options_json', 'is_active',
        'available_from', 'available_until', 'stock_count',
    ];

    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'stock_count' => 'integer',
            'options_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function toOrderSnapshot(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price_pence' => $this->price_pence,
            'image_url' => $this->image_url,
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
