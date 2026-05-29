<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->word() . '.pdf';

        return [
            // Relation polymorphique — surcharger dans les tests si besoin
            'documentable_type' => Projet::class,
            'documentable_id'   => Projet::factory(),

            // Informations fichier
            'nom'          => $nom,
            'nom_stockage' => Str::uuid() . '.pdf',
            'extension'    => 'pdf',
            'mime_type'    => 'application/pdf',
            'taille'       => fake()->numberBetween(1000, 5_000_000),
            'chemin'       => 'documents/' . Str::uuid() . '.pdf',
            'disk'         => 'local',

            // Métadonnées
            'description' => fake()->sentence(),

            // Versionnage
            'version'          => 1,
            'is_latest_version' => true,

            // Propriétaire
            'user_id' => User::factory(),

            // Visibilité
            'visibility' => 'team',
        ];
    }

    public function public(): static
    {
        return $this->state(['visibility' => 'public']);
    }

    public function private(): static
    {
        return $this->state(['visibility' => 'private']);
    }

    public function forProjet(Projet $projet): static
    {
        return $this->state([
            'documentable_type' => Projet::class,
            'documentable_id'   => $projet->id,
            'workspace_id'      => $projet->workspace_id,
        ]);
    }
}
