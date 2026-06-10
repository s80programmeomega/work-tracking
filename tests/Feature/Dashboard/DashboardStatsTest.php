<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\TacheStatut;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 11A — Dashboard stats accuracy.
 *
 * Covers:
 *  - GET /api/dashboard "stats" carry real period-over-period change/trend
 *    (no more hardcoded +12%/+5%/-2%)
 *  - "recent_projects" / Kanban data includes both active and completed
 *    projects (not just active)
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        Cache::flush();
    }

    private function userWithWorkspace(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return [$user, $workspace];
    }

    public function test_stats_no_longer_use_hardcoded_change_values(): void
    {
        [$user, $workspace] = $this->userWithWorkspace();
        Sanctum::actingAs($user);

        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $stats = $response->json('stats');

        // Les anciennes valeurs codées en dur ne doivent plus apparaître.
        $this->assertNotSame('+12%', $stats['projets_actifs']['change']);
        $this->assertNotSame('+5%', $stats['taux_completion']['change']);
        $this->assertNotSame('-2%', $stats['taches_en_retard']['change']);
    }

    public function test_projets_actifs_change_reflects_new_project_created_this_month(): void
    {
        [$user, $workspace] = $this->userWithWorkspace();
        Sanctum::actingAs($user);

        // Un projet actif créé "il y a 2 mois" (existait déjà à la date de comparaison).
        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'active',
            'created_at' => now()->subMonths(2),
        ]);

        // Un projet actif créé aujourd'hui (n'existait pas il y a 1 mois).
        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $stats = $response->json('stats');

        // 2 projets actifs maintenant vs. 1 il y a 1 mois → +100%, tendance up.
        $this->assertSame(2, $stats['projets_actifs']['value']);
        $this->assertSame('+100%', $stats['projets_actifs']['change']);
        $this->assertSame('up', $stats['projets_actifs']['trend']);
    }

    public function test_taches_en_retard_change_reflects_newly_overdue_task(): void
    {
        [$user, $workspace] = $this->userWithWorkspace();
        Sanctum::actingAs($user);

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'active',
        ]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);

        // Tâche dont l'échéance est dépassée depuis hier seulement → pas en
        // retard il y a 1 mois, mais en retard aujourd'hui.
        Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => TacheStatut::A_FAIRE->value,
            'echeance' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $stats = $response->json('stats');

        $this->assertSame(1, $stats['taches_en_retard']['value']);
        // 1 maintenant vs 0 il y a 1 mois → +100%, tendance "down" (dégradation).
        $this->assertSame('+100%', $stats['taches_en_retard']['change']);
        $this->assertSame('down', $stats['taches_en_retard']['trend']);
    }

    public function test_recent_projects_includes_active_and_completed_but_not_archived(): void
    {
        [$user, $workspace] = $this->userWithWorkspace();
        Sanctum::actingAs($user);

        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'active',
            'nom' => 'Projet Actif',
        ]);
        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'completed',
            'nom' => 'Projet Termine',
        ]);
        Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $user->id,
            'status' => 'archived',
            'nom' => 'Projet Archive',
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $names = collect($response->json('recent_projects'))->pluck('name');

        $this->assertTrue($names->contains('Projet Actif'));
        $this->assertTrue($names->contains('Projet Termine'));
        $this->assertFalse($names->contains('Projet Archive'));
    }
}
