<?php

namespace Tests\Browser\Evaluation;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

class PendingValidationsTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * A workspace owner sees the pending validations dashboard with N1 row + urgent badge.
     */
    public function test_workspace_owner_sees_pending_validations_with_urgent_badge(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $n1 = User::factory()->create();
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $n1->id,
        ]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $intervenant = User::factory()->create();

        $collabRoleId = Role::where('name', 'collaborateur')->where('guard_name', 'web')->value('id');
        $tache->assignees()->attach($intervenant->id, ['role_id' => $collabRoleId, 'is_responsable' => false]);

        // Urgent — action_n0_le 25h ago, so 23h remaining on a 48h timer
        TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $intervenant->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHours(26),
            'action_n0' => 'approuve',
            'action_n0_le' => now()->subHours(25),
        ]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/validations/en-attente')
                ->waitForText('Validations en attente', 10)
                ->assertSee('N1 en attente')
                ->waitFor('[dusk="pending-n1-section"]', 5)
                ->assertVisible('@pending-n1-section')
                ->assertSee('Urgent');
        });
    }

    /**
     * G4: after an N1 validation the pending-N2 section appears without a page reload.
     * We seed a result already in en_validation_n2 (simulating post-N1-click state) and
     * assert the dashboard loads it correctly — the statut-transition itself is covered
     * by ValidationStatutTransitionTest; here we verify the dashboard query surfaces it.
     */
    public function test_pending_n2_section_appears_after_n1_validation(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $intervenant = User::factory()->create();

        $collabRoleId = Role::where('name', 'collaborateur')->where('guard_name', 'web')->value('id');
        $tache->assignees()->attach($intervenant->id, ['role_id' => $collabRoleId, 'is_responsable' => false]);

        $n1Validator = User::factory()->create();

        // Seed a result already transitioned to en_validation_n2 (post-N1 state)
        TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $intervenant->id,
            'statut' => 'en_validation_n2',
            'valide_par_n1' => true,
            'validateur_n1_id' => $n1Validator->id,
            'soumis_le' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/validations/en-attente')
                ->waitForText('Validations en attente', 10)
                ->waitFor('[dusk="pending-n2-section"]', 5)
                ->assertVisible('@pending-n2-section')
                ->assertSeeIn('@pending-n2-section', 'En attente N2');
        });
    }
}
