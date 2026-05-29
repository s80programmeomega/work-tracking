<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de gestion des membres du workspace :
 * members, addMember, showMember, updateMember, removeMember,
 * removeMemberWithTransfer, getRemovalPreview, getUserProjects,
 * getTransferCandidates.
 */
class WorkspaceMembersTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
    }

    private function makeCollaborateur(): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, 'collaborateur');

        return $user;
    }

    // =========================================================================
    // LIST MEMBERS
    // =========================================================================

    /** @test */
    public function owner_can_list_members(): void
    {
        $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_member_cannot_list_members(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/workspaces/{$this->workspace->id}/members")
            ->assertForbidden();
    }

    // =========================================================================
    // ADD MEMBER
    // =========================================================================

    /** @test */
    public function owner_can_add_member(): void
    {
        $newUser = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson("/api/workspaces/{$this->workspace->id}/members", [
                'user_id' => $newUser->id,
                'role' => 'collaborateur',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Membre ajouté avec succès');

        $this->assertTrue($this->workspace->isMember($newUser));
    }

    /** @test */
    public function adding_existing_member_returns_422(): void
    {
        $member = $this->makeCollaborateur();

        $this->actingAs($this->owner)
            ->postJson("/api/workspaces/{$this->workspace->id}/members", [
                'user_id' => $member->id,
                'role' => 'collaborateur',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cet utilisateur est déjà membre du workspace');
    }

    /** @test */
    public function non_owner_cannot_add_member(): void
    {
        $collaborateur = $this->makeCollaborateur();
        $newUser = User::factory()->create();

        $this->actingAs($collaborateur)
            ->postJson("/api/workspaces/{$this->workspace->id}/members", [
                'user_id' => $newUser->id,
                'role' => 'collaborateur',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // SHOW MEMBER
    // =========================================================================

    /** @test */
    public function owner_can_show_member_detail(): void
    {
        $member = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user', 'workspace_membership', 'statistics']]);
    }

    /** @test */
    public function show_member_returns_404_for_non_member(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/{$outsider->id}")
            ->assertNotFound();
    }

    // =========================================================================
    // UPDATE MEMBER
    // =========================================================================

    /** @test */
    public function owner_can_update_member_role(): void
    {
        $member = $this->makeCollaborateur();

        $this->actingAs($this->owner)
            ->putJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}", [
                'role' => 'cadre',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Membre mis à jour avec succès');
    }

    // =========================================================================
    // REMOVE MEMBER (simple)
    // =========================================================================

    /** @test */
    public function owner_can_remove_member_without_responsibilities(): void
    {
        $member = $this->makeCollaborateur();

        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Membre retiré avec succès');

        $this->assertFalse($this->workspace->fresh()->isMember($member));
    }

    /** @test */
    public function cannot_remove_workspace_owner(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/{$this->owner->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Impossible de retirer le propriétaire du workspace');
    }

    // =========================================================================
    // REMOVE MEMBER WITH TRANSFER
    // =========================================================================

    /** @test */
    public function owner_can_remove_member_with_transfer(): void
    {
        $member = $this->makeCollaborateur();

        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}/remove")
            ->assertOk()
            ->assertJsonPath('message', 'Membre retiré avec succès du workspace');

        $this->assertFalse($this->workspace->fresh()->isMember($member));
    }

    /** @test */
    public function remove_with_transfer_cannot_remove_owner(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/{$this->owner->id}/remove")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Impossible de retirer le propriétaire du workspace. Transférez d\'abord la propriété.');
    }

    // =========================================================================
    // REMOVAL PREVIEW
    // =========================================================================

    /** @test */
    public function owner_can_get_removal_preview(): void
    {
        $member = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}/removal-preview");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // GET USER PROJECTS
    // =========================================================================

    /** @test */
    public function owner_can_get_user_projects(): void
    {
        $member = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/{$member->id}/projects");

        $response->assertOk()
            ->assertJsonStructure(['data', 'count']);
    }

    // =========================================================================
    // GET TRANSFER CANDIDATES
    // =========================================================================

    /** @test */
    public function owner_can_get_transfer_candidates(): void
    {
        $member = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/transfer-candidates?exclude_user_id={$member->id}");

        $response->assertOk()
            ->assertJsonStructure(['data', 'default_candidate']);
    }
}
