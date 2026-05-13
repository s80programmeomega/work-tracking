<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TacheResultatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tache_id'           => Tache::factory(),
            'user_id'            => User::factory(),
            'is_individual'      => true,
            'resultats_attendus' => fake()->sentence(),
            'resultats_obtenus'  => fake()->sentence(),
            'taux_realisation'   => fake()->numberBetween(0, 100),
            'soumis_le'          => now(),
        ];
    }

    public function valideN1(): static
    {
        return $this->state([
            'valide_par_n1'    => true,
            'valide_le_n1'     => now(),
        ]);
    }

    public function valideN2(): static
    {
        return $this->state([
            'valide_par_n1'    => true,
            'valide_le_n1'     => now(),
            'valide_par_n2'    => true,
            'valide_le_n2'     => now(),
        ]);
    }
}
