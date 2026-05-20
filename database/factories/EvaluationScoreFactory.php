<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationScoreFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [
            'user_id' => User::factory(),
            'periode_start' => $start->toDateString(),
            'periode_end' => $end->toDateString(),
            'critere' => fake()->randomElement([
                'n1_validated_despite_return',
                'n1_confirmed_return',
            ]),
            'valeur' => fake()->randomElement([1.0, -1.0]),
            'meta' => [
                'tache_resultat_id' => fake()->numberBetween(1, 100),
            ],
        ];
    }

    /** Penalty: N1 validated despite N0 return. */
    public function penalty(): static
    {
        return $this->state([
            'critere' => 'n1_validated_despite_return',
            'valeur' => -1.0,
        ]);
    }

    /** Bonus: N1 confirmed N0 return. */
    public function bonus(): static
    {
        return $this->state([
            'critere' => 'n1_confirmed_return',
            'valeur' => 1.0,
        ]);
    }

    /** Score for a specific user. */
    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
