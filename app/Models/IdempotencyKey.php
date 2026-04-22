<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public const UPDATED_AT = null;

    protected $fillable = ['key', 'user_id', 'response_hash', 'response_status'];
}
