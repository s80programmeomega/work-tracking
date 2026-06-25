<?php

namespace App\Services;

use App\Enums\TacheStatut;
use App\Events\Realtime\TacheStatutChanged;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\TacheExternalLink;
use App\Models\User;
use App\Notifications\TacheAssigneeNotification;
use App\Notifications\TacheResourcesNotification;
use App\Notifications\Taches\TacheFileAddedNotification;
use App\Notifications\Taches\TacheFileRemovedNotification;
use App\Notifications\Taches\TacheLinkAddedNotification;
use App\Notifications\Taches\TacheLinkRemovedNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $query = Tache::query()->with(['activite.projet', 'assignees', 'labels'])->withCount('sousTaches');

        // ✅ Appliquer les permissions via Policy
        if (! $user->isSuperAdmin()) {
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
                    })
                    // OU manager/supérieur dans le workspace : voit toutes les tâches du workspace
                    ->orWhereHas('activite.projet.workspace.members', function ($mq) use ($user) {
                        $managerRoleIds = Role::whereIn('name', ['owner', 'manager', 'directeur', 'super_admin'])
                            ->pluck('id');
                        $mq->where('user_id', $user->id)
                            ->whereIn('role_id', $managerRoleIds);
                    });
            });
        }

        // Filtre par workspace
        if (! empty($filters['workspace_id'])) {
            $query->whereHas('activite.projet', fn ($q) => $q->where('workspace_id', $filters['workspace_id']));
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

        // Filtre par plage de dates (écheance)
        if (! empty($filters['date_from'])) {
            $query->where('echeance', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('echeance', '<=', $filters['date_to']);
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
                    'createdBy:id,nom',
                ])
                ->withCount('sousTaches')
                ->orderBy('position')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Tâches récupérées', [
                'activite_id' => $activiteId,
                'total' => $taches->count(),
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
                'termine' => count($kanban['termine']),
            ]);

            return $kanban;

        } catch (\Exception $e) {
            Log::error('Erreur Service getKanbanForActivite', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            ->withCount('sousTaches')
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * ✅ Gérer l'upload de fichiers pour une tâche
     */
    public function handleFileUploads(Tache $tache, array $files, User $uploadedBy): void
    {
        $notifService = app(NotificationService::class);

        foreach ($files as $file) {
            $attachment = $this->uploadFile($tache, $file, $uploadedBy);

            // Notifier tous les assignees sauf l'uploader (G2 garde-fou).
            foreach ($tache->assignees as $assignee) {
                $notifService->sendUnlessSelf(
                    $assignee,
                    $uploadedBy,
                    new TacheFileAddedNotification($tache, $uploadedBy, $attachment)
                );
            }
        }
    }

    /**
     * ✅ Uploader un fichier individuel
     */
    public function uploadFile(Tache $tache, $file, User $uploadedBy): TacheAttachment
    {
        $originalName = $file->getClientOriginalName();
        $fileName = time().'_'.uniqid().'_'.$originalName;
        $filePath = $file->storeAs('tache-attachments', $fileName, 'uploads');

        if (! $filePath) {
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
     * ✅ NOUVEAU : Gérer les liens externes
     */
    protected function handleExternalLinks(Tache $tache, $links, User $user): void
    {
        // Si c'est une string JSON, la décoder
        if (is_string($links)) {
            $links = json_decode($links, true);
        }

        if (! is_array($links)) {
            return;
        }

        foreach ($links as $link) {
            try {
                // Validation
                if (! isset($link['url']) || ! filter_var($link['url'], FILTER_VALIDATE_URL)) {
                    continue;
                }

                TacheExternalLink::create([
                    'tache_id' => $tache->id,
                    'url' => $link['url'],
                    'title' => $link['title'] ?? $link['url'],
                    'created_by' => $user->id,
                ]);

                Log::info('✅ Lien externe ajouté', [
                    'tache_id' => $tache->id,
                    'url' => $link['url'],
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Erreur ajout lien externe', [
                    'tache_id' => $tache->id,
                    'link' => $link,
                    'error' => $e->getMessage(),
                ]);
                // Continuer avec les autres liens
            }
        }
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

        // Notifier les assignees sauf l'acteur (G2).
        if ($deleted) {
            $notifService = app(NotificationService::class);
            foreach ($tache->assignees as $assignee) {
                $notifService->sendUnlessSelf(
                    $assignee,
                    $deletedBy,
                    new TacheFileRemovedNotification($tache, $deletedBy, $attachment)
                );
            }
        }

        return $deleted;
    }

    /**
     * ✅ Ajouter des liens externes
     */
    public function addExternalLinks(Tache $tache, array $links, User $createdBy): void
    {
        $notifService = app(NotificationService::class);

        foreach ($links as $link) {
            $linkModel = TacheExternalLink::create([
                'tache_id' => $tache->id,
                'title' => $link['title'] ?? $link['url'],
                'url' => $link['url'],
                'created_by' => $createdBy->id,
            ]);

            // Notifier les assignees sauf l'acteur (G2).
            foreach ($tache->assignees as $assignee) {
                $notifService->sendUnlessSelf(
                    $assignee,
                    $createdBy,
                    new TacheLinkAddedNotification($tache, $createdBy, $linkModel)
                );
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

        // Notifier les assignees sauf l'acteur (G2).
        if ($deleted) {
            $notifService = app(NotificationService::class);
            foreach ($tache->assignees as $assignee) {
                $notifService->sendUnlessSelf(
                    $assignee,
                    $deletedBy,
                    new TacheLinkRemovedNotification($tache, $deletedBy, $link)
                );
            }
        }

        return $deleted;
    }

    /**
     * ✅ Créer une tâche avec gestion automatique des semaines
     */
    public function createTache(array $data, User $user): Tache
    {
        DB::beginTransaction();

        try {
            // ✅ S'assurer que le responsable est dans les assignés
            if (isset($data['responsable_id'])) {
                if (! isset($data['assignee_ids']) || ! is_array($data['assignee_ids'])) {
                    $data['assignee_ids'] = [];
                }

                if (! in_array($data['responsable_id'], $data['assignee_ids'])) {
                    $data['assignee_ids'][] = $data['responsable_id'];
                }
            }

            // Extraire les relations
            $assigneeIds = $data['assignee_ids'] ?? [];
            $labelIds = $data['label_ids'] ?? [];
            $uploadedFiles = $data['uploaded_files'] ?? [];
            $externalLinks = $data['external_links'] ?? [];

            unset(
                $data['assignee_ids'],
                $data['label_ids'],
                $data['uploaded_files'],
                $data['external_links']
            );

            // Ajouter l'utilisateur créateur
            $data['created_by'] = $user->id;

            // Créer la tâche
            $tache = Tache::create($data);

            // ✅ Assigner les utilisateurs avec rôle spécial pour le responsable
            if (! empty($assigneeIds)) {
                $collaborateurRoleId = Role::findByName('collaborateur', 'web')->id;
                $assignData = [];
                foreach ($assigneeIds as $userId) {
                    $isResponsable = ($userId == $data['responsable_id']);

                    $assignData[$userId] = [
                        'role_id' => $collaborateurRoleId,
                        'is_responsable' => $isResponsable,
                        'can_edit' => $isResponsable,
                        'can_complete' => true,
                        'can_validate' => $isResponsable,
                        'statut_individuel' => 'a_faire',
                        'progression_individuelle' => 0,
                    ];
                }

                $tache->assignees()->attach($assignData);

                Log::info('✅ Assignés attachés avec responsable', [
                    'tache_id' => $tache->id,
                    'responsable_id' => $data['responsable_id'],
                    'total_assignes' => count($assigneeIds),
                ]);
            }

            // Attacher les labels
            if (! empty($labelIds)) {
                $tache->labels()->attach($labelIds);
            }

            // Gérer les fichiers uploadés
            if (! empty($uploadedFiles)) {
                $this->handleFileUploads($tache, $uploadedFiles, $user);
            }

            // Gérer les liens externes
            if (! empty($externalLinks)) {
                $this->handleExternalLinks($tache, $externalLinks, $user);
            }

            DB::commit();

            $fresh = $tache->fresh([
                'activite',
                'assignees',
                'labels',
                'attachments',
                'externalLinks',
                'responsable',
            ]);

            // Task 11 — notifier les intervenants après commit (hors transaction).
            $this->notifyIntervenantsOnCreation($fresh, $user);

            return $fresh;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur création tâche', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Task 11 — Notifie chaque intervenant (sauf le créateur) et déclenche
     * TacheResourcesNotification si la tâche possède des ressources.
     */
    private function notifyIntervenantsOnCreation(Tache $tache, User $creator): void
    {
        $resources = $this->buildResourceList($tache);

        foreach ($tache->assignees as $assignee) {
            if ($assignee->id === $creator->id) {
                continue;
            }

            $assignee->notify(new TacheAssigneeNotification($tache, $creator, $resources));

            if (count($resources) > 0) {
                $assignee->notify(new TacheResourcesNotification($tache, $creator, $resources));
            }
        }

        Log::info('Notifications intervenants envoyées après création tâche wizard', [
            'tache_id' => $tache->id,
            'intervenants_count' => $tache->assignees->count(),
            'resources_count' => count($resources),
        ]);
    }

    /**
     * Construit la liste des ressources (fichiers + liens) pour les notifications.
     *
     * @return array<int, array{nom: string, url: string|null}>
     */
    private function buildResourceList(Tache $tache): array
    {
        $resources = [];

        foreach ($tache->attachments ?? [] as $attachment) {
            $resources[] = [
                'nom' => $attachment->original_name ?? $attachment->file_name ?? 'Fichier',
                'url' => null,
            ];
        }

        foreach ($tache->externalLinks ?? [] as $link) {
            $resources[] = [
                'nom' => $link->title ?? $link->url,
                'url' => $link->url,
            ];
        }

        return $resources;
    }

    /**
     * Règle R6 (Task 9) — verrou post-N2.
     *
     * Dès qu'au moins un résultat de la tâche a été validé au niveau N2,
     * la tâche devient immuable: aucune édition, suppression, déplacement,
     * archivage ou (dés)assignation n'est autorisée.
     *
     * On lève une HttpException 422 plutôt qu'une exception de domaine
     * pure: Laravel la convertit automatiquement en JSON 422 sans que
     * le contrôleur n'ait à l'attraper. Cela garde la règle R6 valable
     * pour tout futur appelant de TacheService.
     */
    private function guardPostN2Immutability(Tache $tache): void
    {
        if (! $tache->isLockedPostN2()) {
            return;
        }

        \Log::warning('Tentative de modification d\'une tâche verrouillée post-N2', [
            'tache_id' => $tache->id,
            'reason' => 'immutable_post_n2',
        ]);

        throw new HttpException(
            422,
            __('evaluation.errors.immutable_post_n2')
        );
    }

    /**
     * ✅ Mettre à jour une tâche avec gestion COMPLÈTE des fichiers
     */
    public function updateTache(Tache $tache, array $data): Tache
    {
        $this->guardPostN2Immutability($tache);

        DB::beginTransaction();

        try {
            // ✅ S'assurer que le responsable est dans les assignés
            if (isset($data['responsable_id'])) {
                if (! isset($data['assignee_ids']) || ! is_array($data['assignee_ids'])) {
                    $data['assignee_ids'] = $tache->assignees->pluck('id')->toArray();
                }

                if (! in_array($data['responsable_id'], $data['assignee_ids'])) {
                    $data['assignee_ids'][] = $data['responsable_id'];
                }
            }

            // Extraire les relations
            $assigneeIds = $data['assignee_ids'] ?? null;
            $labelIds = $data['label_ids'] ?? null;
            $uploadedFiles = $data['uploaded_files'] ?? [];
            $externalLinks = $data['external_links'] ?? [];

            unset(
                $data['assignee_ids'],
                $data['label_ids'],
                $data['uploaded_files'],
                $data['external_links']
            );

            // Mettre à jour les champs de base
            $tache->update($data);

            // ✅ Mettre à jour les assignés si fournis
            if ($assigneeIds !== null) {
                $assignData = [];
                foreach ($assigneeIds as $userId) {
                    $isResponsable = isset($data['responsable_id']) && ($userId == $data['responsable_id']);

                    // Garder les données existantes si l'utilisateur était déjà assigné
                    $existing = $tache->assignees()->where('user_id', $userId)->first();

                    $collaborateurRole = Role::findByName('collaborateur', 'web');
                    $assignData[$userId] = [
                        'role_id' => $existing?->pivot->role_id ?? $collaborateurRole->id,
                        'is_responsable' => $isResponsable,
                        'can_edit' => $isResponsable ? true : ($existing?->pivot->can_edit ?? false),
                        'can_complete' => $existing?->pivot->can_complete ?? true,
                        'can_validate' => $isResponsable ? true : ($existing?->pivot->can_validate ?? false),
                        'statut_individuel' => $existing?->pivot->statut_individuel ?? 'a_faire',
                        'progression_individuelle' => $existing?->pivot->progression_individuelle ?? 0,
                    ];
                }

                $tache->assignees()->sync($assignData);

                Log::info('✅ Assignés synchronisés avec responsable', [
                    'tache_id' => $tache->id,
                    'responsable_id' => $data['responsable_id'] ?? null,
                    'total_assignes' => count($assigneeIds),
                ]);
            } elseif (isset($data['responsable_id'])) {
                // Si seul le responsable a changé mais pas la liste des assignés
                $currentResponsableId = $data['responsable_id'];

                if (! $tache->isAssignedTo(User::find($currentResponsableId))) {
                    $collaborateurRoleId = Role::findByName('collaborateur', 'web')->id;
                    $tache->assignees()->attach($currentResponsableId, [
                        'role_id' => $collaborateurRoleId,
                        'is_responsable' => true,
                        'can_edit' => true,
                        'can_complete' => true,
                        'can_validate' => true,
                        'statut_individuel' => 'a_faire',
                        'progression_individuelle' => 0,
                    ]);
                }
            }

            // Mettre à jour les labels si fournis
            if ($labelIds !== null) {
                $tache->labels()->sync($labelIds);
            }

            // Gérer les nouveaux fichiers uploadés
            if (! empty($uploadedFiles)) {
                $this->handleFileUploads($tache, $uploadedFiles, auth()->user());
            }

            // Gérer les nouveaux liens externes
            if (! empty($externalLinks)) {
                $this->handleExternalLinks($tache, $externalLinks, auth()->user());
            }

            DB::commit();

            return $tache->fresh([
                'activite',
                'assignees',
                'labels',
                'attachments',
                'externalLinks',
                'responsable',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur mise à jour tâche', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
        $this->guardPostN2Immutability($tache);
        $tache->delete();
    }

    /**
     * ✅ Déplacer tâche (Kanban)
     */
    public function moveTache(Tache $tache, TacheStatut $newStatut, int $newPosition): Tache
    {
        $this->guardPostN2Immutability($tache);

        return DB::transaction(function () use ($tache, $newStatut, $newPosition) {
            $oldStatut = $tache->statut;
            $oldPosition = $tache->position ?? 0;

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

            $fresh = $tache->fresh(['activite', 'assignees']);
            broadcast(new TacheStatutChanged($fresh))->toOthers();

            return $fresh;
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

            $newTache->titre = $tache->titre.' (Copie)';
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
        $this->guardPostN2Immutability($tache);

        $tache->archive();

        return $tache->load(['activite', 'assignees', 'labels']);
    }

    /**
     * Désarchiver tâche
     */
    public function unarchiveTache(Tache $tache): Tache
    {
        $this->guardPostN2Immutability($tache);

        $tache->unarchive();

        return $tache->load(['activite', 'assignees', 'labels']);
    }

    /**
     * ✅ Assigner utilisateur avec permissions
     */
    public function assignUser(Tache $tache, array $data): Tache
    {
        $this->guardPostN2Immutability($tache);

        $userId = $data['user_id'];

        if (! $tache->assignees->contains($userId)) {
            $roleName = $data['role'] ?? 'collaborateur';
            $roleId = Role::findByName($roleName, 'web')->id;
            $tache->assignees()->attach($userId, [
                'role_id' => $roleId,
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
        $this->guardPostN2Immutability($tache);

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
            ->withCount('sousTaches')
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
            'with_results' => $taches->filter(fn ($t) => $t->resultats->count() > 0)->count(),
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
            ->withCount('sousTaches')
            ->get();

        // Statistiques par utilisateur
        $userStats = [];
        foreach ($taches as $tache) {
            foreach ($tache->assignees as $user) {
                if (! isset($userStats[$user->id])) {
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
            ],
        ];
    }
}
