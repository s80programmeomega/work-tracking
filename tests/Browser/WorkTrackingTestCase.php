<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Base class for all Dusk browser tests.
 *
 * This app is a token-based SPA (Sanctum tokens stored in localStorage).
 * Dusk's loginAs() sets a session cookie which the SPA ignores — instead:
 *   - signInViaUi()  : authenticates through the real Signin form (use for auth tests)
 *   - signInAs()     : injects a fresh token into localStorage (use for all other tests)
 *
 * Database strategy: use DatabaseTruncation (not DatabaseMigrations) in every subclass.
 * DatabaseMigrations drops the schema between tests, breaking the live server.
 * DatabaseTruncation migrates once then truncates rows — safe for Dusk.
 */
abstract class WorkTrackingTestCase extends DuskTestCase
{
    /**
     * Authenticate by injecting a Sanctum token directly into localStorage.
     * This bypasses the UI and is fast — use it when not testing auth itself.
     */
    protected function signInAs(Browser $browser, User $user): Browser
    {
        $token = $user->createToken('dusk')->plainTextToken;

        // JSON_HEX_APOS escapes ' → ' so the string is safe inside a JS
        // single-quoted or double-quoted context without breaking the JS parser.
        $payload = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            'current_workspace_id' => $user->current_workspace_id,
            'is_super_admin' => $user->isSuperAdmin(),
        ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

        // 1. Clear localStorage on the current page so the SPA's auth guard
        // sees no token and lets /signin render instead of redirecting away.
        try {
            $browser->script(['localStorage.clear();']);
        } catch (\Throwable) {
            // No page loaded yet — safe to ignore
        }

        // 2. Now visit /signin — with no token in localStorage, Vue Router
        // will render the signin form instead of redirecting to the dashboard.
        $browser->visit('/signin')->waitFor('[dusk="email"]', 20);

        // 3. Inject fresh credentials.
        $browser->script([
            "localStorage.setItem('auth_token', '{$token}');",
            "localStorage.setItem('user', '{$payload}');",
        ]);

        // 4. Navigate to the app and wait until the authenticated layout is visible
        // (user-menu-toggle only renders after auth state is confirmed by the SPA).
        return $browser->visit('/taches/mes-taches')->waitFor('[dusk="user-menu-toggle"]', 20);
    }

    /**
     * Authenticate through the real Signin form.
     * Use this when testing the authentication flow itself.
     */
    protected function signInViaUi(Browser $browser, string $email, string $password): Browser
    {
        return $browser
            ->visit('/signin')
            ->waitFor('[dusk="email"]')
            ->type('@email', $email)
            ->type('@password', $password)
            ->click('@login-button');
    }
}
