<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activite;
use App\Models\SousTache;
use App\Models\User;
use Illuminate\Database\Seeder;

class SousTacheSeeder extends Seeder
{
    public function run(): void
    {
        $collaborateur = User::where('email', 'collaborateur@worktracking.com')->first();
        $stagiaire = User::where('email', 'stagiaire@worktracking.com')->first();

        // Seed sous_taches on the first task of every activity,
        // so every kanban view shows the ST badge regardless of which activity is open.
        $activites = Activite::with('taches')->get();

        foreach ($activites as $activite) {
            $firstTask = $activite->taches->first();

            if (! $firstTask) {
                continue;
            }

            // Skip tasks that already have sous_taches (seeded inline in WorkspaceSeeder)
            if ($firstTask->sousTaches()->exists()) {
                continue;
            }

            SousTache::factory()->create([
                'tache_id' => $firstTask->id,
                'responsable_id' => $collaborateur?->id ?? $firstTask->responsable_id,
                'titre' => 'Préparation et analyse',
                'poids' => 40,
                'statut' => 'termine',
                'progression' => 100,
                'ordre' => 1,
                'date_echeance' => now()->addDays(5),
            ]);

            SousTache::factory()->create([
                'tache_id' => $firstTask->id,
                'responsable_id' => $collaborateur?->id ?? $firstTask->responsable_id,
                'titre' => 'Exécution et développement',
                'poids' => 35,
                'statut' => 'en_cours',
                'progression' => 60,
                'ordre' => 2,
                'date_echeance' => now()->addDays(10),
            ]);

            SousTache::factory()->create([
                'tache_id' => $firstTask->id,
                'responsable_id' => $stagiaire?->id ?? $firstTask->responsable_id,
                'titre' => 'Livraison et validation',
                'poids' => 25,
                'statut' => 'a_faire',
                'progression' => 0,
                'ordre' => 3,
                'date_echeance' => now()->addDays(15),
            ]);
        }
    }
}
