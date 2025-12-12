<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\UserResource;
use App\Models\Workspace;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Notifications\WorkspaceMemberAddedNotification;
use App\Services\MemberRemovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
  public function store(StoreWorkspaceRequest $request)
  {
    DB::beginTransaction();

    try {
      // Générer un code unique
      $code = $this->generateUniqueCode();

      // Préparer les settings avec des valeurs par défaut
      $defaultSettings = [
        'default_project_visibility' => 'team',
        'members_can_create_projects' => true,
        'members_can_invite' => false,
        'require_task_validation' => true,
      ];

      // Fusionner avec les settings envoyées
      $settings = $request->has('settings_array') && is_array($request->settings_array)
        ? array_merge($defaultSettings, $request->settings_array) : $defaultSettings;

      // Créer le workspace
      $workspace = Workspace::create([
        'nom' => $request->nom,
        'description' => $request->description,
        'code' => $code,
        'owner_id' => $request->user()->id,
        'settings' => $settings,
        'is_active' => true,
      ]);

      // Gérer l'upload du logo si présent 
      if ($request->hasFile('logo')) {
        try {
          // Stocker directement dans public/uploads/workspaces/logos
          $logoPath = $request->file('logo')->store(
            'workspaces/logos',
            'uploads' // Utiliser un disk custom
          );
          $workspace->update(['logo' => $logoPath]);
        } catch (\Exception $e) {
          Log::warning('Erreur upload logo workspace: ' . $e->getMessage());
          // Continue même si l'upload échoue
        }
      }

      // Ajouter le créateur comme membre owner
      $workspace->members()->attach($request->user()->id, [
        'role' => 'owner',
        'permissions' => json_encode(['all']), // ✅ Convertir en JSON
        'invited_at' => now(),
        'invited_by' => $request->user()->id,
      ]);

      DB::commit();

      // Charger les relations pour la réponse
      $workspace->load(['owner:id,nom,email,avatar', 'members']);
      $workspace->loadCount('projets');

      return response()->json([
        'message' => 'Workspace créé avec succès',
        'data' => $workspace,
      ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      // Erreur de base de données
      Log::error('Erreur DB lors création workspace', [
        'error' => $e->getMessage(),
        'user_id' => $request->user()->id,
      ]);

      return response()->json([
        'message' => 'Erreur de base de données lors de la création du workspace',
        'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
      ], 500);

    } catch (\Exception $e) {
      DB::rollBack();

      // Erreur générale
      Log::error('Erreur lors création workspace', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => $request->user()->id,
      ]);

      return response()->json([
        'message' => 'Erreur lors de la création du workspace',
        'error' => config('app.debug') ? $e->getMessage() : 'Une erreur inattendue est survenue',
      ], 500);
    }
  }

  /**
   * Récupérer les workspaces de l'utilisateur avec les stats
   */
  public function getUserWorkspaces(Request $request)
  {
    $user = $request->user();
    $workspaces = Workspace::accessibleBy($user->id)
      ->withCount('projets', 'members')
      ->get();

    return response()->json(['data' => $workspaces]);
  }

  /**
   * Invite members to workspace - VERSION CORRIGÉE
   */
  public function inviteMembers(Request $request, Workspace $workspace)
  {
    $this->authorize('manageMembers', $workspace);

    $request->validate([
      'emails' => 'required|array|min:1',
      'emails.*' => 'required|email',
      'role' => 'required|in:admin,manager,member,viewer',
      'message' => 'nullable|string|max:500',
      'permissions' => 'nullable|array',
      'permissions.can_create_projects' => 'boolean',
      'permissions.can_invite_members' => 'boolean',
      'permissions.can_manage_settings' => 'boolean',
      'send_email' => 'boolean',
    ]);

    $invitations = [];
    $errors = [];
    $sendEmail = $request->input('send_email', true);

    foreach ($request->emails as $email) {
      try {
        // Check if user already exists
        $user = User::where('email', $email)->first();

        if ($user) {
          // Check if already a member
          if ($workspace->members()->where('user_id', $user->id)->exists()) {
            $errors[] = [
              'email' => $email,
              'message' => 'Cet utilisateur est déjà membre du workspace'
            ];
            continue;
          }

          // ✅ Créer une invitation même pour utilisateur existant
          $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $request->role,
            'token' => Str::random(64),
            'invited_by' => auth()->id(),
            'message' => $request->message,
            'permissions' => [
              'can_create_projects' => $request->input('permissions.can_create_projects', false),
              'can_invite_members' => $request->input('permissions.can_invite_members', false),
              'can_manage_settings' => $request->input('permissions.can_manage_settings', false),
            ],
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
          ]);

          // ✅ Envoyer l'email d'invitation
          if ($sendEmail) {
            try {
              $user->notify(new WorkspaceInvitationNotification($invitation));
            } catch (\Exception $e) {
              Log::error('Failed to send invitation email', [
                'email' => $email,
                'error' => $e->getMessage()
              ]);
            }
          }

          $invitations[] = [
            'email' => $email,
            'status' => 'invited',
            'user_id' => $user->id,
            'user_name' => $user->nom,
            'invitation_id' => $invitation->id,
            'expires_at' => $invitation->expires_at->toISOString(),
          ];
        } else {
          // Create invitation for non-existing user
          $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $request->role,
            'token' => Str::random(64),
            'invited_by' => auth()->id(),
            'message' => $request->message,
            'permissions' => [
              'can_create_projects' => $request->input('permissions.can_create_projects', false),
              'can_invite_members' => $request->input('permissions.can_invite_members', false),
              'can_manage_settings' => $request->input('permissions.can_manage_settings', false),
            ],
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
          ]);

          // Send invitation email
          if ($sendEmail) {
            try {
              Notification::route('mail', $email)
                ->notify(new WorkspaceInvitationNotification($invitation));
            } catch (\Exception $e) {
              Log::error('Failed to send invitation email', [
                'email' => $email,
                'error' => $e->getMessage()
              ]);
            }
          }

          $invitations[] = [
            'email' => $email,
            'status' => 'invited',
            'invitation_id' => $invitation->id,
            'expires_at' => $invitation->expires_at->toISOString(),
          ];
        }
      } catch (\Exception $e) {
        Log::error('Error inviting member', [
          'email' => $email,
          'workspace_id' => $workspace->id,
          'error' => $e->getMessage()
        ]);

        $errors[] = [
          'email' => $email,
          'message' => 'Erreur lors de l\'invitation: ' . $e->getMessage()
        ];
      }
    }

    return response()->json([
      'message' => count($invitations) > 0
        ? 'Invitations envoyées avec succès' : 'Aucune invitation n\'a pu être envoyée',
      'data' => [
        'invitations' => $invitations,
        'errors' => $errors,
        'success_count' => count($invitations),
        'error_count' => count($errors),
      ],
    ], count($invitations) > 0 ? 200 : 422);
  }

  /**
   * ✅ NOUVEAU : Accepter une invitation
   */
  public function acceptInvitation(Request $request, string $token)
  {
    DB::beginTransaction();

    try {
      // Trouver l'invitation
      $invitation = WorkspaceInvitation::where('token', $token)
        ->where('status', 'pending')
        ->where('expires_at', '>', now())
        ->firstOrFail();

      // Charger le workspace
      $workspace = $invitation->workspace;

      if (!$workspace->is_active) {
        throw new \Exception('Ce workspace n\'est plus actif');
      }

      // Vérifier si l'utilisateur existe déjà
      $user = User::where('email', $invitation->email)->first();

      if ($user) {
        // ✅ Utilisateur existant - Accepter l'invitation directement
        return $this->acceptInvitationForExistingUser($invitation, $user);
      } else {
        // ✅ Nouvel utilisateur - Vérifier qu'il a créé son compte
        $userData = $request->validate([
          'prenom' => 'required|string|max:255',
          'nom' => 'required|string|max:255',
          'password' => 'required|string|min:8|confirmed',
        ]);

        return $this->acceptInvitationForNewUser($invitation, $userData);
      }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'message' => 'Invitation invalide ou expirée'
      ], 404);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error accepting invitation', [
        'token' => $token,
        'error' => $e->getMessage()
      ]);

      return response()->json([
        'message' => $e->getMessage()
      ], 400);
    }
  }

  public function getWorkspaces(Request $request)
  {
    $user = $request->user();

    $workspaces = Workspace::accessibleBy($user->id)
      ->withCount([
        'projets' => function ($query) {
          $query->where('status', 'active');
        }
      ])
      ->whereNotNull('id') // ← Filtrer les workspaces sans ID
      ->get()
      ->map(function ($workspace) {
        return [
          'id' => $workspace->id,
          'nom' => $workspace->nom,
          'code' => $workspace->code,
          'projets_count' => $workspace->projets_count,
          'description' => $workspace->description,
          'created_at' => $workspace->created_at,
          'updated_at' => $workspace->updated_at,
        ];
      })
      ->filter() // ← Filtrer les éventuels éléments null
      ->values();

    return response()->json($workspaces);
  }

  /**
   * ✅ Accepter l'invitation pour un utilisateur existant
   */
  private function acceptInvitationForExistingUser(WorkspaceInvitation $invitation, User $user)
  {
    try {
      $workspace = $invitation->workspace;

      // Vérifier si déjà membre
      if ($workspace->members()->where('user_id', $user->id)->exists()) {
        DB::rollBack();
        return response()->json([
          'message' => 'Vous êtes déjà membre de ce workspace'
        ], 400);
      }

      // Ajouter comme membre
      $workspace->members()->attach($user->id, [
        'role' => $invitation->role,
        'permissions' => json_encode($invitation->permissions ?? []),
        'invited_at' => now(),
        'invited_by' => $invitation->invited_by,
      ]);

      // Mettre à jour l'invitation
      $invitation->update([
        'status' => 'accepted',
        'accepted_at' => now(),
      ]);

      // Log d'activité
      activity()
        ->causedBy($user)
        ->performedOn($workspace)
        ->withProperties([
          'role' => $invitation->role,
          'invited_by' => $invitation->invited_by
        ])
        ->log('User accepted workspace invitation');

      DB::commit();

      return response()->json([
        'message' => 'Invitation acceptée avec succès',
        'data' => [
          'workspace' => $workspace,
          'role' => $invitation->role,
          'redirect_to' => "/workspaces/{$workspace->id}"
        ]
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * ✅ Accepter l'invitation pour un nouvel utilisateur
   */
  private function acceptInvitationForNewUser(WorkspaceInvitation $invitation, array $userData)
  {
    try {
      $workspace = $invitation->workspace;

      // Créer le nouvel utilisateur
      $user = User::create([
        'nom' => $userData['nom'],
        'prenom' => $userData['prenom'],
        'nom_complet' => trim("{$userData['prenom']} {$userData['nom']}"),
        'email' => $invitation->email,
        'password' => Hash::make($userData['password']),
        'is_active' => true,
        'email_verified_at' => now(), // Vérification automatique via invitation
      ]);

      // Assigner le rôle par défaut
      $user->assignRole('member');

      // Ajouter au workspace invité
      $workspace->members()->attach($user->id, [
        'role' => $invitation->role,
        'permissions' => json_encode($invitation->permissions ?? []),
        'invited_at' => now(),
        'invited_by' => $invitation->invited_by,
      ]);

      // Définir ce workspace comme courant
      $user->update(['current_workspace_id' => $workspace->id]);

      // Mettre à jour l'invitation
      $invitation->update([
        'status' => 'accepted',
        'accepted_at' => now(),
      ]);

      // Générer le token
      $token = $user->createToken('auth_token', ['*'])->plainTextToken;

      // Log d'activité
      activity()
        ->causedBy($user)
        ->performedOn($workspace)
        ->withProperties([
          'registered_via_invitation' => true,
          'role' => $invitation->role,
          'invited_by' => $invitation->invited_by
        ])
        ->log('User registered via invitation and joined workspace');

      DB::commit();

      return response()->json([
        'message' => 'Compte créé et invitation acceptée avec succès',
        'data' => [
          'user' => new UserResource($user),
          'token' => $token,
          'token_type' => 'Bearer',
          'workspace' => $workspace,
          'redirect_to' => "/workspaces/{$workspace->id}"
        ]
      ], 201);

    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * ✅ NOUVEAU : Vérifier une invitation (avant acceptation)
   */
  public function checkInvitation(string $token)
  {
    try {
      $invitation = WorkspaceInvitation::where('token', $token)
        ->where('status', 'pending')
        ->where('expires_at', '>', now())
        ->with(['workspace', 'invitedBy'])
        ->firstOrFail();

      // Vérifier si l'utilisateur existe déjà
      $userExists = User::where('email', $invitation->email)->exists();

      return response()->json([
        'data' => [
          'invitation' => [
            'email' => $invitation->email,
            'role' => $invitation->role,
            'message' => $invitation->message,
            'expires_at' => $invitation->expires_at->toISOString(),
            'workspace' => [
              'id' => $invitation->workspace->id,
              'nom' => $invitation->workspace->nom,
              'description' => $invitation->workspace->description,
              'logo_url' => $invitation->workspace->logo_url,
            ],
            'inviter' => [
              'nom' => $invitation->invitedBy->nom,
              'email' => $invitation->invitedBy->email,
            ]
          ],
          'user_exists' => $userExists,
          'requires_registration' => !$userExists,
        ]
      ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'message' => 'Invitation invalide ou expirée'
      ], 404);
    }
  }

  /**
   * Get workspace invitations
   */
  public function invitations(Workspace $workspace)
  {
    $this->authorize('manageMembers', $workspace);

    $invitations = WorkspaceInvitation::where('workspace_id', $workspace->id)
      ->where('status', 'pending')
      ->where('expires_at', '>', now())
      ->with('invitedBy')
      ->latest()
      ->get();

    return response()->json([
      'data' => $invitations,
    ]);
  }

  /**
   * Resend invitation
   */
  public function resendInvitation(Workspace $workspace, WorkspaceInvitation $invitation)
  {
    $this->authorize('manageMembers', $workspace);

    if ($invitation->workspace_id !== $workspace->id) {
      abort(403, 'Cette invitation n\'appartient pas à ce workspace');
    }

    if ($invitation->status !== 'pending') {
      abort(400, 'Cette invitation n\'est plus valide');
    }

    // Extend expiration
    $invitation->update([
      'expires_at' => now()->addDays(7),
    ]);

    // Resend email
    // TODO: Send invitation email
    // Notification::route('mail', $invitation->email)
    //     ->notify(new WorkspaceInvitationNotification($invitation));

    return response()->json([
      'message' => 'Invitation renvoyée avec succès',
    ]);
  }

  /**
   * Cancel invitation
   */
  public function cancelInvitation(Workspace $workspace, WorkspaceInvitation $invitation)
  {
    $this->authorize('manageMembers', $workspace);

    if ($invitation->workspace_id !== $workspace->id) {
      abort(403, 'Cette invitation n\'appartient pas à ce workspace');
    }

    $invitation->update([
      'status' => 'cancelled',
    ]);

    return response()->json([
      'message' => 'Invitation annulée avec succès',
    ]);
  }


  /**
   * Get all invitations across workspaces (admin only)
   */
  public function allInvitations(Request $request)
  {
    // Vérifier les permissions admin
    if (!$request->user()->hasRole(['super_admin', 'admin'])) {
      abort(403, 'Accès non autorisé');
    }

    $query = WorkspaceInvitation::with(['workspace', 'invitedBy'])
      ->when($request->search, function ($q, $search) {
        $q->where(function ($query) use ($search) {
          $query->where('email', 'like', "%{$search}%")
            ->orWhereHas('workspace', function ($q) use ($search) {
              $q->where('nom', 'like', "%{$search}%");
            })
            ->orWhereHas('invitedBy', function ($q) use ($search) {
              $q->where('nom', 'like', "%{$search}%");
            });
        });
      })
      ->when($request->status && $request->status !== 'all', function ($q, $status) {
        $q->where('status', $status);
      })
      ->when($request->workspace_id && $request->workspace_id !== 'all', function ($q, $workspaceId) {
        $q->where('workspace_id', $workspaceId);
      })
      ->latest();

    $invitations = $query->paginate($request->per_page ?? 20);

    // Statistiques
    $statistics = [
      'total_invitations' => WorkspaceInvitation::count(),
      'pending_invitations' => WorkspaceInvitation::where('status', 'pending')->count(),
      'accepted_invitations' => WorkspaceInvitation::where('status', 'accepted')->count(),
      'expired_invitations' => WorkspaceInvitation::where('status', 'pending')
        ->where('expires_at', '<', now())
        ->count(),
      'cancelled_invitations' => WorkspaceInvitation::where('status', 'cancelled')->count(),
    ];

    return response()->json([
      'data' => $invitations->items(),
      'meta' => [
        'current_page' => $invitations->currentPage(),
        'last_page' => $invitations->lastPage(),
        'per_page' => $invitations->perPage(),
        'total' => $invitations->total(),
        'from' => $invitations->firstItem(),
        'to' => $invitations->lastItem(),
      ],
      'statistics' => $statistics
    ]);
  }

  /**
   * Get invitation statistics
   */
  public function invitationStatistics(Request $request)
  {
    if (!$request->user()->hasRole(['super_admin', 'admin'])) {
      abort(403, 'Accès non autorisé');
    }

    $statistics = [
      'total_invitations' => WorkspaceInvitation::count(),
      'pending_invitations' => WorkspaceInvitation::where('status', 'pending')->count(),
      'accepted_invitations' => WorkspaceInvitation::where('status', 'accepted')->count(),
      'expired_invitations' => WorkspaceInvitation::where('status', 'pending')
        ->where('expires_at', '<', now())
        ->count(),
      'cancelled_invitations' => WorkspaceInvitation::where('status', 'cancelled')->count(),
      'recent_invitations' => WorkspaceInvitation::where('created_at', '>=', now()->subDays(7))->count(),
    ];

    return response()->json([
      'data' => $statistics
    ]);
  }

  /**
   * Get workspace members
   */
  public function members(Request $request, Workspace $workspace)
  {
    $this->authorize('viewMembers', $workspace);

    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $members = $workspace->members()
      ->withPivot(['role', 'permissions', 'invited_at', 'invited_by'])
      ->get()
      ->map(function ($member) {
        // ✅ Corriger le format des permissions
        $permissions = $member->pivot->permissions;

        // Si c'est une chaîne JSON, la décoder
        if (is_string($permissions)) {
          $decoded = json_decode($permissions, true);
          $member->pivot->permissions = is_array($decoded) ? $decoded : [];
        }

        // Si c'est "all", convertir en permissions complètes
        if ($member->pivot->permissions === ['all']) {
          $member->pivot->permissions = [
            'can_create_projects' => true,
            'can_invite_members' => true,
            'can_manage_settings' => true,
          ];
        }

        return $member;
      });

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
      'role' => ['required', Rule::in(['owner', 'super_admin', 'admin', 'member', 'viewer'])],
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
   * Update member permissions
   */
  public function updateMember_(Request $request, Workspace $workspace, User $user)
  {
    $this->authorize('manageMembers', $workspace);

    $request->validate([
      'role' => 'sometimes|in:admin,member,viewer',
      'permissions' => 'nullable|array',
      'permissions.can_create_projects' => 'boolean',
      'permissions.can_invite_members' => 'boolean',
      'permissions.can_manage_settings' => 'boolean',
    ]);

    // Check if user is member
    if (!$workspace->members()->where('user_id', $user->id)->exists()) {
      abort(404, 'Cet utilisateur n\'est pas membre du workspace');
    }

    // Don't allow changing owner role
    $member = $workspace->members()->where('user_id', $user->id)->first();
    if ($member->pivot->role === 'owner') {
      abort(403, 'Impossible de modifier le rôle du propriétaire');
    }

    // Update permissions
    $workspace->members()->updateExistingPivot($user->id, [
      'role' => $request->input('role', $member->pivot->role),
      'can_create_projects' => $request->input('permissions.can_create_projects', $member->pivot->can_create_projects),
      'can_invite_members' => $request->input('permissions.can_invite_members', $member->pivot->can_invite_members),
      'can_manage_settings' => $request->input('permissions.can_manage_settings', $member->pivot->can_manage_settings),
    ]);

    return response()->json([
      'message' => 'Permissions mises à jour avec succès',
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
   * Remove member from workspace
   */
  public function removeMember_(Workspace $workspace, User $user)
  {
    $this->authorize('manageMembers', $workspace);

    // Check if user is member
    if (!$workspace->members()->where('user_id', $user->id)->exists()) {
      abort(404, 'Cet utilisateur n\'est pas membre du workspace');
    }

    // Don't allow removing owner
    $member = $workspace->members()->where('user_id', $user->id)->first();
    if ($member->pivot->role === 'owner') {
      abort(403, 'Impossible de retirer le propriétaire du workspace');
    }

    DB::transaction(function () use ($workspace, $user) {
      // Remove from workspace
      $workspace->members()->detach($user->id);

      // Remove from all projects in this workspace
      $projects = $workspace->projets;
      foreach ($projects as $projet) {
        $projet->revokeAccess($user);
      }

      // Log the action
      activity()
        ->causedBy(auth()->user())
        ->performedOn($workspace)
        ->withProperties(['removed_user' => $user->id])
        ->log('member_removed');
    });

    return response()->json([
      'message' => 'Membre retiré avec succès',
    ]);
  }


  /**
   * ✅ VERSION CORRIGÉE : Get workspace projects avec permissions correctes
   */
  public function projets(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $user = $request->user();

    $query = $workspace->projets()
      ->with(['responsable:id,nom,avatar', 'members:id,nom,avatar'])
      ->withCount(['activites', 'members']);

    // Filtres de recherche/statut
    $query->when($request->status, function ($q, $status) {
      $q->where('status', $status);
    })
      ->when($request->search, function ($q, $search) {
        $q->where(function ($subQuery) use ($search) {
          $subQuery->where('nom', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
        });
      });

    // 🔥 LOGIQUE DE PERMISSION
    // Owner ou Admin du workspace → Voit TOUT
    if (
      $workspace->owner_id === $user->id ||
      $this->isWorkspaceAdmin($user, $workspace)
    ) {

      // ✅ Aucun filtre supplémentaire : voir tous les projets

    } else {
      // ❌ Membre simple → Uniquement projets accessibles
      $query->where(function ($q) use ($user) {
        $q->where('responsable_id', $user->id)
          ->orWhereHas('members', function ($memberQuery) use ($user) {
            $memberQuery->where('user_id', $user->id);
          });
      });
    }

    $projets = $query->orderBy('created_at', 'desc')
      ->paginate($request->per_page ?? 15);

    return response()->json($projets);
  }

  /**
   * ✅ HELPER : Vérifier si user est Admin du workspace
   */
  private function isWorkspaceAdmin(User $user, Workspace $workspace): bool
  {
    $member = $workspace->members()->where('user_id', $user->id)->first();

    if (!$member) {
      return false;
    }

    return in_array($member->pivot->role, ['super_admin', 'admin']);
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

    return $member && in_array($member->pivot->role, ['owner', 'super_admin', 'admin']);
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
  public function update(UpdateWorkspaceRequest $request, Workspace $workspace)
  {

    // Check if user is owner
    if ($workspace->owner_id !== $request->user()->id) {
      return response()->json([
        'message' => 'Seul le propriétaire peut modifier le workspace'
      ], 403);
    }
    DB::beginTransaction();
    try {

      // Préparer les données à mettre à jour
      $dataToUpdate = [];

      // Nom
      if ($request->has('nom')) {
        $dataToUpdate['nom'] = $request->nom;
      }

      // Description
      if ($request->has('description')) {
        $dataToUpdate['description'] = $request->description;
      }

      // Status
      if ($request->has('is_active')) {
        $dataToUpdate['is_active'] = $request->boolean('is_active');
      }

      // ✅ Gestion des settings de manière sûre
      if ($request->has('settings_array')) {
        $newSettings = $request->input('settings_array');

        // Vérifier que c'est bien un array
        if (is_array($newSettings)) {
          // Récupérer les settings actuelles (garantir que c'est un array)
          $currentSettings = $workspace->settings;
          if (!is_array($currentSettings)) {
            $currentSettings = [];
          }

          // Fusionner avec les nouvelles settings
          $dataToUpdate['settings'] = array_merge($currentSettings, $newSettings);

          Log::info('Settings fusionnées', [
            'workspace_id' => $workspace->id,
            'current' => $currentSettings,
            'new' => $newSettings,
            'merged' => $dataToUpdate['settings']
          ]);
        }
      }

      // ✅ Gestion du logo - Ordre important !

      // 1. D'abord vérifier si on doit supprimer le logo
      if ($request->has('remove_logo') && $request->boolean('remove_logo')) {
        if ($workspace->logo && file_exists(public_path('uploads/' . $workspace->logo))) {
          unlink(public_path('uploads/' . $workspace->logo));
        }
        $dataToUpdate['logo'] = null;

        Log::info('Logo supprimé', ['workspace_id' => $workspace->id]);
      }
      // 2. Ensuite vérifier si on upload un nouveau logo
     elseif ($request->hasFile('logo')) {
        try {
            // Supprimer l'ancien logo si existe
            if ($workspace->logo && file_exists(public_path('uploads/' . $workspace->logo))) {
                unlink(public_path('uploads/' . $workspace->logo));
            }

            // Enregistrer le nouveau logo directement dans public/uploads
            $logoPath = $request->file('logo')->store(
                'workspaces/logos',
                'uploads' // Utiliser le disk custom
            );
            $dataToUpdate['logo'] = $logoPath;

            Log::info('Logo mis à jour avec succès', [
                'workspace_id' => $workspace->id,
                'logo_path' => $logoPath
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur upload logo workspace: ' . $e->getMessage());
            throw $e; // Propager l'erreur pour rollback
        }
      }

      // 3. Sinon, ne rien faire (garder le logo existant)

      // ✅ Log avant mise à jour
      Log::info('Données à mettre à jour', [
        'workspace_id' => $workspace->id,
        'data' => array_keys($dataToUpdate),
        'settings_is_array' => isset($dataToUpdate['settings']) ? is_array($dataToUpdate['settings']) : 'N/A'
      ]);

      // Mettre à jour le workspace
      if (!empty($dataToUpdate)) {
        $workspace->update($dataToUpdate);
      }

      DB::commit();

      // Recharger les relations
      $workspace->load(['owner:id,nom,email,avatar', 'members:id,nom,email,avatar']);
      $workspace->loadCount('projets');

      return response()->json([
        'message' => 'Workspace mis à jour avec succès',
        'data' => $workspace,
      ]);
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      Log::error('Erreur DB lors mise à jour workspace', [
        'error' => $e->getMessage(),
        'workspace_id' => $workspace->id,
        'user_id' => $request->user()->id,
      ]);

      return response()->json([
        'message' => 'Erreur de base de données lors de la mise à jour',
        'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne',
      ], 500);

    } catch (\Exception $e) {
      DB::rollBack();

      Log::error('Erreur lors mise à jour workspace', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'workspace_id' => $workspace->id,
        'user_id' => $request->user()->id,
      ]);

      return response()->json([
        'message' => 'Erreur lors de la mise à jour du workspace',
        'error' => config('app.debug') ? $e->getMessage() : 'Une erreur inattendue est survenue',
      ], 500);
    }
  }

  /**
   * Delete/Archive the specified workspace
   */
  public function destroy(Request $request, Workspace $workspace)
  {
    // Check if user is owner
    if ($workspace->owner_id !== $request->user()->id) {
      return response()->json([
        'message' => 'Seul le propriétaire peut supprimer le workspace'
      ], 403);
    }

    // Check if workspace has projects
    if ($workspace->projets()->count() > 0) {
      return response()->json([
        'message' => 'Impossible de supprimer un workspace contenant des projets. Veuillez d\'abord les supprimer ou les déplacer.',
        'projects_count' => $workspace->projets()->count()
      ], 422);
    }

    DB::beginTransaction();

    try {
      // Delete logo if exists
      if ($workspace->logo && file_exists(public_path('uploads/' . $workspace->logo))) {
          unlink(public_path('uploads/' . $workspace->logo));
      }

      // Detach all members
      $workspace->members()->detach();

      // Soft delete workspace
      $workspace->delete();

      DB::commit();

      return response()->json([
        'message' => 'Workspace supprimé avec succès',
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Erreur lors de la suppression du workspace',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Changer de workspace courant pour l'utilisateur connecté
   */
  public function switch(Request $request, Workspace $workspace)
  {
    $user = $request->user();

    // Vérifie que l'utilisateur a accès à ce workspace
    if (!$workspace->hasAccess($user)) {
      return response()->json([
        'message' => 'Accès refusé à ce workspace',
      ], 403);
    }

    // Met à jour le workspace courant
    $user->update(['current_workspace_id' => $workspace->id]);

    activity()
      ->causedBy($user)
      ->performedOn($workspace)
      ->withProperties(['workspace_id' => $workspace->id])
      ->log('Workspace switched');

    return response()->json([
      'message' => 'Workspace sélectionné avec succès',
      'current_workspace_id' => $workspace->id,
      'workspace' => $workspace->loadCount('projets', 'members'),
    ]);
  }

  /**
   * Archive a workspace
   */
  public function archive(Request $request, Workspace $workspace)
  {
    if (!$this->userCanManageWorkspace($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Vous n\'avez pas la permission d\'archiver ce workspace'
      ], 403);
    }

    $workspace->archive();

    return response()->json([
      'message' => 'Workspace archivé avec succès',
      'data' => $workspace->fresh(['owner', 'members']),
    ]);
  }

  /**
   * Unarchive a workspace
   */
  public function unarchive(Request $request, Workspace $workspace)
  {
    if (!$this->userCanManageWorkspace($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Vous n\'avez pas la permission de restaurer ce workspace'
      ], 403);
    }

    $workspace->activate();

    return response()->json([
      'message' => 'Workspace restauré avec succès',
      'data' => $workspace->fresh(['owner', 'members']),
    ]);
  }

  /**
   * Transfer workspace ownership
   */
  public function transferOwnership(Request $request, Workspace $workspace)
  {
    // Only owner can transfer ownership
    if ($workspace->owner_id !== $request->user()->id) {
      return response()->json([
        'message' => 'Seul le propriétaire peut transférer la propriété du workspace'
      ], 403);
    }

    $validated = $request->validate([
      'new_owner_id' => 'required|exists:users,id',
    ]);

    // Check if new owner is a member
    if (!$workspace->members()->where('user_id', $validated['new_owner_id'])->exists()) {
      return response()->json([
        'message' => 'Le nouvel propriétaire doit être membre du workspace'
      ], 422);
    }

    DB::beginTransaction();

    try {
      $newOwner = User::find($validated['new_owner_id']);
      $oldOwner = $workspace->owner;

      // Update workspace owner
      $workspace->update(['owner_id' => $validated['new_owner_id']]);

      // Update member roles
      $workspace->members()->updateExistingPivot($validated['new_owner_id'], [
        'role' => 'owner',
      ]);

      $workspace->members()->updateExistingPivot($request->user()->id, [
        'role' => 'admin',
      ]);

      DB::commit();

      // TODO: Send notification to new owner

      return response()->json([
        'message' => 'Propriété transférée avec succès',
        'data' => $workspace->fresh(['owner', 'members']),
      ]);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Erreur lors du transfert de propriété',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Duplicate a workspace
   */
  public function duplicate(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    DB::beginTransaction();

    try {
      $newWorkspace = $workspace->replicate();
      $newWorkspace->nom = $workspace->nom . ' (Copie)';
      $newWorkspace->code = $this->generateUniqueCode();
      $newWorkspace->owner_id = $request->user()->id;
      $newWorkspace->save();

      // Copy members
      $members = $workspace->members;
      foreach ($members as $member) {
        $newWorkspace->members()->attach($member->id, [
          'role' => $member->pivot->role,
          'permissions' => $member->pivot->permissions,
          'invited_at' => now(),
          'invited_by' => $request->user()->id,
        ]);
      }

      DB::commit();

      $newWorkspace->load(['owner', 'members']);
      $newWorkspace->loadCount('projets');

      return response()->json([
        'message' => 'Workspace dupliqué avec succès',
        'data' => $newWorkspace,
      ], 201);

    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Erreur lors de la duplication du workspace',
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Get workspace activity log
   */
  public function activityLog(Request $request, Workspace $workspace)
  {
    if (!$this->userHasAccess($request->user(), $workspace)) {
      return response()->json([
        'message' => 'Accès non autorisé'
      ], 403);
    }

    $activities = DB::table('activity_log')
      ->where('subject_type', Workspace::class)
      ->where('subject_id', $workspace->id)
      ->orderBy('created_at', 'desc')
      ->limit($request->limit ?? 50)
      ->get();

    return response()->json([
      'data' => $activities,
    ]);
  }

  /**
   * Helper: Check if user can manage workspace (owner or admin)
   */
  private function userCanManageWorkspace(User $user, Workspace $workspace): bool
  {
    if ($workspace->owner_id === $user->id) {
      return true;
    }

    $member = $workspace->members()
      ->where('user_id', $user->id)
      ->first();

    return $member && in_array($member->pivot->role, ['owner', 'super_admin', 'admin']);
  }


  /**
   * ✅ Obtenir les projets où l'user est responsable
   */
  public function getUserProjects(Request $request, Workspace $workspace, User $user)
  {
    $this->authorize('manageMembers', $workspace);

    $removalService = app(MemberRemovalService::class);
    $projects = $removalService->getUserProjectsAsResponsable($user, $workspace);

    return response()->json([
      'data' => $projects,
      'count' => $projects->count(),
    ]);
  }

  /**
   * ✅ Obtenir les candidats pour le transfert
   */
  public function getTransferCandidates(Request $request, Workspace $workspace)
  {
    $this->authorize('manageMembers', $workspace);

    $request->validate([
      'exclude_user_id' => 'required|exists:users,id',
    ]);

    $removalService = app(MemberRemovalService::class);
    $excludeUser = User::findOrFail($request->exclude_user_id);

    $candidates = $removalService->getTransferCandidates($workspace, $excludeUser);

    return response()->json([
      'data' => $candidates,
    ]);
  }

  /**
   * ✅ Retirer un membre avec transfert de projets
   */
  public function removeMemberWithTransfer(Request $request, Workspace $workspace, User $user)
  {
    $this->authorize('manageMembers', $workspace);

    // Ne peut pas retirer le owner
    if ($workspace->owner_id === $user->id) {
      return response()->json([
        'message' => 'Impossible de retirer le propriétaire du workspace'
      ], 422);
    }

    $request->validate([
      'new_responsable_id' => 'nullable|exists:users,id',
    ]);

    if (!$workspace->members()->where('user_id', $user->id)->exists()) {
      return response()->json([
        'message' => 'Cet utilisateur n\'est pas membre du workspace'
      ], 404);
    }

    try {
      $newResponsable = $request->new_responsable_id
        ? User::findOrFail($request->new_responsable_id)
        : null;

      $removalService = app(MemberRemovalService::class);
      $stats = $removalService->removeFromWorkspace($workspace, $user, $newResponsable);

      return response()->json([
        'message' => 'Membre retiré avec succès',
        'data' => [
          'stats' => $stats,
          'removed_user' => [
            'id' => $user->id,
            'nom' => $user->nom,
          ],
          'new_responsable' => $newResponsable ? [
            'id' => $newResponsable->id,
            'nom' => $newResponsable->nom,
          ] : null,
        ],
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Erreur lors du retrait du membre',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}