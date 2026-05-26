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
 * Tests Dusk pour la création et la modification de tâches via ActiviteDetail.
 */
class TaskCRUDTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** @return array{owner: User, activite: Activite} */
    private function makeOwnerWithActivite(): array
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
        $activite->membres()->attach($owner->id, [
            'role_id' => $cadreRoleId,
            'can_create_tasks' => true,
            'can_edit_tasks' => true,
        ]);

        return ['owner' => $owner, 'activite' => $activite];
    }

    /**
     * Un responsable peut créer une tâche via le formulaire de l'activité.
     * Le titre de la nouvelle tâche doit apparaître dans la liste de l'activité.
     */
    public function test_owner_can_create_task_and_it_appears_in_list(): void
    {
        $ctx = $this->makeOwnerWithActivite();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitFor('[dusk="create-task-btn"]', 10)
                ->click('@create-task-btn')
                ->waitFor('[dusk="tache-form-titre"]', 10)
                ->type('@tache-form-titre', 'Tâche de test Dusk')
                ->click('[dusk="tache-form-tab-equipe"]')
                ->waitUntilEnabled('[dusk="tache-form-responsable"]', 15)
                ->select('@tache-form-responsable', $ctx['owner']->id)
                ->waitUntilEnabled('[dusk="tache-form-submit"]', 10)
                ->click('@tache-form-submit')
                ->waitForText('Tâche de test Dusk', 10)
                ->assertSee('Tâche de test Dusk');
        });
    }

    /**
     * Le formulaire de création de tâche peut être annulé sans créer de tâche.
     */
    public function test_cancel_task_form_does_not_create_task(): void
    {
        $ctx = $this->makeOwnerWithActivite();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitFor('[dusk="create-task-btn"]', 10)
                ->click('@create-task-btn')
                ->waitFor('[dusk="tache-form-titre"]', 10)
                ->type('@tache-form-titre', 'Tâche annulée')
                ->waitFor('[dusk="tache-form-cancel"]', 5)
                ->click('@tache-form-cancel')
                ->pause(1000)
                ->assertDontSee('Tâche annulée');
        });
    }

    /**
     * Une tâche existante apparaît dans la liste de l'activité après chargement.
     */
    public function test_existing_task_is_visible_in_activity_list(): void
    {
        $ctx = $this->makeOwnerWithActivite();

        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $tache = Tache::factory()->create([
            'activite_id' => $ctx['activite']->id,
            'titre' => 'Tâche existante visible',
        ]);
        $tache->assignees()->attach($ctx['owner']->id, [
            'role_id' => $cadreRoleId,
            'is_responsable' => true,
            'can_edit' => true,
        ]);

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitForText('Tâche existante visible', 25)
                ->assertSee('Tâche existante visible');
        });
    }
}
