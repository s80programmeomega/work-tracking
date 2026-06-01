<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property int $attempts
 * @property bool $used
 * @property Carbon $expires_at
 * @property Carbon $created_at
 */
class TwoFactorEmailCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'code',
        'attempts',
        'used',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= 5;
    }
}
