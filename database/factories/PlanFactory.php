<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nom = $this->faker->unique()->word();

        return [
            'slug' => Str::slug($nom).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'nom_fr' => ucfirst($nom),
            'nom_en' => ucfirst($nom),
            'description_fr' => $this->faker->sentence(),
            'description_en' => $this->faker->sentence(),
            'is_free' => false,
            'price' => $this->faker->randomElement([5000, 15000, 50000]),
            'currency' => 'XAF',
            'billing_period' => 'monthly',
            'max_members' => $this->faker->randomElement([5, 20, -1]),
            'max_storage_mb' => $this->faker->randomElement([100, 1000, -1]),
            'max_file_size_mb' => $this->faker->randomElement([2, 10, -1]),
            'features' => [],
            'is_active' => true,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }

    /** Plan gratuit de repli. */
    public function free(): static
    {
        return $this->state(fn (array $attributes): array => [
            'slug' => 'free',
            'nom_fr' => 'Gratuit',
            'nom_en' => 'Free',
            'is_free' => true,
            'price' => 0,
            'max_members' => 5,
            'max_storage_mb' => 100,
            'max_file_size_mb' => 2,
            'position' => 0,
        ]);
    }
}
