<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'brand', 'slug', 'description', 'price_pence',
        'image_url', 'gallery_urls', 'options_json', 'short_spec',
        'is_active', 'is_age_restricted',
        'available_from', 'available_until', 'stock_count',
    ];

    protected function casts(): array
    {
        return [
            'price_pence' => 'integer',
            'stock_count' => 'integer',
            'options_json' => 'array',
            'short_spec' => 'array',
            'gallery_urls' => 'array',
            'is_active' => 'boolean',
            'is_age_restricted' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('price_pence');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
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
            'brand' => $this->brand,
            'slug' => $this->slug,
            'price_pence' => $this->price_pence,
            'image_url' => $this->image_url,
            'gallery_urls' => $this->gallery_urls ?? [],
            'is_age_restricted' => (bool) $this->is_age_restricted,
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
