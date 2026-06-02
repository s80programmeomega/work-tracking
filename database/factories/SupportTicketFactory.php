<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => null,
            'category' => $this->faker->randomElement(['bug', 'feature', 'billing', 'account', 'other']),
            'subject' => $this->faker->sentence(6),
            'message' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement(['open', 'in_progress', 'resolved']),
            'reproducibility' => null,
            'steps_to_reproduce' => null,
            'sla_deadline' => now()->addHours(24),
            'first_responded_at' => null,
            'resolved_at' => null,
        ];
    }
}
