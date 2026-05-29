<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property float $total_score SQL alias from aggregate queries
 * @property int $decision_count SQL alias from aggregate queries
 */
class EvaluationScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'periode_start',
        'periode_end',
        'critere',
        'valeur',
        'meta',
    ];

    protected $casts = [
        'periode_start' => 'date',
        'periode_end' => 'date',
        'valeur' => 'decimal:2',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: rows whose period bucket falls within the given date range.
     * Used for the default monthly dashboard view.
     */
    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->where('periode_start', '>=', $start)
            ->where('periode_end', '<=', $end);
    }

    /**
     * Scope: rows whose decision happened between the given timestamps.
     * Used for custom date ranges picked in the UI.
     */
    public function scopeDecidedBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
