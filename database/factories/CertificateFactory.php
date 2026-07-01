<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titre' => $this->faker->sentence(3),
            'organisme_emetteur' => $this->faker->optional()->company(),
            'date_obtention' => $this->faker->optional()->date(),
            'date_expiration' => $this->faker->optional()->dateTimeBetween('now', '+5 years')?->format('Y-m-d'),
            'credential_id' => $this->faker->optional()->uuid(),
            'credential_url' => $this->faker->optional()->url(),
            'description' => $this->faker->optional()->sentence(),
            'ordre' => $this->faker->numberBetween(0, 10),
        ];
    }
}
