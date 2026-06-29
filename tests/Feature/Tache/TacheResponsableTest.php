<?php

declare(strict_types=1);

namespace Tests\Feature\Tache;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de gestion du responsable d'une tâche :
 * assignResponsable, removeResponsable.
 */
class TacheResponsableTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    private Tache $tache;

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
        ]);

        $this->activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->tache = Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeProjectMember(string $wsRole = 'collaborateur'): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $wsRole);
        $this->attachWithRole($this->projet->members(), $user->id, $wsRole);

        return $user;
    }

    // =========================================================================
    // ASSIGN RESPONSABLE
    // =========================================================================

    /** @test */
    public function authorized_user_can_assign_responsable_to_tache(): void
    {
        $projectMember = $this->makeProjectMember('cadre');

        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/assign-responsable", [
                'responsable_id' => $projectMember->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Responsable assigné avec succès');

        $this->assertDatabaseHas('taches', [
            'id' => $this->tache->id,
            'responsable_id' => $projectMember->id,
        ]);
    }

    /** @test */
    public function assigning_non_member_as_responsable_returns_422(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/assign-responsable", [
                'responsable_id' => $outsider->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'L\'utilisateur doit être membre de l\'activité ou du projet');
    }

    /** @test */
    public function unauthorized_user_cannot_assign_responsable(): void
    {
        $outsider = User::factory()->create();
        $projectMember = $this->makeProjectMember();

        $this->actingAs($outsider)
            ->postJson("/api/taches/{$this->tache->id}/assign-responsable", [
                'responsable_id' => $projectMember->id,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // REMOVE RESPONSABLE
    // =========================================================================

    /** @test */
    public function authorized_user_can_remove_responsable_from_tache(): void
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}/remove-responsable");

        $response->assertOk()
            ->assertJsonPath('message', 'Responsable retiré avec succès');

        $this->assertDatabaseHas('taches', [
            'id' => $this->tache->id,
            'responsable_id' => null,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_remove_responsable(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->deleteJson("/api/taches/{$this->tache->id}/remove-responsable")
            ->assertForbidden();
    }
}
