<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que l'endpoint GET /api/workspaces retourne member_count et projets_count
 * pour alimenter les tuiles de statistiques du WorkspacePicker sans appel API supplémentaire.
 */
class WorkspaceIndexMemberCountTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
    }

    /** @test */
    public function index_includes_members_count_in_response(): void
    {
        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);

        $member = User::factory()->create();
        // Attacher le membre avec le rôle cadre
        $this->attachWithRole($workspace->members(), $member->id, 'cadre');

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/workspaces');

        $response->assertOk();

        $workspaceData = collect($response->json('data'))->firstWhere('id', $workspace->id);

        $this->assertNotNull($workspaceData, 'Le workspace doit apparaître dans la réponse index');
        $this->assertArrayHasKey('members_count', $workspaceData, 'members_count doit être présent');
        $this->assertSame(1, $workspaceData['members_count'], 'members_count doit refléter le nombre de membres attachés');
    }

    /** @test */
    public function index_includes_projets_count_in_response(): void
    {
        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);

        Projet::factory(3)->create(['workspace_id' => $workspace->id]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/workspaces');

        $response->assertOk();

        $workspaceData = collect($response->json('data'))->firstWhere('id', $workspace->id);

        $this->assertNotNull($workspaceData, 'Le workspace doit apparaître dans la réponse index');
        $this->assertArrayHasKey('projets_count', $workspaceData, 'projets_count doit être présent');
        $this->assertSame(3, $workspaceData['projets_count'], 'projets_count doit refléter le nombre de projets créés');
    }

    /** @test */
    public function index_returns_zero_counts_for_empty_workspace(): void
    {
        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/workspaces');

        $response->assertOk();

        $workspaceData = collect($response->json('data'))->firstWhere('id', $workspace->id);

        $this->assertNotNull($workspaceData);
        $this->assertSame(0, $workspaceData['members_count'] ?? -1, 'members_count doit être 0 sans membres');
        $this->assertSame(0, $workspaceData['projets_count'] ?? -1, 'projets_count doit être 0 sans projets');
    }

    /** @test */
    public function index_requires_authentication(): void
    {
        $this->getJson('/api/workspaces')
            ->assertUnauthorized();
    }
}
