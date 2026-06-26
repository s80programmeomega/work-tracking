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
 * Tests Dusk pour le toggle lecture seule + personnalisation des permissions
 * sur la page de création d'admin temporaire.
 *
 * Couvre :
 *  C. Toggle permissions personnalisées
 *     - Toggle désactivé par défaut (panneau permissions masqué)
 *     - Activation du toggle affiche le sélecteur de rôle + le panneau
 *     - Le panneau est masqué à nouveau si le toggle est désactivé
 *     - Changement de rôle met à jour les permissions pré-cochées
 *     - Création avec toggle actif (permissions par défaut) → pas de custom_permissions en BDD
 *     - Création avec permissions personnalisées → custom_permissions sauvegardées en BDD
 *     - Badge "custom" visible sur la ligne du tableau pour un admin avec permissions perso
 *     - Badge "custom" absent pour un admin sans personnalisation
 */
class TempAdminCustomPermissionsTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'temp-admin-perms';

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

    private function makeTempAdmin(User $directeur, Workspace $workspace, ?array $customPermissions = null): User
    {
        $tempAdmin = User::factory()->create([
            'password' => bcrypt('password'),
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays(7),
            'admin_expiry_action' => 'suspend',
            'created_by' => $directeur->id,
        ]);
        $tempAdmin->syncRoles(['super_admin']);

        DB::table('temporary_access')->insert([
            'user_id' => $tempAdmin->id,
            'accessible_type' => Workspace::class,
            'accessible_id' => $workspace->id,
            'role' => 'observateur',
            'custom_permissions' => $customPermissions !== null ? json_encode($customPermissions) : null,
            'created_by' => $directeur->id,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        return $tempAdmin;
    }

    // =========================================================================
    // C. Toggle permissions personnalisées
    // =========================================================================

    /**
     * Le toggle est désactivé par défaut et le panneau permissions est masqué.
     */
    public function test_toggle_is_off_by_default_and_panel_hidden(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="role-custom-toggle"]', 5);

            // Panneau masqué et sélecteur de rôle absent quand toggle est off.
            $browser->assertMissing('[dusk="permissions-panel"]')
                ->assertMissing('[dusk="modal-workspace-role"]');

            $browser->screenshot("{$this->screenshotDir}/01_toggle_off_panel_hidden");
        });
    }

    /**
     * Activer le toggle affiche le sélecteur de rôle et le panneau de permissions.
     */
    public function test_enabling_toggle_shows_role_selector_and_panel(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="role-custom-toggle"]', 5)
                ->click('[dusk="role-custom-toggle"]')
                ->waitFor('[dusk="permissions-panel"]', 5);

            $browser->assertVisible('[dusk="modal-workspace-role"]')
                ->assertVisible('[dusk="permissions-panel"]');

            $browser->screenshot("{$this->screenshotDir}/02_toggle_on_panel_visible");
        });
    }

    /**
     * Désactiver le toggle masque à nouveau le panneau.
     */
    public function test_disabling_toggle_hides_panel_again(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="role-custom-toggle"]', 5)
                // Activer puis désactiver.
                ->click('[dusk="role-custom-toggle"]')
                ->waitFor('[dusk="permissions-panel"]', 5)
                ->click('[dusk="role-custom-toggle"]')
                ->waitUntilMissing('[dusk="permissions-panel"]', 5);

            $browser->assertMissing('[dusk="permissions-panel"]');

            $browser->screenshot("{$this->screenshotDir}/03_toggle_back_off");
        });
    }

    /**
     * Changer le rôle ouvre le groupe Workspace et pré-coche les permissions du rôle.
     * Vérifie cadre vs observateur : cadre a workspaces.invite_member, observateur non.
     */
    public function test_role_change_updates_prechecked_permissions(): void
    {
        $directeur = $this->makeDirecteur();

        $this->browse(function (Browser $browser) use ($directeur) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="role-custom-toggle"]', 5)
                ->click('[dusk="role-custom-toggle"]')
                ->waitFor('[dusk="permissions-panel"]', 5);

            // Ouvrir le groupe Workspace.
            $browser->click('[dusk="perm-group-workspace"]')
                ->waitFor('input[value="workspaces.invite_member"]', 5);

            // Avec observateur (défaut) : workspaces.invite_member NON coché.
            $browser->assertNotChecked('input[value="workspaces.invite_member"]');

            // Changer pour cadre.
            $browser->select('[dusk="modal-workspace-role"]', 'cadre')
                ->pause(300);

            // Avec cadre : workspaces.invite_member coché.
            $browser->assertChecked('input[value="workspaces.invite_member"]');

            $browser->screenshot("{$this->screenshotDir}/04_role_change_updates_perms");
        });
    }

    /**
     * Création avec toggle actif mais permissions inchangées → pas de custom_permissions en BDD.
     */
    public function test_creation_with_unchanged_permissions_stores_no_custom(): void
    {
        $directeur = $this->makeDirecteur();
        $email = 'nocustom@example.com';

        $this->browse(function (Browser $browser) use ($directeur, $email) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="modal-nom"]', 5)
                ->type('[dusk="modal-nom"]', 'Admin No Custom')
                ->type('[dusk="modal-email"]', $email)
                ->check('input[type="checkbox"]')
                // Activer le toggle mais ne pas modifier les permissions.
                ->click('[dusk="role-custom-toggle"]')
                ->waitFor('[dusk="permissions-panel"]', 5)
                ->click('[dusk="modal-submit"]')
                ->waitFor('[dusk="success-message"]', 10);

            $browser->screenshot("{$this->screenshotDir}/05_no_custom_perms_creation");
        });

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);

        $grant = DB::table('temporary_access')->where('user_id', $user->id)->first();
        $this->assertNotNull($grant);
        // Permissions par défaut du rôle → custom_permissions doit être null.
        $this->assertNull($grant->custom_permissions);
    }

    /**
     * Création avec permissions personnalisées (une permission retirée) → custom_permissions en BDD.
     */
    public function test_creation_with_custom_permissions_stores_them(): void
    {
        $directeur = $this->makeDirecteur();
        $email = 'custom@example.com';

        $this->browse(function (Browser $browser) use ($directeur, $email) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor('[dusk="open-promote-modal"]', 10)
                ->click('[dusk="open-promote-modal"]')
                ->waitFor('[dusk="modal-nom"]', 5)
                ->type('[dusk="modal-nom"]', 'Admin Custom Perms')
                ->type('[dusk="modal-email"]', $email)
                ->check('input[type="checkbox"]')
                // Activer le toggle + choisir cadre.
                ->click('[dusk="role-custom-toggle"]')
                ->waitFor('[dusk="permissions-panel"]', 5)
                ->select('[dusk="modal-workspace-role"]', 'cadre')
                ->pause(300)
                // Ouvrir le groupe Workspace et décocher workspaces.invite_member.
                ->click('[dusk="perm-group-workspace"]')
                ->waitFor('input[value="workspaces.invite_member"]', 5)
                ->uncheck('input[value="workspaces.invite_member"]')
                ->pause(200)
                ->click('[dusk="modal-submit"]')
                ->waitFor('[dusk="success-message"]', 10);

            $browser->screenshot("{$this->screenshotDir}/06_custom_perms_creation");
        });

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);

        $grant = DB::table('temporary_access')->where('user_id', $user->id)->first();
        $this->assertNotNull($grant);
        $this->assertNotNull($grant->custom_permissions);

        $stored = json_decode($grant->custom_permissions, true);
        // workspaces.invite_member a été retiré.
        $this->assertIsArray($stored);
        $this->assertNotContains('workspaces.invite_member', $stored);
        // workspaces.view doit toujours être présent.
        $this->assertContains('workspaces.view', $stored);
    }

    /**
     * Un admin avec permissions personnalisées affiche le badge "custom" dans le tableau.
     */
    public function test_custom_badge_visible_for_admin_with_custom_permissions(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace, ['workspaces.view', 'projets.view']);

        $this->browse(function (Browser $browser) use ($directeur, $tempAdmin) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor("[dusk=\"custom-badge-{$tempAdmin->id}\"]", 10)
                ->assertVisible("[dusk=\"custom-badge-{$tempAdmin->id}\"]");

            $browser->screenshot("{$this->screenshotDir}/07_custom_badge_visible");
        });
    }

    /**
     * Un admin sans permissions personnalisées n'affiche pas le badge "custom".
     */
    public function test_no_custom_badge_for_admin_without_custom_permissions(): void
    {
        $directeur = $this->makeDirecteur();
        $workspace = Workspace::where('owner_id', $directeur->id)->first();
        $tempAdmin = $this->makeTempAdmin($directeur, $workspace, null);

        $this->browse(function (Browser $browser) use ($directeur, $tempAdmin) {
            $this->signInAs($browser, $directeur);

            $browser->visit('/workspace/admin-account')
                ->waitFor("[dusk=\"temp-admin-row-{$tempAdmin->id}\"]", 10)
                ->assertMissing("[dusk=\"custom-badge-{$tempAdmin->id}\"]");

            $browser->screenshot("{$this->screenshotDir}/08_no_custom_badge");
        });
    }
}
