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
     *
     * Key invariant: the Pinia authStore reads from localStorage ONCE at store
     * creation time (when the Vue app boots).  We must therefore inject credentials
     * into localStorage BEFORE visiting the destination page so that isTempAdmin,
     * isSuperAdmin, etc. are correct when onMounted() fires.
     *
     * Strategy:
     *   1. Visit a neutral page to get a JS context for localStorage writes.
     *   2. Write token + user payload to localStorage.
     *   3. Visit the destination — this is a full SPA navigation that re-boots
     *      nothing (Vue Router handles it), but on first load the store was already
     *      hydrated in step 1's page context.  We therefore use visit() which does
     *      an actual HTTP GET → full page reload → Vue app re-initialises from the
     *      now-populated localStorage.
     */
    protected function signInAs(Browser $browser, User $user): Browser
    {
        $token = $user->createToken('dusk')->plainTextToken;

        $isTempAdmin = (bool) $user->is_super_admin && $user->admin_expires_at !== null;

        // JSON_HEX_APOS escapes ' → ' so the value is safe inside a JS string literal.
        $payload = json_encode([
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            'current_workspace_id' => $user->current_workspace_id,
            'is_super_admin' => (bool) $user->is_super_admin,
            'is_temp_admin' => $isTempAdmin,
            'admin_expires_at' => $user->admin_expires_at?->toISOString(),
            'roles' => $user->roles->pluck('name')->toArray(),
        ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);

        // 1. Land on a real page — localStorage is disabled on data: URLs (Chrome's
        //    initial about:blank state).  /signin is always accessible without auth.
        $browser->visit('/signin')->waitFor('[dusk="email"]', 20);

        // 2. Inject credentials now that we have a valid http(s) page context.
        $browser->script([
            "localStorage.clear(); localStorage.setItem('auth_token', '{$token}'); localStorage.setItem('user', '{$payload}');",
        ]);

        // 3. Hard-navigate to the destination page.  Dusk's visit() issues a real
        //    HTTP GET which triggers a full page reload → Vue app boots fresh →
        //    authStore reads the localStorage we just populated → isTempAdmin is correct
        //    before onMounted() in WorkspacePicker (or any other component) runs.
        $destination = $isTempAdmin ? '/workspaces/select' : '/taches/mes-taches';

        return $browser->visit($destination)->waitFor('[dusk="user-menu-toggle"]', 20);
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
