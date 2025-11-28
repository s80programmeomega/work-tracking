<?php

namespace App\Models;

use App\Enums\TacheStatut;
use App\Notifications\ResultatEnAttenteN2Notification;
use App\Notifications\ResultatRejeteNotification;
use App\Notifications\ResultatSoumisNotification;
use App\Notifications\ResultatValideN1Notification;
use App\Notifications\ResultatValideN2Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
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
        if (!$this->soumis_le) {
            return 'not_submitted';
        }

        if ($this->rejete_le) {
            return 'rejected';
        }

        if ($this->tache->validation_n2_required) {
            if (!$this->valide_par_n1) {
                return 'pending_n1';
            }
            if (!$this->valide_par_n2) {
                return 'pending_n2';
            }
            return 'fully_validated';
        }

        if ($this->tache->validation_n1_required) {
            if (!$this->valide_par_n1) {
                return 'pending_n1';
            }
            return 'fully_validated';
        }

        return 'submitted';
    }

    public function getCanBeEditedAttribute(): bool
    {
        return !$this->is_fully_validated && !$this->rejete_le;
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
        return $query->where(function ($q) {
            $q->where('valide_par_n2', true)
                ->orWhere(function ($sq) {
                    $sq->where('valide_par_n1', true)
                        ->whereHas('tache', function ($tq) {
                            $tq->where('validation_n2_required', false);
                        });
                });
        });
    }

    // public function scopeRejected($query)
    // {
    //     return $query->whereNotNull('rejete_le');
    // }
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
        return $query->submitted()
            ->where(function ($q) use ($user) {
                // N1
                $q->where(function ($n1) use ($user) {
                    $n1->where('valide_par_n1', false)
                        ->whereHas('tache.activite', function ($aq) use ($user) {
                            $aq->where('responsable_id', $user->id)
                                ->orWhereHas('membres', function ($mq) use ($user) {
                                    $mq->where('user_id', $user->id)
                                        ->where('can_validate_results', true);
                                });
                        });
                })
                    // N2
                    ->orWhere(function ($n2) use ($user) {
                    $n2->where('valide_par_n1', true)
                        ->where('valide_par_n2', false)
                        ->whereHas('tache.activite.projet', function ($pq) use ($user) {
                            $pq->where('responsable_id', $user->id);
                        });
                });
            });
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

    public function validateByN1(User $validator, ?string $commentaire = null): void
    {
        $this->update([
            'valide_par_n1' => true,
            'validateur_n1_id' => $validator->id,
            'valide_le_n1' => now(),
            'commentaire_n1' => $commentaire,
        ]);

        // Notifier l'auteur
        $this->user->notify(new ResultatValideN1Notification($this, $validator, $commentaire));

        // Si N2 requis, notifier le responsable projet
        if ($this->tache->validation_n2_required) {
            $responsableN2 = $this->tache->activite->projet->responsable;
            if ($responsableN2) {
                $responsableN2->notify(new ResultatEnAttenteN2Notification($this));
            }
        }
    }

    public function validateByN2(User $validator, ?string $commentaire = null): void
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

        // Mettre à jour le statut individuel
        if ($this->is_individual) {
            $this->tache->assignees()->updateExistingPivot($this->user_id, [
                'statut_individuel' => 'termine',
                'completed_at' => now(),
                'progression_individuelle' => 100,
            ]);

            $this->recalculateGlobalStatus();
        }

        // Notifier l'auteur
        $this->user->notify(new ResultatValideN2Notification($this, $validator, $commentaire));
    }


    /**
     * ✅ NOUVEAU : Recalculer le statut global de la tâche
     */
    protected function recalculateGlobalStatus(): void
    {
        $tache = $this->tache; // récupérer la tâche liée

        if (!$tache)
            return;

        // Recharger les assignés avec les pivots
        $assignees = $tache->assignees()
            ->withPivot('statut_individuel', 'progression_individuelle')
            ->get();

        if ($assignees->isEmpty())
            return;

        $countTermine = $assignees->where('pivot.statut_individuel', 'termine')->count();
        $countEnCours = $assignees->where('pivot.statut_individuel', 'en_cours')->count();
        $total = $assignees->count();

        // Déterminer le statut global
        if ($countTermine === $total) {
            $newStatut = TacheStatut::TERMINE;
        } elseif ($countEnCours > 0 || $countTermine > 0) {
            $newStatut = TacheStatut::EN_COURS;
        } else {
            $newStatut = TacheStatut::A_FAIRE;
        }

        // Calcul de la progression globale
        $progressionMoyenne = $assignees->avg('pivot.progression_individuelle') ?? 0;

        // Mettre à jour la tâche
        if ($tache->statut !== $newStatut || $tache->taux_realisation !== round($progressionMoyenne)) {
            $tache->update([
                'statut' => $newStatut,
                'taux_realisation' => round($progressionMoyenne),
            ]);

            Log::info('Statut global recalculé', [
                'tache_id' => $tache->id,
                'nouveau_statut_global' => $newStatut->value,
                'progression_moyenne' => round($progressionMoyenne),
                'termine' => $countTermine,
                'en_cours' => $countEnCours,
                'total' => $total,
            ]);
        }
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

        // Remettre le statut individuel à "a_faire"
        $this->tache->updateStatutForUser($this->user, 'a_faire', 0);

        // Notifier l'auteur
        $this->user->notify(new ResultatRejeteNotification($this, $validator, $commentaire, $level));
    }

    /**
     * Réinitialiser après rejet (pour resoumission)
     */
    public function resetAfterRejection(): void
    {
        $this->update([
            'soumis_le' => null,
            'rejete_par' => null,
            'rejete_le' => null,
            'motif_rejet' => null,
            'niveau_rejet' => null,
        ]);
    }


    // ==================== HELPER METHODS ====================

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['resultats_obtenus', 'taux_realisation', 'valide_par_n1', 'valide_par_n2'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==================== PERMISSION METHODS (STRICT) ====================

    /**
     * ✅ STRICT : Uniquement le responsable de l'activité
     */
    public function canBeValidatedByN1(User $user): bool
    {
        // Doit être soumis
        if (!$this->soumis_le) {
            return false;
        }

        // Pas déjà validé
        if ($this->valide_par_n1) {
            return false;
        }

        // Pas son propre résultat
        if ($this->user_id === $user->id) {
            return false;
        }

        // ⚠️ STRICT : Uniquement responsable de l'activité
        return $this->tache->activite &&
            $this->tache->activite->responsable_id === $user->id;
    }

    /**
     * ✅ STRICT : Uniquement le responsable du projet
     */
    public function canBeValidatedByN2(User $user): bool
    {
        // N1 doit être validé
        if (!$this->valide_par_n1) {
            return false;
        }

        // Pas déjà validé
        if ($this->valide_par_n2) {
            return false;
        }

        // Pas son propre résultat
        if ($this->user_id === $user->id) {
            return false;
        }

        // ⚠️ STRICT : Uniquement responsable du projet
        return $this->tache->activite &&
            $this->tache->activite->projet &&
            $this->tache->activite->projet->responsable_id === $user->id;
    }

    /**
     * 👁️ Peut consulter ce résultat
     */
    public function canBeViewedBy(User $user): bool
    {
        // C'est son résultat
        if ($this->user_id === $user->id) {
            return true;
        }

        // Responsable de l'activité
        if ($this->tache->activite && $this->tache->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if (
            $this->tache->activite &&
            $this->tache->activite->projet &&
            $this->tache->activite->projet->responsable_id === $user->id
        ) {
            return true;
        }

        return false;
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