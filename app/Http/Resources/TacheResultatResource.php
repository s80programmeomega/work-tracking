<?php

namespace App\Http\Resources;

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
            'validation_status' => $this->validation_status,
            'is_fully_validated' => $this->is_fully_validated,
            'soumis_le' => $this->soumis_le?->format('Y-m-d H:i:s'),
            
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
            
            // Tâche associée
            'tache' => $this->when($this->relationLoaded('tache'), [
                'id' => $this->tache?->id,
                'titre' => $this->tache?->titre,
                'code' => $this->tache?->code,
                'echeance' => $this->tache?->echeance?->format('Y-m-d'),
            ]),
            
            // Documents
            'documents' => $this->when($this->relationLoaded('documents'), function() {
                return $this->documents->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'nom' => $doc->nom,
                        'nom_fichier' => $doc->nom_fichier,
                        'url' => $doc->url ?? asset('storage/' . $doc->chemin_fichier),
                        'taille_fichier' => $doc->taille_fichier,
                        'taille_humaine' => $this->formatBytes($doc->taille_fichier ?? 0),
                        'type_fichier' => $doc->type_fichier,
                        'extension' => $doc->extension,
                        'uploaded_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            
            // Métadonnées
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Permissions
            'permissions' => $this->when($request->user(), function() use ($request) {
                $user = $request->user();
                
                return [
                    'can_edit' => !$this->is_fully_validated && $this->user_id === $user->id,
                    'can_delete' => !$this->is_fully_validated && $this->user_id === $user->id,
                    'can_submit' => !$this->soumis_le && $this->user_id === $user->id,
                    'can_validate_n1' => $this->canBeValidatedByN1($user),
                    'can_validate_n2' => $this->canBeValidatedByN2($user),
                ];
            }),
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}