<?php

declare(strict_types=1);

namespace Tests\Feature\Projet;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les opérations CRUD de ProjetController :
 * store, update, destroy, archive, unarchive, complete, clone, toggleFavorite.
 */
class ProjetCrudTest extends TestCase
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
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'status' => 'active',
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
            'workspace_id' => $this->workspace->id,
            'nom' => 'Nouveau projet test',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonths(3)->toDateString(),
            'responsable_id' => $this->owner->id,
        ], $overrides);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function workspace_owner_can_create_projet(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/projets', $this->storePayload());

        $response->assertCreated()
            ->assertJsonPath('data.nom', 'Nouveau projet test');

        $this->assertDatabaseHas('projets', ['nom' => 'Nouveau projet test']);
    }

    /** @test */
    public function collaborateur_cannot_create_projet(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->actingAs($collaborateur)
            ->postJson('/api/projets', $this->storePayload())
            ->assertForbidden();
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/projets', ['workspace_id' => $this->workspace->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nom', 'date_debut', 'date_fin', 'responsable_id']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function workspace_owner_can_update_projet(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/projets/{$this->projet->id}", ['nom' => 'Nom modifié'])
            ->assertOk()
            ->assertJsonPath('data.nom', 'Nom modifié');
    }

    /** @test */
    public function collaborateur_cannot_update_projet(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->actingAs($collaborateur)
            ->putJson("/api/projets/{$this->projet->id}", ['nom' => 'Tentative'])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function workspace_owner_can_delete_projet(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/projets/{$this->projet->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Projet supprimé avec succès.');

        $this->assertSoftDeleted('projets', ['id' => $this->projet->id]);
    }

    /** @test */
    public function collaborateur_cannot_delete_projet(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->actingAs($collaborateur)
            ->deleteJson("/api/projets/{$this->projet->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // ARCHIVE / UNARCHIVE
    // =========================================================================

    /** @test */
    public function owner_can_archive_and_unarchive_projet(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/archive")
            ->assertOk();

        $this->assertDatabaseHas('projets', [
            'id' => $this->projet->id,
            'status' => 'archived',
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/unarchive")
            ->assertOk();

        $this->assertDatabaseHas('projets', [
            'id' => $this->projet->id,
            'status' => 'active',
        ]);
    }

    // =========================================================================
    // COMPLETE
    // =========================================================================

    /** @test */
    public function owner_can_complete_projet(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/complete")
            ->assertOk();

        $this->assertDatabaseHas('projets', [
            'id' => $this->projet->id,
            'status' => 'completed',
        ]);
    }

    // =========================================================================
    // CLONE
    // =========================================================================

    /** @test */
    public function owner_can_clone_projet(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/clone", [
                'nom' => 'Copie du projet',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.nom', 'Copie du projet');

        // Le projet original est intact
        $this->assertDatabaseHas('projets', ['id' => $this->projet->id]);
        // Un nouveau projet a été créé
        $this->assertDatabaseHas('projets', ['nom' => 'Copie du projet']);
    }

    // =========================================================================
    // TOGGLE FAVORITE
    // =========================================================================

    /** @test */
    public function owner_can_toggle_favorite_status(): void
    {
        // Activer le favori
        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/toggle-favorite")
            ->assertOk()
            ->assertJsonPath('message', 'Projet ajouté aux favoris.');

        // Désactiver le favori
        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/toggle-favorite")
            ->assertOk()
            ->assertJsonPath('message', 'Projet retiré des favoris.');
    }
}
