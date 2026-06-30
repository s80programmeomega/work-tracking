<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre ProjetService : getGlobalDashboardStats, getWorkspaceDashboardStats,
 * generatePerformanceReport, getDashboardStats, getProjetStats.
 */
class ProjetServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private ProjetService $projetService;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->projetService = app(ProjetService::class);

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // GLOBAL DASHBOARD STATS
    // =========================================================================

    /** @test */
    public function get_global_dashboard_stats_returns_expected_keys(): void
    {
        $stats = $this->projetService->getGlobalDashboardStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_projets', $stats);
        $this->assertArrayHasKey('projets_actifs', $stats);
        $this->assertArrayHasKey('taux_completion', $stats);
    }

    // =========================================================================
    // WORKSPACE DASHBOARD STATS
    // =========================================================================

    /** @test */
    public function get_workspace_dashboard_stats_returns_expected_keys(): void
    {
        $stats = $this->projetService->getWorkspaceDashboardStats($this->workspace->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_projets', $stats);
        $this->assertArrayHasKey('projets_actifs', $stats);
    }

    // =========================================================================
    // PERFORMANCE REPORT
    // =========================================================================

    /** @test */
    public function generate_performance_report_returns_report_array(): void
    {
        $report = $this->projetService->generatePerformanceReport(
            $this->projet,
            now()->subDays(30),
            now()
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('periode', $report);
        $this->assertArrayHasKey('taches', $report);
        $this->assertArrayHasKey('progression', $report);
    }

    // =========================================================================
    // GET DASHBOARD STATS (per user)
    // =========================================================================

    /** @test */
    public function get_dashboard_stats_returns_stats_for_user(): void
    {
        $stats = $this->projetService->getDashboardStats($this->owner);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_projets', $stats);
        $this->assertArrayHasKey('active_projets', $stats);
    }

    // =========================================================================
    // GET PROJET STATS
    // =========================================================================

    /** @test */
    public function get_projet_stats_returns_tache_counts(): void
    {
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        foreach (['Tache A', 'Tache B', 'Tache C'] as $titre) {
            Tache::factory()->create([
                'activite_id' => $activite->id,
                'responsable_id' => $this->owner->id,
                'titre' => $titre,
            ]);
        }

        $stats = $this->projetService->getProjetStats($this->projet);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_taches', $stats);
        $this->assertEquals(3, $stats['total_taches']);
    }
}
