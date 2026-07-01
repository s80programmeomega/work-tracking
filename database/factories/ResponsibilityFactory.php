<?php

namespace Database\Factories;

use App\Models\Responsibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Responsibility>
 */
class ResponsibilityFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-10 years', '-1 year');

        return [
            'user_id' => User::factory(),
            'titre' => $this->faker->jobTitle(),
            'organisation' => $this->faker->optional()->company(),
            'description' => $this->faker->optional()->paragraph(),
            'date_debut' => $start->format('Y-m-d'),
            'date_fin' => $this->faker->optional()->dateTimeBetween($start, 'now')?->format('Y-m-d'),
        ];
    }
}
