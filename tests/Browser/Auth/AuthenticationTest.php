<?php

namespace Tests\Browser\Auth;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

class AuthenticationTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * A user with valid credentials can sign in and lands on the dashboard.
     */
    public function test_user_can_sign_in_with_valid_credentials(): void
    {
        $workspace = Workspace::factory()->create();
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'current_workspace_id' => $workspace->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/signin')
                ->waitFor('[dusk="email"]')
                ->type('@email', 'test@example.com')
                ->type('@password', 'password')
                ->click('@login-button')
                ->waitForLocation('/', 10)
                ->assertPathIs('/');
        });
    }

    /**
     * Wrong password shows an error and stays on /signin.
     */
    public function test_wrong_password_shows_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/signin')
                ->tap(fn ($b) => $b->script(["localStorage.removeItem('auth_token');", "localStorage.removeItem('user');"]))
                ->visit('/signin')
                ->waitFor('[dusk="email"]')
                ->type('@email', 'test@example.com')
                ->type('@password', 'wrong-password')
                ->click('@login-button')
                ->pause(2000)
                ->assertPathIs('/signin');
        });
    }

    /**
     * Unauthenticated user trying to access a protected page is redirected to /signin.
     */
    public function test_unauthenticated_user_is_redirected_to_signin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForLocation('/signin', 5)
                ->assertPathIs('/signin');
        });
    }
}
