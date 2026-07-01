<?php

namespace Database\Factories;

use App\Models\Qualification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Qualification>
 */
class QualificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titre' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'date_obtention' => $this->faker->optional()->date(),
            'ordre' => $this->faker->numberBetween(0, 10),
        ];
    }
}
