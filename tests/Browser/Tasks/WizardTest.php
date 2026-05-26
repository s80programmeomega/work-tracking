<?php

declare(strict_types=1);

namespace Tests\Browser\Tasks;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 11 — couverture Dusk du wizard de création de tâche (TacheCreateWizard.vue).
 *
 * Ce qui est testé:
 *   - Le wizard s'ouvre sur la page ActiviteDetail quand le bouton "Nouvelle tâche" est cliqué.
 *   - Les 4 étapes sont présentes dans l'indicateur de progression.
 *   - La navigation Suivant est bloquée si le titre est vide (validation step 1).
 *   - Après avoir rempli le step 1 et cliqué Suivant, le step 2 (assignation) est affiché.
 */
class WizardTest extends WorkTrackingTestCase
{
    use AttachesWithRoleId, DatabaseTruncation;

    public function test_wizard_opens_and_shows_step_indicator(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->attachWithRole($projet->members(), $owner->id, 'owner');

        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $this->attachWithRole($activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true,
            'can_create_tasks' => true,
            'can_delete_tasks' => true,
        ]);

        $this->browse(function (Browser $browser) use ($owner, $activite) {
            $this->signInAs($browser, $owner)
                ->visit("/activites/{$activite->id}")
                ->waitFor('[dusk="wizard-title"], [dusk="task-create-wizard"], button', 15)
                // Click the "Nouvelle tâche" button — any button containing that text
                ->clickLink('Nouvelle tâche')
                ->waitFor('[dusk="wizard-title"]', 10)
                ->assertVisible('[dusk="wizard-title"]')
                ->assertVisible('[dusk="wizard-steps"]')
                // All 4 step buttons present
                ->assertVisible('[dusk="wizard-step-informations"]')
                ->assertVisible('[dusk="wizard-step-assignation"]')
                ->assertVisible('[dusk="wizard-step-ressources"]')
                ->assertVisible('[dusk="wizard-step-validation"]');
        });
    }

    public function test_wizard_blocks_next_when_titre_is_empty(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->attachWithRole($projet->members(), $owner->id, 'owner');

        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $this->attachWithRole($activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true,
            'can_create_tasks' => true,
            'can_delete_tasks' => true,
        ]);

        $this->browse(function (Browser $browser) use ($owner, $activite) {
            $this->signInAs($browser, $owner)
                ->visit("/activites/{$activite->id}")
                ->clickLink('Nouvelle tâche')
                ->waitFor('[dusk="wizard-title"]', 10)
                // Click Next without filling titre
                ->click('[dusk="wizard-next"]')
                // Error banner must appear
                ->waitFor('[dusk="wizard-error"]', 5)
                ->assertVisible('[dusk="wizard-error"]')
                // Step 2 must NOT be shown yet
                ->assertMissing('[dusk="wizard-step-assignation"][class*="ring"]');
        });
    }

    public function test_wizard_advances_to_step2_after_valid_step1(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->attachWithRole($projet->members(), $owner->id, 'owner');

        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $this->attachWithRole($activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true,
            'can_create_tasks' => true,
            'can_delete_tasks' => true,
        ]);

        $this->browse(function (Browser $browser) use ($owner, $activite) {
            $this->signInAs($browser, $owner)
                ->visit("/activites/{$activite->id}")
                ->clickLink('Nouvelle tâche')
                ->waitFor('[dusk="wizard-titre"]', 10)
                ->type('[dusk="wizard-titre"]', 'Tâche via wizard Dusk')
                ->click('[dusk="wizard-next"]')
                // Step 2 body must be visible
                ->waitFor('[dusk="wizard-step-assignation"]', 5)
                ->assertVisible('[dusk="wizard-step-assignation"]')
                ->assertVisible('[dusk="wizard-responsable"]');
        });
    }
}
