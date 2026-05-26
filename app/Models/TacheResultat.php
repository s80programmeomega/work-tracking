<?php

namespace App\Models;

use App\Enums\TacheStatut;
use App\Notifications\ResultatEnAttenteN2Notification;
use App\Notifications\ResultatRejeteN2InfoNotification;
use App\Notifications\ResultatRejeteNotification;
use App\Notifications\ResultatSoumisNotification;
use App\Notifications\ResultatValidationCompleteNotification;
use App\Notifications\ResultatValideN1Notification;
use App\Notifications\ResultatValideN2Notification;
// G2: ValidationN1ConfirmeeNotification, ValidationN2ConfirmeeNotification,
// RejetConfirmeNotification — supprimées (auto-notifications du validateur
// envers lui-même, déjà couvertes par le toast UI et la réponse API).
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TacheResultat extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tache_id',
        'user_id',
        'is_individual',
        'statut',
        'resultats_attendus',
        'resultats_obtenus',
        'taux_realisation',
        'difficultes_rencontrees',
        'solutions_envisagees',
        'observations',
        'soumis_le',
        'soumis_n0_le',
        'action_n0',
        'commentaire_n0',
        'n0_actor_id',
        'action_n0_le',
        'valide_par_n1',
        'validateur_n1_id',
        'valide_le_n1',
        'commentaire_n1',
        'valide_par_n2',
        'validateur_n2_id',
        'valide_le_n2',
        'commentaire_n2',
        'bypass_active',
        'motif_bypass',
        'bypass_le',
        'bypass_count',
    ];

    protected $casts = [
        'soumis_le' => 'datetime',
        'soumis_n0_le' => 'datetime',
        'action_n0_le' => 'datetime',
        'valide_le_n1' => 'datetime',
        'valide_le_n2' => 'datetime',
        'valide_par_n1' => 'boolean',
        'valide_par_n2' => 'boolean',
        'is_individual' => 'boolean',
        'bypass_active' => 'boolean',
        'bypass_count' => 'integer',
        'bypass_le' => 'datetime',
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

    public function n0Actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'n0_actor_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ValidationAuditLog::class, 'tache_resultat_id')->orderBy('created_at');
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
        // Use the new statut column when present
        if ($this->statut) {
            return match ($this->statut) {
                'brouillon' => 'not_submitted',
                'en_verification_n0' => 'pending_n0',
                'en_validation_n1' => 'pending_n1',
                'en_validation_n2' => 'pending_n2',
                'valide' => 'fully_validated',
                'rejete' => 'rejected',
                'a_refaire' => 'a_refaire',
                default => 'not_submitted',
            };
        }

        // Legacy fallback for rows created before Task 5
        if (! $this->soumis_le) {
            return 'not_submitted';
        }

        if ($this->rejete_le) {
            return 'rejected';
        }

        if ($this->tache->validation_n2_required) {
            if (! $this->valide_par_n1) {
                return 'pending_n1';
            }
            if (! $this->valide_par_n2) {
                return 'pending_n2';
            }

            return 'fully_validated';
        }

        if ($this->tache->validation_n1_required) {
            if (! $this->valide_par_n1) {
                return 'pending_n1';
            }

            return 'fully_validated';
        }

        return 'submitted';
    }

    public function getCanBeEditedAttribute(): bool
    {
        return ! $this->is_fully_validated && ! $this->rejete_le;
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
            // Réinitialiser les rejets s'il y en avait
            'rejete_par' => null,
            'rejete_le' => null,
            'motif_rejet' => null,
            'niveau_rejet' => null,
        ]);

        // Notifier les validateurs
        $this->notifyValidators();
    }

    public function validateByN1(User $validator, ?string $commentaire = null): void
    {
        // ⚠️ statut: transition explicite vers l'étape suivante du circuit.
        // - Si N2 requis → 'en_validation_n2' (le résultat doit apparaître
        //   dans le compteur pending_n2 du responsable projet).
        // - Sinon → 'valide' (circuit terminé, déclenche la règle R6).
        // Avant cette correction, seul valide_par_n1 était mis à jour et
        // le statut restait à 'en_validation_n1', d'où l'invisibilité côté
        // dashboard N2.
        $nextStatut = $this->tache->validation_n2_required ? 'en_validation_n2' : 'valide';

        $this->update([
            'statut' => $nextStatut,
            'valide_par_n1' => true,
            'validateur_n1_id' => $validator->id,
            'valide_le_n1' => now(),
            'commentaire_n1' => $commentaire,
        ]);

        // 📧 G2: garde-fou anti-auto-notification centralisé. L'ancien
        // ValidationN1ConfirmeeNotification (envoyée à $validator pour
        // "confirmer" sa propre action) a été supprimée — c'est du bruit
        // que le toast UI et la réponse API couvrent déjà.
        $notifService = app(NotificationService::class);

        // 1. Notifier l'auteur du résultat (sauf s'il s'auto-valide)
        $notifService->sendUnlessSelf(
            $this->user,
            $validator,
            new ResultatValideN1Notification($this, $validator, $commentaire)
        );

        // 2. Si N2 requis, notifier le responsable projet
        if ($this->tache->validation_n2_required) {
            $responsableN2 = $this->tache->activite->projet?->responsable;
            if ($responsableN2) {
                $notifService->sendUnlessSelf(
                    $responsableN2,
                    $validator,
                    new ResultatEnAttenteN2Notification($this)
                );
            }
        }

        Log::info('✅ Notifications N1 envoyées', [
            'resultat_id' => $this->id,
            'auteur_id' => $this->user_id,
            'validateur_id' => $validator->id,
            'n2_required' => $this->tache->validation_n2_required,
            'statut' => $nextStatut,
        ]);
    }

    public function validateByN2(User $validator, ?string $commentaire = null): void
    {
        if (! $this->valide_par_n1) {
            throw new \Exception('Le résultat doit d\'abord être validé par le N1');
        }

        // ⚠️ statut: passe à 'valide' (circuit terminé). Indispensable pour
        // que la règle R6 (post-N2 immutabilité, Task 9) déclenche via
        // Tache::isLockedPostN2() qui lit valide_par_n2, ET pour que les
        // dashboards filtrant sur statut='valide' voient le résultat.
        $this->update([
            'statut' => 'valide',
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

        // 📧 G2: ValidationN2ConfirmeeNotification supprimée (toujours auto-
        // notification du validateur N2 envers lui-même). Le reste passe
        // par sendUnlessSelf pour empêcher tout cas pathologique où le
        // validateur N2 serait aussi auteur ou validateur N1.
        $notifService = app(NotificationService::class);

        // 1. Notifier l'auteur du résultat
        $notifService->sendUnlessSelf(
            $this->user,
            $validator,
            new ResultatValideN2Notification($this, $validator, $commentaire)
        );

        // 2. Notifier le validateur N1 (validation complète)
        if ($this->validateurN1) {
            $notifService->sendUnlessSelf(
                $this->validateurN1,
                $validator,
                new ResultatValidationCompleteNotification($this)
            );
        }

        Log::info('✅ Notifications N2 envoyées', [
            'resultat_id' => $this->id,
            'auteur_id' => $this->user_id,
            'validateur_n2_id' => $validator->id,
            'validateur_n1_id' => $this->validateur_n1_id,
        ]);
    }

    /**
     * ✅ NOUVEAU : Recalculer le statut global de la tâche
     */
    protected function recalculateGlobalStatus(): void
    {
        $tache = $this->tache; // récupérer la tâche liée

        if (! $tache) {
            return;
        }

        // Recharger les assignés avec les pivots
        $assignees = $tache->assignees()
            ->withPivot('statut_individuel', 'progression_individuelle')
            ->get();

        if ($assignees->isEmpty()) {
            return;
        }

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
                'rejete_par' => $validator->id,
                'rejete_le' => now(),
                'motif_rejet' => $commentaire,
                'niveau_rejet' => 'n1',
            ]);
        } else {
            $this->update([
                'valide_par_n2' => false,
                'validateur_n2_id' => $validator->id,
                'valide_le_n2' => now(),
                'commentaire_n2' => $commentaire,
                'rejete_par' => $validator->id,
                'rejete_le' => now(),
                'motif_rejet' => $commentaire,
                'niveau_rejet' => 'n2',
            ]);
        }

        // Remettre le statut individuel à "a_faire"
        $this->tache->updateStatutForUser($this->user, 'a_faire', 0);

        // 📧 G2: RejetConfirmeNotification supprimée (auto-notification
        // pure du validateur envers lui-même). Le reste passe par
        // sendUnlessSelf.
        $notifService = app(NotificationService::class);

        // 1. Notifier l'auteur du résultat (priorité haute)
        $notifService->sendUnlessSelf(
            $this->user,
            $validator,
            new ResultatRejeteNotification($this, $validator, $commentaire, $level)
        );

        // 2. Si rejet N2, notifier aussi le validateur N1 (pour info)
        if ($level === 'n2' && $this->validateurN1) {
            $notifService->sendUnlessSelf(
                $this->validateurN1,
                $validator,
                new ResultatRejeteN2InfoNotification($this, $commentaire)
            );
        }

        Log::info('❌ Notifications rejet envoyées', [
            'resultat_id' => $this->id,
            'auteur_id' => $this->user_id,
            'validateur_id' => $validator->id,
            'level' => $level,
        ]);
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

    /**
     * 🔔 Notifier les validateurs appropriés
     */
    protected function notifyValidators(): void
    {
        // G2: garde-fou central. L'acteur ici est l'auteur du résultat
        // (this->user) — si lui-même est aussi N1 ou N2, on n'envoie pas.
        $notifService = app(NotificationService::class);
        $author = $this->user;

        // Notifier responsable N1 (activité)
        $responsableN1 = $this->tache->activite->responsable;
        if ($responsableN1) {
            $notifService->sendUnlessSelf($responsableN1, $author, new ResultatSoumisNotification($this));

            Log::info('📧 Notification N1 envoyée', [
                'resultat_id' => $this->id,
                'responsable_n1_id' => $responsableN1->id,
            ]);
        }

        // Si pas de N1 requis, notifier directement N2
        if (! $this->tache->validation_n1_required && $this->tache->validation_n2_required) {
            $responsableN2 = $this->tache->activite->projet?->responsable;
            if ($responsableN2) {
                $notifService->sendUnlessSelf($responsableN2, $author, new ResultatSoumisNotification($this));

                Log::info('📧 Notification N2 directe envoyée', [
                    'resultat_id' => $this->id,
                    'responsable_n2_id' => $responsableN2->id,
                ]);
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
        if (! $this->soumis_le) {
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
        if (! $this->valide_par_n1) {
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
            'taux_realisation' => $this->taux_realisation.'%',
            'difficultes_rencontrees' => $this->difficultes_rencontrees,
            'solutions_envisagees' => $this->solutions_envisagees,
            'observations' => $this->observations,
            'documents' => $this->documents->map(fn ($doc) => [
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
