<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'           => fake()->word() . '.pdf',
            'description'   => fake()->sentence(),
            'uploaded_by'   => User::factory(),
            'visibility'    => 'team',
            'file_path'     => 'documents/' . fake()->uuid() . '.pdf',
            'file_size'     => fake()->numberBetween(1000, 5000000),
            'mime_type'     => 'application/pdf',
        ];
    }

    public function public(): static
    {
        return $this->state(['visibility' => 'public']);
    }
}
