<?php

namespace Database\Seeders;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActiviteSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $projet = Projet::first();

        if (!$projet) {
            echo "Aucun projet trouvé. Veuillez d'abord créer un projet.\n";
            return;
        }

        $activites = [
            [
                'projet_id' => $projet->id,
                'nom' => 'Phase de conception',
                'description' => 'Conception et architecture de la solution',
                'responsable_id' => $user->id,
                'date_debut' => now()->subMonths(2),
                'date_fin' => now()->subMonth(),
                'ordre' => 0,
                'status' => 'active',
                'progression' => 80,
                'couleur' => '#3B82F6',
            ],
            [
                'projet_id' => $projet->id,
                'nom' => 'Développement frontend',
                'description' => 'Développement de l\'interface utilisateur',
                'responsable_id' => $user->id,
                'date_debut' => now()->subMonth(),
                'date_fin' => now()->addMonth(),
                'ordre' => 1,
                'status' => 'active',
                'progression' => 45,
                'couleur' => '#10B981',
            ],
            [
                'projet_id' => $projet->id,
                'nom' => 'Développement backend',
                'description' => 'Développement de l\'API et de la logique métier',
                'responsable_id' => $user->id,
                'date_debut' => now()->subMonth(),
                'date_fin' => now()->addMonth(),
                'ordre' => 2,
                'status' => 'active',
                'progression' => 60,
                'couleur' => '#F59E0B',
            ],
            [
                'projet_id' => $projet->id,
                'nom' => 'Tests et QA',
                'description' => 'Tests unitaires, d\'intégration et assurance qualité',
                'responsable_id' => $user->id,
                'date_debut' => now(),
                'date_fin' => now()->addMonths(2),
                'ordre' => 3,
                'status' => 'active',
                'progression' => 15,
                'couleur' => '#8B5CF6',
            ],
            [
                'projet_id' => $projet->id,
                'nom' => 'Déploiement',
                'description' => 'Mise en production et déploiement',
                'responsable_id' => $user->id,
                'date_debut' => now()->addMonth(),
                'date_fin' => now()->addMonths(2),
                'ordre' => 4,
                'status' => 'active',
                'progression' => 0,
                'couleur' => '#EF4444',
            ],
        ];

        foreach ($activites as $activiteData) {
            Activite::create($activiteData);
        }

        echo "✓ " . count($activites) . " activités créées avec succès!\n";
    }
}
