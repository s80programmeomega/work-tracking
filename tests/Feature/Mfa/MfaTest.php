<?php

declare(strict_types=1);

namespace Tests\Feature\Mfa;

use App\Mail\TwoFactorEmailCodeMail;
use App\Models\TwoFactorEmailCode;
use App\Models\User;
use App\Services\MfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Login sans MFA — comportement inchangé
    // =========================================================================

    /** @test */
    public function login_without_mfa_returns_token_directly(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token', fn ($v) => ! empty($v))
            ->assertJsonMissingPath('two_factor');
    }

    // =========================================================================
    // Login avec TOTP actif — retourne un challenge
    // =========================================================================

    /** @test */
    public function login_with_totp_returns_challenge_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('two_factor', true)
            ->assertJsonStructure(['challenge_token', 'email_otp_available'])
            ->assertJsonMissingPath('data.token');
    }

    // =========================================================================
    // Challenge TOTP — code valide
    // =========================================================================

    /** @test */
    public function valid_totp_challenge_issues_sanctum_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        // Simule la vérification en mockant validateTwoFactorCode
        $this->mock(MfaService::class, function ($mock) use ($challengeToken, $user) {
            $mock->shouldReceive('resolveChallenge')->with($challengeToken)->andReturn($user);
            $mock->shouldReceive('verifyTotp')->andReturn(true);
            $mock->shouldReceive('consumeChallenge')->andReturn(null);
            $mock->shouldNotReceive('verifyEmailOtp');
        });

        $response = $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '123456',
            'type' => 'totp',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token', fn ($v) => ! empty($v))
            ->assertJsonPath('message', 'Login successful');
    }

    // =========================================================================
    // Challenge TOTP — code invalide
    // =========================================================================

    /** @test */
    public function invalid_totp_code_returns_422(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        $this->mock(MfaService::class, function ($mock) use ($challengeToken, $user) {
            $mock->shouldReceive('resolveChallenge')->with($challengeToken)->andReturn($user);
            $mock->shouldReceive('verifyTotp')->andReturn(false);
            $mock->shouldNotReceive('consumeChallenge');
        });

        $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '000000',
            'type' => 'totp',
        ])->assertStatus(422);
    }

    // =========================================================================
    // Challenge expiré
    // =========================================================================

    /** @test */
    public function expired_challenge_token_returns_422(): void
    {
        $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => 'invalid-token-that-doesnt-exist',
            'code' => '123456',
            'type' => 'totp',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => __('auth.mfa.challenge_expired')]);
    }

    // =========================================================================
    // Email OTP — envoi du code
    // =========================================================================

    /** @test */
    public function email_otp_send_queues_mail_and_returns_200(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'email_otp_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        $this->postJson('/api/auth/two-factor-email-send', [
            'challenge_token' => $challengeToken,
        ])->assertOk();

        Mail::assertQueued(TwoFactorEmailCodeMail::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    // =========================================================================
    // Email OTP — cooldown empêche le double envoi
    // =========================================================================

    /** @test */
    public function email_otp_send_respects_cooldown(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'is_active' => true,
            'email_otp_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        // Simule un cooldown déjà actif
        Cache::put("mfa:email_otp_cooldown:{$user->id}", true, now()->addSeconds(60));

        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        $this->postJson('/api/auth/two-factor-email-send', [
            'challenge_token' => $challengeToken,
        ])->assertStatus(429);
    }

    // =========================================================================
    // Email OTP — vérification valide
    // =========================================================================

    /** @test */
    public function valid_email_otp_challenge_issues_token(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_otp_enabled' => true,
        ]);

        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        // Crée un code en base
        $plainCode = '654321';
        TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => $plainCode,
            'type' => 'email',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token', fn ($v) => ! empty($v));

        // Code marqué comme utilisé
        $this->assertTrue(TwoFactorEmailCode::first()->used);
    }

    // =========================================================================
    // Email OTP — code expiré
    // =========================================================================

    /** @test */
    public function expired_email_otp_returns_422(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_otp_enabled' => true]);
        $mfa = app(MfaService::class);
        $challengeToken = $mfa->createChallengeToken($user);

        TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(1), // expiré
            'created_at' => now()->subMinutes(11),
        ]);

        $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => '123456',
            'type' => 'email',
        ])->assertStatus(422);
    }

    // =========================================================================
    // Email OTP — code usage unique
    // =========================================================================

    /** @test */
    public function email_otp_cannot_be_used_twice(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_otp_enabled' => true]);
        $mfa = app(MfaService::class);
        $plainCode = '111222';

        TwoFactorEmailCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
            'used' => true, // déjà utilisé
            'created_at' => now(),
        ]);

        $challengeToken = $mfa->createChallengeToken($user);

        $this->postJson('/api/auth/two-factor-challenge', [
            'challenge_token' => $challengeToken,
            'code' => $plainCode,
            'type' => 'email',
        ])->assertStatus(422);
    }

    // =========================================================================
    // Toggle email OTP — exige TOTP confirmé
    // =========================================================================

    /** @test */
    public function cannot_enable_email_otp_without_totp(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_confirmed_at' => null, // TOTP pas configuré
        ]);

        $this->actingAs($user)
            ->postJson('/api/auth/email-otp-toggle', ['enabled' => true])
            ->assertStatus(422);
    }

    /** @test */
    public function can_enable_email_otp_when_totp_is_active(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
            'email_otp_enabled' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/auth/email-otp-toggle', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('email_otp_enabled', true);
    }

    /**
     * Régression : /auth/me doit exposer l'état MFA (sans le secret) pour que le
     * front affiche le bouton de désactivation et l'activation de l'OTP email.
     *
     * @test
     */
    public function me_endpoint_exposes_mfa_state_without_secret(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_confirmed_at' => now(),
            'email_otp_enabled' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/auth/me')->assertOk();

        $response->assertJsonPath('data.email_otp_enabled', true);
        $this->assertNotNull($response->json('data.two_factor_confirmed_at'));
        // Le secret et les codes de récupération ne doivent JAMAIS être exposés.
        $response->assertJsonMissingPath('data.two_factor_secret');
        $response->assertJsonMissingPath('data.two_factor_recovery_codes');
    }
}
