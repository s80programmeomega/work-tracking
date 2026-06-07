<?php

namespace Database\Factories;

use App\Models\HelpCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpCategory>
 */
class HelpCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nomFr = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($nomFr).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'nom_fr' => ucfirst($nomFr),
            'nom_en' => ucfirst($this->faker->words(2, true)),
            'description_fr' => $this->faker->sentence(),
            'description_en' => $this->faker->sentence(),
            'icon' => 'fa-book',
            'position' => $this->faker->numberBetween(0, 20),
            'published_at' => now(),
        ];
    }

    /** Catégorie non publiée (brouillon). */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
        ]);
    }
}
