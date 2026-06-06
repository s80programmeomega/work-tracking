<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests de la recherche globale — GET /api/search.
 *
 * Driver : SCOUT_DRIVER=collection (phpunit.xml) — aucun Typesense requis.
 * Les modèles Searchable utilisent le driver "collection" en mémoire.
 *
 * Couvre :
 *  - Manager obtient 200 avec résultats structurés
 *  - Cadre/collaborateur reçoit 403
 *  - Non-membre du workspace reçoit 403
 *  - Résultats filtrés par workspace (pas de fuite cross-workspace)
 *  - Terme trop court (< 2 chars) → 422
 *  - Recherche sans résultat → groupes vides
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createWorkspaceWithManager(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $ownerRoleId = Role::where('name', 'owner')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($owner->id, ['role_id' => $ownerRoleId]);

        $manager = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $managerRoleId = Role::where('name', 'manager')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($manager->id, ['role_id' => $managerRoleId]);

        return [$workspace, $owner, $manager];
    }

    private function addCadre(Workspace $workspace): User
    {
        $cadre = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($cadre->id, ['role_id' => $cadreRoleId]);

        return $cadre;
    }

    private function addObservateur(Workspace $workspace): User
    {
        $observateur = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $roleId = Role::where('name', 'observateur')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($observateur->id, ['role_id' => $roleId]);

        return $observateur;
    }

    // ── TC-01 : Manager 200 avec structure correcte ──────────────────────────

    public function test_manager_gets_200_with_grouped_results(): void
    {
        [$workspace,, $manager] = $this->createWorkspaceWithManager();

        // Indexer un projet dans ce workspace
        Projet::factory()->create(['workspace_id' => $workspace->id, 'nom' => 'ProjetAlpha']);

        Sanctum::actingAs($manager);

        $response = $this->getJson("/api/search?q=ProjetAlpha&workspace_id={$workspace->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'query',
                'workspace_id',
                'results' => [
                    'projets',
                    'activites',
                    'taches',
                    'documents',
                    'users',
                ],
            ]);
    }

    // ── TC-02 : Owner 200 (propriétaire du workspace) ────────────────────────

    public function test_workspace_owner_gets_200(): void
    {
        [$workspace, $owner] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($owner);

        $this->getJson("/api/search?q=test&workspace_id={$workspace->id}")
            ->assertStatus(200);
    }

    // ── TC-03 : Cadre → 200 (recherche scopée, Tier 3) ───────────────────────

    public function test_cadre_gets_scoped_search(): void
    {
        // Phase 6 tier-3 : cadre/collaborateur/stagiaire ont search.scoped
        // → accès recherche limité à leurs ressources assignées (200, pas 403).
        [$workspace] = $this->createWorkspaceWithManager();
        $cadre = $this->addCadre($workspace);
        Sanctum::actingAs($cadre);

        $this->getJson("/api/search?q=test&workspace_id={$workspace->id}")
            ->assertStatus(200);
    }

    // ── TC-03b : Observateur (Tier 4) → 403 ──────────────────────────────────

    public function test_observateur_gets_403(): void
    {
        [$workspace] = $this->createWorkspaceWithManager();
        $observateur = $this->addObservateur($workspace);
        Sanctum::actingAs($observateur);

        $this->getJson("/api/search?q=test&workspace_id={$workspace->id}")
            ->assertStatus(403);
    }

    // ── TC-04 : Non-membre → 403 ─────────────────────────────────────────────

    public function test_non_member_gets_403(): void
    {
        [$workspace] = $this->createWorkspaceWithManager();
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $this->getJson("/api/search?q=test&workspace_id={$workspace->id}")
            ->assertStatus(403);
    }

    // ── TC-05 : Non-authentifié → 401 ────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson('/api/search?q=test')
            ->assertStatus(401);
    }

    // ── TC-06 : Terme trop court → 422 ───────────────────────────────────────

    public function test_query_shorter_than_2_chars_returns_422(): void
    {
        [,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        $this->getJson('/api/search?q=a')
            ->assertStatus(422);
    }

    // ── TC-07 : Pas de fuite cross-workspace ─────────────────────────────────

    public function test_results_scoped_to_workspace_no_cross_workspace_leak(): void
    {
        [$workspaceA,, $managerA] = $this->createWorkspaceWithManager();
        [$workspaceB] = $this->createWorkspaceWithManager();

        // Créer un projet dans workspace B avec un nom unique
        Projet::factory()->create(['workspace_id' => $workspaceB->id, 'nom' => 'ProjetSecretB']);

        Sanctum::actingAs($managerA);

        $response = $this->getJson("/api/search?q=ProjetSecretB&workspace_id={$workspaceA->id}");

        $response->assertStatus(200);

        // Les résultats du workspace A ne doivent PAS contenir ProjetSecretB
        $projets = $response->json('results.projets');
        $labels = collect($projets)->pluck('label')->toArray();
        $this->assertNotContains('ProjetSecretB', $labels);
    }

    // ── TC-08 : Filtre par type ───────────────────────────────────────────────

    public function test_types_filter_limits_result_groups(): void
    {
        [$workspace,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        $response = $this->getJson("/api/search?q=test&workspace_id={$workspace->id}&types[]=projets");

        $response->assertStatus(200)
            ->assertJsonStructure(['results' => ['projets']])
            ->assertJsonMissing(['results' => ['taches']]);
    }

    // ── Super-admin global scope ───────────────────────────────────────────────

    public function test_super_admin_gets_global_scope_without_workspace_filter(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        // Deux workspaces distincts avec un projet chacun
        [$wsA] = $this->createWorkspaceWithManager();
        [$wsB] = $this->createWorkspaceWithManager();

        Projet::factory()->create(['workspace_id' => $wsA->id, 'nom' => 'ProjetAlpha']);
        Projet::factory()->create(['workspace_id' => $wsB->id, 'nom' => 'ProjetBeta']);

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/search?q=Projet&types[]=projets');

        $response->assertStatus(200)
            ->assertJsonPath('is_global', true);

        // Le super-admin voit les projets des deux workspaces
        $labels = collect($response->json('results.projets'))->pluck('label')->toArray();
        $this->assertContains('ProjetAlpha', $labels);
        $this->assertContains('ProjetBeta', $labels);
    }

    public function test_super_admin_result_includes_workspace_name(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        [$workspace] = $this->createWorkspaceWithManager();

        Projet::factory()->create(['workspace_id' => $workspace->id, 'nom' => 'ProjetWs']);

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/search?q=ProjetWs&types[]=projets');

        $response->assertStatus(200);
        $results = $response->json('results.projets');
        $this->assertNotEmpty($results);
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    public function test_search_returns_pagination_info(): void
    {
        [$workspace,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        $response = $this->getJson("/api/search?q=test&workspace_id={$workspace->id}&page=1&per_page=5");

        $response->assertStatus(200)
            ->assertJsonStructure(['page', 'per_page', 'totals']);
    }

    // ── Export endpoints ──────────────────────────────────────────────────────

    public function test_export_global_returns_queued_response_for_cap_all(): void
    {
        Queue::fake();

        [$workspace,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        // Vérifier que la requête passe la validation et l'autorisation sans dispatcher réellement le job
        $response = $this->getJson("/api/search/export?q=test&workspace_id={$workspace->id}&cap=all");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_export_selected_requires_ids_and_type(): void
    {
        [,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        $this->postJson('/api/search/export', [])
            ->assertStatus(422);
    }

    public function test_export_cadre_gets_403(): void
    {
        [$workspace] = $this->createWorkspaceWithManager();
        $cadre = $this->addCadre($workspace);
        Sanctum::actingAs($cadre);

        $this->getJson("/api/search/export?q=test&workspace_id={$workspace->id}")
            ->assertStatus(403);
    }

    // ── Nouveau : messages inclus dans les types ──────────────────────────────

    public function test_messages_type_is_accepted(): void
    {
        [$workspace,, $manager] = $this->createWorkspaceWithManager();
        Sanctum::actingAs($manager);

        $response = $this->getJson("/api/search?q=test&workspace_id={$workspace->id}&types[]=messages");

        $response->assertStatus(200)
            ->assertJsonStructure(['results' => ['messages']]);
    }
}
