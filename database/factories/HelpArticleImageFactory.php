<?php

namespace Database\Factories;

use App\Models\HelpArticle;
use App\Models\HelpArticleImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HelpArticleImage>
 */
class HelpArticleImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => HelpArticle::factory(),
            'path' => 'help/images/'.Str::random(40).'.png',
            'mime' => 'image/png',
            'size_bytes' => $this->faker->numberBetween(1024, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
