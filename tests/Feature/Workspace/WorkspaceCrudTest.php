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
 * Couvre les endpoints CRUD + utilitaires du workspace :
 * update, destroy, switch, projets, statistics, subscriptionSummary, updateSubscription.
 */
class WorkspaceCrudTest extends TestCase
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
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function owner_can_update_workspace_nom(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/workspaces/{$this->workspace->id}", [
                'nom' => 'Nouveau Nom Workspace',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('workspaces', [
            'id' => $this->workspace->id,
            'nom' => 'Nouveau Nom Workspace',
        ]);
    }

    /** @test */
    public function non_owner_cannot_update_workspace(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->putJson("/api/workspaces/{$this->workspace->id}", [
                'nom' => 'Tentative',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function update_validates_nom_too_short(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/workspaces/{$this->workspace->id}", [
                'nom' => 'AB',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nom']);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function owner_can_destroy_empty_workspace(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Workspace supprimé avec succès');

        $this->assertSoftDeleted('workspaces', ['id' => $this->workspace->id]);
    }

    /** @test */
    public function non_owner_cannot_destroy_workspace(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->deleteJson("/api/workspaces/{$this->workspace->id}")
            ->assertForbidden();
    }

    /** @test */
    public function destroy_fails_if_workspace_has_projets(): void
    {
        Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Impossible de supprimer un workspace contenant des projets. Veuillez d\'abord les supprimer ou les déplacer.');
    }

    // =========================================================================
    // SWITCH
    // =========================================================================

    /** @test */
    public function member_can_switch_workspace(): void
    {
        $this->workspace->addMember($this->owner, 'owner');

        $response = $this->actingAs($this->owner)
            ->postJson("/api/workspaces/switch/{$this->workspace->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Workspace sélectionné avec succès')
            ->assertJsonPath('current_workspace_id', $this->workspace->id);

        $this->assertDatabaseHas('users', [
            'id' => $this->owner->id,
            'current_workspace_id' => $this->workspace->id,
        ]);
    }

    /** @test */
    public function non_member_cannot_switch_workspace(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson("/api/workspaces/switch/{$this->workspace->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // PROJETS
    // =========================================================================

    /** @test */
    public function owner_can_list_workspace_projets(): void
    {
        Projet::factory()->count(2)->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/projets");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /** @test */
    public function owner_can_view_workspace_statistics(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/statistics");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // SUBSCRIPTION
    // =========================================================================

    /** @test */
    public function owner_can_view_subscription_summary(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/subscription");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_super_admin_cannot_update_subscription(): void
    {
        $this->actingAs($this->owner)
            ->patchJson("/api/workspaces/{$this->workspace->id}/subscription", [
                'subscription_mode' => 'paid',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function super_admin_can_update_subscription(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($superAdmin)
            ->patchJson("/api/workspaces/{$this->workspace->id}/subscription", [
                'subscription_mode' => 'paid',
            ])
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->assertDatabaseHas('workspaces', [
            'id' => $this->workspace->id,
            'subscription_mode' => 'paid',
        ]);
    }
}
