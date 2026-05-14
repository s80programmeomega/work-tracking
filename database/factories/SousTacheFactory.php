<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SousTache;
use App\Models\Tache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SousTache>
 */
class SousTacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tache_id' => Tache::factory(),
            'titre' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'statut' => 'a_faire',
            'progression' => 0,
            'poids' => 0,
            'ordre' => fake()->numberBetween(0, 10),
        ];
    }

    public function termine(): static
    {
        return $this->state(['statut' => 'termine', 'progression' => 100]);
    }

    public function enRetard(): static
    {
        return $this->state([
            'statut' => 'en_retard',
            'date_echeance' => now()->subDays(3),
        ]);
    }

    public function withPoids(int $poids): static
    {
        return $this->state(['poids' => $poids]);
    }
}
