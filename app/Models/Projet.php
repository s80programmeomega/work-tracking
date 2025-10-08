<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Projet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'code',
        'date_debut',
        'date_fin',
        'responsable_id',
        'status',
        'visibility',
        'couleur',
        'budget',
        'progression',
        'is_template',
        'is_favorite',
        'objectifs',
        'metadata',
        'archived_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'budget' => 'decimal:2',
        'progression' => 'integer',
        'is_template' => 'boolean',
        'is_favorite' => 'boolean',
        'metadata' => 'array',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'is_overdue',
        'days_remaining',
        'member_count',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($projet) {
            if (empty($projet->code)) {
                $projet->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate unique project code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            $latestProjet = static::withTrashed()->latest('id')->first();
            $nextId = $latestProjet ? $latestProjet->id + 1 : 1;
            $code = 'PROJ-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Relationships
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projet_user')
            ->withPivot(['role', 'can_edit', 'can_delete', 'can_invite'])
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProjetTag::class, 'projet_projet_tag')
            ->withTimestamps();
    }

    // TODO: Uncomment when Activite model is created
    // public function activites(): HasMany
    // {
    //     return $this->hasMany(Activite::class);
    // }

    // TODO: Uncomment when Tache model is created
    // public function taches(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Tache::class, Activite::class);
    // }

    /**
     * Calculate project progression based on tasks
     * Returns percentage of completed tasks
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
     * Update project progression automatically
     */
    public function updateProgression(): void
    {
        $this->update(['progression' => $this->calculateProgression()]);
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    public function scopeTeam($query)
    {
        return $query->where('visibility', 'team');
    }

    public function scopeTemplate($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('date_fin', '<', now())
            ->whereNotIn('status', ['completed', 'archived']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('responsable_id', $userId)
                ->orWhereHas('members', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nom', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Accessors
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->date_fin || in_array($this->status, ['completed', 'archived'])) {
            return false;
        }

        return $this->date_fin->isPast();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->date_fin || in_array($this->status, ['completed', 'archived'])) {
            return null;
        }

        return now()->diffInDays($this->date_fin, false);
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Helper Methods
     */
    public function isResponsable(User $user): bool
    {
        return $this->responsable_id === $user->id;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function getMemberRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    public function canUserEdit(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_edit ?? false;
    }

    public function canUserDelete(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_delete ?? false;
    }

    public function canUserInvite(User $user): bool
    {
        if ($this->isResponsable($user)) {
            return true;
        }

        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->can_invite ?? false;
    }

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

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'progression' => 100,
        ]);
    }
}
