<?php

declare(strict_types=1);

namespace Tests\Browser\Teams;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la création d'équipes.
 */
class TeamCRUDTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function makeUser(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    /**
     * La page /teams charge et affiche le bouton de création d'équipe.
     */
    public function test_teams_page_shows_create_button(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/teams')
                ->waitFor('[dusk="open-create-team-btn"]', 15)
                ->assertVisible('@open-create-team-btn');
        });
    }

    /**
     * Cliquer sur "Créer une équipe" ouvre le formulaire modal.
     */
    public function test_clicking_create_opens_team_form(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/teams')
                ->waitFor('[dusk="open-create-team-btn"]', 15)
                ->click('@open-create-team-btn')
                ->waitFor('[dusk="team-form-name"]', 10)
                ->assertVisible('@team-form-name')
                ->assertVisible('@team-form-submit');
        });
    }

    /**
     * Un utilisateur peut créer une équipe qui apparaît ensuite dans la liste.
     */
    public function test_user_can_create_team_and_it_appears_in_list(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/teams')
                ->waitFor('[dusk="open-create-team-btn"]', 15)
                ->click('@open-create-team-btn')
                ->waitFor('[dusk="team-form-name"]', 10)
                ->type('@team-form-name', 'Équipe Dusk Test')
                ->click('@team-form-submit')
                ->waitFor('[dusk="team-detail-name"]', 25)
                ->assertSeeIn('[dusk="team-detail-name"]', 'Équipe Dusk Test');
        });
    }
}
