<?php

namespace Database\Factories;

use App\Models\SchoolBackground;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolBackground>
 */
class SchoolBackgroundFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-20 years', '-4 years');
        $end = $this->faker->dateTimeBetween($start, '-1 year');

        return [
            'user_id' => User::factory(),
            'etablissement' => $this->faker->company(),
            'diplome' => $this->faker->randomElement(['Licence', 'Master', 'BTS', 'DUT', 'Doctorat', null]),
            'domaine' => $this->faker->randomElement(['Informatique', 'Gestion', 'Marketing', 'Droit', null]),
            'date_debut' => $start->format('Y-m-d'),
            'date_fin' => $end->format('Y-m-d'),
            'description' => $this->faker->optional()->sentence(),
            'ordre' => $this->faker->numberBetween(0, 10),
        ];
    }
}
