<?php

declare(strict_types=1);

namespace Tests\Browser\Tasks;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour le CRUD des sous-tâches depuis la page de détail d'une tâche.
 */
class SubtaskCRUDTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** @return array{owner: User, tache: Tache} */
    private function makeOwnerWithTask(): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $owner->id,
        ]);

        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $activite->membres()->attach($owner->id, ['role_id' => $cadreRoleId]);

        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $tache->assignees()->attach($owner->id, [
            'role_id' => $cadreRoleId,
            'is_responsable' => true,
            'can_edit' => true,
            'can_complete' => true,
        ]);

        return ['owner' => $owner, 'tache' => $tache];
    }

    /**
     * Un responsable peut créer une sous-tâche depuis l'onglet Sous-tâches.
     * La sous-tâche apparaît dans la liste avec son titre.
     */
    public function test_responsable_can_create_subtask_and_it_appears_in_list(): void
    {
        $ctx = $this->makeOwnerWithTask();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-sous-taches"]', 15)
                ->click('[dusk="tab-sous-taches"]')
                ->waitFor('[dusk="soustache-list"]', 15)
                ->waitFor('[dusk="add-soustache-btn"]', 15)
                ->click('@add-soustache-btn')
                ->waitFor('[dusk="soustache-form-titre"]', 5)
                ->type('@soustache-form-titre', 'Sous-tâche Dusk')
                ->click('@soustache-form-submit')
                ->waitForText('Sous-tâche Dusk', 10)
                ->assertSee('Sous-tâche Dusk');
        });
    }

    /**
     * Le compteur de sous-tâches dans l'onglet est mis à jour après création.
     */
    public function test_subtask_tab_counter_increments_after_creation(): void
    {
        $ctx = $this->makeOwnerWithTask();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-sous-taches"]', 15)
                ->click('[dusk="tab-sous-taches"]')
                ->waitFor('[dusk="add-soustache-btn"]', 15)
                ->click('@add-soustache-btn')
                ->waitFor('[dusk="soustache-form-titre"]', 5)
                ->type('@soustache-form-titre', 'Compteur test')
                ->click('@soustache-form-submit')
                ->waitForTextIn('[dusk="soustache-count"]', '1', 10);
        });
    }
}
