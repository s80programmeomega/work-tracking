<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamMemberController : store, updateRole, updatePermissions, destroy, transferOwnership.
 */
class TeamMembersTest extends TestCase
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
    // ADD MEMBER
    // =========================================================================

    /** @test */
    public function owner_can_add_member_to_team(): void
    {
        $newMember = User::factory()->create();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/members", [
                'user_id' => $newMember->id,
                'role' => 'member',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Membre ajouté avec succès');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $newMember->id,
        ]);
    }

    /** @test */
    public function adding_already_member_returns_400(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/members", [
                'user_id' => $member->id,
                'role' => 'member',
            ])
            ->assertStatus(400);
    }

    /** @test */
    public function non_owner_cannot_add_member(): void
    {
        $other = User::factory()->create(['is_super_admin' => false]);
        $newMember = User::factory()->create();

        $this->actingAs($other)
            ->postJson("/api/teams/{$this->team->uuid}/members", [
                'user_id' => $newMember->id,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE ROLE
    // =========================================================================

    /** @test */
    public function owner_can_update_member_role(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}/members/{$member->id}/role", [
                'role' => 'admin',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function non_owner_cannot_update_member_role(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);

        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->putJson("/api/teams/{$this->team->uuid}/members/{$member->id}/role", [
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE PERMISSIONS
    // =========================================================================

    /** @test */
    public function owner_can_update_member_permissions(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}/members/{$member->id}/permissions", [
                'permissions' => ['can_post' => true, 'can_invite' => false],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    // =========================================================================
    // REMOVE MEMBER
    // =========================================================================

    /** @test */
    public function owner_can_remove_member_from_team(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);

        $this->actingAs($this->owner)
            ->deleteJson("/api/teams/{$this->team->uuid}/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $this->team->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function non_owner_cannot_remove_member(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member->id, ['role' => 'member']);
        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->deleteJson("/api/teams/{$this->team->uuid}/members/{$member->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // TRANSFER OWNERSHIP
    // =========================================================================

    /** @test */
    public function owner_can_transfer_ownership(): void
    {
        $newOwner = User::factory()->create();
        $this->team->members()->attach($newOwner->id, ['role' => 'admin']);

        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/transfer-ownership", [
                'new_owner_id' => $newOwner->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'owner_id' => $newOwner->id,
        ]);
    }

    /** @test */
    public function non_owner_cannot_transfer_ownership(): void
    {
        $newOwner = User::factory()->create();
        $other = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($other)
            ->postJson("/api/teams/{$this->team->uuid}/transfer-ownership", [
                'new_owner_id' => $newOwner->id,
            ])
            ->assertForbidden();
    }
}
