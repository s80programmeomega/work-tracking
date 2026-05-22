<?php

declare(strict_types=1);

namespace Tests\Browser\Notifications;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Task 8b — couverture Dusk de la page Préférences de notification côté
 * Web Push.
 *
 * Ce qui est testé:
 *   - le master switch "push_enabled" pilote la visibilité du panneau
 *     "Cet appareil" (apparition / disparition),
 *   - la valeur du switch est rechargée depuis le backend au montage
 *     (NotificationPreference seedée → toggle déjà coché),
 *   - le bouton "Activer" est rendu quand aucune souscription navigateur
 *     n'existe (cas par défaut dans Chromium headless: pas de permission,
 *     pas de SW enregistré).
 *
 * Ce qui n'est PAS testé ici (et qui reste manuel — cf.
 * docs/testing/TASK_8B_TESTING.md):
 *   - l'inscription réelle via PushManager.subscribe (nécessite un push
 *     service externe + permission utilisateur),
 *   - la réception d'une vraie notification système,
 *   - la désactivation automatique sur 410 Gone (dépend du push service).
 */
class WebPushPreferencesTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    public function test_push_master_switch_drives_device_panel_visibility(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        // Préférence seedée avec push activé → le panneau "Cet appareil"
        // doit être visible dès le chargement.
        NotificationPreference::factory()->forUser($user)->create();

        // L'input est sr-only (caché derrière son label stylé) → on
        // déclenche un click() sur l'élément via JS pour basculer
        // proprement le v-model sans dépendre de la visibilité.
        // Note: Browser::script() retourne un array (résultat des scripts)
        // et non $this, donc impossible de chaîner après — on l'appelle
        // sur sa propre ligne.
        $toggle = ["document.querySelector('[dusk=\"push-enabled-toggle\"]').click()"];

        $this->browse(function (Browser $browser) use ($user, $toggle) {
            $this->signInAs($browser, $user)
                ->visit('/notification-preferences')
                ->waitFor('@push-enabled-toggle', 10)
                ->waitFor('@webpush-device-panel', 5)
                ->assertVisible('@webpush-device-panel');

            // Couper le master switch → le panneau disparaît
            $browser->script($toggle);
            $browser->pause(300)
                ->assertMissing('@webpush-device-panel');

            // Le réactiver → le panneau revient
            $browser->script($toggle);
            $browser->pause(300)
                ->assertVisible('@webpush-device-panel');
        });
    }

    public function test_subscribe_button_is_shown_when_no_browser_subscription_exists(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        NotificationPreference::factory()->forUser($user)->create();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/notification-preferences')
                ->waitFor('@webpush-device-panel', 10)
                // En env Dusk, aucune permission n'est accordée et aucun SW
                // n'est enregistré → useWebPush() reste en 'not-subscribed'
                // et le bouton "Activer" est rendu.
                ->assertVisible('@webpush-subscribe')
                ->assertMissing('@webpush-unsubscribe');
        });
    }

    public function test_device_panel_hidden_when_push_enabled_is_false_in_db(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        // Préférence en BDD: push désactivé → le panneau ne doit
        // jamais apparaître, même après chargement.
        NotificationPreference::factory()->forUser($user)->pushDisabled()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/notification-preferences')
                ->waitFor('@push-enabled-toggle', 10)
                // Attendre que loadPreferences() ait synchronisé le toggle
                // avec la valeur false venant du backend.
                ->pause(800)
                ->assertMissing('@webpush-device-panel');
        });
    }

    public function test_inactive_subscription_row_is_ignored_for_device_state(): void
    {
        // Garantit qu'une souscription soft-disabled côté BDD (410 Gone)
        // ne fait pas basculer l'UI en état "subscribed" — c'est l'état
        // du navigateur qui prime côté affichage.
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        NotificationPreference::factory()->forUser($user)->create();
        PushSubscription::factory()->forUser($user)->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/notification-preferences')
                ->waitFor('@webpush-device-panel', 10)
                ->assertVisible('@webpush-subscribe')
                ->assertMissing('@webpush-unsubscribe');
        });
    }
}
