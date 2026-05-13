<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activite;
use App\Models\User;
use App\Enums\TacheStatut;
use App\Enums\TachePriorite;
use Illuminate\Database\Eloquent\Factories\Factory;

class TacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'titre'                  => fake()->sentence(4),
            'description'            => fake()->paragraph(),
            'statut'                 => TacheStatut::A_FAIRE->value,
            'priorite'               => TachePriorite::MOYENNE->value,
            'echeance'               => now()->addDays(14),
            'taux_realisation'       => 0,
            'validation_n1_required' => true,
            'validation_n2_required' => true,
        ];
    }

    public function enCours(): static
    {
        return $this->state(['statut' => TacheStatut::EN_COURS->value, 'taux_realisation' => 50]);
    }

    public function termine(): static
    {
        return $this->state(['statut' => TacheStatut::TERMINE->value, 'taux_realisation' => 100]);
    }
}
