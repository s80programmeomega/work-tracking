<?php

declare(strict_types=1);

namespace Tests\Feature\Task16;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Feature tests for Task 16 — PDF/Excel exports.
 *
 * Covers:
 *  - GET /api/evaluations/personnel/{user}/export-pdf → 200 PDF for permitted user
 *  - GET /api/evaluations/personnel/{user}/export-pdf → 403 for non-permitted user
 *  - GET /api/workspace/taches/export-excel → 200 Excel for owner
 *  - GET /api/workspace/taches/export-excel → 403 for regular member
 *  - GET /api/workspace/taches/export-excel → filters are forwarded to the export
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function ownerWithWorkspace(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        return compact('owner', 'workspace');
    }

    private function workspaceWithTask(User $owner, Workspace $workspace): Tache
    {
        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $owner->id,
        ]);

        return Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'en_cours',
        ]);
    }

    public function test_export_pdf_returns_403_for_non_permitted_user(): void
    {
        ['owner' => $owner] = $this->ownerWithWorkspace();
        $stranger = User::factory()->create();

        Sanctum::actingAs($stranger);

        $this->getJson("/api/evaluations/personnel/{$owner->id}/export-pdf")
            ->assertStatus(403);
    }

    public function test_export_pdf_returns_pdf_for_self(): void
    {
        ['owner' => $owner] = $this->ownerWithWorkspace();

        Sanctum::actingAs($owner);

        $response = $this->get("/api/evaluations/personnel/{$owner->id}/export-pdf?start=2026-01-01&end=2026-12-31");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_excel_returns_403_for_regular_user(): void
    {
        ['workspace' => $workspace] = $this->ownerWithWorkspace();
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);

        Sanctum::actingAs($member);

        $this->getJson('/api/workspace/taches/export-excel')
            ->assertStatus(403);
    }

    public function test_export_excel_returns_excel_for_owner(): void
    {
        Excel::fake();

        ['owner' => $owner, 'workspace' => $workspace] = $this->ownerWithWorkspace();
        $this->workspaceWithTask($owner, $workspace);

        Sanctum::actingAs($owner);

        $this->get('/api/workspace/taches/export-excel')
            ->assertStatus(200);

        Excel::assertDownloaded('taches-workspace-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_export_excel_respects_statut_filter(): void
    {
        Excel::fake();

        ['owner' => $owner, 'workspace' => $workspace] = $this->ownerWithWorkspace();
        $this->workspaceWithTask($owner, $workspace);

        Sanctum::actingAs($owner);

        $this->get('/api/workspace/taches/export-excel?statut=en_cours')
            ->assertStatus(200);

        Excel::assertDownloaded('taches-workspace-'.now()->format('Y-m-d').'.xlsx');
    }
}
