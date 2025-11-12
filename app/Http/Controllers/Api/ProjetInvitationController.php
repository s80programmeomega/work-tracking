<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Projet;
use App\Models\ProjetInvitation;
use App\Models\User;
use App\Notifications\ProjetInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjetInvitationController extends Controller
{
    /**
     * ✅ Inviter des membres au projet
     */
    public function invite(Request $request, Projet $projet)
    {
        $this->authorize('manageMembers', $projet);

        $request->validate([
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email',
            'role' => 'required|in:admin,member,viewer',
            'message' => 'nullable|string|max:500',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_invite' => 'boolean',
            'send_email' => 'boolean',
        ]);

        $invitations = [];
        $errors = [];
        $sendEmail = $request->input('send_email', true);

        foreach ($request->emails as $email) {
            try {
                // Vérifier si l'utilisateur existe
                $user = User::where('email', $email)->first();

                if ($user) {
                    // ✅ Utilisateur existant
                    
                    // Vérifier si déjà membre du projet
                    if ($projet->members()->where('user_id', $user->id)->exists()) {
                        $errors[] = [
                            'email' => $email,
                            'message' => 'Cet utilisateur est déjà membre du projet'
                        ];
                        continue;
                    }

                    // Vérifier si déjà une invitation en attente
                    $existingInvitation = ProjetInvitation::where('projet_id', $projet->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->first();

                    if ($existingInvitation) {
                        $errors[] = [
                            'email' => $email,
                            'message' => 'Une invitation est déjà en attente pour cet utilisateur'
                        ];
                        continue;
                    }

                    // Créer l'invitation
                    $invitation = ProjetInvitation::create([
                        'projet_id' => $projet->id,
                        'email' => $email,
                        'role' => $request->role,
                        'can_edit' => $request->input('can_edit', false),
                        'can_delete' => $request->input('can_delete', false),
                        'can_invite' => $request->input('can_invite', false),
                        'token' => Str::random(64),
                        'invited_by' => auth()->id(),
                        'message' => $request->message,
                        'status' => 'pending',
                        'expires_at' => now()->addDays(7),
                    ]);

                    // Envoyer l'email
                    if ($sendEmail) {
                        try {
                            $user->notify(new ProjetInvitationNotification($invitation));
                        } catch (\Exception $e) {
                            Log::error('Failed to send projet invitation email', [
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
                    // ✅ Utilisateur externe (n'existe pas dans le système)
                    
                    // Vérifier si déjà invité
                    $existingInvitation = ProjetInvitation::where('projet_id', $projet->id)
                        ->where('email', $email)
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->first();

                    if ($existingInvitation) {
                        $errors[] = [
                            'email' => $email,
                            'message' => 'Une invitation est déjà en attente pour cet email'
                        ];
                        continue;
                    }

                    // Créer l'invitation
                    $invitation = ProjetInvitation::create([
                        'projet_id' => $projet->id,
                        'email' => $email,
                        'role' => $request->role,
                        'can_edit' => $request->input('can_edit', false),
                        'can_delete' => $request->input('can_delete', false),
                        'can_invite' => $request->input('can_invite', false),
                        'token' => Str::random(64),
                        'invited_by' => auth()->id(),
                        'message' => $request->message,
                        'status' => 'pending',
                        'expires_at' => now()->addDays(7),
                    ]);

                    // Envoyer l'email d'invitation
                    if ($sendEmail) {
                        try {
                            Notification::route('mail', $email)
                                ->notify(new ProjetInvitationNotification($invitation));
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
                        'requires_registration' => true,
                        'invitation_id' => $invitation->id,
                        'expires_at' => $invitation->expires_at->toISOString(),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error inviting member to projet', [
                    'email' => $email,
                    'projet_id' => $projet->id,
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
                ? 'Invitations envoyées avec succès' 
                : 'Aucune invitation n\'a pu être envoyée',
            'data' => [
                'invitations' => $invitations,
                'errors' => $errors,
                'success_count' => count($invitations),
                'error_count' => count($errors),
            ],
        ], count($invitations) > 0 ? 200 : 422);
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

            if (!$projet->workspace->is_active) {
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
                        'redirect_to' => "/projets/{$projet->id}"
                    ]
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
                        'redirect_to' => "/projets/{$projet->id}"
                    ]
                ], 201);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Invitation invalide ou expirée'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting projet invitation', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => $e->getMessage()
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
     * ✅ Liste des invitations du projet
     */
    public function index(Projet $projet)
    {
        $this->authorize('manageMembers', $projet);

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
        $this->authorize('manageMembers', $projet);

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
        $this->authorize('manageMembers', $projet);

        if ($invitation->projet_id !== $projet->id) {
            abort(403, 'Cette invitation n\'appartient pas à ce projet');
        }

        $invitation->cancel();

        return response()->json([
            'message' => 'Invitation annulée avec succès',
        ]);
    }
}