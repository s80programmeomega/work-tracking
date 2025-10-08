<?php

namespace Database\Seeders;

use App\Models\Projet;
use App\Models\ProjetTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a user
        $user = User::first();

        if (!$user) {
            echo "Aucun utilisateur trouvé. Veuillez d'abord créer un utilisateur.\n";
            return;
        }

        // Create some tags
        $tags = [
            ['nom' => 'Backend', 'couleur' => '#3B82F6'],
            ['nom' => 'Frontend', 'couleur' => '#10B981'],
            ['nom' => 'Mobile', 'couleur' => '#F59E0B'],
            ['nom' => 'Infrastructure', 'couleur' => '#6366F1'],
        ];

        $createdTags = [];
        foreach ($tags as $tag) {
            $createdTags[] = ProjetTag::firstOrCreate(['nom' => $tag['nom']], $tag);
        }

        // Create projects
        $projets = [
            [
                'nom' => 'Système de gestion des tâches',
                'description' => 'Application web pour gérer les projets et tâches de l\'équipe',
                'date_debut' => now()->subMonths(2),
                'date_fin' => now()->addMonths(2),
                'responsable_id' => $user->id,
                'status' => 'active',
                'visibility' => 'team',
                'couleur' => '#3B82F6',
                'progression' => 45,
                'objectifs' => 'Améliorer la productivité de l\'équipe',
            ],
            [
                'nom' => 'Refonte du site web',
                'description' => 'Modernisation complète du site web corporate',
                'date_debut' => now()->subMonth(),
                'date_fin' => now()->addMonths(3),
                'responsable_id' => $user->id,
                'status' => 'active',
                'visibility' => 'public',
                'couleur' => '#10B981',
                'progression' => 30,
                'objectifs' => 'Améliorer l\'expérience utilisateur',
            ],
            [
                'nom' => 'Application mobile',
                'description' => 'Développement de l\'application mobile iOS et Android',
                'date_debut' => now(),
                'date_fin' => now()->addMonths(6),
                'responsable_id' => $user->id,
                'status' => 'active',
                'visibility' => 'private',
                'couleur' => '#F59E0B',
                'progression' => 10,
                'objectifs' => 'Étendre la présence mobile',
            ],
            [
                'nom' => 'Migration Cloud',
                'description' => 'Migration de l\'infrastructure vers le cloud',
                'date_debut' => now()->subMonths(3),
                'date_fin' => now()->subMonth(),
                'responsable_id' => $user->id,
                'status' => 'completed',
                'visibility' => 'team',
                'couleur' => '#6366F1',
                'progression' => 100,
                'objectifs' => 'Réduire les coûts d\'infrastructure',
            ],
            [
                'nom' => 'Plateforme e-learning',
                'description' => 'Développement d\'une plateforme de formation en ligne',
                'date_debut' => now()->subMonths(6),
                'date_fin' => now()->subMonths(1),
                'responsable_id' => $user->id,
                'status' => 'archived',
                'visibility' => 'team',
                'couleur' => '#8B5CF6',
                'progression' => 75,
                'archived_at' => now()->subWeek(),
                'objectifs' => 'Former les employés en continu',
            ],
        ];

        foreach ($projets as $projetData) {
            $projet = Projet::create($projetData);

            // Attach random tags
            $projet->tags()->attach($createdTags[array_rand($createdTags)]->id);

            // Add the creator as owner member
            $projet->members()->attach($user->id, [
                'role' => 'owner',
                'can_edit' => true,
                'can_delete' => true,
                'can_invite' => true,
            ]);
        }

        echo "✓ " . count($projets) . " projets créés avec succès!\n";
    }
}
