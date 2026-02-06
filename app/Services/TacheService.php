<?php

namespace App\Services;

use App\Enums\TacheStatut;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\TacheExternalLink;
use App\Models\User;
use App\Notifications\Taches\TacheAssignedNotification;
use App\Notifications\Taches\TacheFileAddedNotification;
use App\Notifications\Taches\TacheFileRemovedNotification;
use App\Notifications\Taches\TacheLinkAddedNotification;
use App\Notifications\Taches\TacheLinkRemovedNotification;
use App\Notifications\Taches\TacheUnassignedNotification;
use App\Notifications\Taches\TacheUpdatedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TacheService
{
    /**
     * ✅ Obtenir le numéro de semaine standardisé (ISO 8601)
     * 
     * ISO 8601: La semaine commence le lundi, la première semaine de l'année
     * contient le 4 janvier.
     */
    public static function getWeekInfo(?Carbon $date = null): array
    {
        $date = $date ?? now();

        return [
            'week_number' => $date->isoWeek(),
            'year' => $date->isoWeekYear(),
            'start_date' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'end_date' => $date->copy()->endOfWeek(Carbon::SUNDAY),
        ];
    }


    /**
     * ✅ Obtenir toutes les tâches avec filtres et permissions
     */
    public function getAllTaches(User $user, array $filters = []): Collection
    {
        $query = Tache::query()->with(['activite.projet', 'assignees', 'labels']);

        // ✅ Appliquer les permissions via Policy
        if (!$user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                // Tâches assignées
                $q->whereHas('assignees', function ($aq) use ($user) {
                    $aq->where('user_id', $user->id);
                })
                    // OU tâches des activités où je suis responsable
                    ->orWhereHas('activite', function ($actq) use ($user) {
                        $actq->where('responsable_id', $user->id)
                            // OU membre avec permissions
                            ->orWhereHas('membres', function ($mq) use ($user) {
                                $mq->where('user_id', $user->id)
                                    ->where(function ($pmq) {
                                        $pmq->where('can_edit_tasks', true)
                                            ->orWhere('can_create_tasks', true)
                                            ->orWhere('can_validate_results', true);
                                    });
                            });
                    })
                    // OU tâches des projets où je suis responsable
                    ->orWhereHas('activite.projet', function ($projq) use ($user) {
                        $projq->where('responsable_id', $user->id);
                    })
                    // OU tâches visibles (public) dans mes workspaces
                    ->orWhere(function ($visq) use ($user) {
                        $visq->where('visibility', 'public')
                            ->whereHas('activite.projet.workspace', function ($wsq) use ($user) {
                                $wsq->whereHas('membres', function ($wmq) use ($user) {
                                    $wmq->where('user_id', $user->id);
                                });
                            });
                    });
            });
        }

        // Filtres standards
        if (isset($filters['activite_id'])) {
            $query->where('activite_id', $filters['activite_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['priorite'])) {
            $query->where('priorite', $filters['priorite']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        // ✅ Filtre par semaine (pour rapports hebdomadaires)
        if (isset($filters['week_number']) && isset($filters['year'])) {
            $query->forWeek($filters['week_number'], $filters['year']);
        }

        // ✅ Filtre par statut de validation
        if (isset($filters['validation_status'])) {
            switch ($filters['validation_status']) {
                case 'pending_n1':
                    $query->pendingValidationN1();
                    break;
                case 'pending_n2':
                    $query->pendingValidationN2();
                    break;
                case 'fully_validated':
                    $query->whereNotNull('validated_n2_at');
                    break;
            }
        }

        // Archive status
        if (isset($filters['archive_status'])) {
            if ($filters['archive_status'] === 'archived') {
                $query->archived();
            } else {
                $query->active();
            }
        } else {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * ✅ CORRIGÉ : Récupérer le Kanban pour une activité avec garantie de structure
     */
    public function getKanbanForActivite(int $activiteId): array
    {
        try {
            Log::info('Service: Récupération Kanban', ['activite_id' => $activiteId]);

            // ✅ Charger toutes les tâches actives de l'activité
            $taches = Tache::where('activite_id', $activiteId)
                ->where('archive_status', 'active')
                ->with([
                    'activite:id,nom,code,projet_id,responsable_id',
                    'activite.projet:id,nom,workspace_id',
                    'assignees:id,nom,email,avatar',
                    'labels:id,nom,couleur',
                    'validatedN1By:id,nom',
                    'validatedN2By:id,nom',
                    'createdBy:id,nom'
                ])
                ->orderBy('position')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Tâches récupérées', [
                'activite_id' => $activiteId,
                'total' => $taches->count()
            ]);

            // ✅ Grouper par statut avec garantie de structure
            $kanban = [
                'a_faire' => $taches->where('statut', TacheStatut::A_FAIRE)->values()->all(),
                'en_cours' => $taches->where('statut', TacheStatut::EN_COURS)->values()->all(),
                'termine' => $taches->where('statut', TacheStatut::TERMINE)->values()->all(),
            ];

            Log::info('Kanban groupé', [
                'activite_id' => $activiteId,
                'a_faire' => count($kanban['a_faire']),
                'en_cours' => count($kanban['en_cours']),
                'termine' => count($kanban['termine'])
            ]);

            return $kanban;

        } catch (\Exception $e) {
            Log::error('Erreur Service getKanbanForActivite', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // ✅ Retourner structure vide en cas d'erreur
            return [
                'a_faire' => [],
                'en_cours' => [],
                'termine' => [],
            ];
        }
    }


    /**
     * ✅ Mes tâches (toutes mes tâches accessibles)
     */
    public function getMyTaches(User $user): Collection
    {
        return Tache::assignedTo($user->id)
            ->with(['activite', 'assignees', 'labels'])
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * ✅ Gérer l'upload de fichiers pour une tâche
     */
    public function handleFileUploads(Tache $tache, array $files, User $uploadedBy): void
    {
        foreach ($files as $file) {
            $attachment = $this->uploadFile($tache, $file, $uploadedBy);

            // Notifier tous les assignees
            foreach ($tache->assignees as $assignee) {
                $assignee->notify(new TacheFileAddedNotification($tache, $uploadedBy, $attachment));
            }
        }
    }

    /**
     * ✅ Uploader un fichier individuel
     */
    public function uploadFile(Tache $tache, $file, User $uploadedBy): TacheAttachment
    {
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . uniqid() . '_' . $originalName;
        $filePath = $file->storeAs('tache-attachments', $fileName, 'uploads');

        if (!$filePath) {
            throw new \Exception('Erreur lors de l\'upload du fichier');
        }

        return TacheAttachment::create([
            'tache_id' => $tache->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_name' => $originalName,
            'uploaded_by' => $uploadedBy->id,
        ]);
    }

    /**
     * ✅ Supprimer un fichier attaché
     */
   public function deleteAttachment(TacheAttachment $attachment, User $deletedBy): bool
    {
        $tache = $attachment->tache;

        // Supprimer le fichier physique
        if (Storage::disk('uploads')->exists($attachment->file_path)) {
            Storage::disk('uploads')->delete($attachment->file_path);
        }

        $deleted = $attachment->delete();

        // Notifier les assignees
        if ($deleted) {
            foreach ($tache->assignees as $assignee) {
                $assignee->notify(new TacheFileRemovedNotification($tache, $deletedBy, $attachment));
            }
        }

        return $deleted;
    }

    /**
     * ✅ Ajouter des liens externes
     */
    public function addExternalLinks(Tache $tache, array $links, User $createdBy): void
    {
        foreach ($links as $link) {
            $linkModel = TacheExternalLink::create([
                'tache_id' => $tache->id,
                'title' => $link['title'] ?? $link['url'],
                'url' => $link['url'],
                'created_by' => $createdBy->id,
            ]);

            // Notifier les assignees
            foreach ($tache->assignees as $assignee) {
                $assignee->notify(new TacheLinkAddedNotification($tache, $createdBy, $linkModel));
            }
        }
    }

    /**
     * ✅ Supprimer un lien externe
     */
    public function deleteExternalLink(TacheExternalLink $link, User $deletedBy): bool
    {
        $tache = $link->tache;
        $deleted = $link->delete();

        // Notifier les assignees
        if ($deleted) {
            foreach ($tache->assignees as $assignee) {
                $assignee->notify(new TacheLinkRemovedNotification($tache, $deletedBy, $link));
            }
        }

        return $deleted;
    }


    /**
     * ✅ Créer une tâche avec gestion automatique des semaines
     */
    public function createTache(array $data, User $creator): Tache
    {
        // Vérifier les permissions via Policy
        $activite = Activite::findOrFail($data['activite_id']);
        Gate::authorize('create', [Tache::class, $activite]);

        return DB::transaction(function () use ($data, $creator) {
            // Extraire fichiers et liens des données
            $uploadedFiles = $data['uploaded_files'] ?? [];
            $externalLinks = $data['external_links'] ?? [];
            unset($data['uploaded_files'], $data['external_links']);

            // Extraire relations
            $assigneeIds = $data['assignee_ids'] ?? [];
            $labelIds = $data['label_ids'] ?? [];
            unset($data['assignee_ids'], $data['label_ids']);

            // ✅ Définir semaine et année de manière standardisée (ISO 8601)
            $weekInfo = $this->determineWeekInfo($data);
            $data['week_number'] = $weekInfo['week_number'];
            $data['year'] = $weekInfo['year'];

            // Position par défaut
            if (!isset($data['position'])) {
                $maxPosition = Tache::where('activite_id', $data['activite_id'])
                    ->where('statut', $data['statut'] ?? TacheStatut::A_FAIRE->value)
                    ->max('position');
                $data['position'] = ($maxPosition ?? -1) + 1;
            }

            $data['created_by'] = $creator->id;

            // Créer tâche
            $tache = Tache::create($data);

            // Gérer les fichiers uploadés
            if (!empty($uploadedFiles)) {
                $this->handleFileUploads($tache, $uploadedFiles, $creator);
            }

            // Gérer les liens externes
            if (!empty($externalLinks)) {
                $this->addExternalLinks($tache, $externalLinks, $creator);
            }

            // Assigner membres
            if (!empty($assigneeIds)) {
                foreach ($assigneeIds as $userId) {
                    $tache->assignees()->attach($userId, [
                        'role' => 'assignee',
                        'can_edit' => true,
                        'can_complete' => true,
                        'can_validate' => false,
                        'assigned_at' => now()
                    ]);

                    // Notifier assignés
                    $user = User::find($userId);
                    if ($user) {
                        $user->notify(new TacheAssignedNotification($tache, $creator));
                    }
                    
                }
            }

            // Attacher labels
            if (!empty($labelIds)) {
                $tache->labels()->attach($labelIds);
            }

            // ✅ Log création pour audit
            activity()
                ->causedBy($creator)
                ->performedOn($tache)
                ->withProperties(['data' => $data])
                ->log('Tâche créée');

            return $tache->load(['activite', 'assignees', 'labels', 'attachments', 'externalLinks']);
        });
    }

    /**
     * ✅ Mettre à jour une tâche avec gestion COMPLÈTE des fichiers
     */
    public function updateTache(Tache $tache, array $data): Tache
    {
        Gate::authorize('update', $tache);

        return DB::transaction(function () use ($tache, $data) {

            // Extraire fichiers et liens
            $uploadedFiles = $data['uploaded_files'] ?? [];
            $externalLinks = $data['external_links'] ?? [];
            $coverImagePath = $data['cover_image'] ?? null;

            unset($data['uploaded_files'], $data['external_links'], $data['cover_image']);

            // Extraire relations
            $assigneeIds = $data['assignee_ids'] ?? null;
            $labelIds = $data['label_ids'] ?? null;
            unset($data['assignee_ids'], $data['label_ids']);

            // ✅ Recalculer semaine si dates changent
            if (isset($data['date_debut']) || isset($data['echeance'])) {
                $weekInfo = $this->determineWeekInfo($data);
                $data['week_number'] = $weekInfo['week_number'];
                $data['year'] = $weekInfo['year'];
            }

            // ✅ Ajouter le chemin de l'image de couverture si présent
            if ($coverImagePath) {
                $data['cover_image'] = $coverImagePath;
            }
           

            // Mettre à jour tâche
            $tache->update($data);

            // Sync relations si fournies
             // Assignees
            if ($assigneeIds !== null) {
                $oldAssignees = $tache->assignees->pluck('id')->toArray();
                $tache->assignees()->sync($assigneeIds);

                // Notifier assignés
                $added = array_diff($assigneeIds, $oldAssignees);
                $removed = array_diff($oldAssignees, $assigneeIds);

                foreach ($added as $userId) {
                    $user = User::find($userId);
                    if ($user) $user->notify(new TacheAssignedNotification($tache, auth()->user()));
                }

                foreach ($removed as $userId) {
                    $user = User::find($userId);
                    if ($user) $user->notify(new TacheUnassignedNotification($tache, auth()->user()));
                }
            }

            if ($labelIds !== null) {
                $tache->labels()->sync($labelIds);
            }

            // ✅ Gérer les nouveaux fichiers
            if (!empty($uploadedFiles)) {
                $this->handleFileUploads($tache, $uploadedFiles, auth()->user());
            }

            // ✅ Gérer les liens externes - remplacement complet
            if (array_key_exists('external_links', $data) || !empty($externalLinks)) {
                // Supprimer les anciens liens
                $tache->externalLinks()->delete();

                // Ajouter les nouveaux liens
                if (!empty($externalLinks)) {
                    $this->addExternalLinks($tache, $externalLinks, auth()->user());
                }
            }

            // Notifier modification
            foreach ($tache->assignees as $assignee) {
                $assignee->notify(new TacheUpdatedNotification($tache, auth()->user()));
            }

            // ✅ Log modification
            activity()
                ->causedBy(auth()->user())
                ->performedOn($tache)
                ->withProperties(['changes' => $data])
                ->log('Tâche mise à jour');

            Log::info('Service updateTache - Tâche mise à jour avec succès', [
                'tache_id' => $tache->id
            ]);

            return $tache->fresh(['activite', 'assignees', 'labels', 'attachments', 'externalLinks']);
        });
    }


    /**
     * ✅ Déterminer les informations de semaine à partir des dates
     */
    protected function determineWeekInfo(array $data): array
    {
        // Priorité: date_debut > echeance > maintenant
        if (isset($data['date_debut'])) {
            $date = Carbon::parse($data['date_debut']);
        } elseif (isset($data['echeance'])) {
            $date = Carbon::parse($data['echeance']);
        } else {
            $date = now();
        }

        return self::getWeekInfo($date);
    }

    /**
     * Supprimer une tâche
     */
    public function deleteTache(Tache $tache): void
    {
        $tache->delete();
    }

    /**
     * ✅ Déplacer tâche (Kanban)
     */
    public function moveTache(Tache $tache, TacheStatut $newStatut, int $newPosition): Tache
    {
        return DB::transaction(function () use ($tache, $newStatut, $newPosition) {
            $oldStatut = $tache->statut;
            $oldPosition = $tache->position;

            if ($oldStatut === $newStatut) {
                $this->reorderTachesInStatus($tache->activite_id, $newStatut, $oldPosition, $newPosition);
            } else {
                // Ajuster ordre ancien statut
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $oldStatut)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                // Ajuster ordre nouveau statut
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $newStatut)
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            }

            $tache->update([
                'statut' => $newStatut,
                'position' => $newPosition,
            ]);

            return $tache->fresh(['activite', 'assignees']);
        });
    }

    /**
     * Réordonner tâches dans même statut
     */
    protected function reorderTachesInStatus(int $activiteId, TacheStatut $statut, int $oldPosition, int $newPosition): void
    {
        if ($oldPosition === $newPosition) {
            return;
        }

        if ($oldPosition < $newPosition) {
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('position', '>', $oldPosition)
                ->where('position', '<=', $newPosition)
                ->decrement('position');
        } else {
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('position', '>=', $newPosition)
                ->where('position', '<', $oldPosition)
                ->increment('position');
        }
    }

    /**
     * Dupliquer une tâche
     */
    public function duplicateTache(Tache $tache): Tache
    {
        return DB::transaction(function () use ($tache) {
            $newTache = $tache->replicate([
                'validated_n1_by',
                'validated_n1_at',
                'validated_n2_by',
                'validated_n2_at',
            ]);

            $newTache->titre = $tache->titre . ' (Copie)';
            $newTache->taux_realisation = 0;
            $newTache->statut = TacheStatut::A_FAIRE;

            $maxPosition = Tache::where('activite_id', $tache->activite_id)
                ->where('statut', TacheStatut::A_FAIRE)
                ->max('position');
            $newTache->position = ($maxPosition ?? -1) + 1;

            $newTache->save();

            // Copier assignés
            $assigneeIds = $tache->assignees->pluck('id')->toArray();
            $newTache->assignees()->attach($assigneeIds);

            // Copier labels
            $labelIds = $tache->labels->pluck('id')->toArray();
            $newTache->labels()->attach($labelIds);

            return $newTache->load(['activite', 'assignees', 'labels']);
        });
    }

    /**
     * Archiver tâche
     */
    public function archiveTache(Tache $tache): Tache
    {
        $tache->archive();
        return $tache->load(['activite', 'assignees', 'labels']);
    }

    /**
     * Désarchiver tâche
     */
    public function unarchiveTache(Tache $tache): Tache
    {
        $tache->unarchive();
        return $tache->load(['activite', 'assignees', 'labels']);
    }

    /**
     * ✅ Assigner utilisateur avec permissions
     */
    public function assignUser(Tache $tache, array $data): Tache
    {
        $userId = $data['user_id'];

        if (!$tache->assignees->contains($userId)) {
            $tache->assignees()->attach($userId, [
                'role' => $data['role'] ?? 'collaborator',
                'can_edit' => $data['can_edit'] ?? false,
                'can_complete' => $data['can_complete'] ?? true,
                'can_validate' => $data['can_validate'] ?? false,
            ]);
        }

        return $tache->fresh(['activite', 'assignees']);
    }

    /**
     * Désassigner utilisateur
     */
    public function unassignUser(Tache $tache, int $userId): Tache
    {
        $tache->assignees()->detach($userId);
        return $tache->fresh(['activite', 'assignees']);
    }

    /**
     * ✅ Rapport hebdomadaire pour un utilisateur avec détails
     */
    public function getWeeklyReport(User $user, ?int $weekNumber = null, ?int $year = null): array
    {
        $weekInfo = self::getWeekInfo();
        $weekNumber = $weekNumber ?? $weekInfo['week_number'];
        $year = $year ?? $weekInfo['year'];

        $taches = Tache::assignedTo($user->id)
            ->forWeek($weekNumber, $year)
            ->with(['activite.projet', 'labels', 'validatedN1By', 'validatedN2By', 'resultats'])
            ->get();

        // Statistiques détaillées
        $stats = [
            'total' => $taches->count(),
            'completed' => $taches->where('statut', TacheStatut::TERMINE)->count(),
            'in_progress' => $taches->where('statut', TacheStatut::EN_COURS)->count(),
            'pending' => $taches->where('statut', TacheStatut::A_FAIRE)->count(),
            'overdue' => $taches->filter->is_overdue->count(),
            'validated_n1' => $taches->whereNotNull('validated_n1_at')->count(),
            'validated_n2' => $taches->whereNotNull('validated_n2_at')->count(),
            'with_results' => $taches->filter(fn($t) => $t->resultats->count() > 0)->count(),
            'estimated_hours' => $taches->sum('estimated_hours'),
            'actual_hours' => $taches->sum('actual_hours'),
        ];

        $stats['completion_rate'] = $stats['total'] > 0
            ? round(($stats['completed'] / $stats['total']) * 100, 2)
            : 0;

        $stats['validation_rate'] = $stats['completed'] > 0 ? round(($stats['validated_n2'] / $stats['completed']) * 100, 2) : 0;

        $stats['time_variance'] = $stats['estimated_hours'] > 0 ? round((($stats['actual_hours'] - $stats['estimated_hours']) / $stats['estimated_hours']) * 100, 2) : 0;

        // Grouper par activité
        $tachesByActivite = $taches->groupBy('activite_id')->map(function ($groupedTaches) {
            $activite = $groupedTaches->first()->activite;
            return [
                'activite' => [
                    'id' => $activite->id,
                    'nom' => $activite->nom,
                    'projet_nom' => $activite->projet->nom ?? null,
                ],
                'tasks' => $groupedTaches->values(),
                'count' => $groupedTaches->count(),
                'completed' => $groupedTaches->where('statut', TacheStatut::TERMINE)->count(),
            ];
        });

        return [
            'week_number' => $weekNumber,
            'year' => $year,
            'week_dates' => [
                'start' => Carbon::now()->setISODate($year, $weekNumber)->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->setISODate($year, $weekNumber)->endOfWeek()->format('Y-m-d'),
            ],
            'user' => $user->only(['id', 'nom', 'email']),
            'statistics' => $stats,
            'tasks_by_activite' => $tachesByActivite->values(),
            'all_tasks' => $taches,
        ];
    }

    /**
     * ✅ Performance d'équipe avec analyses détaillées
     */
    public function getTeamPerformance(int $activiteId, ?int $weekNumber = null, ?int $year = null): array
    {
        $weekInfo = self::getWeekInfo();
        $weekNumber = $weekNumber ?? $weekInfo['week_number'];
        $year = $year ?? $weekInfo['year'];

        $activite = Activite::with('projet')->findOrFail($activiteId);

        $taches = Tache::forActivite($activiteId)
            ->forWeek($weekNumber, $year)
            ->with(['assignees', 'resultats'])
            ->get();

        // Statistiques par utilisateur
        $userStats = [];
        foreach ($taches as $tache) {
            foreach ($tache->assignees as $user) {
                if (!isset($userStats[$user->id])) {
                    $userStats[$user->id] = [
                        'user' => $user->only(['id', 'nom', 'email', 'avatar']),
                        'total' => 0,
                        'completed' => 0,
                        'in_progress' => 0,
                        'overdue' => 0,
                        'validated' => 0,
                        'estimated_hours' => 0,
                        'actual_hours' => 0,
                    ];
                }

                $userStats[$user->id]['total']++;

                if ($tache->statut === TacheStatut::TERMINE) {
                    $userStats[$user->id]['completed']++;
                }

                if ($tache->statut === TacheStatut::EN_COURS) {
                    $userStats[$user->id]['in_progress']++;
                }

                if ($tache->is_overdue) {
                    $userStats[$user->id]['overdue']++;
                }

                if ($tache->validated_n2_at) {
                    $userStats[$user->id]['validated']++;
                }

                $userStats[$user->id]['estimated_hours'] += $tache->estimated_hours ?? 0;
                $userStats[$user->id]['actual_hours'] += $tache->actual_hours ?? 0;
            }
        }

        // Ajouter taux de complétion
        foreach ($userStats as $userId => &$stats) {
            $stats['completion_rate'] = $stats['total'] > 0
                ? round(($stats['completed'] / $stats['total']) * 100, 2)
                : 0;

            $stats['time_variance'] = $stats['estimated_hours'] > 0
                ? round((($stats['actual_hours'] - $stats['estimated_hours']) / $stats['estimated_hours']) * 100, 2)
                : 0;
        }

        return [
            'week_number' => $weekNumber,
            'year' => $year,
            'activite' => [
                'id' => $activite->id,
                'nom' => $activite->nom,
                'projet' => $activite->projet->only(['id', 'nom']),
            ],
            'team_stats' => array_values($userStats),
            'overall' => [
                'total_tasks' => $taches->count(),
                'completed' => $taches->where('statut', TacheStatut::TERMINE)->count(),
                'completion_rate' => $taches->count() > 0
                    ? round(($taches->where('statut', TacheStatut::TERMINE)->count() / $taches->count()) * 100, 2)
                    : 0,
                'estimated_hours' => $taches->sum('estimated_hours'),
                'actual_hours' => $taches->sum('actual_hours'),
            ]
        ];
    }


}