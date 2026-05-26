<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TacheResultatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tache_id' => Tache::factory(),
            'user_id' => User::factory(),
            'is_individual' => true,
            'statut' => 'brouillon',
            'resultats_attendus' => fake()->sentence(),
            'resultats_obtenus' => fake()->paragraph(),
            'taux_realisation' => fake()->numberBetween(0, 100),
            'soumis_le' => null,
            // N0 circuit
            'soumis_n0_le' => null,
            'action_n0' => null,
            'commentaire_n0' => null,
            'n0_actor_id' => null,
            'action_n0_le' => null,
            // Bypass circuit
            'bypass_active' => false,
            'motif_bypass' => null,
            'bypass_le' => null,
            'bypass_count' => 0,
        ];
    }

    /** Result submitted and awaiting N0 review. */
    public function enVerificationN0(): static
    {
        return $this->state([
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
        ]);
    }

    /** N0 returned the result to the author — ready for bypass. */
    public function renvoyeParN0(?int $n0ActorId = null): static
    {
        return $this->state([
            'statut' => 'a_refaire',
            'soumis_le' => now()->subHours(2),
            'soumis_n0_le' => now()->subHours(2),
            'action_n0' => 'renvoye',
            'commentaire_n0' => fake()->sentence(12),
            'n0_actor_id' => $n0ActorId,
            'action_n0_le' => now()->subHour(),
        ]);
    }

    /** Bypass activated — result escalated directly to N1. */
    public function bypassActive(?string $motif = null): static
    {
        return $this->state([
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHours(3),
            'soumis_n0_le' => now()->subHours(3),
            'action_n0' => 'renvoye',
            'commentaire_n0' => fake()->sentence(12),
            'action_n0_le' => now()->subHours(2),
            'bypass_active' => true,
            'motif_bypass' => $motif ?? fake()->sentence(15),
            'bypass_le' => now()->subHour(),
        ]);
    }

    public function valideN1(): static
    {
        return $this->state([
            'statut' => 'en_validation_n2',
            'valide_par_n1' => true,
            'valide_le_n1' => now(),
        ]);
    }

    public function valideN2(): static
    {
        return $this->state([
            'statut' => 'valide',
            'valide_par_n1' => true,
            'valide_le_n1' => now()->subHours(2),
            'validateur_n1_id' => User::factory(),
            'valide_par_n2' => true,
            'valide_le_n2' => now(),
            'validateur_n2_id' => User::factory(),
        ]);
    }

    /** Result rejected at N1 — sent back to author for rework. */
    public function rejectedN1(?int $validateurId = null): static
    {
        return $this->state([
            'statut' => 'rejete',
            'soumis_le' => now()->subHours(4),
            'soumis_n0_le' => now()->subHours(4),
            'action_n0' => 'approuve',
            'action_n0_le' => now()->subHours(3),
            'validateur_n1_id' => $validateurId,
            'commentaire_n1' => fake()->sentence(12),
            'rejete_par' => $validateurId,
            'rejete_le' => now()->subHour(),
            'niveau_rejet' => 'n1',
        ]);
    }

    /** Result rejected at N2 — N1 had already validated. */
    public function rejectedN2(?int $validateurN1Id = null, ?int $validateurN2Id = null): static
    {
        return $this->state([
            'statut' => 'rejete',
            'soumis_le' => now()->subHours(6),
            'soumis_n0_le' => now()->subHours(6),
            'action_n0' => 'approuve',
            'action_n0_le' => now()->subHours(5),
            'valide_par_n1' => true,
            'valide_le_n1' => now()->subHours(3),
            'validateur_n1_id' => $validateurN1Id,
            'commentaire_n1' => fake()->sentence(10),
            'validateur_n2_id' => $validateurN2Id,
            'commentaire_n2' => fake()->sentence(12),
            'rejete_par' => $validateurN2Id,
            'rejete_le' => now()->subHour(),
            'niveau_rejet' => 'n2',
        ]);
    }
}
