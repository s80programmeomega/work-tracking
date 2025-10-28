<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
  /**
   * Display a listing of workspaces for authenticated user
   */
  public function index(Request $request)
  {
    $user = $request->user();

    $workspaces = Workspace::where(function ($query) use ($user) {
      $query->where('owner_id', $user->id)
        ->orWhereHas('members', function ($q) use ($user) {
          $q->where('user_id', $user->id);
        });
    })
      ->withCount('projets')
      ->with(['owner:id,nom,email,avatar'])
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('nom', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
        });
      })
      ->when($request->is_active !== null, function ($query) use ($request) {
        $query->where('is_active', $request->is_active);
      })
      ->orderBy('created_at', 'desc')
      ->paginate($request->per_page ?? 15);

    return response()->json($workspaces);
  }

  /**
   * Store a newly created workspace
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'nom' => 'required|string|max:255',
      'description' => 'nullable|string',
      'logo' => 'nullable|image|max:2048',
      'settings' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
      // Generate unique code
      $code = $this->generateUniqueCode();

      $workspace = Workspace::create([
        'nom' => $validated['nom'],
        'description' => $validated['description'] ?? null,
        'code' => $code,
        'owner_id' => $request->user()->id,
        'settings' => $validated['settings'] ?? [],
        'is_active' => true,
      ]);

      // Handle logo upload
      if ($request->hasFile('logo')) {
        $logoPath = $request->file('logo')->store('workspaces/logos', 'public');
        $workspace->update(['logo' => $logoPath]);
      }

      // Automatically add creator as member with full permissions
      $workspace->members()->attach($request->user()->id, [
        'role' => 'owner',
        'permissions' => ['all'],
        'invited_at' => now(),
        'invited_by' => $request->user()->id,
      ]);

      DB::commit();

      $workspace->load(['owner', 'members']);
      $workspace->loadCount('projets');

      return response()->json([
        'message' => 'Workspace créé avec succès',
        'data' => $workspace,
      ], 201);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Erreur lors de la suppression du workspace',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get workspace members
   */
  public function members(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $members = $workspace->members()
      ->withPivot(['role', 'permissions', 'invited_at', 'invited_by'])
      ->with([
        'teamMemberships' => function ($query) use ($workspace) {
          $query->whereHas('team', function ($q) use ($workspace) {
            $q->whereHas('project', function ($p) use ($workspace) {
              $p->where('workspace_id', $workspace->id);
            });
          });
        }
      ])
      ->get();

    return response()->json([
      'data' => $members,
    ]);
  }

  /**
   * Add a member to workspace
   */
  public function addMember(Request $request, Workspace $workspace)
  {
    // Check if user is owner or admin
    if (!$this->userCanManageMembers($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Vous n\'avez pas la permission d\'ajouter des membres'
      ], 403);
    }

    $validated = $request->validate([
      'user_id' => 'required|exists:users,id',
      'role' => ['required', Rule::in(['owner', 'admin', 'member', 'viewer'])],
      'permissions' => 'nullable|array',
    ]);

    // Check if user is already a member
    if ($workspace->members()->where('user_id', $validated['user_id'])->exists()) {
      return response()->json([
        'message' => 'Cet utilisateur est déjà membre du workspace'
      ], 422);
    }

    $workspace->members()->attach($validated['user_id'], [
      'role' => $validated['role'],
      'permissions' => $validated['permissions'] ?? [],
      'invited_at' => now(),
      'invited_by' => $request->user()->id,
    ]);

    $member = User::find($validated['user_id']);

    // TODO: Send notification to invited user

    return response()->json([
      'message' => 'Membre ajouté avec succès',
      'data' => $member,
    ], 201);
  }

  /**
   * Update workspace member
   */
  public function updateMember(Request $request, Workspace $workspace, User $user)
  {
    if (!$this->userCanManageMembers($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Vous n\'avez pas la permission de modifier les membres'
      ], 403);
    }

    $validated = $request->validate([
      'role' => ['sometimes', Rule::in(['owner', 'admin', 'member', 'viewer'])],
      'permissions' => 'nullable|array',
    ]);

    if (!$workspace->members()->where('user_id', $user->id)->exists()) {
      return response()->json([
        'message' => 'Cet utilisateur n\'est pas membre du workspace'
      ], 404);
    }

    $workspace->members()->updateExistingPivot($user->id, $validated);

    return response()->json([
      'message' => 'Membre mis à jour avec succès',
    ]);
  }

  /**
   * Remove a member from workspace
   */
  public function removeMember(Request $request, Workspace $workspace, User $user)
  {
    if (!$this->userCanManageMembers($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Vous n\'avez pas la permission de retirer des membres'
      ], 403);
    }

    // Cannot remove workspace owner
    if ($workspace->owner_id === $user->id) {
      return response()->json([
        'message' => 'Impossible de retirer le propriétaire du workspace'
      ], 422);
    }

    if (!$workspace->members()->where('user_id', $user->id)->exists()) {
      return response()->json([
        'message' => 'Cet utilisateur n\'est pas membre du workspace'
      ], 404);
    }

    DB::beginTransaction();

    try {
      // Detach member from workspace
      $workspace->members()->detach($user->id);

      // Remove from all projects in this workspace
      $projets = $workspace->projets;
      foreach ($projets as $projet) {
        $projet->members()->detach($user->id);

        // Revoke access to project tasks and documents
        foreach ($projet->activites as $activite) {
          foreach ($activite->taches as $tache) {
            $tache->assignees()->detach($user->id);
          }
        }
      }

      DB::commit();

      // TODO: Send notification to removed user

      return response()->json([
        'message' => 'Membre retiré avec succès',
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Erreur lors du retrait du membre',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get workspace projects
   */
  public function projets(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $projets = $workspace->projets()
      ->with(['responsable:id,nom,avatar', 'members:id,nom,avatar'])
      ->withCount(['activites', 'members'])
      ->when($request->status, function ($query, $status) {
        $query->where('status', $status);
      })
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('nom', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
        });
      })
      ->orderBy('created_at', 'desc')
      ->paginate($request->per_page ?? 15);

    return response()->json($projets);
  }

  /**
   * Get workspace statistics
   */
  public function statistics(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $stats = [
      'total_projets' => $workspace->projets()->count(),
      'projets_actifs' => $workspace->projets()->where('status', 'active')->count(),
      'projets_termines' => $workspace->projets()->where('status', 'completed')->count(),
      'projets_archives' => $workspace->projets()->where('status', 'archived')->count(),
      'total_membres' => $workspace->members()->count(),
      'total_activites' => DB::table('activites')
        ->whereIn('projet_id', $workspace->projets()->pluck('id'))
        ->count(),
      'total_taches' => DB::table('taches')
        ->whereIn('activite_id', function ($query) use ($workspace) {
          $query->select('id')
            ->from('activites')
            ->whereIn('projet_id', $workspace->projets()->pluck('id'));
        })
        ->count(),
      'taches_terminees' => DB::table('taches')
        ->whereIn('activite_id', function ($query) use ($workspace) {
          $query->select('id')
            ->from('activites')
            ->whereIn('projet_id', $workspace->projets()->pluck('id'));
        })
        ->where('statut', 'termine')
        ->count(),
      'taux_completion' => 0,
    ];

    // Calculate completion rate
    if ($stats['total_taches'] > 0) {
      $stats['taux_completion'] = round(
        ($stats['taches_terminees'] / $stats['total_taches']) * 100,
        2
      );
    }

    // Recent activity
    $stats['activite_recente'] = DB::table('activity_log')
      ->whereIn('subject_id', $workspace->projets()->pluck('id'))
      ->where('subject_type', 'App\\Models\\Projet')
      ->orWhereIn('subject_id', function ($query) use ($workspace) {
        $query->select('id')
          ->from('activites')
          ->whereIn('projet_id', $workspace->projets()->pluck('id'));
      })
      ->where('subject_type', 'App\\Models\\Activite')
      ->orderBy('created_at', 'desc')
      ->limit(10)
      ->get();

    return response()->json([
      'data' => $stats,
    ]);
  }

  /**
   * Helper: Check if user has access to workspace
   */
  private function userHasAccess(User $user, Workspace $workspace): bool
  {
    return $workspace->owner_id === $user->id ||
      $workspace->members()->where('user_id', $user->id)->exists();
  }

  /**
   * Helper: Check if user can manage members
   */
  private function userCanManageMembers(User $user, Workspace $workspace): bool
  {
    if ($workspace->owner_id === $user->id) {
      return true;
    }

    $member = $workspace->members()
      ->where('user_id', $user->id)
      ->first();

    return $member && in_array($member->pivot->role, ['owner', 'admin']);
  }

  /**
   * Generate unique workspace code
   */
  private function generateUniqueCode(): string
  {
    do {
      $latest = Workspace::withTrashed()->latest('id')->first();
      $nextId = $latest ? $latest->id + 1 : 1;
      $code = 'WS-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    } while (Workspace::where('code', $code)->exists());

    return $code;
  }


  /**
   * Display the specified workspace
   */
  public function show(Request $request, Workspace $workspace)
  {
    // Check if user has access
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé à ce workspace'
      ], 403);
    }

    $workspace->load([
      'owner:id,nom,email,avatar',
      'members:id,nom,email,avatar',
      'projets' => function ($query) {
        $query->active()
          ->with(['responsable:id,nom,avatar'])
          ->withCount('activites');
      },
    ]);

    $workspace->loadCount(['projets', 'members']);

    return response()->json([
      'data' => $workspace,
    ]);
  }

  /**
   * Update the specified workspace
   */
  public function update(Request $request, Workspace $workspace)
  {
    // Check if user is owner
    if ($workspace->owner_id !== $request->user()->id) {
      return response()->json([
        'message' => 'Seul le propriétaire peut modifier le workspace'
      ], 403);
    }

    $validated = $request->validate([
      'nom' => 'sometimes|required|string|max:255',
      'description' => 'nullable|string',
      'logo' => 'nullable|image|max:2048',
      'settings' => 'nullable|array',
      'is_active' => 'sometimes|boolean',
    ]);

    if ($request->hasFile('logo')) {
      // Delete old logo if exists
      if ($workspace->logo) {
        \Storage::disk('public')->delete($workspace->logo);
      }

      $logoPath = $request->file('logo')->store('workspaces/logos', 'public');
      $validated['logo'] = $logoPath;
    }

    $workspace->update($validated);

    $workspace->load(['owner', 'members']);
    $workspace->loadCount('projets');

    return response()->json([
      'message' => 'Workspace mis à jour avec succès',
      'data' => $workspace,
    ]);
  }

  /**
   * Remove the specified workspace
   */
  // public function destroy(Request $request, Workspace $workspace)
  // {
  //     // Check if user is owner
  //     if ($workspace->owner_id !== $request->user()->id) {
  //         return response()->json([
  //             'message' => 'Seul le propriétaire peut supprimer le workspace'
  //         ], 403);
  //     }

  //     // Check if workspace has projects
  //     if ($workspace->projets()->count() > 0) {
  //         return response()->json([
  //             'message' => 'Impossible de supprimer un workspace contenant des projets. Veuillez d\'abord les supprimer ou les déplacer.'
  //         ], 422);
  //     }

  //     DB::beginTransaction();

  //     try {
  //         // Delete logo if exists
  //         if ($workspace->logo) {
  //             \Storage::disk('public')->delete($workspace->logo);
  //         }

  //         // Detach all members
  //         $workspace->members()->detach();

  //         // Soft delete workspace
  //         $workspace->delete();

  //         DB::commit();

  //         return response()->json([
  //             'message' => 'Workspace supprimé avec succès',
  //         ]);

  //     } catch (\Exception $e) {
  //         DB::rollBack();
  //         // return response()->json([
  //     }
  //   }
}