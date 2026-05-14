<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id'   => Workspace::factory(),
            'nom'            => fake()->sentence(3),
            'description'    => fake()->paragraph(),
            'responsable_id' => User::factory(),
            'date_debut'     => now(),
            'date_fin'       => now()->addMonths(3),
            'visibility'     => 'team',
            'status'         => 'active',
        ];
    }
}
