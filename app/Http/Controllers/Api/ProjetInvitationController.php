<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projet;
use App\Models\ProjetInvitation;
use App\Models\User;
use App\Notifications\ProjetInvitationNotification;
use App\Notifications\ProjetMemberAddedNotification;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ProjetInvitationController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    /**
     * ✅ AMÉLIORATION : Gestion intelligente des invitations
     * - Membres workspace : Ajout direct + notification
     * - Externes : Invitation par email
     */
    public function invite(Projet $projet, Request $request)
    {
        abort_unless($this->permissionService->canManageProjectMembers(auth()->user(), $projet), 403);

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
            'role' => 'required|in:admin,member,viewer',
            'message' => 'nullable|string|max:500',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_invite' => 'boolean',
            'can_delete_member' => 'boolean',
            'can_create_activity' => 'boolean',
            'can_edit_activity' => 'boolean',
            'can_delete_activity' => 'boolean',
            'send_email' => 'boolean',
        ]);

        // ✅ Validation des permissions selon le rôle
        if ($request->role === 'viewer') {
            $request->merge([
                'can_edit' => false,
                'can_delete' => false,
                'can_invite' => false,
                'can_delete_member' => false,
                'can_create_activity' => false,
                'can_edit_activity' => false,
                'can_delete_activity' => false,
            ]);
        } elseif ($request->role === 'member') {
            $request->merge([
                'can_delete' => false,
                'can_invite' => false,
                'can_delete_member' => false,
                'can_delete_activity' => false,
            ]);
        }

        $addedMembers = [];
        $invitations = [];
        $errors = [];
        $warnings = [];
        $sendEmail = $request->input('send_email', true);

        $permissions = [
            'role' => $request->role,
            'can_edit' => $request->input('can_edit', false),
            'can_delete' => $request->input('can_delete', false),
            'can_invite' => $request->input('can_invite', false),
            'can_delete_member' => $request->input('can_delete_member', false),
            'can_create_activity' => $request->input('can_create_activity', false),
            'can_edit_activity' => $request->input('can_edit_activity', false),
            'can_delete_activity' => $request->input('can_delete_activity', false),
        ];

        foreach ($request->emails as $email) {
            try {
                $user = User::where('email', $email)->first();

                if ($user) {
                    // ✅ CAS 1 : Utilisateur existant dans le système

                    // Vérifier si déjà membre du projet
                    if ($projet->members()->where('user_id', $user->id)->exists()) {
                        $errors[] = [
                            'email' => $email,
                            'message' => 'Cet utilisateur est déjà membre de ce projet',
                            'type' => 'already_member',
                            'user_id' => $user->id,
                            'user_name' => $user->nom,
                        ];

                        continue;
                    }

                    // Vérifier si membre du workspace
                    $isWorkspaceMember = $projet->workspace->members()
                        ->where('user_id', $user->id)
                        ->exists();

                    if ($isWorkspaceMember) {
                        // ✅ MEMBRE DU WORKSPACE : Ajout direct sans invitation
                        DB::transaction(function () use ($projet, $user, $permissions, $sendEmail, &$addedMembers) {
                            // Ajouter au projet
                            $projet->members()->attach($user->id, $permissions);

                            // Envoyer notification (email + système)
                            if ($sendEmail) {
                                $user->notify(new ProjetMemberAddedNotification(
                                    $projet,
                                    auth()->user(),
                                    $permissions['role'],
                                    $permissions
                                ));
                            }

                            $addedMembers[] = [
                                'email' => $user->email,
                                'user_id' => $user->id,
                                'user_name' => $user->nom,
                                'role' => $permissions['role'],
                                'added_directly' => true,
                                'is_workspace_member' => true,
                            ];
                        });

                        continue;
                    }

                    // ✅ UTILISATEUR EXISTANT mais PAS dans le workspace
                    // Vérifier invitation en attente
                    $existingInvitation = ProjetInvitation::where('projet_id', $projet->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->first();

                    if ($existingInvitation && $existingInvitation->expires_at > now()) {
                        $warnings[] = [
                            'email' => $email,
                            'message' => 'Une invitation est déjà en attente. Elle expire '.
                                       $existingInvitation->expires_at->diffForHumans(),
                            'type' => 'pending_invitation',
                            'expires_at' => $existingInvitation->expires_at->toISOString(),
                            'invitation_id' => $existingInvitation->id,
                            'user_name' => $user->nom,
                        ];

                        continue;
                    }

                    // Marquer ancienne invitation comme expirée
                    if ($existingInvitation) {
                        $existingInvitation->update(['status' => 'expired']);
                    }

                    // Créer nouvelle invitation
                    $invitation = $this->createInvitation($projet, $email, $permissions, $request->message);

                    if ($sendEmail) {
                        $user->notify(new ProjetInvitationNotification($invitation));
                    }

                    $invitations[] = [
                        'email' => $email,
                        'user_id' => $user->id,
                        'user_name' => $user->nom,
                        'invitation_id' => $invitation->id,
                        'expires_at' => $invitation->expires_at->toISOString(),
                        'requires_registration' => false,
                        'is_workspace_member' => false,
                    ];

                } else {
                    // ✅ CAS 2 : Utilisateur externe (n'existe pas dans le système)

                    // Vérifier invitation en attente
                    $existingInvitation = ProjetInvitation::where('projet_id', $projet->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->first();

                    if ($existingInvitation && $existingInvitation->expires_at > now()) {
                        $warnings[] = [
                            'email' => $email,
                            'message' => 'Une invitation est déjà en attente. Elle expire '.
                                       $existingInvitation->expires_at->diffForHumans(),
                            'type' => 'pending_invitation',
                            'expires_at' => $existingInvitation->expires_at->toISOString(),
                            'invitation_id' => $existingInvitation->id,
                        ];

                        continue;
                    }

                    if ($existingInvitation) {
                        $existingInvitation->update(['status' => 'expired']);
                    }

                    // Créer invitation
                    $invitation = $this->createInvitation($projet, $email, $permissions, $request->message);

                    if ($sendEmail) {
                        Notification::route('mail', $email)
                            ->notify(new ProjetInvitationNotification($invitation));
                    }

                    $invitations[] = [
                        'email' => $email,
                        'invitation_id' => $invitation->id,
                        'expires_at' => $invitation->expires_at->toISOString(),
                        'requires_registration' => true,
                    ];
                }

            } catch (\Exception $e) {
                Log::error('Error processing projet invitation', [
                    'email' => $email,
                    'projet_id' => $projet->id,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'email' => $email,
                    'message' => 'Erreur: '.$e->getMessage(),
                    'type' => 'server_error',
                ];
            }
        }

        // Construction du message de réponse
        $successCount = count($addedMembers) + count($invitations);
        $directAddCount = count($addedMembers);
        $invitationCount = count($invitations);
        $errorCount = count($errors);
        $warningCount = count($warnings);

        $messageParts = [];

        if ($directAddCount > 0) {
            $messageParts[] = "{$directAddCount} membre(s) ajouté(s) directement";
        }

        if ($invitationCount > 0) {
            $messageParts[] = "{$invitationCount} invitation(s) envoyée(s)";
        }

        if ($warningCount > 0) {
            $messageParts[] = "{$warningCount} déjà en attente";
        }

        if ($errorCount > 0) {
            $messageParts[] = "{$errorCount} erreur(s)";
        }

        $message = $successCount > 0
            ? implode(', ', $messageParts)
            : 'Aucune action effectuée';

        $statusCode = $successCount > 0 ? 200 : 422;

        return response()->json([
            'message' => $message,
            'data' => [
                'added_members' => $addedMembers,      // Nouveaux membres ajoutés directement
                'invitations' => $invitations,          // Invitations envoyées
                'warnings' => $warnings,                // Invitations déjà en attente
                'errors' => $errors,                    // Erreurs
                'success_count' => $successCount,
                'direct_add_count' => $directAddCount,
                'invitation_count' => $invitationCount,
                'warning_count' => $warningCount,
                'error_count' => $errorCount,
            ],
        ], $statusCode);
    }

    /**
     * Helper pour créer une invitation
     */
    private function createInvitation(Projet $projet, string $email, array $permissions, ?string $message): ProjetInvitation
    {
        return ProjetInvitation::create([
            'projet_id' => $projet->id,
            'email' => $email,
            'role' => $permissions['role'],
            'can_edit' => $permissions['can_edit'],
            'can_delete' => $permissions['can_delete'],
            'can_invite' => $permissions['can_invite'],
            'can_delete_member' => $permissions['can_delete_member'],
            'can_create_activity' => $permissions['can_create_activity'] ?? false,
            'can_edit_activity' => $permissions['can_edit_activity'] ?? false,
            'can_delete_activity' => $permissions['can_delete_activity'] ?? false,
            'token' => Str::random(64),
            'invited_by' => auth()->id(),
            'message' => $message,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * ✅ Accepter une invitation
     */
    public function accept(Request $request, string $token)
    {
        DB::beginTransaction();

        try {
            $invitation = ProjetInvitation::where('token', $token)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->firstOrFail();

            $projet = $invitation->projet;

            if (! $projet->workspace->is_active) {
                throw new \Exception('Ce projet n\'est plus actif');
            }

            // Vérifier si l'utilisateur existe
            $user = User::where('email', $invitation->email)->first();

            if ($user) {
                // ✅ Utilisateur existant
                $invitation->accept($user);

                DB::commit();

                return response()->json([
                    'message' => 'Invitation acceptée avec succès',
                    'data' => [
                        'projet' => $projet,
                        'role' => $invitation->role,
                        'redirect_to' => "/projets/{$projet->id}",
                    ],
                ]);
            } else {
                // ✅ Nouvel utilisateur - Doit s'enregistrer d'abord
                $userData = $request->validate([
                    'prenom' => 'required|string|max:255',
                    'nom' => 'required|string|max:255',
                    'password' => 'required|string|min:8|confirmed',
                ]);

                // Créer le compte
                $user = User::create([
                    'nom' => $userData['nom'],
                    'prenom' => $userData['prenom'],
                    'nom_complet' => trim("{$userData['prenom']} {$userData['nom']}"),
                    'email' => $invitation->email,
                    'password' => Hash::make($userData['password']),
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'current_workspace_id' => $projet->workspace_id,
                ]);

                // Assigner le rôle par défaut
                $user->assignRole('member');

                // Accepter l'invitation
                $invitation->accept($user);

                // Générer le token
                $token = $user->createToken('auth_token', ['*'])->plainTextToken;

                DB::commit();

                return response()->json([
                    'message' => 'Compte créé et invitation acceptée avec succès',
                    'data' => [
                        'user' => $user,
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'projet' => $projet,
                        'redirect_to' => "/projets/{$projet->id}",
                    ],
                ], 201);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Invitation invalide ou expirée',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting projet invitation', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * ✅ Vérifier une invitation
     */
    public function check(string $token)
    {
        try {
            $invitation = ProjetInvitation::where('token', $token)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->with(['projet', 'invitedBy'])
                ->firstOrFail();

            $userExists = User::where('email', $invitation->email)->exists();

            return response()->json([
                'data' => [
                    'invitation' => [
                        'email' => $invitation->email,
                        'role' => $invitation->role,
                        'message' => $invitation->message,
                        'expires_at' => $invitation->expires_at->toISOString(),
                        'projet' => [
                            'id' => $invitation->projet->id,
                            'nom' => $invitation->projet->nom,
                            'description' => $invitation->projet->description,
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
     * ✅ Liste des invitations du projet
     */
    public function index(Projet $projet)
    {
        abort_unless($this->permissionService->canManageProjectMembers(auth()->user(), $projet), 403);

        $invitations = ProjetInvitation::where('projet_id', $projet->id)
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
     * ✅ Renvoyer une invitation
     */
    public function resend(Projet $projet, ProjetInvitation $invitation)
    {
        abort_unless($this->permissionService->canManageProjectMembers(auth()->user(), $projet), 403);

        if ($invitation->projet_id !== $projet->id) {
            abort(403, 'Cette invitation n\'appartient pas à ce projet');
        }

        if ($invitation->status !== 'pending') {
            abort(400, 'Cette invitation n\'est plus valide');
        }

        // Prolonger l'expiration
        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        // Renvoyer l'email
        $user = User::where('email', $invitation->email)->first();
        if ($user) {
            $user->notify(new ProjetInvitationNotification($invitation));
        } else {
            Notification::route('mail', $invitation->email)
                ->notify(new ProjetInvitationNotification($invitation));
        }

        return response()->json([
            'message' => 'Invitation renvoyée avec succès',
        ]);
    }

    /**
     * ✅ Annuler une invitation
     */
    public function cancel(Projet $projet, ProjetInvitation $invitation)
    {
        abort_unless($this->permissionService->canManageProjectMembers(auth()->user(), $projet), 403);

        if ($invitation->projet_id !== $projet->id) {
            abort(403, 'Cette invitation n\'appartient pas à ce projet');
        }

        $invitation->cancel();

        return response()->json([
            'message' => 'Invitation annulée avec succès',
        ]);
    }
}
