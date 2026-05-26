<?php

declare(strict_types=1);

namespace Tests\Browser\Profile;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour l'édition du profil utilisateur.
 */
class ProfileEditTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function makeUser(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create(['nom' => 'Nom Original']);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    /**
     * La page de profil charge correctement et affiche les onglets.
     */
    public function test_profile_page_loads_with_tabs(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitForText('Profil', 15)
                ->assertSee('Profil');
        });
    }

    /**
     * Le bouton d'édition du profil ouvre le formulaire inline.
     */
    public function test_edit_button_opens_profile_form(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-edit-btn"]', 15)
                ->click('@profile-edit-btn')
                ->waitFor('[dusk="profile-form-nom"]', 10)
                ->assertVisible('@profile-form-nom')
                ->assertVisible('@profile-form-save');
        });
    }

    /**
     * L'utilisateur peut sauvegarder le formulaire de profil sans erreur.
     * Le modal se ferme après la sauvegarde (indiquant un succès).
     */
    public function test_user_can_save_profile_form_without_error(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-edit-btn"]', 15)
                ->click('@profile-edit-btn')
                ->waitFor('[dusk="profile-form-nom"]', 10)
                ->clear('@profile-form-nom')
                ->type('@profile-form-nom', 'NomModifié')
                ->click('@profile-form-save')
                ->pause(3000)
                // modal closes on success — form field should no longer be visible
                ->assertMissing('@profile-form-save');
        });
    }
}
