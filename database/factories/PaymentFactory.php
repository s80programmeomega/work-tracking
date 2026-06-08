<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::uuid(),
            'workspace_id' => Workspace::factory(),
            'plan_id' => Plan::factory(),
            'user_id' => User::factory(),
            'provider' => Payment::PROVIDER_MTN,
            'amount' => 15000,
            'currency' => 'XAF',
            'status' => Payment::STATUS_PENDING,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);
    }

    public function orange(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => Payment::PROVIDER_ORANGE,
        ]);
    }
}
