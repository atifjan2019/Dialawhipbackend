<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'line1', 'line2', 'city', 'postcode',
        'latitude', 'longitude', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setPostcodeAttribute(string $value): void
    {
        $this->attributes['postcode'] = strtoupper(preg_replace('/\s+/', '', $value));
    }
}
