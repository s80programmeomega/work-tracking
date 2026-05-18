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

        $payload = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            'current_workspace_id' => $user->current_workspace_id,
            'is_super_admin' => $user->hasRole('super_admin'),
        ]);

        // 1. Clear any existing session so Vue doesn't redirect away from /signin.
        // We visit a neutral URL first to ensure localStorage is accessible.
        try {
            $browser->script(['localStorage.clear();']);
        } catch (\Throwable) {
            // No page loaded yet — safe to ignore
        }

        // 2. Visit /signin, wait for the form, inject the new token.
        $browser->visit('/signin')->waitFor('[dusk="email"]', 10);

        $browser->script([
            "localStorage.setItem('auth_token', '{$token}');",
            "localStorage.setItem('user', '{$payload}');",
        ]);

        // 3. Navigate to the target page — Vue boots fresh with the token set.
        return $browser->visit('/taches/mes-taches')->pause(3000);
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
