<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiviteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'projet_id'      => Projet::factory(),
            'nom'            => fake()->sentence(3),
            'description'    => fake()->paragraph(),
            'responsable_id' => User::factory(),
            'date_debut'     => now(),
            'date_fin'       => now()->addMonths(2),
        ];
    }
}
