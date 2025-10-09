<?php

namespace Database\Seeders;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use App\Models\Activite;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TacheSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all activities
        $activites = Activite::all();

        if ($activites->isEmpty()) {
            $this->command->warn('No activities found. Please run ActiviteSeeder first.');
            return;
        }

        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $taches = [
            // Tasks for first activity
            [
                'titre' => 'Analyse des besoins',
                'description' => 'Recueillir et analyser tous les besoins fonctionnels du projet',
                'objectif' => 'Avoir une vision claire des fonctionnalités attendues',
                'indicateurs_resultats' => 'Document de spécification validé par le client',
                'statut' => TacheStatut::TERMINE,
                'priorite' => TachePriorite::ELEVEE,
                'echeance' => now()->subDays(10),
                'taux_realisation' => 100,
                'couleur' => '#10B981',
            ],
            [
                'titre' => 'Conception de la base de données',
                'description' => 'Créer le schéma de la base de données avec toutes les relations',
                'statut' => TacheStatut::EN_COURS,
                'priorite' => TachePriorite::ELEVEE,
                'echeance' => now()->addDays(3),
                'taux_realisation' => 65,
                'couleur' => '#3B82F6',
            ],
            [
                'titre' => 'Développement API REST',
                'description' => 'Implémenter tous les endpoints de l\'API',
                'statut' => TacheStatut::EN_COURS,
                'priorite' => TachePriorite::CRITIQUE,
                'echeance' => now()->addDays(7),
                'taux_realisation' => 45,
                'couleur' => '#EF4444',
            ],
            [
                'titre' => 'Interface utilisateur - Dashboard',
                'description' => 'Créer le tableau de bord principal avec les widgets',
                'statut' => TacheStatut::A_FAIRE,
                'priorite' => TachePriorite::MOYENNE,
                'echeance' => now()->addDays(14),
                'taux_realisation' => 0,
                'couleur' => '#F59E0B',
            ],
            [
                'titre' => 'Tests unitaires',
                'description' => 'Écrire les tests unitaires pour toutes les fonctionnalités',
                'statut' => TacheStatut::A_FAIRE,
                'priorite' => TachePriorite::MOYENNE,
                'echeance' => now()->addDays(20),
                'taux_realisation' => 0,
                'couleur' => '#8B5CF6',
            ],
            [
                'titre' => 'Documentation technique',
                'description' => 'Rédiger la documentation complète du projet',
                'statut' => TacheStatut::A_FAIRE,
                'priorite' => TachePriorite::FAIBLE,
                'echeance' => now()->addDays(25),
                'taux_realisation' => 0,
                'couleur' => '#6B7280',
            ],
        ];

        $ordre = 0;
        foreach ($taches as $tacheData) {
            $activite = $activites->random();

            $tache = Tache::create([
                'activite_id' => $activite->id,
                'titre' => $tacheData['titre'],
                'description' => $tacheData['description'],
                'objectif' => $tacheData['objectif'] ?? null,
                'indicateurs_resultats' => $tacheData['indicateurs_resultats'] ?? null,
                'statut' => $tacheData['statut'],
                'priorite' => $tacheData['priorite'],
                'echeance' => $tacheData['echeance'],
                'taux_realisation' => $tacheData['taux_realisation'],
                'ordre' => $ordre++,
                'couleur' => $tacheData['couleur'],
            ]);

            // Assign random users to task (1-3 users)
            $assignees = $users->random(rand(1, min(3, $users->count())));
            $tache->assignees()->attach($assignees->pluck('id'));

            // If task is completed, mark as validated
            if ($tache->statut === TacheStatut::TERMINE) {
                $validator = $users->random();
                $tache->validate($validator);
            }
        }

        $this->command->info('Created ' . count($taches) . ' tasks');
    }
}
