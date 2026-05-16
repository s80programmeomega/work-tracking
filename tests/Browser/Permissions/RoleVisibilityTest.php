<?php

namespace Tests\Browser\Permissions;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Role Visibility Test
 *
 * Verifies that UI elements show or hide correctly based on the authenticated user's role.
 * Complements PermissionsMatrixTest (API layer) by testing the Vue layer.
 *
 * No database reset trait — data is seeded once in setUp() via a static guard,
 * and cleaned up in tearDownAfterClass(). This avoids DB truncation/rollback
 * between tests which would break the live Dusk server connection.
 */
class RoleVisibilityTest extends WorkTrackingTestCase
{
    use AttachesWithRoleId;

    private static bool $worldBuilt = false;

    private static int $cadreId;

    private static int $collaborateurId;

    private static int $observateurId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$worldBuilt) {
            if (! \DB::table('roles')->exists()) {
                Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
            }
            $this->buildWorld();
            self::$worldBuilt = true;
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up created records so subsequent test runs start fresh.
        User::whereIn('id', array_filter([
            self::$cadreId ?? null,
            self::$collaborateurId ?? null,
            self::$observateurId ?? null,
        ]))->forceDelete();

        self::$worldBuilt = false;

        parent::tearDownAfterClass();
    }

    private function buildWorld(): void
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'en_cours',
            'echeance' => now()->format('Y-m-d'),
            'week_number' => now()->isoWeek(),
            'year' => now()->year,
        ]);

        // cadre — is_responsable, sees Edit button, NOT Submit
        $cadre = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $cadre->id, 'cadre');
        $this->attachWithRole($activite->members(), $cadre->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($tache->assignees(), $cadre->id, 'collaborateur', [
            'is_responsable' => true, 'can_edit' => true, 'can_complete' => true,
            'can_validate' => true, 'statut_individuel' => 'en_cours', 'progression_individuelle' => 50,
        ]);
        $tache->update(['responsable_id' => $cadre->id]);

        // collaborateur — NOT is_responsable, sees Submit button
        $collaborateur = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $collaborateur->id, 'collaborateur');
        $this->attachWithRole($activite->members(), $collaborateur->id, 'collaborateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($tache->assignees(), $collaborateur->id, 'collaborateur', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => true,
            'can_validate' => false, 'statut_individuel' => 'en_cours', 'progression_individuelle' => 30,
        ]);

        // observateur — assigned but no action buttons
        $observateur = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $observateur->id, 'observateur');
        $this->attachWithRole($activite->members(), $observateur->id, 'observateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($tache->assignees(), $observateur->id, 'observateur', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => false,
            'can_validate' => false, 'statut_individuel' => 'a_faire', 'progression_individuelle' => 0,
        ]);

        self::$cadreId = $cadre->id;
        self::$collaborateurId = $collaborateur->id;
        self::$observateurId = $observateur->id;
    }

    private function cadre(): User
    {
        return User::findOrFail(self::$cadreId);
    }

    private function collaborateur(): User
    {
        return User::findOrFail(self::$collaborateurId);
    }

    private function observateur(): User
    {
        return User::findOrFail(self::$observateurId);
    }

    // =========================================================================
    // Submit Result button — visible to collaborateur, hidden for cadre/observateur
    // =========================================================================

    /** @test */
    public function collaborateur_sees_submit_result_button_on_task_card(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->collaborateur())
                ->visit('/taches/mes-taches')
                ->waitFor('[dusk="submit-result-btn"]', 10)
                ->assertVisible('[dusk="submit-result-btn"]');
        });
    }

    /** @test */
    public function cadre_with_is_responsable_also_sees_submit_result_button(): void
    {
        // cadre has tache_user.role='collaborateur' — they can submit their own result
        // AND supervise (is_responsable). Both buttons should be present.
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->cadre())
                ->visit('/taches/mes-taches')
                ->waitFor('[dusk="submit-result-btn"]', 10)
                ->assertVisible('[dusk="submit-result-btn"]');
        });
    }

    /** @test */
    public function observateur_does_not_see_submit_result_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->observateur())
                ->visit('/taches/mes-taches')
                ->pause(3000)
                ->assertMissing('[dusk="submit-result-btn"]');
        });
    }

    // =========================================================================
    // Edit Task button — visible to is_responsable cadre, hidden for others
    // =========================================================================

    /** @test */
    public function cadre_with_is_responsable_sees_edit_task_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->cadre())
                ->visit('/taches/mes-taches')
                ->waitFor('[dusk="edit-task-responsable-btn"]', 10)
                ->assertVisible('[dusk="edit-task-responsable-btn"]');
        });
    }

    /** @test */
    public function collaborateur_without_is_responsable_does_not_see_edit_task_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->collaborateur())
                ->visit('/taches/mes-taches')
                ->pause(3000)
                ->assertMissing('[dusk="edit-task-responsable-btn"]');
        });
    }

    /** @test */
    public function observateur_does_not_see_edit_task_button(): void
    {
        $this->browse(function (Browser $browser) {
            $this->signInAs($browser, $this->observateur())
                ->visit('/taches/mes-taches')
                ->pause(3000)
                ->assertMissing('[dusk="edit-task-responsable-btn"]');
        });
    }
}
