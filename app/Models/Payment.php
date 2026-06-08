<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference
 * @property int $workspace_id
 * @property int $plan_id
 * @property int|null $user_id
 * @property string $provider
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property string|null $provider_reference
 * @property string|null $provider_token
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $paid_at
 */
class Payment extends Model
{
    use HasFactory;

    // Statuts du cycle de vie (chaînes, cohérentes avec subscription_status).
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    // Fournisseurs supportés.
    public const PROVIDER_MTN = 'mtn_momo';

    public const PROVIDER_ORANGE = 'orange_money';

    protected $fillable = [
        'reference',
        'workspace_id',
        'plan_id',
        'user_id',
        'provider',
        'amount',
        'currency',
        'status',
        'provider_reference',
        'provider_token',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Génère automatiquement la référence UUID à la création si absente.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment): void {
            if (empty($payment->reference)) {
                $payment->reference = (string) Str::uuid();
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Le paiement attend-il encore une confirmation du fournisseur ? */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }
}
