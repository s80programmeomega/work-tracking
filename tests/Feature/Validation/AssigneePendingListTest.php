<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * G5: /api/evaluations/mes-resultats/en-attente is scoped to the
 * authenticated user's own submitted results (user_id = auth()->id()).
 */
class AssigneePendingListTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    private function makeResultat(User $author, string $statut): TacheResultat
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        return TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $author->id,
            'statut' => $statut,
            'soumis_le' => now(),
        ]);
    }

    /** @test */
    public function unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/evaluations/mes-resultats/en-attente')
            ->assertStatus(401);
    }

    /** @test */
    public function returns_only_own_results_awaiting_n1_validation(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();

        $own = $this->makeResultat($author, 'en_validation_n1');
        $this->makeResultat($other, 'en_validation_n1');  // should not appear

        $response = $this->actingAs($author)
            ->getJson('/api/evaluations/mes-resultats/en-attente');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.resultats')
            ->assertJsonPath('data.resultats.0.id', $own->id);
    }

    /** @test */
    public function returns_only_own_results_awaiting_n2_validation(): void
    {
        $author = User::factory()->create();
        $n1 = User::factory()->create();

        $own = $this->makeResultat($author, 'en_validation_n2');
        $own->update(['valide_par_n1' => true, 'validateur_n1_id' => $n1->id]);

        $this->makeResultat(User::factory()->create(), 'en_validation_n2');

        $response = $this->actingAs($author)
            ->getJson('/api/evaluations/mes-resultats/en-attente');

        $response->assertOk()
            ->assertJsonCount(1, 'data.resultats')
            ->assertJsonPath('data.resultats.0.id', $own->id);
    }

    /** @test */
    public function returns_both_n1_and_n2_pending_results_for_same_user(): void
    {
        $author = User::factory()->create();

        $this->makeResultat($author, 'en_validation_n1');
        $this->makeResultat($author, 'en_validation_n2');

        $response = $this->actingAs($author)
            ->getJson('/api/evaluations/mes-resultats/en-attente');

        $response->assertOk()
            ->assertJsonCount(2, 'data.resultats')
            ->assertJsonPath('data.counts.en_validation_n1', 1)
            ->assertJsonPath('data.counts.en_validation_n2', 1)
            ->assertJsonPath('data.counts.total', 2);
    }

    /** @test */
    public function does_not_return_already_validated_or_rejected_results(): void
    {
        $author = User::factory()->create();

        $this->makeResultat($author, 'valide');
        $this->makeResultat($author, 'rejete');

        $response = $this->actingAs($author)
            ->getJson('/api/evaluations/mes-resultats/en-attente');

        $response->assertOk()
            ->assertJsonCount(0, 'data.resultats')
            ->assertJsonPath('data.counts.total', 0);
    }

    /** @test */
    public function returns_empty_list_when_user_has_no_submitted_results(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/evaluations/mes-resultats/en-attente');

        $response->assertOk()
            ->assertJsonCount(0, 'data.resultats')
            ->assertJsonPath('data.counts.total', 0);
    }
}
