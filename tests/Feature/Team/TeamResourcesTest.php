<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamResourceController : index, store, update, destroy.
 */
class TeamResourcesTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create(['is_super_admin' => true]);
        $this->team = Team::factory()->create(['owner_id' => $this->owner->id]);
    }

    private function makeResource(): TeamResource
    {
        return TeamResource::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'name' => 'Guide d\'onboarding',
            'title' => 'Guide d\'onboarding',
            'type' => 'document',
            'url' => 'https://example.com/guide',
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function authenticated_user_can_list_team_resources(): void
    {
        $this->makeResource();

        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/resources")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['resources']);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_create_resource(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/resources", [
                'title' => 'Procédures internes',
                'type' => 'link',
                'url' => 'https://example.com/procedures',
                'description' => 'Lien vers les procédures',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_resources', [
            'team_id' => $this->team->id,
            'title' => 'Procédures internes',
        ]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/resources", [])
            ->assertUnprocessable();
    }

    /** @test */
    public function store_validates_resource_type(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/resources", [
                'title' => 'Test',
                'type' => 'invalid_type',
                'url' => 'https://example.com',
            ])
            ->assertUnprocessable();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_update_resource(): void
    {
        $resource = $this->makeResource();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}/resources/{$resource->id}", [
                'title' => 'Guide mis à jour',
                'description' => 'Nouvelle description',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_resources', [
            'id' => $resource->id,
            'title' => 'Guide mis à jour',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function authenticated_user_can_delete_resource(): void
    {
        $resource = $this->makeResource();

        $this->actingAs($this->owner)
            ->deleteJson("/api/teams/{$this->team->uuid}/resources/{$resource->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('team_resources', ['id' => $resource->id]);
    }
}
