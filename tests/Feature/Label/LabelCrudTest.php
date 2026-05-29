<?php

declare(strict_types=1);

namespace Tests\Feature\Label;

use App\Models\Label;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre LabelController : index, store, show, update, destroy, reorder, duplicate, stats.
 */
class LabelCrudTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeLabel(bool $global = true): Label
    {
        return Label::create([
            'nom' => 'Étiquette test',
            'couleur' => '#FF5733',
            'is_global' => $global,
            'projet_id' => $global ? null : $this->projet->id,
            'created_by' => $this->owner->id,
            'ordre' => 0,
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function user_can_list_global_labels(): void
    {
        $this->makeLabel();

        $this->actingAs($this->owner)
            ->getJson('/api/labels?scope=global')
            ->assertOk();
    }

    /** @test */
    public function user_can_list_project_labels(): void
    {
        $this->makeLabel(false);

        $this->actingAs($this->owner)
            ->getJson("/api/labels?scope=project&projet_id={$this->projet->id}")
            ->assertOk();
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function user_can_create_global_label(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/labels', [
                'nom' => 'Priorité haute',
                'couleur' => '#E74C3C',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Label créé avec succès.');

        $this->assertDatabaseHas('labels', [
            'nom' => 'Priorité haute',
            'couleur' => '#E74C3C',
            'is_global' => true,
        ]);
    }

    /** @test */
    public function user_can_create_project_label(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/labels', [
                'nom' => 'Révision client',
                'couleur' => '#3498DB',
                'projet_id' => $this->projet->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Label créé avec succès.');

        $this->assertDatabaseHas('labels', [
            'nom' => 'Révision client',
            'projet_id' => $this->projet->id,
            'is_global' => false,
        ]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/labels', ['nom' => 'Sans couleur'])
            ->assertUnprocessable();
    }

    /** @test */
    public function store_validates_hex_color_format(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/labels', [
                'nom' => 'Mauvaise couleur',
                'couleur' => 'rouge',
            ])
            ->assertUnprocessable();
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function user_can_view_label(): void
    {
        $label = $this->makeLabel();

        $this->actingAs($this->owner)
            ->getJson("/api/labels/{$label->id}")
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function user_can_update_label(): void
    {
        $label = $this->makeLabel();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/labels/{$label->id}", [
                'nom' => 'Nom mis à jour',
                'couleur' => '#2ECC71',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Label mis à jour avec succès.');

        $this->assertDatabaseHas('labels', [
            'id' => $label->id,
            'nom' => 'Nom mis à jour',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function user_can_delete_label(): void
    {
        $label = $this->makeLabel();

        $this->actingAs($this->owner)
            ->deleteJson("/api/labels/{$label->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Label supprimé avec succès.');

        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    }

    // =========================================================================
    // REORDER
    // =========================================================================

    /** @test */
    public function user_can_reorder_labels(): void
    {
        $labelA = $this->makeLabel();
        $labelB = $this->makeLabel();

        $response = $this->actingAs($this->owner)
            ->postJson('/api/labels/reorder', [
                'labels' => [
                    ['id' => $labelA->id, 'ordre' => 1],
                    ['id' => $labelB->id, 'ordre' => 0],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Labels réordonnés avec succès.');
    }

    // =========================================================================
    // DUPLICATE
    // =========================================================================

    /** @test */
    public function user_can_duplicate_label(): void
    {
        $label = $this->makeLabel();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/labels/{$label->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('message', 'Label dupliqué avec succès.');

        $this->assertDatabaseCount('labels', 2);
    }

    // =========================================================================
    // STATS
    // =========================================================================

    /** @test */
    public function user_can_get_label_stats(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/labels/stats')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
