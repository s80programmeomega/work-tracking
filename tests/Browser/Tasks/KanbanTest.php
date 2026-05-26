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
 * Tests Dusk pour la vue Kanban — colonnes, déplacement et apparence des cartes.
 */
class KanbanTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** @return array{owner: User, activite: Activite, tache: Tache} */
    private function makeOwnerWithTask(string $statut = 'a_faire'): array
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

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'titre' => 'Tâche kanban',
            'statut' => $statut,
        ]);
        $tache->assignees()->attach($owner->id, [
            'role_id' => $cadreRoleId,
            'is_responsable' => true,
            'can_edit' => true,
        ]);

        return ['owner' => $owner, 'activite' => $activite, 'tache' => $tache];
    }

    /**
     * Les trois colonnes kanban (À faire, En cours, Terminé) sont présentes.
     */
    public function test_kanban_shows_three_columns(): void
    {
        $ctx = $this->makeOwnerWithTask();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitFor('[dusk="create-task-btn"]', 20)
                ->click('@toggle-kanban-btn')
                ->waitFor('[dusk="kanban-column-a_faire"]', 15)
                ->assertPresent('[dusk="kanban-column-a_faire"]')
                ->assertPresent('[dusk="kanban-column-en_cours"]')
                ->assertPresent('[dusk="kanban-column-termine"]');
        });
    }

    /**
     * Une tâche en statut a_faire apparaît dans la colonne À faire du kanban.
     */
    public function test_task_appears_in_correct_kanban_column(): void
    {
        $ctx = $this->makeOwnerWithTask('a_faire');

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitFor('[dusk="create-task-btn"]', 20)
                ->click('@toggle-kanban-btn')
                ->waitFor('[dusk="kanban-column-a_faire"]', 15)
                ->assertSeeIn('[dusk="kanban-column-a_faire"]', 'Tâche kanban');
        });
    }

    /**
     * Une tâche en statut en_cours apparaît dans la colonne En cours.
     */
    public function test_en_cours_task_appears_in_en_cours_column(): void
    {
        $ctx = $this->makeOwnerWithTask('en_cours');

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/activites/{$ctx['activite']->id}")
                ->waitFor('[dusk="create-task-btn"]', 20)
                ->click('@toggle-kanban-btn')
                ->waitFor('[dusk="kanban-column-en_cours"]', 15)
                ->assertSeeIn('[dusk="kanban-column-en_cours"]', 'Tâche kanban');
        });
    }
}
