<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'projet_id',
        'nom',
        'description',
        'code',
        'responsable_id',
        'date_debut',
        'date_fin',
        'ordre',
        'status',
        'progression',
        'couleur',
        'metadata',
        'archived_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'metadata' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'is_overdue',
        'days_remaining',
        'tache_count',
    ];

    /**
     * Boot method - Auto-generate code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($activite) {
            if (!$activite->code) {
                $activite->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $latest = static::withTrashed()->latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $code = 'ACTIV-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Relationships
     */
    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // TODO: Uncomment when Tache model is created
    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeForProjet($query, $projetId)
    {
        return $query->where('projet_id', $projetId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('responsable_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('date_fin', '<', now())
            ->where('status', 'active')
            ->where('progression', '<', 100);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeOrderByPosition($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Accessors
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->date_fin || $this->status !== 'active') {
            return false;
        }

        return $this->date_fin->isPast() && $this->progression < 100;
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->date_fin || $this->status !== 'active') {
            return null;
        }

        return now()->diffInDays($this->date_fin, false);
    }

    public function getTacheCountAttribute(): int
    {
        // TODO: Implement when Tache model is created
        // return $this->taches()->count();
        return 0;
    }

    /**
     * Calculate activity progression based on tasks
     */
    public function calculateProgression(): int
    {
        // TODO: Implement when Tache model is created
        // For now, return the manual progression value
        return $this->progression ?? 0;

        // Future implementation:
        // $totalTaches = $this->taches()->count();
        // if ($totalTaches === 0) {
        //     return 0;
        // }
        // $completedTaches = $this->taches()->where('statut', 'termine')->count();
        // return (int) round(($completedTaches / $totalTaches) * 100);
    }

    /**
     * Update activity progression automatically
     */
    public function updateProgression(): void
    {
        $this->update(['progression' => $this->calculateProgression()]);
    }

    /**
     * Actions
     */
    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'status' => 'active',
            'archived_at' => null,
        ]);
    }

    /**
     * Reorder activities
     */
    public static function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            static::where('id', $id)->update(['ordre' => $index]);
        }
    }
}
