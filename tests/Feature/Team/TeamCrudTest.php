<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamController : index, myTeams, store, show, update, archive, restore, destroy, stats, activities, presence.
 */
class TeamCrudTest extends TestCase
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

    // =========================================================================
    // INDEX / MY TEAMS
    // =========================================================================

    /** @test */
    public function authenticated_user_can_list_teams(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/teams')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['teams']);
    }

    /** @test */
    public function authenticated_user_can_get_their_teams(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/teams/my-teams')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['teams']);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function super_admin_can_create_team(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/teams', [
                'name' => 'Nouvelle équipe',
                'description' => 'Description de l\'équipe',
                'visibility' => 'public',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Équipe créée avec succès');

        $this->assertDatabaseHas('teams', ['name' => 'Nouvelle équipe']);
    }

    /** @test */
    public function regular_user_cannot_create_team(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)
            ->postJson('/api/teams', ['name' => 'Équipe test'])
            ->assertForbidden();
    }

    /** @test */
    public function store_validates_required_name(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/teams', ['description' => 'Pas de nom'])
            ->assertUnprocessable();
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_team(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['team']);
    }

    /** @test */
    public function show_returns_404_for_nonexistent_uuid(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/teams/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function owner_can_update_team(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}", [
                'name' => 'Nom modifié',
                'visibility' => 'public',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teams', ['id' => $this->team->id, 'name' => 'Nom modifié']);
    }

    /** @test */
    public function non_owner_cannot_update_team(): void
    {
        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->putJson("/api/teams/{$this->team->uuid}", ['name' => 'Hack'])
            ->assertForbidden();
    }

    // =========================================================================
    // ARCHIVE / RESTORE
    // =========================================================================

    /** @test */
    public function owner_can_archive_team(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/archive")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teams', ['id' => $this->team->id, 'is_active' => false]);
    }

    /** @test */
    public function non_owner_cannot_archive_team(): void
    {
        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->postJson("/api/teams/{$this->team->uuid}/archive")
            ->assertForbidden();
    }

    /** @test */
    public function owner_can_restore_archived_team(): void
    {
        $this->team->update(['is_active' => false, 'archived_at' => now()]);

        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teams', ['id' => $this->team->id, 'is_active' => true]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function owner_can_delete_team(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/teams/{$this->team->uuid}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('teams', ['id' => $this->team->id]);
    }

    /** @test */
    public function non_owner_cannot_delete_team(): void
    {
        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->deleteJson("/api/teams/{$this->team->uuid}")
            ->assertForbidden();
    }

    // =========================================================================
    // STATS / ACTIVITIES / PRESENCE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_get_team_stats(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/stats")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['stats']);
    }

    /** @test */
    public function authenticated_user_can_get_team_activities(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/activities")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['activities']);
    }

    /** @test */
    public function authenticated_user_can_update_presence(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/presence", ['status' => 'online'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function update_presence_validates_status(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/presence", ['status' => 'invalid'])
            ->assertUnprocessable();
    }
}
