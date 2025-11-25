<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TacheResultat extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
         'tache_id',
        'user_id',
        'is_individual',
        'resultats_attendus',
        'resultats_obtenus',
        'taux_realisation',
        'difficultes_rencontrees',
        'solutions_envisagees',
        'observations',
        'soumis_le',
        'valide_par_n1',
        'validateur_n1_id',
        'valide_le_n1',
        'commentaire_n1',
        'valide_par_n2',
        'validateur_n2_id',
        'valide_le_n2',
        'commentaire_n2',
    ];

    protected $casts = [
        'soumis_le' => 'datetime',
        'valide_le_n1' => 'datetime',
        'valide_le_n2' => 'datetime',
        'valide_par_n1' => 'boolean',
        'valide_par_n2' => 'boolean',
        'is_individual' => 'boolean',
        'taux_realisation' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'is_fully_validated',
        'validation_status',
    ];

    /**
     * Activity logging configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['resultats_obtenus', 'taux_realisation', 'valide_par_n1', 'valide_par_n2'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationships
     */
    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validateurN1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_n1_id');
    }

    public function validateurN2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_n2_id');
    }

    /**
     * Documents attached to results (polymorphic)
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Accessors
     */
    public function getIsFullyValidatedAttribute(): bool
    {
        // Si validation N1 et N2 requises
        if ($this->tache->validation_n1_required && $this->tache->validation_n2_required) {
            return $this->valide_par_n1 && $this->valide_par_n2;
        }
        
        // Si seulement N1 requise
        if ($this->tache->validation_n1_required) {
            return $this->valide_par_n1;
        }
        
        // Si seulement N2 requise (rare)
        if ($this->tache->validation_n2_required) {
            return $this->valide_par_n2;
        }
        
        // Aucune validation requise
        return true;
    }

    public function getValidationStatusAttribute(): string
    {
         if ($this->is_fully_validated) {
            return 'fully_validated';
        }
        
        if ($this->valide_par_n1) {
            return 'validated_n1';
        }
        
        if ($this->soumis_le) {
            return 'submitted';
        }
        
        return 'draft';
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('soumis_le');
    }

    public function scopePendingN1Validation($query)
    {
        return $query->whereNotNull('soumis_le')
            ->where('valide_par_n1', false);
    }

    public function scopePendingN2Validation($query)
    {
        return $query->whereNotNull('soumis_le')
            ->where('valide_par_n1', true)
            ->where('valide_par_n2', false);
    }

    public function scopeFullyValidated($query)
    {
        return $query->where('valide_par_n1', true)
            ->where('valide_par_n2', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTache($query, int $tacheId)
    {
        return $query->where('tache_id', $tacheId);
    }

    // ✅ NOUVEAU : Scope pour résultats individuels
    public function scopeIndividual($query)
    {
        return $query->where('is_individual', true);
    }

    // ✅ NOUVEAU : Scope pour résultats globaux
    public function scopeGlobal($query)
    {
        return $query->where('is_individual', false);
    }

    public function scopeForWeek($query, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfWeek();
        $endDate = $endDate ?? now()->endOfWeek();
        
        return $query->whereBetween('soumis_le', [$startDate, $endDate]);
    }

    public function scopeRequiringValidationFrom($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // N1 validations
            $q->whereHas('tache.activite', function ($actQuery) use ($user) {
                $actQuery->where('responsable_id', $user->id);
            })->where('valide_par_n1', false)
            
            // Or N2 validations
            ->orWhere(function ($subQ) use ($user) {
                $subQ->whereHas('tache.activite.projet', function ($projQuery) use ($user) {
                    $projQuery->where('responsable_id', $user->id);
                })
                ->where('valide_par_n1', true)
                ->where('valide_par_n2', false);
            });
        })
        ->whereNotNull('soumis_le');
    }

    /**
     * Validation Methods
     */
    public function submit(): void
    {
        $this->update([
            'soumis_le' => now(),
        ]);
        
        // Notifier les validateurs
        $this->notifyValidators();
    }

    public function validateByN1(User $validator, string $commentaire = null): void
    {
        $this->update([
            'valide_par_n1' => true,
            'validateur_n1_id' => $validator->id,
            'valide_le_n1' => now(),
            'commentaire_n1' => $commentaire,
        ]);
       
         // Notifier l'auteur
        $this->user->notify(new ResultatValideN1Notification($this, $validator, $commentaire));
        
        // Si N2 requis, notifier le validateur N2
        if ($this->tache->validation_n2_required) {
            $responsableN2 = $this->tache->activite->projet->responsable;
            if ($responsableN2) {
                $responsableN2->notify(new ResultatEnAttenteN2Notification($this));
            }
        }
    }

