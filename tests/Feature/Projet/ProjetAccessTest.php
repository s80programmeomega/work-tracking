<?php

declare(strict_types=1);

namespace Tests\Feature\Projet;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Phase 1 — vérifie que l'accès aux projets repose sur les rôles/permissions,
 * sans aucune colonne `visibility`.
 */
class ProjetAccessTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

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

        $this->attachWithRole(
            $this->projet->members(),
            $this->owner->id,
            'manager',
        );
    }

    // =========================================================================
    // PROJET N'A PLUS DE COLONNE VISIBILITY
    // =========================================================================

    /** @test */
    public function projet_table_has_no_visibility_column(): void
    {
        $columns = Schema::getColumnListing('projets');
        $this->assertNotContains('visibility', $columns);
    }

    // =========================================================================
    // OWNER VOIT SES PROJETS VIA L'ENDPOINT ACCESSIBLE
    // =========================================================================

    /** @test */
    public function owner_can_list_accessible_projets(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/projets/projets-accessibles')
            ->assertOk()
            ->assertJsonIsArray();
    }

    /** @test */
    public function owner_can_view_projet_detail(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->projet->id);
    }

    // =========================================================================
    // MANAGER VOIT TOUS LES PROJETS (PROJETS_VIEW_ALL via rôle workspace)
    // =========================================================================

    /** @test */
    public function manager_can_view_any_projet(): void
    {
        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($manager, 'manager');

        $this->actingAs($manager)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertOk();
    }

    // =========================================================================
    // CADRE / COLLABORATEUR VOIENT LES PROJETS VIA LEUR RÔLE WORKSPACE
    // (l'accès granulaire est contrôlé par l'assignation à une activité/tâche)
    // =========================================================================

    /** @test */
    public function cadre_can_view_projet_via_workspace_role(): void
    {
        $cadre = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($cadre, 'cadre');

        // Le rôle cadre a projets.view → peut voir les projets du workspace
        $this->actingAs($cadre)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertOk();
    }

    /** @test */
    public function cadre_added_as_project_member_can_view_projet(): void
    {
        $cadre = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($cadre, 'cadre');
        $this->attachWithRole($this->projet->members(), $cadre->id, 'collaborateur');

        $this->actingAs($cadre)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertOk();
    }

    /** @test */
    public function outsider_not_in_workspace_cannot_view_projet(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // PROJET RESOURCE NE CONTIENT PAS DE CHAMP VISIBILITY
    // =========================================================================

    /** @test */
    public function projet_resource_has_no_visibility_field(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}")
            ->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('visibility', $data);
    }

    // =========================================================================
    // CRÉATION SANS VISIBILITY
    // =========================================================================

    /** @test */
    public function creating_projet_without_visibility_succeeds(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/projets', [
                'nom' => 'Projet sans visibilité',
                'workspace_id' => $this->workspace->id,
                'responsable_id' => $this->owner->id,
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(30)->toDateString(),
            ])
            ->assertCreated();
    }

    /** @test */
    public function creating_projet_with_visibility_field_returns_created(): void
    {
        // Le champ visibility doit être ignoré (pas de validation, pas d'erreur, pas de colonne)
        $this->actingAs($this->owner)
            ->postJson('/api/projets', [
                'nom' => 'Projet avec champ ignoré',
                'workspace_id' => $this->workspace->id,
                'responsable_id' => $this->owner->id,
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(30)->toDateString(),
            ])
            ->assertCreated();
    }
}
