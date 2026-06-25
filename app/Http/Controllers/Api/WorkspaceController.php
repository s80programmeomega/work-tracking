<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use App\Services\MemberRemovalService;
use App\Services\PermissionService;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function __construct(
        protected MemberRemovalService $removalService,
        protected PermissionService $permissionService,
    ) {}

    /**
     * Display a listing of workspaces for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $baseQuery = $user->isSuperAdmin()
            ? Workspace::query()
            : Workspace::where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            });

        $workspaces = $baseQuery
            // Chargement du nombre de projets et de membres pour les cartes du picker
            ->withCount(['projets', 'members'])
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

        $gate = app(ContextualPermissionGate::class);
        $workspaces->getCollection()->transform(function ($workspace) use ($user, $gate) {
            if ($workspace->owner_id === $user->id) {
                $workspace->setAttribute('user_role', 'owner');
            } else {
                $member = $workspace->members()->where('user_id', $user->id)->first();
                $workspace->setAttribute('user_role', $member
                    ? (\Spatie\Permission\Models\Role::find($member->pivot->role_id)?->name ?? 'membre')
                    : null);
            }

            $workspace->user_permissions = [
                'can_view_workspace' => $gate->userCan($user, Permission::WORKSPACES_VIEW, $workspace),
                'can_create_project' => $gate->userCan($user, Permission::WORKSPACES_CREATE_PROJECT, $workspace),
                'can_invite_members' => $gate->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace),
                'can_remove_members' => $gate->userCan($user, Permission::WORKSPACES_REMOVE_MEMBER, $workspace),
                'can_manage_workspace_settings' => $gate->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace),
                'can_view_pending_validations' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_PENDING, $workspace),
                'can_view_evaluation_score' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_SCORE, $workspace),
                'can_view_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_FICHE, $workspace),
                'can_export_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_EXPORT_FICHE, $workspace),
                'can_manage_notification_preferences' => $gate->userCan($user, Permission::NOTIFICATIONS_MANAGE_PREFERENCES, $workspace),
                'can_view_evaluation_dashboard' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_DASHBOARD, $workspace),
                'can_view_workspace_taches' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_WORKSPACE_TACHES, $workspace),
                'can_inline_edit_tache' => $gate->userCan($user, Permission::TACHES_INLINE_EDIT, $workspace),
                'can_manage_workspace_documents' => $gate->userCan($user, Permission::DOCUMENTS_MANAGE_WORKSPACE, $workspace),
                'can_manage_subscription' => $workspace->isOwnerOrAdmin($user),
                'can_submit_result' => $gate->userCan($user, Permission::TACHES_SUBMIT_RESULT, $workspace),
                'can_search_global' => $gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace),
                'can_search_scoped' => $gate->userCan($user, Permission::SEARCH_SCOPED, $workspace),
                'can_help_articles_read' => $gate->userCan($user, Permission::HELP_ARTICLES_READ, $workspace),
                'can_help_articles_create' => $gate->userCan($user, Permission::HELP_ARTICLES_CREATE, $workspace),
                'can_help_articles_edit' => $gate->userCan($user, Permission::HELP_ARTICLES_EDIT, $workspace),
                'can_help_articles_publish' => $gate->userCan($user, Permission::HELP_ARTICLES_PUBLISH, $workspace),
                'can_help_articles_delete' => $gate->userCan($user, Permission::HELP_ARTICLES_DELETE, $workspace),
                'can_help_articles_upload_image' => $gate->userCan($user, Permission::HELP_ARTICLES_UPLOAD_IMAGE, $workspace),
                'can_help_categories_manage' => $gate->userCan($user, Permission::HELP_CATEGORIES_MANAGE, $workspace),
                'can_view_members' => $gate->userCan($user, Permission::WORKSPACES_VIEW_MEMBERS, $workspace),
            ];

            return $workspace;
        });

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
                    Log::warning('Erreur upload logo workspace: '.$e->getMessage());
                    // Continue même si l'upload échoue
                }
            }

            // Add creator as owner member
            $ownerRole = \Spatie\Permission\Models\Role::findByName('owner', 'web');
            $workspace->members()->attach($request->user()->id, [
                'role_id' => $ownerRole->id,
                'invited_at' => now(),
                'invited_by' => $request->user()->id,
            ]);

            // Assign directeur global role if not already super_admin
            $user = $request->user();
            if (! $user->isSuperAdmin() && ! $user->hasRole(Role::DIRECTEUR->value)) {
                $user->syncRoles([Role::DIRECTEUR->value]);
            }

            // Set as current workspace
            $user->update(['current_workspace_id' => $workspace->id]);

            DB::commit();

            // Charger les relations pour la réponse
            $workspace->load(['owner:id,nom,email,avatar', 'members']);
            $workspace->loadCount('projets');

            return response()->json([
                'message' => 'Workspace créé avec succès',
                'data' => $workspace,
            ], 201);

        } catch (QueryException $e) {
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

        $workspaces = $user->isSuperAdmin()
            ? Workspace::withCount('projets', 'members')->get()
            : Workspace::accessibleBy($user->id)->withCount('projets', 'members')->get();

        return response()->json(['data' => $workspaces]);
    }

    /**
     * Invite members to workspace - VERSION CORRIGÉE
     */
    /**
     * Invite members to workspace - VERSION CORRIGÉE (sans routes nommées)
     */
    public function inviteMembers(Request $request, Workspace $workspace)
    {
        $this->authorize('inviteMember', $workspace);

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
            'role' => 'required|in:owner,manager,cadre,collaborateur,stagiaire,observateur',
            'message' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.can_create_projects' => 'boolean',
            'permissions.can_invite_members' => 'boolean',
            'permissions.can_manage_settings' => 'boolean',
            'send_email' => 'boolean',
        ]);

        $invitations = [];
        $errors = [];
        $warnings = [];
        $sendEmail = $request->input('send_email', true);

        foreach ($request->emails as $email) {
            try {
                // Check if user already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // ✅ Vérifier si déjà membre du workspace
                    if ($workspace->members()->where('user_id', $user->id)->exists()) {
                        // Récupérer le membre existant avec son rôle
                        $existingMember = $workspace->members()
                            ->where('user_id', $user->id)
                            ->withPivot(['role_id', 'invited_at'])
                            ->first();

                        $existingRoleName = \Spatie\Permission\Models\Role::find($existingMember->pivot->role_id)?->name ?? 'membre';
                        $roleLabel = $this->getRoleLabel($existingRoleName);
                        $joinedAt = $existingMember->pivot->created_at
                          ? $existingMember->pivot->created_at->format('d/m/Y')
                          : 'Date inconnue';

                        // Récupérer les statistiques du membre - CORRECTION
                        $projectsCount = $user->projets()
                            ->whereHas('workspace', function ($query) use ($workspace) {
                                $query->where('id', $workspace->id);
                            })
                            ->count();

                        $tasksCount = $user->taches()
                            ->whereHas('activite.projet', function ($query) use ($workspace) {
                                $query->where('workspace_id', $workspace->id);
                            })->count();

                        $errors[] = [
                            'email' => $email,
                            'message' => "{$user->nom} est déjà {$roleLabel} de ce workspace depuis le {$joinedAt}.",
                            'type' => 'already_member',
                            'user_id' => $user->id,
                            'user_name' => $user->nom,
                            'existing_role' => $existingRoleName,
                            'existing_role_label' => $roleLabel,
                            'joined_at' => $joinedAt,
                            'stats' => [
                                'projects_count' => $projectsCount,
                                'tasks_count' => $tasksCount,
                            ],
                            'suggestions' => [
                                // 'view_profile_url' => "/workspaces/{$workspace->id}/members/{$user->id}",
                                // 'update_role_url' => "/workspaces/{$workspace->id}/members/{$user->id}",
                            ],
                        ];

                        continue;
                    }

                    // ✅ Vérifier si déjà une invitation en attente
                    $existingInvitation = WorkspaceInvitation::where('workspace_id', $workspace->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->first();

                    if ($existingInvitation) {
                        if ($existingInvitation->expires_at < now()) {
                            // Invitation expirée - on peut la supprimer
                            $existingInvitation->update(['status' => 'expired']);
                        } else {
                            // Invitation encore valide - ajouter aux warnings
                            $expiresIn = $existingInvitation->expires_at->diffForHumans();

                            $warnings[] = [
                                'email' => $email,
                                'message' => 'Une invitation est déjà en attente pour cet utilisateur. Elle expire '.$expiresIn,
                                'type' => 'pending_invitation',
                                'expires_at' => $existingInvitation->expires_at->toISOString(),
                                'invitation_id' => $existingInvitation->id,
                                'user_name' => $user->nom,
                            ];

                            continue;
                        }
                    }

                    // ✅ Créer une invitation
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
                                'error' => $e->getMessage(),
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
                        'requires_registration' => false,
                    ];
                } else {
                    // ✅ Utilisateur externe (n'existe pas dans le système)

                    // Vérifier si déjà invité
                    $existingInvitation = WorkspaceInvitation::where('workspace_id', $workspace->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->first();

                    if ($existingInvitation) {
                        if ($existingInvitation->expires_at < now()) {
                            // Invitation expirée
                            $existingInvitation->update(['status' => 'expired']);
                        } else {
                            // Invitation encore valide - ajouter aux warnings
                            $expiresIn = $existingInvitation->expires_at->diffForHumans();

                            $warnings[] = [
                                'email' => $email,
                                'message' => 'Une invitation est déjà en attente pour cette adresse. Elle expire '.$expiresIn,
                                'type' => 'pending_invitation',
                                'expires_at' => $existingInvitation->expires_at->toISOString(),
                                'invitation_id' => $existingInvitation->id,
                            ];

                            continue;
                        }
                    }

                    // Créer l'invitation
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
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $invitations[] = [
                        'email' => $email,
                        'status' => 'invited',
                        'requires_registration' => true,
                        'invitation_id' => $invitation->id,
                        'expires_at' => $invitation->expires_at->toISOString(),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error inviting member', [
                    'email' => $email,
                    'workspace_id' => $workspace->id,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'email' => $email,
                    'message' => 'Erreur lors de l\'invitation: '.$e->getMessage(),
                    'type' => 'server_error',
                ];
            }
        }

        // Logique améliorée pour les messages avec distinction warnings/errors
        $successCount = count($invitations);
        $errorCount = count($errors);
        $warningCount = count($warnings);
        $totalAttempts = count($request->emails);

        // Construction du message principal
        $messageParts = [];

        if ($successCount > 0) {
            $messageParts[] = "{$successCount} invitation(s) envoyée(s) avec succès";
        }

        if ($warningCount > 0) {
            $messageParts[] = "{$warningCount} invitation(s) déjà en attente";
        }

        if ($errorCount > 0) {
            $messageParts[] = "{$errorCount} erreur(s)";
        }

        // Déterminer le message et le code de statut
        if ($successCount > 0) {
            // Au moins une invitation envoyée
            $message = implode(', ', $messageParts);
            $statusCode = 200;
        } elseif ($warningCount > 0 && $errorCount === 0) {
            // Uniquement des invitations déjà en attente
            $message = 'Toutes les invitations sont déjà en attente de réponse';
            $statusCode = 200;
        } elseif ($warningCount === 0 && $errorCount > 0) {
            // Uniquement des erreurs
            $message = "Aucune invitation n'a pu être envoyée";
            $statusCode = 422;
        } else {
            // Mix de warnings et erreurs
            $message = implode(', ', $messageParts);
            $statusCode = 422;
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'invitations' => $invitations,
                'warnings' => $warnings,
                'errors' => $errors,
                'success_count' => $successCount,
                'warning_count' => $warningCount,
                'error_count' => $errorCount,
                'total_attempts' => $totalAttempts,
            ],
        ], $statusCode);
    }

    /**
     * Helper pour obtenir le label d'un rôle
     */
    private function getRoleLabel(string $role): string
    {
        $labels = [
            'owner' => 'Propriétaire',
            'manager' => 'Manager',
            'cadre' => 'Cadre',
            'collaborateur' => 'Collaborateur',
            'stagiaire' => 'Stagiaire',
            'observateur' => 'Observateur',
        ];

        return $labels[$role] ?? $role;
    }

    /**
     * Afficher les détails d'un membre spécifique
     */
    public function showMember(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('view', $workspace);

        // Vérifier que l'utilisateur est bien membre du workspace
        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre de ce workspace',
            ], 404);
        }

        // Charger les informations du membre
        $member = $workspace->members()
            ->where('user_id', $user->id)
            ->withPivot(['role_id', 'invited_at', 'invited_by'])
            ->first();

        $memberRoleName = \Spatie\Permission\Models\Role::find($member->pivot->role_id)?->name ?? 'membre';

        // Charger les statistiques
        $projectsCount = $user->projets()
            ->whereHas('workspace', function ($query) use ($workspace) {
                $query->where('id', $workspace->id);
            })
            ->count();

        $tasksCount = $user->taches()
            ->whereHas('activite.projet', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            ->count();

        // Charger les projets où l'utilisateur est responsable
        $responsibleProjects = $workspace->projets()
            ->where('responsable_id', $user->id)
            ->select('id', 'nom', 'code', 'status', 'created_at')
            ->get();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                ],
                'workspace_membership' => [
                    'role' => $memberRoleName,
                    'role_label' => $this->getRoleLabel($memberRoleName),
                    'invited_at' => $member->pivot->invited_at,
                    'invited_by' => $member->pivot->invited_by,
                ],
                'statistics' => [
                    'projects_count' => $projectsCount,
                    'tasks_count' => $tasksCount,
                    'responsible_projects_count' => $responsibleProjects->count(),
                ],
                'responsible_projects' => $responsibleProjects,
                'last_activity' => $user->last_activity_at,
            ],
        ]);
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function updateMemberRole(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manage', $workspace);

        $request->validate([
            'role' => ['required', Rule::in(['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'])],
        ]);

        // Ne pas permettre de modifier le rôle du propriétaire
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Impossible de modifier le rôle du propriétaire du workspace',
            ], 422);
        }

        // Vérifier que l'utilisateur est bien membre
        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre de ce workspace',
            ], 404);
        }

        $role = \Spatie\Permission\Models\Role::findByName($request->role, 'web');

        // Mettre à jour le rôle
        $workspace->members()->updateExistingPivot($user->id, [
            'role_id' => $role->id,
        ]);

        // Log d'activité
        activity()
            ->causedBy(auth()->user())
            ->performedOn($workspace)
            ->withProperties([
                'member_id' => $user->id,
                'member_name' => $user->nom,
                'new_role' => $request->role,
            ])
            ->log('Membre mis à jour');

        return response()->json([
            'message' => 'Rôle du membre mis à jour avec succès',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'role' => $request->role,
                    'role_label' => $this->getRoleLabel($request->role),
                ],
            ],
        ]);
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

            if (! $workspace->is_active) {
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

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Invitation invalide ou expirée',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting invitation', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getWorkspaces(Request $request)
    {
        $user = $request->user();

        $gate = app(ContextualPermissionGate::class);

        $workspaces = Workspace::accessibleBy($user->id)
            ->withCount([
                'projets' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->whereNotNull('id')
            ->get()
            ->map(function ($workspace) use ($user, $gate) {
                return [
                    'id' => $workspace->id,
                    'nom' => $workspace->nom,
                    'code' => $workspace->code,
                    'projets_count' => $workspace->projets_count,
                    'description' => $workspace->description,
                    'created_at' => $workspace->created_at,
                    'updated_at' => $workspace->updated_at,
                    'owner_id' => $workspace->owner_id,
                    'user_permissions' => [
                        'can_view_workspace' => $gate->userCan($user, Permission::WORKSPACES_VIEW, $workspace),
                        'can_create_project' => $gate->userCan($user, Permission::WORKSPACES_CREATE_PROJECT, $workspace),
                        'can_invite_members' => $gate->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace),
                        'can_remove_members' => $gate->userCan($user, Permission::WORKSPACES_REMOVE_MEMBER, $workspace),
                        'can_manage_workspace_settings' => $gate->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace),
                        'can_view_pending_validations' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_PENDING, $workspace),
                        'can_view_evaluation_score' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_SCORE, $workspace),
                        'can_view_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_FICHE, $workspace),
                        'can_export_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_EXPORT_FICHE, $workspace),
                        'can_manage_notification_preferences' => $gate->userCan($user, Permission::NOTIFICATIONS_MANAGE_PREFERENCES, $workspace),
                        'can_view_evaluation_dashboard' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_DASHBOARD, $workspace),
                        'can_view_workspace_taches' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_WORKSPACE_TACHES, $workspace),
                        'can_inline_edit_tache' => $gate->userCan($user, Permission::TACHES_INLINE_EDIT, $workspace),
                        'can_manage_workspace_documents' => $gate->userCan($user, Permission::DOCUMENTS_MANAGE_WORKSPACE, $workspace),
                        'can_manage_subscription' => $workspace->isOwnerOrAdmin($user),
                        'can_submit_result' => $gate->userCan($user, Permission::TACHES_SUBMIT_RESULT, $workspace),
                        'can_search_global' => $gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace),
                        'can_search_scoped' => $gate->userCan($user, Permission::SEARCH_SCOPED, $workspace),
                        'can_help_articles_read' => $gate->userCan($user, Permission::HELP_ARTICLES_READ, $workspace),
                        'can_help_articles_create' => $gate->userCan($user, Permission::HELP_ARTICLES_CREATE, $workspace),
                        'can_help_articles_edit' => $gate->userCan($user, Permission::HELP_ARTICLES_EDIT, $workspace),
                        'can_help_articles_publish' => $gate->userCan($user, Permission::HELP_ARTICLES_PUBLISH, $workspace),
                        'can_help_articles_delete' => $gate->userCan($user, Permission::HELP_ARTICLES_DELETE, $workspace),
                        'can_help_articles_upload_image' => $gate->userCan($user, Permission::HELP_ARTICLES_UPLOAD_IMAGE, $workspace),
                        'can_help_categories_manage' => $gate->userCan($user, Permission::HELP_CATEGORIES_MANAGE, $workspace),
                        'can_view_members' => $gate->userCan($user, Permission::WORKSPACES_VIEW_MEMBERS, $workspace),
                    ],
                ];
            })
            ->filter()
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
                    'message' => 'Vous êtes déjà membre de ce workspace',
                ], 400);
            }

            // Ajouter comme membre
            $invitedRole = \Spatie\Permission\Models\Role::findByName($invitation->role ?? 'collaborateur', 'web');
            $workspace->members()->attach($user->id, [
                'role_id' => $invitedRole->id,
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
                    'invited_by' => $invitation->invited_by,
                ])
                ->log('User accepted workspace invitation');

            DB::commit();

            return response()->json([
                'message' => 'Invitation acceptée avec succès',
                'data' => [
                    'workspace' => $workspace,
                    'role' => $invitation->role,
                    'redirect_to' => "/workspaces/{$workspace->id}",
                ],
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
            $user->assignRole('collaborateur');

            // Ajouter au workspace invité
            $invitedRole = \Spatie\Permission\Models\Role::findByName($invitation->role ?? 'collaborateur', 'web');
            $workspace->members()->attach($user->id, [
                'role_id' => $invitedRole->id,
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
                    'invited_by' => $invitation->invited_by,
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
                    'redirect_to' => "/workspaces/{$workspace->id}",
                ],
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
                        ],
                    ],
                    'user_exists' => $userExists,
                    'requires_registration' => ! $userExists,
                ],
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Invitation invalide ou expirée',
            ], 404);
        }
    }

    /**
     * Get workspace invitations
     */
    public function invitations(Workspace $workspace)
    {
        if (! $this->userCanManageMembers(request()->user(), $workspace)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

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
        if (! $this->userCanManageMembers(request()->user(), $workspace)) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

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

        // Renvoi de l'email d'invitation
        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation));

        return response()->json([
            'message' => 'Invitation renvoyée avec succès',
        ]);
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(Workspace $workspace, WorkspaceInvitation $invitation)
    {
        $this->authorize('manage', $workspace);

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
        if (! $request->user()->isSuperAdmin()) {
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
            'statistics' => $statistics,
        ]);
    }

    /**
     * Get invitation statistics
     */
    public function invitationStatistics(Request $request)
    {
        if (! $request->user()->isSuperAdmin()) {
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
            'data' => $statistics,
        ]);
    }

    /**
     * Get workspace members
     */
    public function members(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);

        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $members = $workspace->members()
            ->withPivot(['role_id', 'invited_at', 'invited_by'])
            ->get()
            ->map(function ($member) {
                $roleName = \Spatie\Permission\Models\Role::find($member->pivot->role_id)?->name ?? 'membre';
                $member->pivot->role = $roleName;

                return $member;
            });

        return response()->json([
            'data' => $members,
        ]);
    }

    /**
     * Liste paginée des membres du workspace avec métadonnées de gestion.
     * Requiert WORKSPACES_VIEW_MEMBERS (owner/directeur/manager).
     */
    public function workspaceUsers(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $gate = app(ContextualPermissionGate::class);

        abort_unless(
            $gate->userCan($user, Permission::WORKSPACES_VIEW_MEMBERS, $workspace),
            403,
            'Accès non autorisé'
        );

        $request->validate([
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $workspace->members()
            ->withPivot(['role_id', 'invited_at', 'invited_by'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('nom', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            });

        $members = $query->paginate($request->integer('per_page', 20));

        $members->getCollection()->transform(function (User $member) {
            $roleName = \Spatie\Permission\Models\Role::find($member->pivot->role_id)?->name ?? 'membre';
            $member->pivot->role = $roleName;

            return [
                'id' => $member->id,
                'nom' => $member->nom,
                'email' => $member->email,
                'avatar' => $member->avatar,
                'fonction' => $member->fonction,
                'workspace_role' => $roleName,
                'joined_at' => $member->pivot->invited_at,
                'last_login_at' => $member->last_login_at,
            ];
        });

        return response()->json([
            'data' => $members->items(),
            'current_page' => $members->currentPage(),
            'last_page' => $members->lastPage(),
            'per_page' => $members->perPage(),
            'total' => $members->total(),
        ]);
    }

    /**
     * Add a member to workspace
     */
    public function addMember(Request $request, Workspace $workspace)
    {
        // Check if user is owner or admin
        if (! $this->userCanManageMembers($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission d\'ajouter des membres',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => ['required', Rule::in(['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'])],
        ]);

        // Check if user is already a member
        if ($workspace->members()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà membre du workspace',
            ], 422);
        }

        $role = \Spatie\Permission\Models\Role::findByName($validated['role'], 'web');

        $workspace->members()->attach($validated['user_id'], [
            'role_id' => $role->id,
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
        if (! $this->userCanManageMembers($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de modifier les membres',
            ], 403);
        }

        $validated = $request->validate([
            'role' => ['sometimes', Rule::in(['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'])],
        ]);

        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du workspace',
            ], 404);
        }

        $pivotData = [];
        if (isset($validated['role'])) {
            $role = \Spatie\Permission\Models\Role::findByName($validated['role'], 'web');
            $pivotData['role_id'] = $role->id;
        }

        $workspace->members()->updateExistingPivot($user->id, $pivotData);

        return response()->json([
            'message' => 'Membre mis à jour avec succès',
        ]);
    }

    /**
     * ✅ Retirer un membre avec transfert de responsabilités
     *
     * DELETE /workspaces/{workspace}/members/{user}/remove
     */
    public function removeMemberWithTransfer(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manage', $workspace);

        // Ne peut pas retirer le owner
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Impossible de retirer le propriétaire du workspace. Transférez d\'abord la propriété.',
            ], 422);
        }

        // Vérifier que l'utilisateur est membre
        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du workspace',
            ], 404);
        }

        $request->validate([
            'new_responsable_id' => 'nullable|exists:users,id',
        ]);

        try {
            $newResponsable = null;

            if ($request->new_responsable_id) {
                $newResponsable = User::findOrFail($request->new_responsable_id);

                // Vérifier que le nouveau responsable est membre du workspace
                if (! $workspace->members()->where('user_id', $newResponsable->id)->exists()) {
                    return response()->json([
                        'message' => 'Le nouveau responsable doit être membre du workspace',
                    ], 422);
                }
            }

            $stats = $this->removalService->removeFromWorkspace($workspace, $user, $newResponsable);

            return response()->json([
                'message' => 'Membre retiré avec succès du workspace',
                'data' => [
                    'removed_user' => [
                        'id' => $user->id,
                        'nom' => $user->nom,
                        'email' => $user->email,
                    ],
                    'new_responsable' => $newResponsable ? [
                        'id' => $newResponsable->id,
                        'nom' => $newResponsable->nom,
                    ] : [
                        'id' => $workspace->owner->id,
                        'nom' => $workspace->owner->nom,
                        'is_default' => true,
                    ],
                    'stats' => $stats,
                    'summary' => [
                        'projets_impactes' => $stats['projets_transferred'] + $stats['projets_membership_removed'],
                        'activites_impactees' => $stats['activites_transferred'] + $stats['activites_membership_removed'],
                        'taches_transferees' => $stats['taches_reassigned'],
                        'taches_desassignees' => $stats['taches_unassigned'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du retrait du membre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ BONUS : Retrait simple sans transfert (si aucune responsabilité)
     *
     * DELETE /workspaces/{workspace}/members/{user}
     */
    public function removeMember(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manage', $workspace);

        // Ne peut pas retirer le owner
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Impossible de retirer le propriétaire du workspace',
            ], 422);
        }

        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du workspace',
            ], 404);
        }

        // Vérifier s'il a des responsabilités
        $preview = $this->removalService->getRemovalPreview($workspace, $user);

        if ($preview['requires_transfer']) {
            return response()->json([
                'message' => 'Ce membre a des responsabilités. Utilisez l\'endpoint de transfert.',
                'impact' => $preview,
            ], 422);
        }

        try {
            // Pas de responsabilités → retrait simple avec transfert automatique au owner
            $stats = $this->removalService->removeFromWorkspace($workspace, $user);

            return response()->json([
                'message' => 'Membre retiré avec succès',
                'data' => ['stats' => $stats],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du retrait du membre',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ VERSION CORRIGÉE : Get workspace projects avec permissions correctes
     */
    public function projets(Request $request, Workspace $workspace)
    {
        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé',
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

        // super_admin, workspace owner ou admin → voit TOUS les projets
        if (
            $user->isSuperAdmin() ||
            $workspace->owner_id === $user->id ||
            $this->isWorkspaceAdmin($user, $workspace)
        ) {

            // ✅ Aucun filtre supplémentaire

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
        return app(ContextualPermissionGate::class)->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace);
    }

    /**
     * Get workspace statistics
     */
    public function statistics(Request $request, Workspace $workspace)
    {
        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé',
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
        return app(ContextualPermissionGate::class)->userCan($user, Permission::WORKSPACES_VIEW, $workspace);
    }

    /**
     * Helper: Check if user can manage workspace members.
     */
    private function userCanManageMembers(User $user, Workspace $workspace): bool
    {
        return app(ContextualPermissionGate::class)->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace);
    }

    /**
     * Generate unique workspace code
     */
    private function generateUniqueCode(): string
    {
        do {
            $latest = Workspace::withTrashed()->latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $code = 'WS-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
        } while (Workspace::where('code', $code)->exists());

        return $code;
    }

    /**
     * Display the specified workspace
     */
    public function show(Request $request, Workspace $workspace)
    {
        // Check if user has access
        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé à ce workspace',
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

        $user = $request->user();
        $gate = app(ContextualPermissionGate::class);

        return response()->json([
            'data' => array_merge($workspace->toArray(), [
                'user_permissions' => [
                    'can_view_workspace' => $gate->userCan($user, Permission::WORKSPACES_VIEW, $workspace),
                    'can_create_project' => $gate->userCan($user, Permission::WORKSPACES_CREATE_PROJECT, $workspace),
                    'can_invite_members' => $gate->userCan($user, Permission::WORKSPACES_INVITE_MEMBER, $workspace),
                    'can_remove_members' => $gate->userCan($user, Permission::WORKSPACES_REMOVE_MEMBER, $workspace),
                    'can_manage_workspace_settings' => $gate->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace),
                    'can_view_pending_validations' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_PENDING, $workspace),
                    'can_view_evaluation_score' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_SCORE, $workspace),
                    'can_view_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_FICHE, $workspace),
                    'can_export_fiche_evaluation' => $gate->userCan($user, Permission::EVALUATIONS_EXPORT_FICHE, $workspace),
                    'can_manage_notification_preferences' => $gate->userCan($user, Permission::NOTIFICATIONS_MANAGE_PREFERENCES, $workspace),
                    'can_view_evaluation_dashboard' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_DASHBOARD, $workspace),
                    'can_view_workspace_taches' => $gate->userCan($user, Permission::EVALUATIONS_VIEW_WORKSPACE_TACHES, $workspace),
                    'can_inline_edit_tache' => $gate->userCan($user, Permission::TACHES_INLINE_EDIT, $workspace),
                    'can_manage_workspace_documents' => $gate->userCan($user, Permission::DOCUMENTS_MANAGE_WORKSPACE, $workspace),
                    'can_manage_subscription' => $workspace->isOwnerOrAdmin($user),
                    'can_submit_result' => $gate->userCan($user, Permission::TACHES_SUBMIT_RESULT, $workspace),
                    'can_search_global' => $gate->userCan($user, Permission::SEARCH_GLOBAL, $workspace),
                    'can_search_scoped' => $gate->userCan($user, Permission::SEARCH_SCOPED, $workspace),
                    'can_help_articles_read' => $gate->userCan($user, Permission::HELP_ARTICLES_READ, $workspace),
                    'can_help_articles_create' => $gate->userCan($user, Permission::HELP_ARTICLES_CREATE, $workspace),
                    'can_help_articles_edit' => $gate->userCan($user, Permission::HELP_ARTICLES_EDIT, $workspace),
                    'can_help_articles_publish' => $gate->userCan($user, Permission::HELP_ARTICLES_PUBLISH, $workspace),
                    'can_help_articles_delete' => $gate->userCan($user, Permission::HELP_ARTICLES_DELETE, $workspace),
                    'can_help_articles_upload_image' => $gate->userCan($user, Permission::HELP_ARTICLES_UPLOAD_IMAGE, $workspace),
                    'can_help_categories_manage' => $gate->userCan($user, Permission::HELP_CATEGORIES_MANAGE, $workspace),
                    'can_view_members' => $gate->userCan($user, Permission::WORKSPACES_VIEW_MEMBERS, $workspace),
                ],
                'subscription_summary' => app(SubscriptionService::class)->summary($workspace),
            ]),
        ]);
    }

    /**
     * Super-admin: configure subscription mode and trial duration for a workspace.
     */
    public function updateSubscription(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Accès réservé au super-admin'], 403);
        }

        $validated = $request->validate([
            'subscription_mode' => 'sometimes|string|in:trial,paid,free',
            'trial_duration_days' => 'sometimes|integer|min:1|max:365',
            'trial_started_at' => 'sometimes|nullable|date',
        ]);

        $workspace->fill($validated)->save();

        return response()->json([
            'data' => app(SubscriptionService::class)->summary($workspace->fresh()),
        ]);
    }

    /**
     * Lightweight subscription summary for the trial banner.
     */
    public function subscriptionSummary(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();

        $isMember = $workspace->owner_id === $user->id
            || $workspace->members()->where('user_id', $user->id)->exists();

        if (! $isMember && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return response()->json([
            'data' => app(SubscriptionService::class)->summary($workspace),
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
                'message' => 'Seul le propriétaire peut modifier le workspace',
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
                    if (! is_array($currentSettings)) {
                        $currentSettings = [];
                    }

                    // Fusionner avec les nouvelles settings
                    $dataToUpdate['settings'] = array_merge($currentSettings, $newSettings);

                    Log::info('Settings fusionnées', [
                        'workspace_id' => $workspace->id,
                        'current' => $currentSettings,
                        'new' => $newSettings,
                        'merged' => $dataToUpdate['settings'],
                    ]);
                }
            }

            // ✅ Gestion du logo - Ordre important !

            // 1. D'abord vérifier si on doit supprimer le logo
            if ($request->has('remove_logo') && $request->boolean('remove_logo')) {
                if ($workspace->logo && file_exists(public_path('uploads/'.$workspace->logo))) {
                    unlink(public_path('uploads/'.$workspace->logo));
                }
                $dataToUpdate['logo'] = null;

                Log::info('Logo supprimé', ['workspace_id' => $workspace->id]);
            }
            // 2. Ensuite vérifier si on upload un nouveau logo
            elseif ($request->hasFile('logo')) {
                try {
                    // Supprimer l'ancien logo si existe
                    if ($workspace->logo && file_exists(public_path('uploads/'.$workspace->logo))) {
                        unlink(public_path('uploads/'.$workspace->logo));
                    }

                    // Enregistrer le nouveau logo directement dans public/uploads
                    $logoPath = $request->file('logo')->store(
                        'workspaces/logos',
                        'uploads' // Utiliser le disk custom
                    );
                    $dataToUpdate['logo'] = $logoPath;

                    Log::info('Logo mis à jour avec succès', [
                        'workspace_id' => $workspace->id,
                        'logo_path' => $logoPath,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur upload logo workspace: '.$e->getMessage());
                    throw $e; // Propager l'erreur pour rollback
                }
            }

            // 3. Sinon, ne rien faire (garder le logo existant)

            // ✅ Log avant mise à jour
            Log::info('Données à mettre à jour', [
                'workspace_id' => $workspace->id,
                'data' => array_keys($dataToUpdate),
                'settings_is_array' => isset($dataToUpdate['settings']) ? is_array($dataToUpdate['settings']) : 'N/A',
            ]);

            // Mettre à jour le workspace
            if (! empty($dataToUpdate)) {
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
        } catch (QueryException $e) {
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
                'message' => 'Seul le propriétaire peut supprimer le workspace',
            ], 403);
        }

        // Check if workspace has projects
        if ($workspace->projets()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un workspace contenant des projets. Veuillez d\'abord les supprimer ou les déplacer.',
                'projects_count' => $workspace->projets()->count(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Delete logo if exists
            if ($workspace->logo && file_exists(public_path('uploads/'.$workspace->logo))) {
                unlink(public_path('uploads/'.$workspace->logo));
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
        if (! $workspace->hasAccess($user)) {
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
        if (! $this->userCanManageWorkspace($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission d\'archiver ce workspace',
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
        if (! $this->userCanManageWorkspace($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Vous n\'avez pas la permission de restaurer ce workspace',
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
                'message' => 'Seul le propriétaire peut transférer la propriété du workspace',
            ], 403);
        }

        $validated = $request->validate([
            'new_owner_id' => 'required|exists:users,id',
        ]);

        // Check if new owner is a member
        if (! $workspace->members()->where('user_id', $validated['new_owner_id'])->exists()) {
            return response()->json([
                'message' => 'Le nouvel propriétaire doit être membre du workspace',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $newOwner = User::find($validated['new_owner_id']);
            $oldOwner = $workspace->owner;

            // Update workspace owner
            $workspace->update(['owner_id' => $validated['new_owner_id']]);

            // Update member roles
            $ownerRoleId = \Spatie\Permission\Models\Role::findByName('owner', 'web')->id;
            $managerRoleId = \Spatie\Permission\Models\Role::findByName('manager', 'web')->id;

            $workspace->members()->updateExistingPivot($validated['new_owner_id'], ['role_id' => $ownerRoleId]);
            $workspace->members()->updateExistingPivot($request->user()->id, ['role_id' => $managerRoleId]);

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
        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $newWorkspace = $workspace->replicate();
            $newWorkspace->nom = $workspace->nom.' (Copie)';
            $newWorkspace->code = $this->generateUniqueCode();
            $newWorkspace->owner_id = $request->user()->id;
            $newWorkspace->save();

            // Copy members
            $members = $workspace->members;
            foreach ($members as $member) {
                $newWorkspace->members()->attach($member->id, [
                    'role_id' => $member->pivot->role_id,
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
        if (! $this->userHasAccess($request->user(), $workspace)) {
            return response()->json([
                'message' => 'Accès non autorisé',
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
        return app(ContextualPermissionGate::class)->userCan($user, Permission::WORKSPACES_MANAGE_SETTINGS, $workspace);
    }

    /**
     * ✅ Obtenir un aperçu de l'impact du retrait d'un membre
     *
     * GET /workspaces/{workspace}/members/{user}/removal-preview
     */
    public function getRemovalPreview(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manage', $workspace);

        // Vérifier que l'utilisateur est membre
        if (! $workspace->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas membre du workspace',
            ], 404);
        }

        // Ne peut pas retirer le owner
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Impossible de retirer le propriétaire du workspace',
            ], 422);
        }

        try {
            $preview = $this->removalService->getRemovalPreview($workspace, $user);

            return response()->json([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'nom' => $user->nom,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ],
                    'impact' => $preview,
                    'default_responsable' => [
                        'id' => $workspace->owner->id,
                        'nom' => $workspace->owner->nom,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'analyse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Obtenir les projets où l'utilisateur est responsable
     *
     * GET /workspaces/{workspace}/members/{user}/projects
     */
    public function getUserProjects(Request $request, Workspace $workspace, User $user)
    {
        $this->authorize('manage', $workspace);

        $projects = $this->removalService->getUserProjectsAsResponsable($user, $workspace);

        return response()->json([
            'data' => $projects->map(function ($projet) {
                return [
                    'id' => $projet->id,
                    'nom' => $projet->nom,
                    'code' => $projet->code,
                    'couleur' => $projet->couleur,
                    'activites_count' => $projet->activites_count,
                    'members_count' => $projet->members_count,
                ];
            }),
            'count' => $projects->count(),
        ]);
    }

    /**
     * ✅ Obtenir les candidats pour le transfert
     *
     * GET /workspaces/{workspace}/transfer-candidates
     */
    public function getTransferCandidates(Request $request, Workspace $workspace)
    {
        $this->authorize('manage', $workspace);

        $request->validate([
            'exclude_user_id' => 'required|exists:users,id',
        ]);

        $excludeUser = User::findOrFail($request->exclude_user_id);
        $candidates = $this->removalService->getTransferCandidates($workspace, $excludeUser);

        return response()->json([
            'data' => $candidates,
            'default_candidate' => [
                'id' => $workspace->owner->id,
                'nom' => $workspace->owner->nom,
                'email' => $workspace->owner->email,
                'avatar' => $workspace->owner->avatar,
                'is_owner' => true,
            ],
        ]);
    }
}