public function validateByN2(User $validator, string $commentaire ): void
    {
        if (!$this->valide_par_n1) {
            throw new \Exception('Le résultat doit d\'abord être validé par le N1');
        }
        
        $this->update([
            'valide_par_n2' => true,
            'validateur_n2_id' => $validator->id,
            'valide_le_n2' => now(),
            'commentaire_n2' => $commentaire,
        ]);
        
        // ✅ Mettre à jour le statut individuel de l'assigné si nécessaire
        if ($this->is_individual) {
            $this->tache->assignees()->updateExistingPivot($this->user_id, [
                'statut_individuel' => 'termine',
                'completed_at' => now(),
                'progression_individuelle' => 100,
            ]);
            
            // Recalculer le statut global
            $this->tache->recalculateGlobalStatus();
        }
        
        // Notifier l'auteur
        $this->user->notify(new ResultatValideN2Notification($this, $validator, $commentaire));
    }

    public function reject(User $validator, string $commentaire, string $level = 'n1'): void
    {
        if ($level === 'n1') {
            $this->update([
                'valide_par_n1' => false,
                'validateur_n1_id' => $validator->id,
                'valide_le_n1' => now(),
                'commentaire_n1' => $commentaire,
            ]);
        } else {
            $this->update([
                'valide_par_n2' => false,
                'validateur_n2_id' => $validator->id,
                'valide_le_n2' => now(),
                'commentaire_n2' => $commentaire,
            ]);
        }
        
        // Notifier l'auteur du rejet
        $this->user->notify(new ResultatRejeteNotification($this, $validator, $commentaire, $level));
    }

    /**
     * ✅ NOUVEAU : Notifier les validateurs
     */
    protected function notifyValidators(): void
    {
        // Notifier responsable N1 (activité)
        $responsableN1 = $this->tache->activite->responsable;
        if ($responsableN1 && $responsableN1->id !== $this->user_id) {
            $responsableN1->notify(new ResultatSoumisNotification($this));
        }
        
        // Si pas de N1 requis, notifier directement N2
        if (!$this->tache->validation_n1_required && $this->tache->validation_n2_required) {
            $responsableN2 = $this->tache->activite->projet->responsable;
            if ($responsableN2 && $responsableN2->id !== $this->user_id) {
                $responsableN2->notify(new ResultatSoumisNotification($this));
            }
        }
    }

    /**
     * Check if user can validate this result
     */
    public function canBeValidatedByN1(User $user): bool
    {
        $activite = $this->tache->activite;
        
        // Responsable activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }
        
        // Membre avec permission validation
        $membre = $activite->membres()->where('user_id', $user->id)->first();
        if ($membre && $membre->pivot->can_validate_results) {
            return true;
        }
        
        return false;
    }

   public function canBeValidatedByN2(User $user): bool
    {
        if (!$this->valide_par_n1) {
            return false;
        }
        
        $projet = $this->tache->activite->projet;
        return $projet && $projet->responsable_id === $user->id;
    }

    /**
     * Get responsable N1 (activity responsable)
     */
    public function getResponsableN1()
    {
        return $this->tache->activite->responsable;
    }

    /**
     * Get responsable N2 (project responsable)
     */
    public function getResponsableN2()
    {
        return $this->tache->activite->projet->responsable;
    }

     /**
     * ✅ NOUVEAU : Vérifier si c'est le résultat de l'utilisateur
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Generate evaluation sheet data for reporting
     */
    /**
     * Generate evaluation sheet data for reporting
     */
    public function toEvaluationSheet(): array
    {
        return [
            'numero' => $this->id,
            'tache' => $this->tache->titre,
            'code_tache' => $this->tache->code,
            'auteur' => $this->user->nom,
            'is_individual' => $this->is_individual,
            'resultats_attendus' => $this->resultats_attendus,
            'delai_execution' => $this->tache->echeance?->format('d/m/Y'),
            'resultats_obtenus' => $this->resultats_obtenus,
            'taux_realisation' => $this->taux_realisation . '%',
            'difficultes_rencontrees' => $this->difficultes_rencontrees,
            'solutions_envisagees' => $this->solutions_envisagees,
            'observations' => $this->observations,
            'documents' => $this->documents->map(fn($doc) => [
                'nom' => $doc->nom,
                'url' => $doc->url,
            ]),
            'validation_n1' => [
                'valide' => $this->valide_par_n1,
                'validateur' => $this->validateurN1?->nom,
                'date' => $this->valide_le_n1?->format('d/m/Y H:i'),
                'commentaire' => $this->commentaire_n1,
            ],
            'validation_n2' => [
                'valide' => $this->valide_par_n2,
                'validateur' => $this->validateurN2?->nom,
                'date' => $this->valide_le_n2?->format('d/m/Y H:i'),
                'commentaire' => $this->commentaire_n2,
            ],
        ];
    }
}