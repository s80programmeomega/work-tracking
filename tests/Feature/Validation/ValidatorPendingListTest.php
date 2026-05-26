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
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * G5: /api/evaluations/resultats/en-attente (validator queue) returns only
 * results where the authenticated user is the N1 (activite.responsable_id)
 * or N2 (projet.responsable_id) validator. Verifies scope isolation.
 */
class ValidatorPendingListTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    private function makeContext(User $n1Validator, User $n2Validator, User $author): TacheResultat
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $n2Validator->id,
        ]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $n1Validator->id,
        ]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $collabRoleId = Role::where('name', 'collaborateur')->where('guard_name', 'web')->value('id');
        $tache->assignees()->attach($author->id, ['role_id' => $collabRoleId]);

        $managerRoleId = Role::where('name', 'manager')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($n1Validator->id, ['role_id' => $managerRoleId]);

        return TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $author->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now(),
        ]);
    }

    /** @test */
    public function n1_validator_sees_only_their_activity_results(): void
    {
        $n1a = User::factory()->create();
        $n1b = User::factory()->create();
        $n2 = User::factory()->create();
        $author = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $n2->id]);
        $n2->update(['current_workspace_id' => $workspace->id]);
        $n1a->update(['current_workspace_id' => $workspace->id]);

        $this->makeContext($n1a, $n2, $author);
        $this->makeContext($n1b, $n2, $author);  // n1a should NOT see this one

        $response = $this->actingAs($n1a)
            ->getJson('/api/evaluations/resultats/en-attente');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data['pending_n1'], 'N1 validator should only see their own activity results.');
    }

    /** @test */
    public function scope_leak_n1_validator_a_does_not_see_n1b_results(): void
    {
        $n1a = User::factory()->create();
        $n1b = User::factory()->create();
        $n2 = User::factory()->create();
        $author = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $n2->id]);
        $n1a->update(['current_workspace_id' => $workspace->id]);
        $n1b->update(['current_workspace_id' => $workspace->id]);

        $resultatForN1b = $this->makeContext($n1b, $n2, $author);

        $response = $this->actingAs($n1a)
            ->getJson('/api/evaluations/resultats/en-attente');

        $response->assertOk();

        $pendingIds = collect($response->json('data.pending_n1'))->pluck('id');
        $this->assertNotContains($resultatForN1b->id, $pendingIds, 'N1a must not see results scoped to N1b.');
    }

    /** @test */
    public function n2_validator_sees_n1_validated_results_in_their_projects(): void
    {
        $n1 = User::factory()->create();
        $n2 = User::factory()->create();
        $author = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $n2->id]);
        $n2->update(['current_workspace_id' => $workspace->id]);

        $resultat = $this->makeContext($n1, $n2, $author);
        $resultat->update([
            'statut' => 'en_validation_n2',
            'valide_par_n1' => true,
            'validateur_n1_id' => $n1->id,
        ]);

        $response = $this->actingAs($n2)
            ->getJson('/api/evaluations/resultats/en-attente');

        $response->assertOk();

        $pendingIds = collect($response->json('data.pending_n2'))->pluck('id');
        $this->assertContains($resultat->id, $pendingIds, 'N2 validator should see results validated by N1 in their project.');
    }
}
