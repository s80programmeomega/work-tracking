<?php

namespace Database\Factories;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticle>
 */
class HelpArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titreFr = $this->faker->unique()->sentence(4);
        $bodyFr = '<p>'.$this->faker->paragraph().'</p>';
        $bodyEn = '<p>'.$this->faker->paragraph().'</p>';

        return [
            'category_id' => HelpCategory::factory(),
            'slug' => Str::slug($titreFr).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'titre_fr' => $titreFr,
            'titre_en' => $this->faker->sentence(4),
            'body_fr' => $bodyFr,
            'body_en' => $bodyEn,
            'body_plain_fr' => trim(strip_tags($bodyFr)),
            'body_plain_en' => trim(strip_tags($bodyEn)),
            'cover_image' => null,
            'views_count' => 0,
            'published_at' => now(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    /** Article non publié (brouillon). */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
        ]);
    }
}
