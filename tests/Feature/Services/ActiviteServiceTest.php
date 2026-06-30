<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActiviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre ActiviteService : duplicateActivite, canManageMembers, canEditActivite,
 * getActiviteStats, reorderActivites.
 */
class ActiviteServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private ActiviteService $activiteService;

    private User $owner;

    private Projet $projet;

    private Activite $activite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->activiteService = app(ActiviteService::class);

        $this->owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // DUPLICATE ACTIVITE
    // =========================================================================

    /** @test */
    public function duplicate_activite_creates_a_copy(): void
    {
        $copy = $this->activiteService->duplicateActivite($this->activite);

        $this->assertInstanceOf(Activite::class, $copy);
        $this->assertNotEquals($this->activite->id, $copy->id);
        $this->assertEquals($this->activite->projet_id, $copy->projet_id);
        $this->assertDatabaseCount('activites', 2);
    }

    /** @test */
    public function duplicate_activite_respects_overrides(): void
    {
        $copy = $this->activiteService->duplicateActivite($this->activite, [
            'nom' => 'Activité dupliquée',
        ]);

        $this->assertEquals('Activité dupliquée', $copy->nom);
    }

    // =========================================================================
    // CAN MANAGE MEMBERS
    // =========================================================================

    /** @test */
    public function projet_responsable_can_manage_activite_members(): void
    {
        $result = $this->activiteService->canManageMembers($this->activite, $this->owner);

        $this->assertTrue($result);
    }

    /** @test */
    public function outsider_cannot_manage_activite_members(): void
    {
        $outsider = User::factory()->create();

        $result = $this->activiteService->canManageMembers($this->activite, $outsider);

        $this->assertFalse($result);
    }

    // =========================================================================
    // CAN EDIT ACTIVITE
    // =========================================================================

    /** @test */
    public function projet_responsable_can_edit_activite(): void
    {
        $result = $this->activiteService->canEditActivite($this->activite, $this->owner);

        $this->assertTrue($result);
    }

    /** @test */
    public function outsider_cannot_edit_activite(): void
    {
        $outsider = User::factory()->create();

        $result = $this->activiteService->canEditActivite($this->activite, $outsider);

        $this->assertFalse($result);
    }

    // =========================================================================
    // GET ACTIVITE STATS
    // =========================================================================

    /** @test */
    public function get_activite_stats_returns_stats_array(): void
    {
        $stats = $this->activiteService->getActiviteStats($this->activite);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('tache_count', $stats);
    }

    // =========================================================================
    // REORDER ACTIVITES
    // =========================================================================

    /** @test */
    public function reorder_activites_updates_positions(): void
    {
        $second = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->activiteService->reorderActivites([
            $second->id,
            $this->activite->id,
        ]);

        $this->assertDatabaseHas('activites', ['id' => $second->id, 'ordre' => 0]);
        $this->assertDatabaseHas('activites', ['id' => $this->activite->id, 'ordre' => 1]);
    }
}
