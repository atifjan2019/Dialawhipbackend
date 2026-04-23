<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ServiceArea extends Model
{
    use HasUlids;

    protected $fillable = [
        'postcode_prefix', 'delivery_fee_pence', 'is_active',
        'eta_standard_minutes', 'eta_priority_minutes',
        'priority_fee_pence', 'super_fee_pence',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee_pence' => 'integer',
            'eta_standard_minutes' => 'integer',
            'eta_priority_minutes' => 'integer',
            'priority_fee_pence' => 'integer',
            'super_fee_pence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function setPostcodePrefixAttribute(string $value): void
    {
        $this->attributes['postcode_prefix'] = strtoupper(preg_replace('/\s+/', '', $value));
    }

    public static function normalisePostcode(string $postcode): string
    {
        return strtoupper(preg_replace('/\s+/', '', $postcode));
    }

    public static function findForPostcode(string $postcode): ?self
    {
        $normalised = self::normalisePostcode($postcode);
        return self::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (self $area) => str_starts_with($normalised, $area->postcode_prefix));
    }
}
