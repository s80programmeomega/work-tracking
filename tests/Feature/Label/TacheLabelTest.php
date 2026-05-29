<?php

declare(strict_types=1);

namespace Tests\Feature\Label;

use App\Models\Activite;
use App\Models\Label;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TacheLabelController : index, sync, attach, detach, detachAll.
 */
class TacheLabelTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Tache $tache;

    private Label $label;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $workspace->addMember($this->owner, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $this->owner->id,
        ]);

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->label = Label::create([
            'nom' => 'Urgence',
            'couleur' => '#E74C3C',
            'is_global' => true,
            'created_by' => $this->owner->id,
            'ordre' => 0,
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function user_can_list_labels_for_tache(): void
    {
        $this->tache->labels()->attach($this->label->id);

        $this->actingAs($this->owner)
            ->getJson("/api/taches/{$this->tache->id}/labels")
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // SYNC
    // =========================================================================

    /** @test */
    public function user_can_sync_labels_on_tache(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/labels/sync", [
                'label_ids' => [$this->label->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Labels mis à jour avec succès.');

        $this->assertDatabaseHas('label_tache', [
            'tache_id' => $this->tache->id,
            'label_id' => $this->label->id,
        ]);
    }

    /** @test */
    public function sync_validates_label_ids(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/labels/sync", [])
            ->assertUnprocessable();
    }

    // =========================================================================
    // ATTACH
    // =========================================================================

    /** @test */
    public function user_can_attach_label_to_tache(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/labels/attach", [
                'label_id' => $this->label->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Label ajouté avec succès.');

        $this->assertDatabaseHas('label_tache', [
            'tache_id' => $this->tache->id,
            'label_id' => $this->label->id,
        ]);
    }

    /** @test */
    public function attaching_duplicate_label_returns_400(): void
    {
        $this->tache->labels()->attach($this->label->id);

        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/labels/attach", [
                'label_id' => $this->label->id,
            ])
            ->assertStatus(400);
    }

    // =========================================================================
    // DETACH
    // =========================================================================

    /** @test */
    public function user_can_detach_label_from_tache(): void
    {
        $this->tache->labels()->attach($this->label->id);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/labels/detach", [
                'label_id' => $this->label->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Label supprimé avec succès.');

        $this->assertDatabaseMissing('label_tache', [
            'tache_id' => $this->tache->id,
            'label_id' => $this->label->id,
        ]);
    }
}
