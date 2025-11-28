<?php

namespace App\Models;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use App\Notifications\AssigneCompletedTaskNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

        /**
     * ✅ CORRECTION : Méthode pour vérifier si une tâche est en retard
     */
    public function isOverdue(): bool
    {
        // Si pas de date d'échéance ou tâche terminée, pas en retard
        if (!$this->echeance || $this->statut === TacheStatut::TERMINE) {
            return false;
        }

        // Vérifier si la date d'échéance est dépassée
        return $this->echeance->isPast();
    }

    /**
     * ✅ NOUVELLE : Scope pour les tâches en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('echeance', '<', now())
            ->where('statut', '!=', TacheStatut::TERMINE->value)
            ->where(function ($q) {
                $q->whereNull('date_fin_reelle')
                  ->orWhere('date_fin_reelle', '>', $this->echeance);
            });
    }

    /**
     * ✅ NOUVELLE : Scope pour les tâches urgentes (échéance dans 7 jours)
     */
    public function scopeUrgent($query)
    {
        return $query->where('echeance', '<=', now()->addDays(7))
            ->where('echeance', '>=', now())
            ->where('statut', '!=', TacheStatut::TERMINE->value)
            ->whereIn('priorite', [TachePriorite::ELEVEE, TachePriorite::MOYENNE]);
    }
    /**
     * Fichiers attachés
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TacheAttachment::class);
    }

    /**
     * Liens externes
     */
    public function externalLinks(): HasMany
    {
        return $this->hasMany(TacheExternalLink::class);
    }
    // ==================== RELATIONSHIPS ====================

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tache_user')
            ->withPivot([
                'role',
                'can_edit',
                'can_complete',
                'can_validate',
                'statut_individuel',
                'progression_individuelle',
                'started_at',
                'completed_at',
                'notes_personnelles'
            ])
            ->withTimestamps()
            ->withCasts([
                'started_at' => 'datetime',
                'completed_at' => 'datetime',
                'progression_individuelle' => 'integer',
            ]);
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

    /**
     * ✅ NOUVEAU : Obtenir le résultat d'un utilisateur spécifique
     */
    public function getResultatForUser(User $user): ?TacheResultat
    {
        return $this->resultats()
            ->where('user_id', $user->id)
            ->where('is_individual', true)
            ->first();
    }

    /**
     * ✅ NOUVEAU : Obtenir tous les résultats individuels
     */
    public function getResultatsIndividuels()
    {
        return $this->resultats()
            ->where('is_individual', true)
            ->with(['user', 'validateurN1', 'validateurN2', 'documents'])
            ->get();
    }


    public function resultatsIndividuels(): HasMany
    {
        return $this->hasMany(TacheResultat::class)
            ->where('is_individual', true)
            ->with(['user', 'validateurN1', 'validateurN2', 'documents']);
    }

    /**
     * ✅ NOUVEAU : Obtenir le résultat global (si existe)
     */
    public function getResultatGlobal(): ?TacheResultat
    {
        return $this->resultats()
            ->where('is_individual', false)
            ->first();
    }

    /**
     * ✅ CORRECTION 2: Relation pour mon résultat (helper)
     */
    public function monResultat(?User $user = null): ?TacheResultat
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return null;
        }

        return $this->resultatsIndividuels()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * ✅ NOUVEAU : Obtenir le statut individuel d'un utilisateur
     */
    public function getStatutForUser(User $user): string
    {
        $pivot = $this->assignees()
            ->where('user_id', $user->id)
            ->first();

        // ✅ CORRECTION: Retourner le statut individuel s'il existe
        if ($pivot && $pivot->pivot->statut_individuel) {
            return $pivot->pivot->statut_individuel;
        }

        // Sinon, retourner le statut global par défaut
        return $this->statut->value;
    }

    /**
     * ✅ CORRECTION 4: Obtenir toutes les infos de statut pour un utilisateur
     */
    public function getMyStatusInfo(User $user): ?array
    {
        $pivot = $this->assignees()
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return null;
        }

        return [
            'statut' => $pivot->pivot->statut_individuel ?? $this->statut->value,
            'progression' => $pivot->pivot->progression_individuelle ?? 0,
            'started_at' => $pivot->pivot->started_at,
            'completed_at' => $pivot->pivot->completed_at,
            'notes_personnelles' => $pivot->pivot->notes_personnelles,
        ];
    }


    /**
     * ✅ NOUVEAU : Obtenir la progression individuelle
     */
    public function getProgressionForUser(User $user): int
    {
        $pivot = $this->assignees()
            ->where('user_id', $user->id)
            ->first();

        return $pivot?->pivot->progression_individuelle ?? 0;
    }

    public function updateStatutForUser(User $user, string $newStatut, ?int $progression = null): void
    {
        if (!$this->isAssignedTo($user)) {
            throw new \Exception('Cet utilisateur n\'est pas assigné à cette tâche');
        }

        $updateData = [
            'statut_individuel' => $newStatut,
        ];

        // Mettre à jour progression si fournie
        if ($progression !== null) {
            $updateData['progression_individuelle'] = max(0, min(100, $progression));
        }

        // ✅ AUTO-GÉRER les timestamps selon le statut
        if ($newStatut === 'en_cours') {
            $pivot = $this->assignees()->where('user_id', $user->id)->first();
            if (!$pivot->pivot->started_at) {
                $updateData['started_at'] = now();
            }
        } elseif ($newStatut === 'termine') {
            $updateData['completed_at'] = now();
            $updateData['progression_individuelle'] = 100;
        } elseif ($newStatut === 'a_faire') {
            $updateData['started_at'] = null;
            $updateData['completed_at'] = null;
            $updateData['progression_individuelle'] = 0;
        }

        // ✅ IMPORTANT: Mettre à jour le pivot sans toucher aux autres utilisateurs
        $this->assignees()->updateExistingPivot($user->id, $updateData);

        // ✅ Recalculer le statut global de la tâche (APRÈS la mise à jour du pivot)
        $this->recalculateGlobalStatus();

        // Log de l'action
        Log::info('Statut individuel mis à jour', [
            'tache_id' => $this->id,
            'user_id' => $user->id,
            'nouveau_statut' => $newStatut,
            'progression' => $progression,
        ]);
    }

    /**
     * ✅ NOUVEAU : Recalculer le statut global de la tâche
     */
    protected function recalculateGlobalStatus(): void
    {
        // Recharger les assignés avec les pivots à jour
        $assignees = $this->assignees()
            ->withPivot('statut_individuel', 'progression_individuelle')
            ->get();

        if ($assignees->isEmpty()) {
            return;
        }

        // Compter les statuts individuels
        $countTermine = $assignees->where('pivot.statut_individuel', 'termine')->count();
        $countEnCours = $assignees->where('pivot.statut_individuel', 'en_cours')->count();
        $total = $assignees->count();

        // ✅ RÈGLES de calcul du statut global
        $newStatut = null;

        if ($countTermine === $total) {
            // Tous ont terminé → Tâche terminée
            $newStatut = TacheStatut::TERMINE;
        } elseif ($countEnCours > 0 || $countTermine > 0) {
            // Au moins un en cours ou terminé → Tâche en cours
            $newStatut = TacheStatut::EN_COURS;
        } else {
            // Tous à faire → Tâche à faire
            $newStatut = TacheStatut::A_FAIRE;
        }

        // Calculer la progression globale (moyenne)
        $progressionMoyenne = $assignees->avg('pivot.progression_individuelle') ?? 0;

        // ✅ Mettre à jour UNIQUEMENT si changement
        if ($this->statut !== $newStatut || $this->taux_realisation !== round($progressionMoyenne)) {
            $this->update([
                'statut' => $newStatut,
                'taux_realisation' => round($progressionMoyenne),
            ]);

            Log::info('Statut global recalculé', [
                'tache_id' => $this->id,
                'nouveau_statut_global' => $newStatut->value,
                'progression_moyenne' => round($progressionMoyenne),
                'termine' => $countTermine,
                'en_cours' => $countEnCours,
                'total' => $total,
            ]);
        }
    }

    /**
     * ✅ NOUVEAU: Scope pour mes tâches avec mon statut
     */
    public function scopeWithMyStatus($query, User $user)
    {
        return $query->with([
            'assignees' => function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->withPivot([
                        'statut_individuel',
                        'progression_individuelle',
                        'started_at',
                        'completed_at',
                        'notes_personnelles'
                    ]);
            }
        ]);
    }


    /**
     * ✅ NOUVEAU: Obtenir les tâches d'un utilisateur par statut individuel
     */
    public function scopeForUserByStatus($query, User $user, string $statut)
    {
        return $query->whereHas('assignees', function ($q) use ($user, $statut) {
            $q->where('user_id', $user->id)
                ->where('statut_individuel', $statut);
        });
    }

    /**
     * ✅ NOUVEAU : Notifier le responsable qu'un assigné a terminé
     */
    protected function notifyResponsableOfCompletion(User $assigneWhoCompleted): void
    {
        $responsable = $this->activite->responsable;

        if ($responsable && $responsable->id !== $assigneWhoCompleted->id) {
            $responsable->notify(new AssigneCompletedTaskNotification(
                $this,
                $assigneWhoCompleted
            ));
        }

        // Notifier aussi le responsable du projet
        $projetResponsable = $this->activite->projet->responsable ?? null;
        if (
            $projetResponsable &&
            $projetResponsable->id !== $assigneWhoCompleted->id &&
            $projetResponsable->id !== $responsable->id
        ) {
            $projetResponsable->notify(new AssigneCompletedTaskNotification(
                $this,
                $assigneWhoCompleted
            ));
        }
    }

    /**
     * ✅ NOUVEAU : Obtenir les statistiques par assigné
     */
    public function getStatistiquesAssignes(): array
    {
        $assignees = $this->assignees()
            ->withPivot([
                'statut_individuel',
                'progression_individuelle',
                'started_at',
                'completed_at'
            ])
            ->get();

        return $assignees->map(function ($user) {
            $pivot = $user->pivot;

            return [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
                'statut' => $pivot->statut_individuel,
                'progression' => $pivot->progression_individuelle,
                'started_at' => $pivot->started_at,
                'completed_at' => $pivot->completed_at,
                'duree' => ($pivot->started_at && $pivot->completed_at)
                    ? Carbon::parse($pivot->started_at)->diffInHours(Carbon::parse($pivot->completed_at))
                    : null,
                'en_retard' => $pivot->statut_individuel !== 'termine' && $this->is_overdue,
            ];
        })->toArray();
    }

    /**
     * ✅ NOUVEAU : Vérifier si tous les assignés ont terminé
     */
    public function tousLesAssignesOntTermine(): bool
    {
        $assignees = $this->assignees()
            ->withPivot('statut_individuel')
            ->get();

        if ($assignees->isEmpty()) {
            return false;
        }

        return $assignees->every(fn($u) => $u->pivot->statut_individuel === 'termine');
    }

    /**
     * ✅ NOUVEAU : Obtenir les assignés qui n'ont pas terminé
     */
    public function getAssignesEnCours(): Collection
    {
        return $this->assignees()
            ->withPivot('statut_individuel')
            ->get()
            ->filter(fn($u) => $u->pivot->statut_individuel !== 'termine');
    }

    /**
     * ✅ NOUVEAU : Scope pour mes tâches en attente de collègues
     */
    public function scopeEnAttenteCollegues($query, User $user)
    {
        return $query->whereHas('assignees', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('statut_individuel', 'termine');
        })->whereHas('assignees', function ($q) {
            $q->where('statut_individuel', '!=', 'termine');
        });
    }


    /**
     * ✅ NOUVEAU : Soumettre un résultat individuel
     */
    public function soumettreResultatIndividuel(User $user, array $data): TacheResultat
    {
        if (!$this->isAssignedTo($user)) {
            throw new \Exception('Vous n\'êtes pas assigné à cette tâche');
        }

        // Vérifier que l'utilisateur a terminé sa partie
        // $statutUser = $this->getStatutForUser($user);
        // if ($statutUser !== 'termine') {
        //     throw new \Exception('Vous devez d\'abord terminer votre partie de la tâche');
        // }

        // Créer ou mettre à jour le résultat
        $resultat = TacheResultat::updateOrCreate(
            [
                'tache_id' => $this->id,
                'user_id' => $user->id,
                'is_individual' => true,
            ],
            array_merge($data, [
                'soumis_le' => now(),
            ])
        );
        $resultat->notifyValidators();

        return $resultat;
    }

    /**
     * ✅ NOUVEAU : Vérifier si tous les assignés ont soumis leurs résultats
     */
    public function tousLesResultatsSoumis(): bool
    {
        $assigneesCount = $this->assignees()->count();
        $resultatsCount = $this->resultats()
            ->where('is_individual', true)
            ->whereNotNull('soumis_le')
            ->count();

        return $assigneesCount > 0 && $assigneesCount === $resultatsCount;
    }

    /**
     * ✅ NOUVEAU : Vérifier si tous les résultats sont validés
     */
    public function tousLesResultatsValides(): bool
    {
        $assigneesCount = $this->assignees()->count();

        $resultatsValidesCount = $this->resultats()
            ->where('is_individual', true)
            ->where('valide_par_n1', true)
            ->where(function ($q) {
                // Si N2 requis, doit être validé aussi
                $q->where('valide_par_n2', true)
                    ->orWhereHas('tache', function ($tq) {
                    $tq->where('validation_n2_required', false);
                });
            })
            ->count();

        return $assigneesCount > 0 && $assigneesCount === $resultatsValidesCount;
    }

    /**
     * ✅ NOUVEAU : Obtenir les statistiques des résultats
     */
    public function getStatsResultats(): array
    {
        $resultats = $this->getResultatsIndividuels();
        $assigneesCount = $this->assignees()->count();

        return [
            'total_assignes' => $assigneesCount,
            'resultats_soumis' => $resultats->whereNotNull('soumis_le')->count(),
            'resultats_valides_n1' => $resultats->where('valide_par_n1', true)->count(),
            'resultats_valides_n2' => $resultats->where('valide_par_n2', true)->count(),
            'taux_soumission' => $assigneesCount > 0
                ? round(($resultats->whereNotNull('soumis_le')->count() / $assigneesCount) * 100)
                : 0,
            'taux_validation' => $assigneesCount > 0
                ? round(($resultats->where('valide_par_n2', true)->count() / $assigneesCount) * 100)
                : 0,
        ];
    }

    /**
     * ✅ Méthode helper pour la fiche d'évaluation
     */
    public function getResultatForEvaluation(User $user): ?TacheResultat
    {
        // Si tâche multi-assignée : chercher résultat individuel
        if ($this->assignees()->count() > 1) {
            return $this->getResultatForUser($user);
        }

        // Sinon : chercher résultat global ou individuel
        return $this->getResultatGlobal() ?? $this->getResultatForUser($user);
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
        if (
            $this->activite && $this->activite->projet &&
            $this->activite->projet->responsable_id === $user->id
        ) {
            return true;
        }

        // Assigné à la tâche
        if ($this->assignees()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Membre de l'activité avec permissions
        if (
            $this->activite && $this->activite->membres()
                ->where('user_id', $user->id)
                ->wherePivot('can_edit_tasks', true)
                ->exists()
        ) {
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
        if (
            $this->activite && $this->activite->projet &&
            $this->activite->projet->responsable_id === $user->id
        ) {
            return true;
        }

        // Super admin du workspace
        if (
            $this->activite && $this->activite->projet &&
            $this->activite->projet->workspace
        ) {
            $workspace = $this->activite->projet->workspace;
            if ($workspace->owner_id === $user->id) {
                return true;
            }
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
        if (!$this->estimated_hours || $this->estimated_hours == 0 || !$this->actual_hours) {
            return null;
        }

        return round((($this->actual_hours - $this->estimated_hours) / $this->estimated_hours) * 100, 2);
    }


    public function canBeStarted(User $user = null): bool
    {
        // État de base
        if ($this->statut === TacheStatut::TERMINE) {
            return false;
        }

        if ($this->archive_status === 'archived') {
            return false;
        }

        // Vérifier les permissions utilisateur si fourni
        if ($user && !$this->canBeEditedBy($user)) {
            return false;
        }

        // Vérifier les dépendances (si vous avez cette fonctionnalité)
        // if ($this->hasBlockingDependencies()) {
        //     return false;
        // }

        // Vérifier les prérequis métier
        // if (!$this->meetsStartRequirements()) {
        //     return false;
        // }

        return true;
    }

    /**
     * Vérifier si la tâche a des dépendances bloquantes
     */
    protected function hasBlockingDependencies(): bool
    {
        // Implémentez votre logique de dépendances ici
        // Par exemple :
        // return $this->dependencies()
        //     ->where('statut', '!=', TacheStatut::TERMINE)
        //     ->where('is_blocking', true)
        //     ->exists();

        return false; // Temporairement désactivé
    }

    /**
     * Vérifier les prérequis métier pour démarrer
     */
    protected function meetsStartRequirements(): bool
    {
        // Implémentez vos règles métier ici
        // Par exemple :
        // - Date de début dans le futur
        // - Ressources disponibles
        // - Budget approuvé, etc.

        return true; // Temporairement toujours vrai
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

    // public function scopeOverdue($query)
    // {
    //     return $query->where('echeance', '<', now())
    //         ->where('statut', '!=', TacheStatut::TERMINE->value);
    // }

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

    /**
     * ✅ NOUVEAU : Vérifier si l'utilisateur peut compléter la tâche
     */
    public function canBeCompletedBy(User $user): bool
    {
        // Super admin peut tout faire
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if ($this->activite && $this->activite->projet && $this->activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Assigné avec permission can_complete
        $assignment = $this->assignees()->where('user_id', $user->id)->first();
        return $assignment && ($assignment->pivot->can_complete ?? true);
    }

    /**
     * ✅ NOUVEAU : Vérifier si l'utilisateur peut valider N1
     */
    public function canBeValidatedN1By(User $user): bool
    {
        // Tâche doit être en attente de validation N1
        if (!$this->validation_n1_required || $this->validated_n1_at) {
            return false;
        }

        // Super admin
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Responsable de l'activité (N1)
        if ($this->activite && $this->activite->responsable_id === $user->id) {
            return true;
        }

        // Membre de l'activité avec permission de validation
        if ($this->activite) {
            $membre = $this->activite->membres()->where('user_id', $user->id)->first();
            if ($membre && $membre->pivot->can_validate_results) {
                return true;
            }
        }

        return false;
    }




    /**
     * ✅ NOUVEAU : Valider la tâche (N1)
     */
    public function validateN1(User $user, ?string $commentaire = null): void
    {
        if (!$this->canBeValidatedN1By($user)) {
            throw new \Exception('Vous n\'avez pas la permission de valider cette tâche (N1)');
        }

        if ($this->statut !== TacheStatut::TERMINE) {
            throw new \Exception('La tâche doit être terminée avant validation');
        }

        $this->update([
            'validated_n1_at' => now(),
            'validated_n1_by' => $user->id,
            'commentaire_n1' => $commentaire,
        ]);

        // Notification N2 si requis
        if ($this->validation_n2_required && $this->activite && $this->activite->projet && $this->activite->projet->responsable) {
            // TODO: Envoyer notification au responsable projet
        }

        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties(['commentaire' => $commentaire])
            ->log('Validation N1 effectuée');
    }

    /**
     * ✅ NOUVEAU : Valider la tâche (N2)
     */
    public function validateN2(User $user, ?string $commentaire = null): void
    {
        if (!$this->canBeValidatedN2By($user)) {
            throw new \Exception('Vous n\'avez pas la permission de valider cette tâche (N2)');
        }

        if (!$this->validated_n1_at) {
            throw new \Exception('La validation N1 doit être effectuée avant la validation N2');
        }

        $this->update([
            'validated_n2_at' => now(),
            'validated_n2_by' => $user->id,
            'commentaire_n2' => $commentaire,
            'validation_superieur' => true, // Marque complètement validée
        ]);

        // Notification à l'utilisateur assigné
        if ($this->assignees->first()) {
            // TODO: Envoyer notification
        }

        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties(['commentaire' => $commentaire])
            ->log('Validation N2 effectuée - Validation complète');
    }

    /**
     * ✅ NOUVEAU : Vérifier si la tâche est complètement validée
     */
    public function isFullyValidated(): bool
    {
        if ($this->validation_n1_required && !$this->validated_n1_at) {
            return false;
        }

        if ($this->validation_n2_required && !$this->validated_n2_at) {
            return false;
        }

        return true;
    }


}