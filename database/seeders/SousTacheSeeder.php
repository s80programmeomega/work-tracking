<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SousTache;
use App\Models\Tache;
use Illuminate\Database\Seeder;

class SousTacheSeeder extends Seeder
{
    public function run(): void
    {
        $taches = Tache::query()->inRandomOrder()->limit(5)->get();

        foreach ($taches as $tache) {
            // 3 sous-tâches weighted 40/35/25
            SousTache::factory()->create(['tache_id' => $tache->id, 'titre' => 'Préparation', 'poids' => 40, 'ordre' => 1]);
            SousTache::factory()->create(['tache_id' => $tache->id, 'titre' => 'Exécution', 'poids' => 35, 'ordre' => 2]);
            SousTache::factory()->create(['tache_id' => $tache->id, 'titre' => 'Livraison', 'poids' => 25, 'ordre' => 3]);
        }
    }
}
