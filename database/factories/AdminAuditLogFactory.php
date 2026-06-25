<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminAuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'actor_type' => fake()->randomElement(['permanent_superadmin', 'temporary_superadmin', 'system_owner', 'directeur']),
            'action' => fake()->randomElement(['stats.read', 'workspaces.list', 'users.list', 'workspace.suspend', 'workspace.reactivate', 'user.role_updated']),
            'target_type' => null,
            'target_id' => null,
            'context' => null,
            'ip_address' => fake()->ipv4(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}
