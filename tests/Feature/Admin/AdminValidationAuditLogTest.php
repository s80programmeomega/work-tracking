<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Activite;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests du journal d'audit de validation — GET /api/admin/validation-audit-log.
 *
 * Couvre :
 *  - 200 + structure paginée pour super_admin
 *  - Filtre par action
 *  - Filtre par actor_id
 *  - Filtre par plage de dates
 *  - 403 pour utilisateur non-super-admin
 *  - 401 pour requête non authentifiée
 */
class AdminValidationAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['is_super_admin' => false]);
    }

    /** Crée une entrée d'audit avec l'acteur et l'action fournis. */
    private function makeLog(User $actor, string $action, array $context = [], ?string $createdAt = null): ValidationAuditLog
    {
        // La hiérarchie complète est requise : Activite → Tache → TacheResultat
        $activite = Activite::factory()->create();
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id]);

        return ValidationAuditLog::create([
            'tache_resultat_id' => $resultat->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'context' => $context,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    public function test_super_admin_gets_paginated_list(): void
    {
        $admin = $this->superAdmin();
        $actor = User::factory()->create();

        $this->makeLog($actor, 'approuve', ['taux_realisation' => 75]);
        $this->makeLog($actor, 'n1_valide');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/validation-audit-log');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'context',
                        'created_at',
                        'actor' => ['id', 'nom', 'email'],
                        'resultat',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_by_action_returns_only_matching_entries(): void
    {
        $admin = $this->superAdmin();
        $actor = User::factory()->create();

        $this->makeLog($actor, 'approuve');
        $this->makeLog($actor, 'renvoye');
        $this->makeLog($actor, 'renvoye');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/validation-audit-log?action=renvoye');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        // Toutes les entrées retournées ont l'action filtrée
        collect($response->json('data'))->each(
            fn ($entry) => $this->assertSame('renvoye', $entry['action'])
        );
    }

    public function test_filter_by_actor_id_returns_only_that_actors_entries(): void
    {
        $admin = $this->superAdmin();
        $actor1 = User::factory()->create();
        $actor2 = User::factory()->create();

        $this->makeLog($actor1, 'approuve');
        $this->makeLog($actor1, 'n1_valide');
        $this->makeLog($actor2, 'bypass');

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/validation-audit-log?actor_id={$actor1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        collect($response->json('data'))->each(
            fn ($entry) => $this->assertSame($actor1->id, $entry['actor']['id'])
        );
    }

    public function test_filter_by_date_range_respects_bounds(): void
    {
        $admin = $this->superAdmin();
        $actor = User::factory()->create();

        $this->makeLog($actor, 'approuve', [], '2026-01-10 10:00:00');
        $this->makeLog($actor, 'renvoye', [], '2026-01-20 10:00:00');
        $this->makeLog($actor, 'bypass', [], '2026-02-05 10:00:00');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/validation-audit-log?date_from=2026-01-15&date_to=2026-01-31');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        $this->assertSame('renvoye', $response->json('data.0.action'));
    }

    public function test_regular_user_gets_403(): void
    {
        Sanctum::actingAs($this->regularUser());

        $this->getJson('/api/admin/validation-audit-log')
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson('/api/admin/validation-audit-log')
            ->assertStatus(401);
    }
}
