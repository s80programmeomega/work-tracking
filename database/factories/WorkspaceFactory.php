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
            'nom' => fake()->company(),
            'description' => fake()->sentence(),
            'owner_id' => User::factory(),
            'is_active' => true,
            'settings' => [
                'default_project_visibility' => 'team',
                'members_can_create_projects' => true,
                'members_can_invite' => false,
                'require_task_validation' => true,
            ],
        ];
    }

    public function paid(): static
    {
        return $this->state(['subscription_mode' => 'paid']);
    }

    public function trialExpired(): static
    {
        return $this->state([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(60),
            'trial_duration_days' => 30,
        ]);
    }

    public function trialExpiringSoon(int $daysLeft = 3): static
    {
        return $this->state([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(30 - $daysLeft),
            'trial_duration_days' => 30,
        ]);
    }
}
