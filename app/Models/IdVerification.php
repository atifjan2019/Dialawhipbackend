<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdVerification extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const DOC_PASSPORT = 'passport';
    public const DOC_DRIVING_LICENCE = 'driving_licence';
    public const DOC_RESIDENCY_CARD = 'residency_card';
    public const DOC_CITIZEN_CARD = 'citizen_card';
    public const DOC_MILITARY_ID = 'military_id';

    public const DOC_TYPES = [
        self::DOC_PASSPORT,
        self::DOC_DRIVING_LICENCE,
        self::DOC_RESIDENCY_CARD,
        self::DOC_CITIZEN_CARD,
        self::DOC_MILITARY_ID,
    ];

    protected $fillable = [
        'user_id', 'doc_type', 'file_path', 'mime_type', 'size_bytes',
        'status', 'reviewed_by', 'reviewed_at', 'rejection_reason', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
