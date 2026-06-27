<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la gestion des comptes admin temporaires.
 *
 * Couvre :
 *  A. Page directeur (/workspace/admin-account)
 *     - Chargement de la page
 *     - Bouton submit désactivé tant que les champs sont vides
 *     - Création réussie + message de succès
 *     - Bouton "Envoyer les identifiants" visible sur chaque ligne
 *     - Bouton "Révoquer" visible sur chaque ligne
 *     - Accès refusé pour un non-directeur
 *
 *  B. Flux login admin temporaire
 *     - Atterrissage sur le workspace picker (pas admin dashboard)
 *     - Picker affiche les workspaces accordés (non vide)
 *     - Sélection d'un workspace → redirection vers le dashboard
 *     - Compte révoqué → message d'erreur explicite à la connexion
 */
class TempAdminTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'temp-admin';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function makeDirecteur(): User
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $user->syncRoles(['directeur']);

        return $user;
    }

    private function makeTempAdmin(User $directeur, Workspace $workspace, int $days = 7): User
    {
        $tempAdmin = User::factory()->create([
            'password' => bcrypt('password'),
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays($days),
            'admin_expiry_action' => 'suspend',
            'created_by' => $directeur->id,
        ]);
        $tempAdmin->syncRoles(['super_admin']);

        DB::table('temporary_access')->insert([
            'user_id' => $tempAdmin->id,
            'accessible_type' => Workspace::class,
            'accessible_id' => $workspace->id,
            'role' => 'observateur',
            'created_by' => $directeur->id,
            'expires_at' => now()->addDays($days),
            'created_at' => now(),
        ]);

        return $tempAdmin;
    }

    // =========================================================================
    // A. Page directeur
    // =========================================================================

    /**
     * La page /workspace/admin-account se charge pour un directeur.
     */
    public function test_page_loads_for_directeur(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->assertVisible('[dusk="open-promote-modal"]');

            $browser->screenshot("{$this->screenshotDir}/01_page_loads");
        });
    }

    /**
     * Le bouton de soumission est désactivé tant que les champs obligatoires sont vides.
     */
    public function test_submit_button_disabled_until_all_fields_filled(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="modal-submit"]', 5)
                ->assertDisabled('[dusk="modal-submit"]');

            // Nom seulement — toujours désactivé
            $browser->type('[dusk="modal-nom"]', 'Admin Test')
                ->assertDisabled('[dusk="modal-submit"]');

            // Nom + email — toujours désactivé (pas de workspace coché)
            $browser->type('[dusk="modal-email"]', 'partial@example.com')
                ->assertDisabled('[dusk="modal-submit"]');

            $browser->screenshot("{$this->screenshotDir}/02_submit_disabled");
        });
    }

    /**
     * La création réussit et affiche le message de succès.
     */
    public function test_create_temp_admin_shows_success_message(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="modal-nom"]', 5)
                ->type('[dusk="modal-nom"]', 'Admin Temporaire Test')
                ->type('[dusk="modal-email"]', 'tempAdminDusk@example.com')
                ->check('input[type="checkbox"]')
                ->click('[dusk="modal-submit"]')
                ->waitFor('[dusk="success-message"]', 10)
                ->assertVisible('[dusk="success-message"]');

            $browser->screenshot("{$this->screenshotDir}/03_creation_success");
        });

        $this->assertDatabaseHas('users', [
            'email' => 'tempAdminDusk@example.com',
            'is_super_admin' => true,
        ]);
    }

    /**
     * Le bouton "Envoyer les identifiants" est visible sur chaque ligne.
     */
    public function test_send_credentials_button_visible_on_admin_row(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        $this->browse(function (Browser $browser) use ($directeur, $tempAdmin) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor("[dusk=\"send-credentials-{$tempAdmin->id}\"]", 10)
                ->assertVisible("[dusk=\"send-credentials-{$tempAdmin->id}\"]");

            $browser->screenshot("{$this->screenshotDir}/04_send_credentials_button");
        });
    }

    /**
     * Le bouton "Révoquer" est visible sur chaque ligne.
     */
    public function test_terminate_button_visible_on_admin_row(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        $this->browse(function (Browser $browser) use ($directeur, $tempAdmin) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor("[dusk=\"terminate-admin-{$tempAdmin->id}\"]", 10)
                ->assertVisible("[dusk=\"terminate-admin-{$tempAdmin->id}\"]");

            $browser->screenshot("{$this->screenshotDir}/05_terminate_button");
        });
    }

    /**
     * Un utilisateur sans rôle directeur voit le message "accès refusé".
     */
    public function test_non_directeur_sees_access_denied(): void
    {
        $regular = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $regular->update(['current_workspace_id' => $workspace->id]);
        $workspace->addMember($regular, 'collaborateur');
        $regular->syncRoles(['utilisateur']);

        $this->browse(function (Browser $browser) use ($regular) {
            $this->signInAs($browser, $regular);

            $browser->visit('/workspace/admin-account')
                ->waitForText('accès', 10)
                ->assertMissing('[dusk="open-promote-modal"]');

            $browser->screenshot("{$this->screenshotDir}/06_access_denied");
        });
    }

    // =========================================================================
    // B. Flux login admin temporaire
    // =========================================================================

    /**
     * Un admin temporaire atterrit sur le workspace picker, pas le dashboard admin.
     */
    public function test_temp_admin_lands_on_workspace_picker_not_admin_dashboard(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        $this->browse(function (Browser $browser) use ($tempAdmin) {
            $this->signInAs($browser, $tempAdmin);

            // Doit être sur le picker, pas le dashboard admin
            $browser->assertPathIs('/workspaces/select');
            $browser->assertDontSee('admin');

            $browser->screenshot("{$this->screenshotDir}/07_temp_admin_on_picker");
        });
    }

    /**
     * Le workspace picker affiche les workspaces accordés au temp admin (non vide).
     */
    public function test_temp_admin_picker_shows_granted_workspaces(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        $this->browse(function (Browser $browser) use ($tempAdmin, $workspace) {
            $this->signInAs($browser, $tempAdmin);

            // Attendre que les cartes workspace se chargent — pas l'état vide
            $browser->waitFor('[dusk="workspace-card-'.$workspace->id.'"]', 15)
                ->assertVisible('[dusk="workspace-card-'.$workspace->id.'"]')
                ->assertMissing('[dusk="picker-empty-state"]');

            $browser->screenshot("{$this->screenshotDir}/08_picker_shows_workspace");
        });
    }

    /**
     * Le temp admin peut sélectionner un workspace et accéder au dashboard.
     */
    public function test_temp_admin_can_select_workspace_and_enter(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        $this->browse(function (Browser $browser) use ($tempAdmin, $workspace) {
            $this->signInAs($browser, $tempAdmin);

            $browser->waitFor('[dusk="workspace-card-'.$workspace->id.'"]', 15)
                ->click('[dusk="workspace-card-'.$workspace->id.'"]')
                ->waitFor('[dusk="user-menu-toggle"]', 15);

            // Doit être dans l'app, pas sur le picker ni le dashboard admin
            $browser->assertPathIsNot('/workspaces/select')
                ->assertPathIsNot('/admin/dashboard');

            $browser->screenshot("{$this->screenshotDir}/09_temp_admin_entered_workspace");
        });
    }

    /**
     * Un compte temp admin révoqué reçoit un message d'erreur explicite à la connexion.
     */
    public function test_revoked_temp_admin_sees_explicit_error_on_login(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace);

        // Révoquer le compte (suspend = is_active=false)
        $tempAdmin->update(['is_active' => false, 'is_super_admin' => false]);
        $tempAdmin->syncRoles([]);
        DB::table('temporary_access')->where('user_id', $tempAdmin->id)->delete();

        $this->browse(function (Browser $browser) use ($tempAdmin) {
            // Partir d'une session propre — visiter d'abord /signin pour obtenir
            // un contexte de page valide avant d'effacer le localStorage.
            $browser->visit('/signin')->waitFor('[dusk="email"]', 15);
            $browser->script(['localStorage.clear();']);
            $browser->visit('/signin')
                ->waitFor('[dusk="email"]', 15)
                ->type('[dusk="email"]', $tempAdmin->email)
                ->type('[dusk="password"]', 'password')
                ->click('[dusk="login-button"]')
                ->waitFor('[dusk="auth-error"]', 10);

            $browser->assertVisible('[dusk="auth-error"]');
            $browser->assertPathIs('/signin');

            $browser->screenshot("{$this->screenshotDir}/10_revoked_account_error");
        });
    }
}
