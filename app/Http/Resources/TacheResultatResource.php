<?php

namespace App\Http\Resources;

use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheResultatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tache_id' => $this->tache_id,

            // Informations utilisateur
            'user' => $this->when($this->user, [
                'id' => $this->user?->id,
                'nom' => $this->user?->nom,
                'email' => $this->user?->email,
                'avatar' => $this->user?->avatar,
            ]),

            // Résultats
            'resultats_attendus' => $this->resultats_attendus,
            'resultats_obtenus' => $this->resultats_obtenus,
            'taux_realisation' => $this->taux_realisation,
            'difficultes_rencontrees' => $this->difficultes_rencontrees,
            'solutions_envisagees' => $this->solutions_envisagees,
            'observations' => $this->observations,

            // Statut
            'statut' => $this->statut,
            'validation_status' => $this->validation_status,
            'is_fully_validated' => $this->is_fully_validated,
            'soumis_le' => $this->soumis_le?->format('Y-m-d H:i:s'),

            // N0 circuit
            'validation_n0' => [
                'soumis_n0_le' => $this->soumis_n0_le?->format('Y-m-d H:i:s'),
                'action' => $this->action_n0,
                'commentaire' => $this->commentaire_n0,
                'action_le' => $this->action_n0_le?->format('Y-m-d H:i:s'),
                'actor' => $this->when($this->n0Actor, [
                    'id' => $this->n0Actor?->id,
                    'nom' => $this->n0Actor?->nom,
                ]),
            ],

            // Bypass circuit (N1 context panel)
            'bypass' => [
                'active' => (bool) $this->bypass_active,
                'motif' => $this->motif_bypass,
                'bypass_le' => $this->bypass_le?->format('Y-m-d H:i:s'),
                'bypass_count' => (int) $this->bypass_count,
            ],

            // Full audit trail for N1 context panel
            'audit_logs' => $this->when($this->relationLoaded('auditLogs'), function () {
                return $this->auditLogs->map(fn ($log) => [
                    'action' => $log->action,
                    'actor_id' => $log->actor_id,
                    'context' => $log->context,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ]);
            }),

            // Validation N1
            'validation_n1' => [
                'valide' => $this->valide_par_n1,
                'validateur' => $this->when($this->validateurN1, [
                    'id' => $this->validateurN1?->id,
                    'nom' => $this->validateurN1?->nom,
                    'avatar' => $this->validateurN1?->avatar,
                ]),
                'valide_le' => $this->valide_le_n1?->format('Y-m-d H:i:s'),
                'commentaire' => $this->commentaire_n1,
            ],
            // Validation N2
            'validation_n2' => [
                'valide' => $this->valide_par_n2,
                'validateur' => $this->when($this->validateurN2, [
                    'id' => $this->validateurN2?->id,
                    'nom' => $this->validateurN2?->nom,
                    'avatar' => $this->validateurN2?->avatar,
                ]),
                'valide_le' => $this->valide_le_n2?->format('Y-m-d H:i:s'),
                'commentaire' => $this->commentaire_n2,
            ],

            // 🔥 IMPORTANT : Tâche avec TOUTES les relations
            'tache' => $this->when($this->relationLoaded('tache'), [
                'id' => $this->tache?->id,
                'titre' => $this->tache?->titre,
                'code' => $this->tache?->code,
                'echeance' => $this->tache?->echeance?->format('Y-m-d'),

                // 🔥 Activité (OBLIGATOIRE pour les permissions)
                'activite' => $this->when($this->tache?->relationLoaded('activite'), [
                    'id' => $this->tache->activite?->id,
                    'titre' => $this->tache->activite?->titre,
                    'responsable_id' => $this->tache->activite?->responsable_id,
                    'responsable' => $this->when($this->tache->activite?->relationLoaded('responsable'), [
                        'id' => $this->tache->activite->responsable?->id,
                        'nom' => $this->tache->activite->responsable?->nom,
                        'avatar' => $this->tache->activite->responsable?->avatar,
                    ]),

                    // 🔥 Projet (OBLIGATOIRE pour validation N2)
                    'projet' => $this->when($this->tache->activite?->relationLoaded('projet'), [
                        'id' => $this->tache->activite->projet?->id,
                        'titre' => $this->tache->activite->projet?->titre,
                        'responsable_id' => $this->tache->activite->projet?->responsable_id,
                        'responsable' => $this->when($this->tache->activite->projet?->relationLoaded('responsable'), [
                            'id' => $this->tache->activite->projet->responsable?->id,
                            'nom' => $this->tache->activite->projet->responsable?->nom,
                            'avatar' => $this->tache->activite->projet->responsable?->avatar,
                        ]),
                    ]),
                ]),
            ]),

            // Documents
            'documents' => $this->when($this->relationLoaded('documents'), function () use ($request) {
                return $this->documents->map(function ($doc) use ($request) {
                    return [
                        'id' => $doc->id,
                        'nom' => $doc->nom,
                        'nom_stockage' => $doc->nom_stockage,
                        'url' => $doc->url,
                        'thumbnail_url' => $doc->thumbnail_url,
                        'extension' => $doc->extension,
                        'mime_type' => $doc->mime_type,
                        'taille' => $doc->taille,
                        'taille_humaine' => $doc->formatted_size,
                        'is_image' => $doc->is_image,
                        'is_pdf' => $doc->is_pdf,
                        'is_video' => $doc->is_video,
                        'is_audio' => $doc->is_audio,
                        'version' => $doc->version,
                        'is_latest_version' => $doc->is_latest_version,
                        'parent_id' => $doc->parent_id,
                        'uploaded_by' => $doc->user ? [
                            'id' => $doc->user->id,
                            'nom' => $doc->user->nom,
                            'avatar' => $doc->user->avatar,
                        ] : null,
                        'uploaded_at' => $doc->created_at?->format('Y-m-d H:i:s'),
                        'can_view' => $doc->canBeViewedBy($request->user()),
                        'can_download' => $doc->canBeDownloadedBy($request->user()),
                        'can_edit' => $doc->canBeEditedBy($request->user()),
                        'can_delete' => $doc->canBeDeletedBy($request->user()),
                    ];
                });
            }),

            // Métadonnées
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Permissions
            'permissions' => $this->when($request->user(), function () use ($request) {
                $user = $request->user();
                $gate = app(ContextualPermissionGate::class);
                $tache = $this->tache;

                return [
                    'can_edit' => ! $this->is_fully_validated && $this->user_id === $user->id,
                    'can_delete' => ! $this->is_fully_validated && $this->user_id === $user->id,
                    'can_submit' => ! $this->soumis_le && $this->user_id === $user->id,
                    'can_validate_n1' => $this->canBeValidatedByN1($user),
                    'can_validate_n2' => $this->canBeValidatedByN2($user),
                    'can_approuver_n0' => $tache && $gate->userCan($user, Permission::TACHES_APPROVE_N0, $tache),
                    'can_renvoyer_n0' => $tache && $gate->userCan($user, Permission::RESULTATS_RENVOYER_N0, $tache),
                    'can_activer_bypass' => $this->user_id === $user->id
                        && $this->statut === 'a_refaire'
                        && ! $this->bypass_active,
                ];
            }),
        ];
    }
}
