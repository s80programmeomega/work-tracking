<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom'       => fake()->company(),
            'description' => fake()->sentence(),
            'owner_id'  => User::factory(),
            'is_active' => true,
            'settings'  => [
                'default_project_visibility' => 'team',
                'members_can_create_projects' => true,
                'members_can_invite' => false,
                'require_task_validation' => true,
            ],
        ];
    }
}
