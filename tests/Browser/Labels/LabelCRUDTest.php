<?php

declare(strict_types=1);

namespace Tests\Browser\Labels;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la création et la liste des labels.
 */
class LabelCRUDTest extends WorkTrackingTestCase
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
     * La page /labels charge et affiche le bouton "Nouveau Label".
     */
    public function test_labels_page_shows_create_button(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/labels')
                ->waitFor('[dusk="open-create-label-btn"]', 15)
                ->assertVisible('@open-create-label-btn');
        });
    }

    /**
     * Cliquer sur "Nouveau Label" ouvre le modal de création.
     */
    public function test_clicking_new_label_opens_modal(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/labels')
                ->waitFor('[dusk="open-create-label-btn"]', 15)
                ->click('@open-create-label-btn')
                ->waitFor('[dusk="label-form-nom"]', 10)
                ->assertVisible('@label-form-nom')
                ->assertVisible('@label-form-submit');
        });
    }

    /**
     * Un utilisateur peut créer un label qui apparaît ensuite dans la grille.
     */
    public function test_user_can_create_label_and_it_appears_in_grid(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/labels')
                ->waitFor('[dusk="open-create-label-btn"]', 15)
                ->click('@open-create-label-btn')
                ->waitFor('[dusk="label-form-nom"]', 10)
                ->type('@label-form-nom', 'Label Dusk Test')
                ->click('@label-form-submit')
                ->waitForText('Label Dusk Test', 15)
                ->assertSee('Label Dusk Test');
        });
    }
}
