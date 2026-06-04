<?php

namespace App\Http\Resources;

use App\Models\ValidationAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ValidationAuditLog $resource
 *
 * @mixin ValidationAuditLog
 */
class ValidationAuditLogResource extends JsonResource
{
    /**
     * @responseField id integer Identifiant unique de l'entrée d'audit.
     * @responseField action string Action réalisée (approuve, renvoye, timeout, bypass, n1_valide, n1_rejete, n2_valide, n2_rejete).
     * @responseField context object|null Données contextuelles de l'action (taux, commentaire, motif…).
     * @responseField created_at string Date ISO 8601 de l'action.
     * @responseField actor object Auteur de l'action {id, nom, email}.
     * @responseField resultat object|null Résultat concerné {id, tache_id, tache_titre}.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'context' => $this->context,
            'created_at' => $this->created_at->toISOString(),

            'actor' => $this->when($this->actor, [
                'id' => $this->actor?->id,
                'nom' => $this->actor?->nom,
                'email' => $this->actor?->email,
            ]),

            'resultat' => $this->when($this->resultat, function () {
                $tache = $this->resultat->tache;

                return [
                    'id' => $this->resultat->id,
                    'tache_id' => $this->resultat->tache_id,
                    'tache_titre' => $tache?->titre,
                ];
            }),
        ];
    }
}
