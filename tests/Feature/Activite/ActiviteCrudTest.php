<?php

declare(strict_types=1);

namespace Tests\Feature\Activite;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les opérations CRUD de ActiviteController :
 * store, show, update, destroy, archive, unarchive, duplicate.
 */
class ActiviteCrudTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);
        $this->activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'projet_id' => $this->projet->id,
            'nom' => 'Nouvelle activité test',
            'responsable_id' => $this->owner->id,
        ], $overrides);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function project_responsable_can_create_activite(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/activites', $this->storePayload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Activité créée avec succès');

        $this->assertDatabaseHas('activites', ['nom' => 'Nouvelle activité test']);
    }

    /** @test */
    public function collaborateur_without_edit_permission_cannot_create_activite(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->actingAs($collaborateur)
            ->postJson('/api/activites', $this->storePayload(['responsable_id' => $collaborateur->id]))
            ->assertForbidden();
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/activites', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['projet_id', 'nom', 'responsable_id']);
    }

    /** @test */
    public function store_rejects_responsable_not_in_project(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson('/api/activites', $this->storePayload(['responsable_id' => $outsider->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsable_id']);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function project_member_can_view_activite(): void
    {
        $member = $this->makeWsMember('collaborateur');

        $response = $this->actingAs($member)
            ->getJson("/api/activites/{$this->activite->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->activite->id);
    }

    /** @test */
    public function outsider_cannot_view_activite(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/activites/{$this->activite->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function responsable_can_update_activite(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/activites/{$this->activite->id}", ['nom' => 'Nom modifié'])
            ->assertOk()
            ->assertJsonPath('message', 'Activité mise à jour avec succès');

        $this->assertDatabaseHas('activites', [
            'id' => $this->activite->id,
            'nom' => 'Nom modifié',
        ]);
    }

    /** @test */
    public function collaborateur_without_edit_permission_cannot_update_activite(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->actingAs($collaborateur)
            ->putJson("/api/activites/{$this->activite->id}", ['nom' => 'Tentative'])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function responsable_can_delete_activite_without_taches(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/activites/{$this->activite->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Activité supprimée avec succès');

        $this->assertSoftDeleted('activites', ['id' => $this->activite->id]);
    }

    /** @test */
    public function cannot_delete_activite_with_taches(): void
    {
        Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/activites/{$this->activite->id}")
            ->assertUnprocessable();
    }

    // =========================================================================
    // ARCHIVE / UNARCHIVE
    // =========================================================================

    /** @test */
    public function responsable_can_archive_and_unarchive_activite(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/activites/{$this->activite->id}/archive")
            ->assertOk()
            ->assertJsonPath('message', 'Activité archivée avec succès.');

        $this->assertDatabaseHas('activites', [
            'id' => $this->activite->id,
            'status' => 'archived',
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/activites/{$this->activite->id}/unarchive")
            ->assertOk()
            ->assertJsonPath('message', 'Activité désarchivée avec succès.');

        $this->assertDatabaseHas('activites', [
            'id' => $this->activite->id,
            'status' => 'active',
        ]);
    }

    // =========================================================================
    // DUPLICATE
    // =========================================================================

    /** @test */
    public function responsable_can_duplicate_activite(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/activites/{$this->activite->id}/duplicate", [
                'nom' => 'Copie activité',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activites', ['nom' => 'Copie activité']);
    }
}
