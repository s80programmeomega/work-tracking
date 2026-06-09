<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $nom = fake()->lastName();
        $prenom = fake()->firstName();

        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'nom_complet' => "{$prenom} {$nom}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Crée l'utilisateur AVEC un workspace dont il est propriétaire, et pose
     * current_workspace_id. Évite les utilisateurs « sans workspace » (qui se
     * retrouvent piégés sur /workspaces/create dans le SPA).
     *
     * NB : on n'attache pas de workspace par défaut (dépendance circulaire
     * Workspace→owner User, et de nombreux tests veulent un utilisateur nu) —
     * c'est un état explicite à utiliser quand un workspace courant est requis.
     */
    public function withWorkspace(): static
    {
        return $this->afterCreating(function (User $user): void {
            $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        });
    }
}
