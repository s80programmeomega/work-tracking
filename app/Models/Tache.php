<?php

namespace App\Models;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Tache extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activite_id',
        'parent_tache_id',
        'titre',
        'code',
        'description',
        'objectif',
        'indicateurs_resultats',
        'statut',
        'priorite',
        'echeance',
        'date_debut',
        'date_fin_reelle',
        'taux_realisation',
        'validation_n1_required',
        'validation_n2_required',
        'validated_n1_by',
        'validated_n1_at',
        'validated_n2_by',
        'validated_n2_at',
        'commentaire_n1',
        'commentaire_n2',
        'verrou_reevaluation',
        'commentaire',
        'position',
        'couleur',
        'cover_image',
        'metadata',
        'estimated_hours',
        'actual_hours',
        'archive_status',
        'archived_at',
        'created_by',
        'visibility',
        'week_number',
        'year',
    ];

    protected $casts = [
        'statut' => TacheStatut::class,
        'priorite' => TachePriorite::class,
        'echeance' => 'date',
        'date_debut' => 'date',
        'date_fin_reelle' => 'date',
        'validation_n1_required' => 'boolean',
        'validation_n2_required' => 'boolean',
        'validated_n1_at' => 'datetime',
        'validated_n2_at' => 'datetime',
        'verrou_reevaluation' => 'boolean',
        'metadata' => 'array',
        'taux_realisation' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'archived_at' => 'datetime',
        'visibility' => 'string',
        'week_number' => 'integer',
        'year' => 'integer',
    ];
 

    protected $appends = [
        'is_overdue',
        'validation_status',
        'can_be_completed',
        'time_variance_percentage',
    ];

    protected $with = ['activite', 'labels'];
    
       // ==================== ACTIVITY LOG ====================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['titre', 'statut', 'priorite', 'taux_realisation', 'validated_n1_at', 'validated_n2_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tache) {
            if (!$tache->code) {
                $tache->code = static::generateUniqueCode();
            }
            
            // ✅ Auto-définir semaine et année
            if (!$tache->week_number && $tache->date_debut) {
                $tache->week_number = $tache->date_debut->weekOfYear;
                $tache->year = $tache->date_debut->year;
            }
        });
    }

    /**
     * Generate unique task code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $latest = static::withTrashed()->latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $code = 'TASK-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    // ==================== RELATIONSHIPS ====================

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tache_user')
            ->withPivot(['role', 'can_edit', 'can_complete', 'can_validate'])
            ->withTimestamps();
    }

    public function validatedN1By(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_n1_by');
    }

    public function validatedN2By(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_n2_by');
    }

    public function resultats(): HasMany
    {
        return $this->hasMany(TacheResultat::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_tache')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Tache::class, 'parent_tache_id');
    }

    public function sousTaches(): HasMany
    {
        return $this->hasMany(Tache::class, 'parent_tache_id');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Tache::class,
            'tache_dependencies',
            'tache_id',
            'depends_on_tache_id'
        )->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== HELPER METHODS ====================
/**
     * ✅ Vérifier si un utilisateur est assigné
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->assignees()->where('user_id', $user->id)->exists();
    }

    /**
     * ✅ Marquer la tâche comme terminée
     */
    public function markAsCompleted(User $user): void
    {
        if ($this->statut === TacheStatut::TERMINE) {
            throw new \Exception('La tâche est déjà marquée comme terminée');
        }

        $this->update([
            'statut' => TacheStatut::TERMINE,
            'taux_realisation' => 100,
            'date_fin_reelle' => now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->log('Tâche marquée comme terminée');
    }

      /**
     * ✅ Valider N1 avec traçabilité
     */
    public function validateN1(User $validator, ?string $commentaire = null): void
    {
        if (!$this->validation_n1_required) {
            throw new \Exception('Cette tâche ne nécessite pas de validation N1');
        }

        if ($this->validated_n1_at) {
            throw new \Exception('Cette tâche est déjà validée (N1)');
        }

        if ($this->statut !== TacheStatut::TERMINE) {
            throw new \Exception('La tâche doit être terminée avant d\'être validée');
        }

        $this->update([
            'validated_n1_by' => $validator->id,
            'validated_n1_at' => now(),
            'commentaire_n1' => $commentaire,
        ]);

        activity()
            ->causedBy($validator)
            ->performedOn($this)
            ->withProperties(['commentaire' => $commentaire])
            ->log('Validation N1 effectuée');

        // TODO: Notifier responsable projet pour N2
    }

    /**
     * ✅ Valider N2 avec traçabilité
     */
    public function validateN2(User $validator, ?string $commentaire = null): void
    {
        if (!$this->validation_n2_required) {
            throw new \Exception('Cette tâche ne nécessite pas de validation N2');
        }

        if (!$this->validated_n1_at) {
            throw new \Exception('La validation N1 doit être effectuée en premier');
        }

        if ($this->validated_n2_at) {
            throw new \Exception('Cette tâche est déjà validée (N2)');
        }

        $this->update([
            'validated_n2_by' => $validator->id,
            'validated_n2_at' => now(),
            'commentaire_n2' => $commentaire,
        ]);

        activity()
            ->causedBy($validator)
            ->performedOn($this)
            ->withProperties(['commentaire' => $commentaire])
            ->log('Validation N2 effectuée - Tâche entièrement validée');

        // TODO: Notifier tous les participants
    }

    

    /**
     * ✅ Vérifier si la tâche est entièrement validée
     */
    public function isFullyValidated(): bool
    {
        $n1Valid = !$this->validation_n1_required || $this->validated_n1_at !== null;
        $n2Valid = !$this->validation_n2_required || $this->validated_n2_at !== null;

        return $n1Valid && $n2Valid;
    }

     /**
     * ✅ Archiver/Désarchiver
     */
    public function archive(): void
    {
        $this->update([
            'archive_status' => 'archived',
            'archived_at' => now(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'archive_status' => 'active',
            'archived_at' => null,
        ]);
    }

    /**
     * ✅ Vérifier si l'utilisateur peut voir la tâche
     */
    public function isAccessibleBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if ($this->activite && $this->activite->projet && 
            $this->activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Assigné à la tâche
        if ($this->assignees()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Membre de l'activité avec permissions
        if ($this->activite && $this->activite->membres()
            ->where('user_id', $user->id)
            ->wherePivot('can_edit_tasks', true)
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Vérifier si l'utilisateur peut modifier la tâche
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        // Assigné avec permission d'édition
        $assignment = $this->assignees()->where('user_id', $user->id)->first();
        if ($assignment && ($assignment->pivot->can_edit ?? false)) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Vérifier si l'utilisateur peut valider N1
     */
    public function canBeValidatedN1By(User $user): bool
    {
        if (!$this->validation_n1_required) {
            return false;
        }

        // Déjà validé N1
        if ($this->validated_n1_at) {
            return false;
        }

        // Responsable de l'activité
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        // Membre de l'activité avec permission de validation
        if ($this->activite && $this->activite->membres()
            ->where('user_id', $user->id)
            ->wherePivot('can_validate_results', true)
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * ✅ Vérifier si l'utilisateur peut valider N2
     */
    public function canBeValidatedN2By(User $user): bool
    {
        if (!$this->validation_n2_required) {
            return false;
        }

        // N1 doit être validé d'abord
        if (!$this->validated_n1_at) {
            return false;
        }

        // Déjà validé N2
        if ($this->validated_n2_at) {
            return false;
        }

        // Responsable du projet
        if ($this->activite && $this->activite->projet && 
            $this->activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Super admin du workspace
        if ($this->activite && $this->activite->projet && 
            $this->activite->projet->workspace) {
            $workspace = $this->activite->projet->workspace;
            if ($workspace->owner_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ Vérifier si l'utilisateur peut marquer comme terminé
     */
    public function canBeCompletedBy(User $user): bool
    {
        // Assigné avec permission de complétion
        $assignment = $this->assignees()->where('user_id', $user->id)->first();
        if ($assignment && ($assignment->pivot->can_complete ?? false)) {
            return true;
        }

        // Responsable de l'activité
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        return false;
    }
 
    // ==================== ACCESSORS ====================

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->echeance || $this->statut === TacheStatut::TERMINE) {
            return false;
        }

        return $this->echeance->isPast();
    }

    public function getValidationStatusAttribute(): string
    {
        if ($this->validated_n2_at) {
            return 'fully_validated';
        }
        
        if ($this->validated_n1_at) {
            return 'validated_n1';
        }
        
        if ($this->statut === TacheStatut::TERMINE) {
            return 'pending_validation';
        }
        
        return 'not_validated';
    }


    public function getCanBeCompletedAttribute(): bool
    {
        // Vérifier si toutes les sous-tâches sont terminées
        return $this->sousTaches()->where('statut', '!=', TacheStatut::TERMINE->value)->count() === 0;
    }

    public function getTimeVariancePercentageAttribute(): ?float
    {
        if (!$this->estimated_hours || !$this->actual_hours) {
            return null;
        }

        return round((($this->actual_hours - $this->estimated_hours) / $this->estimated_hours) * 100, 2);
    }


    // ==================== SCOPES ====================

    public function scopeForWeek($query, int $weekNumber, int $year)
    {
        return $query->where('week_number', $weekNumber)
                    ->where('year', $year);
    }

     public function scopeForActivite($query, int $activiteId)
    {
        return $query->where('activite_id', $activiteId);
    }

    public function scopePendingValidationN1($query)
    {
        return $query->where('statut', TacheStatut::TERMINE)
                    ->where('validation_n1_required', true)
                    ->whereNull('validated_n1_at');
    }

    public function scopePendingValidationN2($query)
    {
        return $query->where('statut', TacheStatut::TERMINE)
                    ->where('validation_n2_required', true)
                    ->whereNotNull('validated_n1_at')
                    ->whereNull('validated_n2_at');
    }

    public function scopeOverdue($query)
    {
        return $query->where('echeance', '<', now())
                    ->where('statut', '!=', TacheStatut::TERMINE->value);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->whereHas('assignees', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

     public function scopeActive($query)
    {
        return $query->where('archive_status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('archive_status', 'archived');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('created_at', 'desc');
    }
}