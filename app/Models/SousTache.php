<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SousTache extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tache_id',
        'responsable_id',
        'titre',
        'description',
        'statut',
        'progression',
        'poids',
        'date_echeance',
        'validation_n0_required',
        'validation_n1_required',
        'validation_n2_required',
        'ordre',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'validation_n0_required' => 'boolean',
        'validation_n1_required' => 'boolean',
        'validation_n2_required' => 'boolean',
        'progression' => 'integer',
        'poids' => 'integer',
        'ordre' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['titre', 'statut', 'progression', 'poids'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function intervenants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sous_tache_user')
            ->withPivot(['can_edit', 'can_complete', 'statut_individuel', 'progression_individuelle'])
            ->withTimestamps();
    }

    // =========================================================================
    // BUSINESS RULES
    // =========================================================================

    /**
     * R2: Validate that sibling sous-taches weights sum <= 100 (or all are 0 = unweighted).
     * Throws InvalidArgumentException on violation (caught by controller → 422).
     */
    public static function enforceWeights(int $tacheId, int $poids, ?int $userId = null, ?int $excludeId = null): void
    {
        if ($poids === 0) {
            return;
        }

        $siblingsSum = static::query()
            ->where('tache_id', $tacheId)
            ->whereNull('deleted_at')
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->sum('poids');

        $total = $siblingsSum + $poids;

        if ($total > 100) {
            Log::warning('SousTache R2 violation: weights sum exceeds 100', [
                'user_id' => $userId,
                'tache_id' => $tacheId,
                'reason' => 'weights_sum_invalid',
                'sum' => $total,
            ]);

            throw new \InvalidArgumentException(__('sous_taches.errors.weights_sum_invalid'));
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function isOverdue(): bool
    {
        return $this->date_echeance
            && ! in_array($this->statut, ['termine', 'annule'])
            && $this->date_echeance->isPast();
    }

    public function scopeOrdered($query): void
    {
        $query->orderBy('ordre')->orderBy('created_at');
    }
}
