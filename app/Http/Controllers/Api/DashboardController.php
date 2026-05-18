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
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Get dashboard data with member filtering
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Filtrage par membre
        $memberId = $request->input('member_id');
        $projectStatus = $request->input('project_status');
        $priority = $request->input('priority');

        // Workspaces accessibles (super_admin voit tous)
        $workspaceIds = $user->isSuperAdmin()
            ? Workspace::pluck('id')
            : Workspace::accessibleBy($user->id)->pluck('id');

        if ($request->has('workspace_id') && $request->workspace_id) {
            $workspaceIds = collect([$request->workspace_id]);
        }

        // Projets avec filtres
        $projetsQuery = Projet::accessibleBy($user->id)
            ->whereIn('workspace_id', $workspaceIds);

        // Filtre par statut de projet
        if ($projectStatus) {
            $projetsQuery->where('status', $projectStatus);
        }

        $projets = $projetsQuery->get();
        $projetIds = $projets->pluck('id');

        // Tâches avec filtres
        $activiteIds = Activite::whereIn('projet_id', $projetIds)->pluck('id');
        $tachesQuery = Tache::whereIn('activite_id', $activiteIds);

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

        return response()->json([
            'stats' => $this->calculateStats($projets, $taches, $myTasks),
            'monthly_progress' => $this->getMonthlyProgress($user, $workspaceIds),
            'recent_projects' => $this->getRecentProjects($projets),
            'my_tasks' => $myTasks,
            'team_members' => $teamMembers,
            // ... autres données existantes
        ]);
    }

    /**
     * Calculate statistics with member context
     */
    private function calculateStats($projets, $taches, $myTasks)
    {
        return [
            'projets_actifs' => [
                'value' => $projets->where('status', 'active')->count(),
                'change' => '+12%',
                'trend' => 'up',
            ],
            'taux_completion' => [
                'value' => $taches->count() > 0 ?
                    round(($taches->where('statut', TacheStatut::TERMINE)->count() / $taches->count()) * 100) : 0,
                'change' => '+5%',
                'trend' => 'up',
            ],
            'taches_en_retard' => [
                'value' => $taches->filter(fn ($t) => $t->isOverdue())->count(),
                'change' => '-2%',
                'trend' => 'down',
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
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $projets = Projet::accessibleBy($user->id)
                ->whereIn('workspace_id', $workspaceIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $projetIds = Projet::accessibleBy($user->id)
                ->whereIn('workspace_id', $workspaceIds)
                ->pluck('id');

            $activiteIds = Activite::whereIn('projet_id', $projetIds)->pluck('id');

            $taches = Tache::whereIn('activite_id', $activiteIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $completes = Tache::whereIn('activite_id', $activiteIds)
                ->where('statut', TacheStatut::TERMINE)
                ->whereBetween('date_fin_reelle', [$startOfMonth, $endOfMonth])
                ->count();

            $months[] = [
                'month' => $date->locale('fr')->isoFormat('MMM'),
                'projets' => $projets,
                'taches' => $taches,
                'completes' => $completes,
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
            ->where('status', 'active')
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
                    'team' => $projet->members()->count(),
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
