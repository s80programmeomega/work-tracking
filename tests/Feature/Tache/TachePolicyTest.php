<?php

declare(strict_types=1);

namespace Tests\Feature\Tache;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Phase 1 — vérifie la TachePolicy : view, update, delete pour chaque rôle.
 * La colonne `visibility` n'existe plus ; l'accès est entièrement basé sur les rôles.
 */
class TachePolicyTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    private Tache $tache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($this->projet->members(), $this->owner->id, 'manager');

        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($activite->membres(), $this->owner->id, 'cadre');

        $this->tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // TACHE TABLE HAS NO VISIBILITY COLUMN
    // =========================================================================

    /** @test */
    public function tache_table_has_no_visibility_column(): void
    {
        $columns = Schema::getColumnListing('taches');
        $this->assertNotContains('visibility', $columns);
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function owner_can_view_tache(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertOk();
    }

    /** @test */
    public function manager_can_view_tache(): void
    {
        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($manager, 'manager');

        $this->actingAs($manager)
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertOk();
    }

    /** @test */
    public function assignee_can_view_their_tache(): void
    {
        $collab = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($collab, 'collaborateur');
        $this->attachWithRole($this->projet->members(), $collab->id, 'collaborateur');
        $this->tache->assignees()->attach($collab->id, [
            'role_id' => $this->roleId('collaborateur'),
            'is_responsable' => false,
        ]);

        $this->actingAs($collab)
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertOk();
    }

    /** @test */
    public function user_outside_workspace_cannot_view_tache(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function responsable_can_update_tache(): void
    {
        $this->actingAs($this->owner)
            ->patchJson("/api/taches/{$this->tache->id}", [
                'titre' => 'Titre mis à jour',
            ])
            ->assertOk();
    }

    /** @test */
    public function collaborateur_cannot_update_tache_they_did_not_create(): void
    {
        $collab = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($collab, 'collaborateur');
        $this->attachWithRole($this->projet->members(), $collab->id, 'collaborateur');

        $this->actingAs($collab)
            ->patchJson("/api/taches/{$this->tache->id}", [
                'titre' => 'Tentative modification',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function responsable_can_delete_tache(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}")
            ->assertOk();
    }

    /** @test */
    public function collaborateur_cannot_delete_tache(): void
    {
        $collab = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($collab, 'collaborateur');
        $this->attachWithRole($this->projet->members(), $collab->id, 'collaborateur');

        $this->actingAs($collab)
            ->deleteJson("/api/taches/{$this->tache->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // TACHE RESOURCE HAS NO VISIBILITY FIELD
    // =========================================================================

    /** @test */
    public function tache_resource_has_no_visibility_field(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('visibility', $data);
    }
}
