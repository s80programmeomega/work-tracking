<?php

namespace App\Http\Controllers\Api;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use App\Http\Controllers\Controller;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AdaptiveCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __construct(private AdaptiveCache $cache) {}

    /**
     * Get dashboard data with member filtering.
     *
     * Mis en cache (~60 s, TTL adaptatif) : agrégats coûteux (~34 requêtes) sur
     * la page la plus consultée. CLÉ STRICTEMENT par utilisateur + workspace +
     * filtres → aucune fuite inter-tenant ; fraîcheur ≤ ~1 min (acceptable pour
     * un tableau de bord statistique).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $memberId = $request->input('member_id');
        $projectStatus = $request->input('project_status');
        $priority = $request->input('priority');
        $workspaceParam = $request->has('workspace_id') ? $request->workspace_id : 'accessible';

        // Clé de cache cloisonnée : un utilisateur ne voit jamais les données d'un
        // autre, ni d'un autre workspace, ni d'un autre jeu de filtres.
        $cacheKey = 'dashboard:'.$user->id.':'.$workspaceParam
            .':m='.($memberId ?? '-').':s='.($projectStatus ?? '-').':p='.($priority ?? '-');

        $payload = $this->cache->remember($cacheKey, 60, fn () => $this->computeDashboard($request, $user, $memberId, $projectStatus, $priority));

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function computeDashboard(Request $request, $user, $memberId, $projectStatus, $priority): array
    {

        // Workspaces accessibles (super_admin voit tous)
        $workspaceIds = $user->isSuperAdmin()
            ? Workspace::pluck('id')
            : Workspace::accessibleBy($user->id)->pluck('id');

        if ($request->has('workspace_id') && $request->workspace_id) {
            $workspaceIds = collect([$request->workspace_id]);
        }

        // Projets avec filtres
        $projetsQuery = Projet::accessibleBy($user->id)
            ->whereIn('workspace_id', $workspaceIds)
            // Perf : eager-load des relations lues par les helpers (évite des N+1
            // sur responsable/activites/membres dans getRecentProjects).
            ->with(['responsable:id,nom,prenom', 'activites:id,projet_id'])
            ->withCount('members');

        // Filtre par statut de projet
        if ($projectStatus) {
            $projetsQuery->where('status', $projectStatus);
        }

        $projets = $projetsQuery->get();
        $projetIds = $projets->pluck('id');

        // Tâches avec filtres
        $activiteIds = Activite::whereIn('projet_id', $projetIds)->pluck('id');
        // Perf : eager-load activite.projet + assignees (lus par getUrgentTasks /
        // myTasks) pour éviter les N+1 par tâche.
        $tachesQuery = Tache::whereIn('activite_id', $activiteIds)
            ->with(['activite:id,projet_id,nom', 'activite.projet:id,nom,code', 'assignees:id,nom_complet']);

        // Filtre par membre assigné
        if ($memberId) {
            $tachesQuery->whereHas('assignees', function ($q) use ($memberId) {
                $q->where('user_id', $memberId);
            });
        }

        // Filtre par priorité
        if ($priority) {
            $priorityEnum = match ($priority) {
                'high' => TachePriorite::ELEVEE,
                'medium' => TachePriorite::MOYENNE,
                'low' => TachePriorite::FAIBLE,
            };
            $tachesQuery->where('priorite', $priorityEnum);
        }

        $taches = $tachesQuery->get();

        // Mes tâches (assignées à l'utilisateur connecté)
        $myTasks = Tache::whereIn('activite_id', $activiteIds)
            ->assignedTo($user->id)
            ->where('statut', '!=', TacheStatut::TERMINE)
            ->orderBy('echeance')
            ->get()
            ->take(5)
            ->map(function ($tache) {
                return [
                    'id' => $tache->id,
                    'title' => $tache->titre,
                    'project' => $tache->activite->projet->nom,
                    'priority' => $tache->priorite->value,
                    'due_date' => $tache->echeance?->format('d M Y'),
                    'status' => $tache->statut->value,
                    'is_overdue' => $tache->isOverdue(),
                ];
            });

        // Membres de l'équipe
        $teamMembers = User::whereHas('workspaces', function ($q) use ($workspaceIds) {
            $q->whereIn('workspaces.id', $workspaceIds);
        })
            ->withCount(['taches' => function ($q) use ($activiteIds) {
                $q->whereIn('activite_id', $activiteIds)
                    ->where('statut', '!=', TacheStatut::TERMINE);
            }])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'nom' => $member->nom,
                    'prenom' => $member->prenom,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'taches_count' => $member->taches_count,
                ];
            });

        return [
            'stats' => $this->calculateStats($projets, $taches, $myTasks),
            'monthly_progress' => $this->getMonthlyProgress($user),
            'recent_projects' => $this->getRecentProjects($projets),
            'my_tasks' => $myTasks,
            'team_members' => $teamMembers,
            // ... autres données existantes
        ];
    }

    /**
     * Calculate statistics with member context, including real
     * period-over-period (vs. il y a 1 mois) change/trend.
     */
    private function calculateStats($projets, $taches, $myTasks)
    {
        $previousCutoff = now()->subMonth();

        // Projets actifs : nombre actuel vs. nombre de projets actifs il y a 1 mois.
        // "Il y a 1 mois" = projets créés avant la coupure (les nouveaux du mois
        // n'existaient pas encore à cette date).
        $activeProjetsNow = $projets->where('status', 'active')->count();
        $activeProjetsPrevious = $projets
            ->where('status', 'active')
            ->filter(fn ($p) => $p->created_at && $p->created_at->lt($previousCutoff))
            ->count();

        // Taux de complétion : taux actuel (toutes tâches) vs. taux sur les
        // tâches qui existaient déjà il y a 1 mois (snapshot basé sur created_at).
        // Pour les "terminées avant la coupure" on utilise updated_at comme proxy
        // de date de complétion (date_fin_reelle n'est pas chargée ici).
        $tachesPrevious = $taches->filter(fn ($t) => $t->created_at && $t->created_at->lt($previousCutoff));
        $completionNow = $taches->count() > 0
            ? round(($taches->where('statut', TacheStatut::TERMINE)->count() / $taches->count()) * 100)
            : 0;
        $completionPrevious = $tachesPrevious->count() > 0
            ? round(($tachesPrevious->filter(fn ($t) => $t->statut === TacheStatut::TERMINE
                && $t->updated_at && $t->updated_at->lt($previousCutoff))->count() / $tachesPrevious->count()) * 100)
            : 0;

        // Tâches en retard : nombre actuel vs. nombre qui étaient en retard il y a
        // 1 mois (échéance dépassée par rapport à la coupure, créées avant la coupure,
        // et toujours non terminées aujourd'hui — proxy fiable sans date_fin_reelle).
        $overdueNow = $taches->filter(fn ($t) => $t->isOverdue())->count();
        $overduePrevious = $taches->filter(fn ($t) => $t->created_at
            && $t->created_at->lt($previousCutoff)
            && $t->echeance
            && $t->echeance->lt($previousCutoff)
            && $t->statut !== TacheStatut::TERMINE
        )->count();

        return [
            'projets_actifs' => [
                'value' => $activeProjetsNow,
                'change' => $this->calculateChange($activeProjetsNow, $activeProjetsPrevious),
                'trend' => $activeProjetsNow >= $activeProjetsPrevious ? 'up' : 'down',
            ],
            'taux_completion' => [
                'value' => $completionNow,
                'change' => $this->calculateChange($completionNow, $completionPrevious),
                'trend' => $completionNow >= $completionPrevious ? 'up' : 'down',
            ],
            'taches_en_retard' => [
                'value' => $overdueNow,
                'change' => $this->calculateChange($overdueNow, $overduePrevious),
                'trend' => $overdueNow <= $overduePrevious ? 'up' : 'down',
            ],
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;
        $sign = $change >= 0 ? '+' : '';

        return $sign.round($change).'%';
    }

    /**
     * Get monthly progress data for the last 6 months
     */
    private function getMonthlyProgress($user)
    {
        $workspaceIds = Workspace::accessibleBy($user->id)->pluck('id');

        // Perf : ces ensembles d'IDs ne dépendent PAS du mois — on les calcule
        // UNE fois au lieu de les recharger à chaque itération (évitait 12 requêtes
        // redondantes sur 6 mois).
        $projetIds = Projet::accessibleBy($user->id)
            ->whereIn('workspace_id', $workspaceIds)
            ->pluck('id');
        $activiteIds = Activite::whereIn('projet_id', $projetIds)->pluck('id');

        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        // Perf : 3 requêtes GROUPÉES par mois sur toute la fenêtre (au lieu de
        // 18 comptes mois par mois). On agrège ensuite par clé "Y-m".
        $countByMonth = function ($query) use ($start, $end): Collection {
            return $query
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym');
        };

        $projetsByMonth = $countByMonth(
            Projet::accessibleBy($user->id)->whereIn('workspace_id', $workspaceIds)
        );
        $tachesByMonth = $countByMonth(
            Tache::query()->whereIn('activite_id', $activiteIds)
        );
        // Complétées : groupées par date de fin réelle (et non de création).
        $completesByMonth = Tache::whereIn('activite_id', $activiteIds)
            ->where('statut', TacheStatut::TERMINE)
            ->whereBetween('date_fin_reelle', [$start, $end])
            ->selectRaw("DATE_FORMAT(date_fin_reelle, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');

            $months[] = [
                'month' => $date->locale('fr')->isoFormat('MMM'),
                'projets' => (int) ($projetsByMonth[$key] ?? 0),
                'taches' => (int) ($tachesByMonth[$key] ?? 0),
                'completes' => (int) ($completesByMonth[$key] ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Get task status distribution
     */
    private function getStatusDistribution($taches)
    {
        return [
            [
                'name' => 'Terminé',
                'value' => $taches->where('statut', TacheStatut::TERMINE)->count(),
                'color' => '#10b981',
            ],
            [
                'name' => 'En Cours',
                'value' => $taches->where('statut', TacheStatut::EN_COURS)->count(),
                'color' => '#3b82f6',
            ],
            [
                'name' => 'À Faire',
                'value' => $taches->where('statut', TacheStatut::A_FAIRE)->count(),
                'color' => '#f59e0b',
            ],
            [
                'name' => 'En Retard',
                'value' => $taches->filter(fn ($t) => $t->isOverdue())->count(),
                'color' => '#ef4444',
            ],
        ];
    }

    /**
     * Get task priority distribution
     */
    private function getPriorityDistribution($taches)
    {
        return [
            [
                'name' => 'Élevée',
                'value' => $taches->where('priorite', TachePriorite::ELEVEE)->count(),
                'color' => '#ef4444',
            ],
            [
                'name' => 'Moyenne',
                'value' => $taches->where('priorite', TachePriorite::MOYENNE)->count(),
                'color' => '#f59e0b',
            ],
            [
                'name' => 'faible',
                'value' => $taches->where('priorite', TachePriorite::FAIBLE)->count(),
                'color' => '#3b82f6',
            ],
        ];
    }

    /**
     * Get recent projects with details
     */
    private function getRecentProjects($projets)
    {
        return $projets
            ->whereIn('status', ['active', 'completed'])
            ->sortByDesc('updated_at')
            ->take(3)
            ->map(function ($projet) {
                $activiteIds = $projet->activites->pluck('id');
                $taches = Tache::whereIn('activite_id', $activiteIds)->get();

                return [
                    'id' => $projet->id,
                    'name' => $projet->nom,
                    'code' => $projet->code,
                    'progress' => $projet->progression,
                    'status' => $projet->status,
                    'due_date' => $projet->date_fin?->format('d M Y'),
                    'team' => $projet->members_count ?? $projet->members()->count(),
                    'tasks' => [
                        'total' => $taches->count(),
                        'completed' => $taches->where('statut', TacheStatut::TERMINE)->count(),
                    ],
                    'responsable' => [
                        'id' => $projet->responsable?->id,
                        'name' => $projet->responsable?->name,
                    ],
                ];
            })
            ->values();
    }

    /**
     * Get urgent tasks (high priority, due soon)
     */
    private function getUrgentTasks($taches)
    {
        return $taches
            ->filter(function ($tache) {
                return $tache->priorite === TachePriorite::ELEVEE
                    && $tache->statut !== TacheStatut::TERMINE
                    && $tache->echeance
                    && $tache->echeance->lte(now()->addDays(7))
                    && $tache->isOverdue(); // ✅ Utilisation de la méthode corrigée

            })
            ->sortBy('echeance')
            ->take(5)
            ->map(function ($tache) {
                return [
                    'id' => $tache->id,
                    'title' => $tache->titre,
                    'code' => $tache->code,
                    'project' => $tache->activite->projet->nom,
                    'project_code' => $tache->activite->projet->code,
                    'assignees' => $tache->assignees->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                    ]),
                    'priority' => match ($tache->priorite) {
                        TachePriorite::ELEVEE => 'Élevée',
                        TachePriorite::MOYENNE => 'Moyenne',
                        TachePriorite::FAIBLE => 'faible',
                    },
                    'due_date' => $tache->echeance?->format('d M Y'),
                    'status' => match ($tache->statut) {
                        TacheStatut::EN_COURS => 'En cours',
                        TacheStatut::A_FAIRE => 'À faire',
                        TacheStatut::EN_ATTENTE => 'En attente',
                        TacheStatut::TERMINE => 'Terminé',
                    },
                    'is_overdue' => $tache->isOverdue(),
                ];
            })
            ->values();
    }

    /**
     * Get workspace-specific statistics
     */
    private function getWorkspaceStats($user)
    {
        $workspaces = Workspace::accessibleBy($user->id)
            ->with(['projets', 'members'])
            ->get();

        return $workspaces->map(function ($workspace) {
            $stats = $workspace->getStatistics();

            return [
                'id' => $workspace->id,
                'name' => $workspace->nom,
                'code' => $workspace->code,
                'stats' => $stats,
                'is_owner' => $workspace->isOwner(auth()->user()),
            ];
        });
    }

    /**
     * Get tasks by workspace
     */
    public function tasksByWorkspace(Request $request, $workspaceId)
    {
        $user = $request->user();

        $workspace = Workspace::accessibleBy($user->id)
            ->findOrFail($workspaceId);

        $projets = $workspace->projets;
        $activiteIds = Activite::whereIn('projet_id', $projets->pluck('id'))->pluck('id');
        $taches = Tache::whereIn('activite_id', $activiteIds)->get();

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->nom,
                'code' => $workspace->code,
            ],
            'stats' => [
                'total' => $taches->count(),
                'en_cours' => $taches->where('statut', TacheStatut::EN_COURS)->count(),
                'termine' => $taches->where('statut', TacheStatut::TERMINE)->count(),
                'en_retard' => $taches->filter(fn ($t) => $t->isOverdue())->count(),
            ],
            'tasks' => $taches->map(function ($tache) {
                return [
                    'id' => $tache->id,
                    'titre' => $tache->titre,
                    'code' => $tache->code,
                    'statut' => $tache->statut->value,
                    'priorite' => $tache->priorite->value,
                    'echeance' => $tache->echeance?->format('Y-m-d'),
                    'taux_realisation' => $tache->taux_realisation,
                ];
            }),
        ]);
    }

    /**
     * Get user's personal dashboard stats
     */
    public function personalStats(Request $request)
    {
        $user = $request->user();

        // Tasks assigned to user
        $myTasks = Tache::assignedTo($user->id)->get();

        // Projects where user is responsable
        $myProjects = Projet::where('responsable_id', $user->id)->get();

        return response()->json([
            'my_tasks' => [
                'total' => $myTasks->count(),
                'en_cours' => $myTasks->where('statut', TacheStatut::EN_COURS)->count(),
                'termine' => $myTasks->where('statut', TacheStatut::TERMINE)->count(),
                'en_retard' => $myTasks->filter(fn ($t) => $t->isOverdue())->count(),
            ],
            'my_projects' => [
                'total' => $myProjects->count(),
                'actifs' => $myProjects->where('status', 'active')->count(),
                'completion_moyenne' => $myProjects->avg('progression'),
            ],
            'recent_activity' => $this->getRecentActivity($user),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($user),
        ]);
    }

    /**
     * Get team members for workspace
     */
    public function getTeamMembers(Request $request, $workspaceId)
    {
        $user = $request->user();

        $workspace = Workspace::accessibleBy($user->id)->findOrFail($workspaceId);

        $teamMembers = $workspace->members()
            ->withPivot(['role_id'])
            ->withCount(['taches' => function ($q) use ($workspace) {
                $q->whereHas('activite.projet', function ($pq) use ($workspace) {
                    $pq->where('workspace_id', $workspace->id);
                })->where('statut', '!=', TacheStatut::TERMINE);
            }])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'nom' => $member->nom,
                    'prenom' => $member->prenom,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'taches_count' => $member->taches_count,
                    'role' => Role::find($member->pivot->role_id)?->name ?? 'collaborateur',
                ];
            });

        return response()->json([
            'data' => $teamMembers,
        ]);
    }

    /**
     * Get recent activity for user
     */
    private function getRecentActivity($user)
    {
        // This would integrate with activity log
        // For now, return recent task updates
        return Tache::assignedTo($user->id)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($tache) {
                return [
                    'id' => $tache->id,
                    'type' => 'task_update',
                    'title' => $tache->titre,
                    'description' => 'Tâche mise à jour',
                    'timestamp' => $tache->updated_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get upcoming deadlines for user
     */
    private function getUpcomingDeadlines($user)
    {
        return Tache::assignedTo($user->id)
            ->where('statut', '!=', TacheStatut::TERMINE->value)
            ->whereNotNull('echeance')
            ->where('echeance', '>=', now())
            ->where('echeance', '<=', now()->addDays(14))
            ->orderBy('echeance')
            ->get()
            ->map(function ($tache) {
                return [
                    'id' => $tache->id,
                    'title' => $tache->titre,
                    'code' => $tache->code,
                    'due_date' => $tache->echeance->format('Y-m-d'),
                    'days_remaining' => now()->diffInDays($tache->echeance),
                    'priority' => $tache->priorite->value,
                    'project' => $tache->activite->projet->nom,
                ];
            });
    }
}
