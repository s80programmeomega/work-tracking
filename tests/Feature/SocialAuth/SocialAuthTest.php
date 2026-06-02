<?php

declare(strict_types=1);

namespace Tests\Feature\SocialAuth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialiteUser(
        string $id = 'google-123',
        string $email = 'john@gmail.com',
        string $name = 'John Doe',
        string $avatar = 'https://avatar.example.com/john.jpg',
    ): void {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $factory);
    }

    // =========================================================================
    // Redirect vers Google
    // =========================================================================

    /** @test */
    public function redirect_returns_redirect_response(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $factory);

        $this->get('/api/auth/google/redirect')->assertRedirect();
    }

    // =========================================================================
    // Callback — nouvel utilisateur créé
    // =========================================================================

    /** @test */
    public function callback_creates_new_user_and_redirects_with_token(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->mockSocialiteUser(email: 'newuser@gmail.com', name: 'Jane Smith');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('/auth/callback', $redirectUrl);
        $this->assertStringContainsString('token=', $redirectUrl);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@gmail.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    // =========================================================================
    // Callback — email existant → liaison du compte
    // =========================================================================

    /** @test */
    public function callback_links_google_to_existing_account(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@gmail.com',
            'provider' => null,
            'provider_id' => null,
            'is_active' => true,
        ]);

        $this->mockSocialiteUser(email: 'existing@gmail.com', name: 'Existing User');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('token=', $response->headers->get('Location'));

        $user->refresh();
        $this->assertEquals('google', $user->provider);
        $this->assertEquals('google-123', $user->provider_id);
    }

    // =========================================================================
    // Callback — compte inactif → redirige avec erreur
    // =========================================================================

    /** @test */
    public function callback_redirects_with_error_for_inactive_account(): void
    {
        User::factory()->create([
            'email' => 'inactive@gmail.com',
            'is_active' => false,
            'provider' => 'google',
            'provider_id' => 'google-456',
        ]);

        $this->mockSocialiteUser(id: 'google-456', email: 'inactive@gmail.com');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('error=account_inactive', $response->headers->get('Location'));
    }

    // =========================================================================
    // Callback — liaison par email ne crée pas de doublon
    // =========================================================================

    /** @test */
    public function callback_does_not_create_duplicate_when_email_exists(): void
    {
        User::factory()->create([
            'email' => 'nodup@gmail.com',
            'is_active' => true,
        ]);

        $this->mockSocialiteUser(email: 'nodup@gmail.com');

        $this->get('/api/auth/google/callback');

        $this->assertDatabaseCount('users', 1);
    }

    // =========================================================================
    // Callback — échec Socialite → redirige avec erreur
    // =========================================================================

    /** @test */
    public function callback_redirects_with_error_on_socialite_exception(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth error'));

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $factory);

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('error=social_auth_failed', $response->headers->get('Location'));
    }
}
