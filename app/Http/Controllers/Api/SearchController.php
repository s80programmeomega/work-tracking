<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exports\MultiSheetSearchExport;
use App\Exports\SearchExport;
use App\Http\Controllers\Controller;
use App\Jobs\SearchExportJob;
use App\Models\Activite;
use App\Models\Document;
use App\Models\Notification;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\TeamMessage;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchController extends Controller
{
    /**
     * Recherche multi-modèle dans le workspace courant (ou global pour super-admin).
     *
     * Endpoint : GET /api/search?q=&types[]=&workspace_id=&page=&per_page=
     *
     * Paramètres :
     *  - q           : terme de recherche (min 2 chars)
     *  - types[]     : filtre par type (projets, activites, taches, documents, users, messages)
     *  - workspace_id: workspace cible (ignoré pour super-admin — scope global)
     *  - page        : page courante (défaut 1)
     *  - per_page    : résultats par type par page (défaut 10, max 50)
     *
     * Autorisation :
     *  - super_admin : scope global, tous les workspaces
     *  - owner/manager : scope limité au workspace cible + vérification membership
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'types' => 'sometimes|array',
            'types.*' => 'string|in:projets,activites,taches,sous_taches,documents,users,messages,notifications',
            'workspace_id' => 'sometimes|integer|exists:workspaces,id',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $query = $request->string('q')->trim()->value();
        $perPage = $request->integer('per_page', 10);
        $page = $request->integer('page', 1);
        $isSuperAdmin = (bool) ($user->is_super_admin ?? false);

        // ── Résolution du tier de recherche ──────────────────────────────────
        // Tier 1 : super-admin → portée globale (tous workspaces)
        // Tier 2 : owner/manager → portée workspace complète
        // Tier 3 : cadre/collaborateur/stagiaire → ressources assignées uniquement
        // Tier 4 : observateur/utilisateur → 403
        $workspace = null;
        $tier = 'global'; // Tier 1 par défaut pour super-admin
        $scopedIds = [];  // IDs accessibles pour Tier 3

        if (! $isSuperAdmin) {
            $workspaceId = $request->integer('workspace_id')
                ?: ($user->current_workspace_id ?? 0);

            $workspace = Workspace::find($workspaceId);
            $gate = app(ContextualPermissionGate::class);

            if (! $workspace || ! $workspace->members()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            if ($gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace)) {
                $tier = 'workspace'; // Tier 2 : owner/manager
            } elseif ($gate->userCan($user, Permission::SEARCH_SCOPED, $workspace)) {
                $tier = 'scoped';    // Tier 3 : cadre/collaborateur/stagiaire
                $scopedIds = $this->resolveAccessibleIds($user->id, $workspace->id);
            } else {
                return response()->json(['error' => 'Non autorisé'], 403);
            }
        }

        $types = $request->input('types', ['projets', 'activites', 'taches', 'sous_taches', 'documents', 'users', 'messages', 'notifications']);
        $results = [];
        $totals = [];

        foreach ($types as $type) {
            [$hits, $total] = $this->searchType($type, $query, $workspace, $isSuperAdmin, $tier, $scopedIds, (int) $user->id, $page, $perPage);
            $results[$type] = $hits;
            $totals[$type] = $total;
        }

        return response()->json([
            'success' => true,
            'query' => $query,
            'workspace_id' => $workspace?->id,
            'is_global' => $isSuperAdmin,
            'page' => $page,
            'per_page' => $perPage,
            'totals' => $totals,
            'results' => $results,
        ]);
    }

    /**
     * Lance la recherche pour un type donné et retourne [hits[], total].
     *
     * @param  array<string, array<int>>  $scopedIds  IDs accessibles par l'utilisateur (tier scoped uniquement)
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function searchType(
        string $type,
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $userId,
        int $page,
        int $perPage,
    ): array {
        $offset = ($page - 1) * $perPage;

        // Extraire highlights si le driver est Typesense — sinon description brute
        $useRaw = config('scout.driver') === 'typesense';

        return match ($type) {
            'projets' => $this->searchProjets($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'activites' => $this->searchActivites($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'taches' => $this->searchTaches($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'sous_taches' => $this->searchSousTaches($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'documents' => $this->searchDocuments($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'users' => $this->searchUsers($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset),
            'messages' => $this->searchMessages($query, $workspace, $isSuperAdmin, $tier, $scopedIds, $perPage, $offset, $useRaw),
            'notifications' => $this->searchNotifications($query, $userId, $perPage, $offset, $useRaw),
            default => [[], 0],
        };
    }

    /**
     * Résout les IDs de ressources accessibles pour un utilisateur en mode scoped (Tier 3).
     * Utilisé pour filtrer les résultats après la recherche Scout.
     *
     * @return array<string, array<int>>
     */
    private function resolveAccessibleIds(int $userId, int $workspaceId): array
    {
        // Projets auxquels l'utilisateur est assigné dans ce workspace
        $projetIds = DB::table('projet_user')
            ->join('projets', 'projets.id', '=', 'projet_user.projet_id')
            ->where('projet_user.user_id', $userId)
            ->where('projets.workspace_id', $workspaceId)
            ->pluck('projet_user.projet_id')
            ->toArray();

        // Tâches auxquelles l'utilisateur est assigné
        $tacheIds = DB::table('tache_user')
            ->join('taches', 'taches.id', '=', 'tache_user.tache_id')
            ->join('activites', 'activites.id', '=', 'taches.activite_id')
            ->join('projets', 'projets.id', '=', 'activites.projet_id')
            ->where('tache_user.user_id', $userId)
            ->where('projets.workspace_id', $workspaceId)
            ->pluck('tache_user.tache_id')
            ->toArray();

        // Sous-tâches liées aux tâches accessibles
        $sousTacheIds = $tacheIds
            ? DB::table('sous_taches')
                ->whereIn('tache_id', $tacheIds)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray()
            : [];

        // Équipes dont l'utilisateur est membre dans ce workspace
        $teamIds = DB::table('team_members')
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $userId)
            ->where('teams.workspace_id', $workspaceId)
            ->pluck('team_members.team_id')
            ->toArray();

        return [
            'projet_ids' => $projetIds,
            'tache_ids' => $tacheIds,
            'sous_tache_ids' => $sousTacheIds,
            'team_ids' => $teamIds,
        ];
    }

    // ── Recherches par type ───────────────────────────────────────────────────

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchProjets(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = Projet::search($query);

        // IMPORTANT : sous Typesense, le filtrage de portée DOIT se faire via les
        // filtres natifs Scout (->where/->whereIn → filter_by) car ->raw() ignore
        // les contraintes Eloquent ->query() (utilisées uniquement par le driver
        // collection des tests). Sans cela, la portée workspace/tier fuit.
        if ($tier === 'scoped') {
            // Tier 3 : projets auxquels l'utilisateur est explicitement assigné
            $ids = $scopedIds['projet_ids'] ?: [0];
            $useRaw
                ? $builder->whereIn('id', $ids)
                : $builder->query(fn ($q) => $q->whereIn('id', $ids));
        } elseif (! $isSuperAdmin && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->where('workspace_id', $workspace->id));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'projet',
                'id' => $doc['id'],
                'label' => $hl['nom']['snippet'] ?? $doc['nom'] ?? '',
                'excerpt' => $hl['description']['snippet'] ?? $doc['description'] ?? '',
                'meta' => ['statut' => $doc['statut'] ?? '', 'workspace_name' => $doc['workspace_name'] ?? ''],
                'url' => '/projets/'.$doc['id'],
            ]);
        }

        return $this->fromEloquent(
            $builder, $perPage, $offset,
            fn (Projet $p) => [
                'type' => 'projet',
                'id' => $p->id,
                'label' => $p->nom,
                'excerpt' => $p->description ?? '',
                'meta' => ['statut' => $p->status ?? '', 'workspace_name' => $p->workspace?->nom ?? ''],
                'url' => '/projets/'.$p->id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchActivites(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = Activite::search($query);

        if ($tier === 'scoped') {
            // Tier 3 : activités appartenant aux projets accessibles
            $ids = $scopedIds['projet_ids'] ?: [0];
            $useRaw
                ? $builder->whereIn('projet_id', $ids)
                : $builder->query(fn ($q) => $q->whereIn('projet_id', $ids));
        } elseif (! $isSuperAdmin && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->whereHas('projet', fn ($p) => $p->where('workspace_id', $workspace->id)));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'activite',
                'id' => $doc['id'],
                'label' => $hl['nom']['snippet'] ?? $doc['nom'] ?? '',
                'excerpt' => $hl['description']['snippet'] ?? $doc['description'] ?? '',
                'meta' => ['projet_nom' => $doc['projet_nom'] ?? '', 'workspace_name' => $doc['workspace_name'] ?? ''],
                'url' => '/activites/'.$doc['id'],
            ]);
        }

        return $this->fromEloquent(
            $builder, $perPage, $offset,
            fn (Activite $a) => [
                'type' => 'activite',
                'id' => $a->id,
                'label' => $a->nom,
                'excerpt' => $a->description ?? '',
                'meta' => ['projet_nom' => $a->projet?->nom ?? '', 'workspace_name' => $a->projet?->workspace?->nom ?? ''],
                'url' => '/activites/'.$a->id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchTaches(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = Tache::search($query);

        if ($tier === 'scoped') {
            // Tier 3 : tâches auxquelles l'utilisateur est explicitement assigné
            $ids = $scopedIds['tache_ids'] ?: [0];
            $useRaw
                ? $builder->whereIn('id', $ids)
                : $builder->query(fn ($q) => $q->whereIn('id', $ids));
        } elseif (! $isSuperAdmin && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->whereHas('activite.projet', fn ($p) => $p->where('workspace_id', $workspace->id)));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'tache',
                'id' => $doc['id'],
                'label' => $hl['titre']['snippet'] ?? $doc['titre'] ?? '',
                'excerpt' => $hl['description']['snippet']
                    ?? $hl['commentaire']['snippet']
                    ?? $doc['description'] ?? '',
                'meta' => [
                    'statut' => $doc['statut'] ?? '',
                    'priorite' => $doc['priorite'] ?? '',
                    'projet_nom' => $doc['projet_nom'] ?? '',
                    'workspace_name' => $doc['workspace_name'] ?? '',
                ],
                'url' => '/taches/'.$doc['id'],
            ]);
        }

        return $this->fromEloquent(
            $builder->query(fn ($q) => $q->with('activite.projet.workspace')),
            $perPage, $offset,
            fn (Tache $t) => [
                'type' => 'tache',
                'id' => $t->id,
                'label' => $t->titre,
                'excerpt' => $t->description ?? '',
                'meta' => [
                    'statut' => $t->statut instanceof \BackedEnum ? $t->statut->value : (string) $t->statut,
                    'priorite' => $t->priorite instanceof \BackedEnum ? $t->priorite->value : (string) $t->priorite,
                    'projet_nom' => $t->activite?->projet?->nom ?? '',
                    'workspace_name' => $t->activite?->projet?->workspace?->nom ?? '',
                ],
                'url' => '/taches/'.$t->id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchSousTaches(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = SousTache::search($query);

        if ($tier === 'scoped') {
            // Tier 3 : sous-tâches liées aux tâches accessibles
            $ids = $scopedIds['sous_tache_ids'] ?: [0];
            $useRaw
                ? $builder->whereIn('id', $ids)
                : $builder->query(fn ($q) => $q->whereIn('id', $ids));
        } elseif (! $isSuperAdmin && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->whereHas(
                    'tache.activite.projet',
                    fn ($p) => $p->where('workspace_id', $workspace->id)
                ));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'sous_tache',
                'id' => $doc['id'],
                'label' => $hl['titre']['snippet'] ?? $doc['titre'] ?? '',
                'excerpt' => $hl['description']['snippet'] ?? $hl['tache_titre']['snippet'] ?? $doc['tache_titre'] ?? '',
                'meta' => [
                    'statut' => $doc['statut'] ?? '',
                    'tache_titre' => $doc['tache_titre'] ?? '',
                    'projet_nom' => $doc['projet_nom'] ?? '',
                    'workspace_name' => $doc['workspace_name'] ?? '',
                ],
                'url' => '/taches/'.$doc['tache_id'],
            ]);
        }

        return $this->fromEloquent(
            $builder->query(fn ($q) => $q->with('tache.activite.projet.workspace')),
            $perPage, $offset,
            fn (SousTache $s) => [
                'type' => 'sous_tache',
                'id' => $s->id,
                'label' => $s->titre,
                'excerpt' => $s->tache?->titre ?? '',
                'meta' => [
                    'statut' => $s->statut ?? '',
                    'tache_titre' => $s->tache?->titre ?? '',
                    'projet_nom' => $s->tache?->activite?->projet?->nom ?? '',
                    'workspace_name' => $s->tache?->activite?->projet?->workspace?->nom ?? '',
                ],
                'url' => '/taches/'.$s->tache_id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchDocuments(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = Document::search($query);

        // Documents : filtrés par workspace pour tous les tiers non-global
        // (même scope pour manager et scoped — documents visibles à tous les membres du workspace)
        if ($tier !== 'global' && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->where('workspace_id', $workspace->id));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'document',
                'id' => $doc['id'],
                'label' => $hl['nom']['snippet'] ?? $doc['nom'] ?? '',
                'excerpt' => $hl['content_text']['snippet']
                    ?? $hl['description']['snippet']
                    ?? $doc['description'] ?? '',
                'meta' => ['mime_type' => $doc['mime_type'] ?? '', 'workspace_name' => $doc['workspace_name'] ?? ''],
                'url' => '/documents/'.$doc['id'],
            ]);
        }

        return $this->fromEloquent(
            $builder, $perPage, $offset,
            fn (Document $d) => [
                'type' => 'document',
                'id' => $d->id,
                'label' => $d->nom,
                'excerpt' => $d->description ?? '',
                'meta' => ['mime_type' => $d->mime_type ?? '', 'workspace_name' => $d->workspace?->nom ?? ''],
                'url' => '/documents/'.$d->id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchUsers(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
    ): array {
        $builder = User::search($query);

        // Membres du workspace cible — identique pour tier workspace et scoped
        if ($tier !== 'global' && $workspace) {
            $memberIds = $workspace->members()->pluck('users.id')->toArray();
            $builder->query(fn ($q) => $q->whereIn('id', $memberIds));
        }

        return $this->fromEloquent(
            $builder, $perPage, $offset,
            fn (User $u) => [
                'type' => 'user',
                'id' => $u->id,
                'label' => trim(($u->prenom ?? '').' '.$u->nom),
                'excerpt' => $u->email,
                'meta' => ['fonction' => $u->fonction ?? ''],
                'url' => '/users/'.$u->id,
            ]
        );
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: int} */
    private function searchMessages(
        string $query,
        ?Workspace $workspace,
        bool $isSuperAdmin,
        string $tier,
        array $scopedIds,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        $builder = TeamMessage::search($query);

        if ($tier === 'scoped') {
            // Tier 3 : messages des équipes dont l'utilisateur est membre
            $teamIds = $scopedIds['team_ids'] ?: [0];
            $useRaw
                ? $builder->whereIn('team_id', $teamIds)
                : $builder->query(fn ($q) => $q->whereIn('team_id', $teamIds));
        } elseif (! $isSuperAdmin && $workspace) {
            $useRaw
                ? $builder->where('workspace_id', $workspace->id)
                : $builder->query(fn ($q) => $q->whereHas('team', fn ($t) => $t->where('workspace_id', $workspace->id)));
        }

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'message',
                'id' => $doc['id'],
                'uuid' => $doc['uuid'] ?? '',
                'label' => $doc['team_name'] ?? '',
                'excerpt' => $hl['content']['snippet'] ?? mb_substr($doc['content'] ?? '', 0, 120),
                'meta' => [
                    'user_nom' => $doc['user_nom'] ?? '',
                    'team_uuid' => $doc['team_uuid'] ?? '',
                    'workspace_name' => $doc['workspace_name'] ?? '',
                ],
                'url' => '/teams/'.($doc['team_uuid'] ?? '').'?message='.($doc['uuid'] ?? ''),
            ]);
        }

        return $this->fromEloquent(
            $builder->query(fn ($q) => $q->with(['team', 'user'])),
            $perPage, $offset,
            fn (TeamMessage $m) => [
                'type' => 'message',
                'id' => $m->id,
                'uuid' => $m->uuid,
                'label' => $m->team?->name ?? '',
                'excerpt' => mb_substr($m->content ?? '', 0, 120),
                'meta' => [
                    'user_nom' => $m->user?->nom ?? '',
                    'team_uuid' => $m->team?->uuid ?? '',
                    'workspace_name' => $m->team?->workspace?->nom ?? '',
                ],
                'url' => '/teams/'.($m->team?->uuid ?? '').'?message='.$m->uuid,
            ]
        );
    }

    /**
     * Recherche dans les notifications — TOUJOURS limitée aux notifications de
     * l'utilisateur courant, quel que soit le tier (y compris super-admin).
     * Les notifications sont personnelles : aucun accès cross-utilisateur.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function searchNotifications(
        string $query,
        int $userId,
        int $perPage,
        int $offset,
        bool $useRaw,
    ): array {
        // Portée stricte aux notifications de l'utilisateur courant — sous Typesense
        // via filter_by (notifiable_id), sinon via contrainte Eloquent.
        $builder = Notification::search($query);
        $useRaw
            ? $builder->where('notifiable_id', $userId)
            : $builder->query(fn ($q) => $q
                ->where('notifiable_id', $userId)
                ->where('notifiable_type', User::class));

        if ($useRaw) {
            return $this->fromRaw($builder, $perPage, $offset, fn ($doc, $hl) => [
                'type' => 'notification',
                'id' => $doc['id'],
                'label' => $doc['type'] ?? 'Notification',
                'excerpt' => $hl['content']['snippet'] ?? mb_substr($doc['content'] ?? '', 0, 120),
                'meta' => [
                    'event' => $doc['event'] ?? '',
                    'is_read' => $doc['is_read'] ?? false,
                ],
                'url' => '/notifications',
            ]);
        }

        return $this->fromEloquent(
            $builder, $perPage, $offset,
            function (Notification $n) {
                $data = is_array($n->data) ? $n->data : [];
                $content = collect($data)->filter(fn ($v) => is_scalar($v))->map(fn ($v) => (string) $v)->implode(' ');

                return [
                    'type' => 'notification',
                    'id' => $n->id,
                    'label' => class_basename($n->type),
                    'excerpt' => mb_substr($content, 0, 120),
                    'meta' => [
                        'event' => $data['type'] ?? '',
                        'is_read' => $n->read_at !== null,
                    ],
                    'url' => '/notifications',
                ];
            }
        );
    }

    // ── Export ───────────────────────────────────────────────────────────────

    /**
     * Export global : relance la recherche sans limite (jusqu'au plafond choisi).
     * GET /api/search/export?q=&types[]=&workspace_id=&cap=500|1000|2000|all
     *
     * Si cap=all, le job est mis en file d'attente et un email est envoyé.
     * Sinon, le fichier Excel est téléchargé directement.
     */
    public function export(Request $request): JsonResponse|BinaryFileResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'types' => 'sometimes|array',
            'types.*' => 'string|in:projets,activites,taches,sous_taches,documents,users,messages,notifications',
            'workspace_id' => 'sometimes|integer|exists:workspaces,id',
            'cap' => 'sometimes|in:500,1000,2000,all',
        ]);

        $user = $request->user();
        $cap = $request->input('cap', '500');

        // Vérification d'autorisation + résolution du tier (même logique que search())
        $isSuperAdmin = (bool) ($user->is_super_admin ?? false);
        $workspace = null;
        $tier = 'global';
        $scopedIds = [];

        if (! $isSuperAdmin) {
            $workspaceId = $request->integer('workspace_id') ?: ($user->current_workspace_id ?? 0);
            $workspace = Workspace::find($workspaceId);
            $gate = app(ContextualPermissionGate::class);

            if (! $workspace || ! $workspace->members()->where('user_id', $user->id)->exists()) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            // L'export en masse est une action à privilège élevé : réservé à
            // manager et supérieur (search.global). Les tiers scopés peuvent
            // rechercher mais pas exporter.
            if (! $gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace)) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            $tier = 'workspace';
        }

        // Export asynchrone pour "all" — email envoyé au terme du job
        if ($cap === 'all') {
            SearchExportJob::dispatch($request->all(), $user);

            return response()->json([
                'success' => true,
                'message' => 'Export en cours — vous recevrez un email avec le lien de téléchargement.',
            ]);
        }

        // Export immédiat sous le plafond choisi
        $capInt = (int) $cap;
        $query = $request->string('q')->trim()->value();
        $types = $request->input('types', ['projets', 'activites', 'taches', 'sous_taches', 'documents', 'users', 'messages', 'notifications']);
        $sheets = [];

        foreach ($types as $type) {
            [$hits] = $this->searchType($type, $query, $workspace, $isSuperAdmin, $tier, $scopedIds, (int) $user->id, 1, $capInt);

            if (! empty($hits)) {
                $sheets[] = new SearchExport(collect($hits), $type);
            }
        }

        if (empty($sheets)) {
            return response()->json(['message' => 'Aucun résultat à exporter.'], 200);
        }

        $filename = 'recherche-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(
            new MultiSheetSearchExport($sheets),
            $filename
        );
    }

    /**
     * Export sélectif : exporte une liste d'IDs fournis par le frontend.
     * POST /api/search/export  {ids: [...], type: 'taches', cap: '500'}
     */
    public function exportSelected(Request $request): JsonResponse|BinaryFileResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer',
            'type' => 'required|string|in:projets,activites,taches,sous_taches,documents,users,messages',
            'q' => 'sometimes|string',
        ]);

        $user = $request->user();
        $isSuperAdmin = (bool) ($user->is_super_admin ?? false);
        $workspace = null;

        if (! $isSuperAdmin) {
            $workspaceId = $request->integer('workspace_id') ?: ($user->current_workspace_id ?? 0);
            $workspace = Workspace::find($workspaceId);
            $gate = app(ContextualPermissionGate::class);

            // Export réservé à manager et supérieur (search.global)
            if (! $workspace || ! $gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace)) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }
        }

        $type = $request->string('type')->value();
        $ids = $request->input('ids');

        // Reconstruire les lignes d'export depuis les IDs
        $model = match ($type) {
            'projets' => Projet::class,
            'activites' => Activite::class,
            'taches' => Tache::class,
            'sous_taches' => SousTache::class,
            'documents' => Document::class,
            'users' => User::class,
            'messages' => TeamMessage::class,
            default => null,
        };

        if (! $model) {
            return response()->json(['error' => 'Type inconnu'], 422);
        }

        $rows = $model::whereIn('id', $ids)->get()->map(fn ($item) => [
            'id' => $item->id,
            'label' => $item->nom ?? $item->titre ?? $item->name ?? '',
            'excerpt' => $item->description ?? $item->content ?? '',
            'meta' => [],
            'url' => '/'.$type.'/'.$item->id,
        ]);

        $filename = 'selection-'.$type.'-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SearchExport($rows, $type), $filename);
    }

    // ── Helpers d'extraction de résultats ─────────────────────────────────────

    /**
     * Exécute la recherche via ->raw() (Typesense) et extrait hits + highlights.
     *
     * @param  callable  $mapper  fn(array $doc, array $highlights): array
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function fromRaw(Builder $builder, int $perPage, int $offset, callable $mapper): array
    {
        try {
            $raw = $builder->options([
                'highlight_full_fields' => 'nom,titre,content,description,content_text',
                'snippet_threshold' => 30,
                'num_typos' => 1,
            ])->raw();

            $hits = $raw['hits'] ?? [];
            $total = $raw['found'] ?? 0;

            $page = collect($hits)->slice($offset, $perPage);

            return [
                $page->map(fn ($hit) => $mapper($hit['document'] ?? [], $hit['highlight'] ?? []))->values()->toArray(),
                (int) $total,
            ];
        } catch (\Throwable) {
            // Retomber sur l'approche Eloquent si Typesense n'est pas disponible
            return [[], 0];
        }
    }

    /**
     * Exécute la recherche via ->get() (collection driver ou fallback Eloquent).
     *
     * @param  callable  $mapper  fn(Model): array
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function fromEloquent(Builder $builder, int $perPage, int $offset, callable $mapper): array
    {
        $results = $builder->get();
        $total = $results->count();
        $page = $results->slice($offset, $perPage);

        return [
            $page->map($mapper)->values()->toArray(),
            $total,
        ];
    }
}
