<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'owner_id' => User::factory(),
            'visibility' => 'private',
            'is_active' => true,
        ];
    }

    public function public(): static
    {
        return $this->state(['visibility' => 'public']);
    }

    public function archived(): static
    {
        return $this->state([
            'is_active' => false,
            'archived_at' => now(),
        ]);
    }
}
