<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\Certificate;
use App\Models\Qualification;
use App\Models\Responsibility;
use App\Models\SchoolBackground;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSectionsTest extends TestCase
{
    use RefreshDatabase;

    // ── SchoolBackground ──────────────────────────────────────────────────────

    /** @test */
    public function user_can_list_own_school_backgrounds(): void
    {
        $user = User::factory()->create();
        SchoolBackground::factory()->create(['user_id' => $user->id, 'etablissement' => 'Université X']);

        $response = $this->actingAs($user)->getJson('/api/users/profile/school-backgrounds');

        $response->assertOk()->assertJsonPath('data.0.etablissement', 'Université X');
    }

    /** @test */
    public function user_can_create_school_background(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/users/profile/school-backgrounds', [
            'etablissement' => 'École Polytechnique',
            'diplome' => 'Master',
            'domaine' => 'Informatique',
            'date_debut' => '2018-09-01',
            'date_fin' => '2020-06-30',
        ]);

        $response->assertCreated()->assertJsonPath('data.etablissement', 'École Polytechnique');
        $this->assertDatabaseHas('school_backgrounds', ['user_id' => $user->id, 'etablissement' => 'École Polytechnique']);
    }

    /** @test */
    public function user_can_update_own_school_background(): void
    {
        $user = User::factory()->create();
        $entry = SchoolBackground::factory()->create(['user_id' => $user->id, 'etablissement' => 'Old Name']);

        $response = $this->actingAs($user)->putJson("/api/users/profile/school-backgrounds/{$entry->id}", [
            'etablissement' => 'New Name',
        ]);

        $response->assertOk()->assertJsonPath('data.etablissement', 'New Name');
    }

    /** @test */
    public function user_cannot_update_another_users_school_background(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = SchoolBackground::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->putJson("/api/users/profile/school-backgrounds/{$entry->id}", [
            'etablissement' => 'Hacked',
        ])->assertForbidden();
    }

    /** @test */
    public function user_can_delete_own_school_background(): void
    {
        $user = User::factory()->create();
        $entry = SchoolBackground::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->deleteJson("/api/users/profile/school-backgrounds/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('school_backgrounds', ['id' => $entry->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_school_background(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = SchoolBackground::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->deleteJson("/api/users/profile/school-backgrounds/{$entry->id}")
            ->assertForbidden();
    }

    /** @test */
    public function school_background_requires_etablissement(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/school-backgrounds', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['etablissement']);
    }

    // ── Certificate ───────────────────────────────────────────────────────────

    /** @test */
    public function user_can_list_own_certificates(): void
    {
        $user = User::factory()->create();
        Certificate::factory()->create(['user_id' => $user->id, 'titre' => 'AWS Solutions Architect']);

        $this->actingAs($user)->getJson('/api/users/profile/certificates')
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'AWS Solutions Architect');
    }

    /** @test */
    public function user_can_create_certificate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/certificates', [
            'titre' => 'PMP Certification',
            'organisme_emetteur' => 'PMI',
            'date_obtention' => '2023-03-15',
        ])->assertCreated()->assertJsonPath('data.titre', 'PMP Certification');
    }

    /** @test */
    public function user_cannot_update_another_users_certificate(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $cert = Certificate::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->putJson("/api/users/profile/certificates/{$cert->id}", [
            'titre' => 'Hacked',
        ])->assertForbidden();
    }

    /** @test */
    public function certificate_requires_titre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/certificates', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titre']);
    }

    // ── Qualification ─────────────────────────────────────────────────────────

    /** @test */
    public function user_can_create_qualification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/qualifications', [
            'titre' => 'Expert en gestion de projet',
            'description' => 'Spécialiste certifié.',
        ])->assertCreated()->assertJsonPath('data.titre', 'Expert en gestion de projet');
    }

    /** @test */
    public function user_cannot_delete_another_users_qualification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $qual = Qualification::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->deleteJson("/api/users/profile/qualifications/{$qual->id}")
            ->assertForbidden();
    }

    /** @test */
    public function qualification_requires_titre(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/qualifications', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titre']);
    }

    // ── Responsibility ────────────────────────────────────────────────────────

    /** @test */
    public function user_can_create_responsibility(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/responsibilities', [
            'titre' => 'Chef de projet senior',
            'organisation' => 'Acme Corp',
            'date_debut' => '2020-01-01',
        ])->assertCreated()->assertJsonPath('data.titre', 'Chef de projet senior');
    }

    /** @test */
    public function responsibility_requires_titre_and_date_debut(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/users/profile/responsibilities', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titre', 'date_debut']);
    }

    /** @test */
    public function user_cannot_update_another_users_responsibility(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $resp = Responsibility::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)->putJson("/api/users/profile/responsibilities/{$resp->id}", [
            'titre' => 'Hacked',
            'date_debut' => '2020-01-01',
        ])->assertForbidden();
    }

    // ── profileView exposes sections ──────────────────────────────────────────

    /** @test */
    public function profile_view_includes_all_sections_for_self(): void
    {
        $user = User::factory()->create();
        SchoolBackground::factory()->create(['user_id' => $user->id]);
        Certificate::factory()->create(['user_id' => $user->id]);
        Qualification::factory()->create(['user_id' => $user->id]);
        Responsibility::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson("/api/users/{$user->id}/profile-view");

        $response->assertOk()
            ->assertJsonCount(1, 'data.school_backgrounds')
            ->assertJsonCount(1, 'data.certificates')
            ->assertJsonCount(1, 'data.qualifications')
            ->assertJsonCount(1, 'data.responsibilities');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_profile_sections(): void
    {
        $this->getJson('/api/users/profile/school-backgrounds')->assertUnauthorized();
        $this->getJson('/api/users/profile/certificates')->assertUnauthorized();
        $this->getJson('/api/users/profile/qualifications')->assertUnauthorized();
        $this->getJson('/api/users/profile/responsibilities')->assertUnauthorized();
    }
}
