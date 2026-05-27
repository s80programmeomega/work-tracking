<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            WorkspaceSeeder::class,
            SousTacheSeeder::class,
            // Préférences notification + souscriptions Web Push de démo
            // (Tasks 8 / 8b). Doit s'exécuter après WorkspaceSeeder qui
            // crée les comptes de démo.
            NotificationDemoSeeder::class,
            // Étiquettes globales de démonstration (Task 0).
            // Doit s'exécuter après RolePermissionSeeder.
            LabelSeeder::class,
        ]);
    }
}
