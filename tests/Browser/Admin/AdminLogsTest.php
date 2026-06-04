<?php

namespace Tests\Browser\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la page des journaux d'administration (/admin/logs).
 *
 * Vérifie :
 *  - L'onglet Activité se charge et affiche le tableau
 *  - Un clic sur une ligne ouvre le panneau de détail
 *  - L'onglet Logs applicatifs s'affiche (liste de fichiers ou état vide)
 *  - L'onglet Journal de validation se charge (état vide acceptable)
 */
class AdminLogsTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Super-admin visite la page et l'onglet Activité charge le tableau.
     */
    public function test_activity_tab_renders_for_super_admin(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/admin/logs')
                ->waitFor('[dusk="log-tab"]', 5)
                ->assertSee('Journal d\'activité');
        });
    }

    /**
     * Un clic sur une ligne d'activité ouvre le panneau de détail.
     */
    public function test_clicking_activity_row_opens_drawer(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        // Créer une entrée d'activité Spatie via activity()
        activity()->causedBy($admin)->log('Test activity entry for drawer');

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/admin/logs')
                ->waitFor('[dusk="activity-row"]', 8)
                ->click('[dusk="activity-row"]')
                ->waitFor('[dusk="activity-drawer"]', 5)
                ->assertVisible('[dusk="activity-drawer"]');
        });
    }

    /**
     * L'onglet Logs applicatifs s'affiche après clic sur l'onglet.
     */
    public function test_app_logs_tab_renders(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/admin/logs')
                ->waitFor('[dusk="log-tab"]', 5)
                ->click('[dusk="log-tab"]:nth-child(2)')
                ->waitFor('[dusk="app-logs-tab"]', 8)
                ->assertVisible('[dusk="app-logs-tab"]');
        });
    }

    /**
     * L'onglet Journal de validation se charge (état vide acceptable).
     */
    public function test_validation_audit_tab_renders(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/admin/logs')
                ->waitFor('[dusk="log-tab"]', 5)
                ->click('[dusk="log-tab"]:nth-child(3)')
                ->waitFor('[dusk="audit-tab"]', 8)
                ->assertVisible('[dusk="audit-tab"]');
        });
    }
}
