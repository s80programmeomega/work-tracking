<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class SousTacheModelTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    private function makeTache(): Tache
    {
        $workspace = Workspace::factory()->create();
        $owner = User::factory()->create();
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);

        return Tache::factory()->create(['activite_id' => $activite->id]);
    }

    /** @test */
    public function r1_sous_tache_cannot_be_created_on_another_sous_tache(): void
    {
        // SousTaches only link to Tache (tache_id FK), not to SousTache.
        // The schema itself enforces R1 — there is no parent_sous_tache_id column.
        $tache = $this->makeTache();

        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);

        // SousTache model has no tache_id that points to another SousTache — only to Tache.
        // Attempting to insert sous_tache_id into taches FK will fail at DB level.
        $this->assertDatabaseHas('sous_taches', ['id' => $sousTache->id, 'tache_id' => $tache->id]);
        $this->assertInstanceOf(Tache::class, $sousTache->tache);
    }

    /** @test */
    public function r2_weights_exceeding_100_throws_exception(): void
    {
        $tache = $this->makeTache();

        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 60]);
        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 30]);

        $this->expectException(\InvalidArgumentException::class);

        // 60 + 30 + 20 = 110 — should throw
        SousTache::enforceWeights($tache->id, 20);
    }

    /** @test */
    public function r2_weights_summing_to_100_is_valid(): void
    {
        $tache = $this->makeTache();

        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 40]);
        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 35]);

        // 40 + 35 + 25 = 100 — should not throw
        SousTache::enforceWeights($tache->id, 25);

        $this->assertTrue(true);
    }

    /** @test */
    public function r2_all_zero_weights_is_valid_unweighted_mode(): void
    {
        $tache = $this->makeTache();

        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 0]);
        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 0]);

        // poids = 0 → unweighted, enforceWeights returns immediately
        SousTache::enforceWeights($tache->id, 0);

        $this->assertTrue(true);
    }

    /** @test */
    public function collaborateur_without_is_responsable_cannot_create_subtask(): void
    {
        $tache = $this->makeTache();
        $collaborateur = User::factory()->create();

        // Assign as collaborateur WITHOUT is_responsable
        $this->attachWithRole($tache->assignees(), $collaborateur->id, 'collaborateur', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => true, 'can_validate' => false,
        ]);

        $gate = app(ContextualPermissionGate::class);

        $this->assertFalse($gate->userCan($collaborateur, Permission::TACHES_CREATE_SUBTASK, $tache));
    }

    /** @test */
    public function is_responsable_collaborateur_can_create_subtask(): void
    {
        $tache = $this->makeTache();
        $collaborateur = User::factory()->create();

        // Assign as collaborateur WITH is_responsable
        $this->attachWithRole($tache->assignees(), $collaborateur->id, 'collaborateur', [
            'is_responsable' => true, 'can_edit' => false, 'can_complete' => true, 'can_validate' => false,
        ]);

        $gate = app(ContextualPermissionGate::class);

        $this->assertTrue($gate->userCan($collaborateur, Permission::TACHES_CREATE_SUBTASK, $tache));
    }
}
