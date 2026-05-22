<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 9 step 3 — smoke tests pour GET /api/evaluations/personnel/{user}/score:
 *   - utilisateur authentifié peut consulter sa propre fiche (200)
 *   - consultation d'une fiche d'autrui par un rôle sans scope → 403
 *
 * On reste sur 2 tests ici (smoke); la couverture détaillée des
 * permissions + immutabilité post-N2 vient à l'étape 9.
 */
class EvaluationAgentSheetEndpointTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /** @test */
    public function authenticated_user_can_view_own_sheet(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);
        $this->attachWithRole($workspace->members(), $user->id, 'collaborateur');

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/evaluations/personnel/{$user->id}/score");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'periode_start',
                    'periode_end',
                    'score_global',
                    'criteria',
                    'indicators',
                    'user' => ['id', 'nom', 'email'],
                    'meta' => ['can_export', 'is_self'],
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.meta.is_self', true);
    }

    /** @test */
    public function collaborateur_cannot_view_another_users_sheet(): void
    {
        $workspace = Workspace::factory()->create();
        $actor = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $target = User::factory()->create(['current_workspace_id' => $workspace->id]);

        // Acteur collaborateur — n'a pas le scope pour consulter une fiche
        // autre que la sienne.
        $this->attachWithRole($workspace->members(), $actor->id, 'collaborateur');
        $this->attachWithRole($workspace->members(), $target->id, 'collaborateur');

        Sanctum::actingAs($actor);

        $this->getJson("/api/evaluations/personnel/{$target->id}/score")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
