<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PermissionService — verifies the 4-level permission hierarchy:
 * super_admin > directeur (owner) > manager > cadre > collaborateur > stagiaire > observateur
 */
class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $service;
    private Workspace $workspace;
    private User $superAdmin;
    private User $directeur;
    private User $manager;
    private User $cadre;
    private User $collaborateur;
    private User $observateur;
    private User $stranger; // not a member of the workspace

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PermissionService();

        // Seed roles (required by Spatie)
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        // Create users
        $this->superAdmin    = User::factory()->create(['is_super_admin' => true]);
        $this->directeur     = User::factory()->create();
        $this->manager       = User::factory()->create();
        $this->cadre         = User::factory()->create();
        $this->collaborateur = User::factory()->create();
        $this->observateur   = User::factory()->create();
        $this->stranger      = User::factory()->create();

        $this->directeur->assignRole(Role::DIRECTEUR->value);
        $this->manager->assignRole(Role::UTILISATEUR->value);
        $this->cadre->assignRole(Role::UTILISATEUR->value);
        $this->collaborateur->assignRole(Role::UTILISATEUR->value);
        $this->observateur->assignRole(Role::UTILISATEUR->value);

        // Create workspace owned by directeur
        $this->workspace = Workspace::create([
            'nom'      => 'Test Workspace',
            'code'     => 'TEST-001',
            'owner_id' => $this->directeur->id,
            'is_active' => true,
        ]);

        // Attach members with contextual roles
        $this->workspace->members()->attach($this->directeur->id, [
            'role' => 'owner', 'permissions' => json_encode(['all']),
        ]);
        $this->workspace->members()->attach($this->manager->id, [
            'role' => 'manager',
            'permissions' => json_encode([
                'can_create_projects' => true,
                'can_invite_members'  => true,
                'can_manage_settings' => true,
                'can_delete_members'  => true,
                'can_view_all_projects' => true,
            ]),
        ]);
        $this->workspace->members()->attach($this->cadre->id, ['role' => 'cadre']);
        $this->workspace->members()->attach($this->collaborateur->id, ['role' => 'collaborateur']);
        $this->workspace->members()->attach($this->observateur->id, ['role' => 'observateur']);
    }

    // =========================================================================
    // WORKSPACE PERMISSIONS
    // =========================================================================

    /** @test */
    public function super_admin_can_manage_any_workspace(): void
    {
        $this->assertTrue($this->service->canManageWorkspace($this->superAdmin, $this->workspace));
    }

    /** @test */
    public function directeur_can_manage_their_workspace(): void
    {
        $this->assertTrue($this->service->canManageWorkspace($this->directeur, $this->workspace));
    }

    /** @test */
    public function manager_cannot_manage_workspace(): void
    {
        $this->assertFalse($this->service->canManageWorkspace($this->manager, $this->workspace));
    }

    /** @test */
    public function manager_can_create_projects(): void
    {
        $this->assertTrue($this->service->canCreateProject($this->manager, $this->workspace));
    }

    /** @test */
    public function cadre_cannot_create_projects_without_permission(): void
    {
        $this->assertFalse($this->service->canCreateProject($this->cadre, $this->workspace));
    }

    /** @test */
    public function observateur_cannot_view_workspace_they_are_not_member_of(): void
    {
        $this->assertFalse($this->service->canViewWorkspace($this->stranger, $this->workspace));
    }

    // =========================================================================
    // PROJECT PERMISSIONS
    // =========================================================================

    /** @test */
    public function manager_can_edit_project_they_are_member_of(): void
    {
        $projet = $this->createProjet();
        $projet->members()->attach($this->manager->id, ['role' => 'manager']);

        $this->assertTrue($this->service->canEditProject($this->manager, $projet));
    }

    /** @test */
    public function collaborateur_cannot_edit_project(): void
    {
        $projet = $this->createProjet();
        $projet->members()->attach($this->collaborateur->id, ['role' => 'collaborateur']);

        $this->assertFalse($this->service->canEditProject($this->collaborateur, $projet));
    }

    /** @test */
    public function only_directeur_can_delete_project(): void
    {
        $projet = $this->createProjet();

        $this->assertTrue($this->service->canDeleteProject($this->directeur, $projet));
        $this->assertFalse($this->service->canDeleteProject($this->manager, $projet));
    }

    // =========================================================================
    // ACTIVITY PERMISSIONS
    // =========================================================================

    /** @test */
    public function cadre_can_create_task_with_permission(): void
    {
        $projet  = $this->createProjet();
        $activite = $this->createActivite($projet);

        $activite->members()->attach($this->cadre->id, [
            'role'             => 'cadre',
            'can_create_tasks' => true,
        ]);

        $this->assertTrue($this->service->canCreateTask($this->cadre, $activite));
    }

    /** @test */
    public function collaborateur_cannot_create_task_without_permission(): void
    {
        $projet  = $this->createProjet();
        $activite = $this->createActivite($projet);

        $activite->members()->attach($this->collaborateur->id, [
            'role'             => 'collaborateur',
            'can_create_tasks' => false,
        ]);

        $this->assertFalse($this->service->canCreateTask($this->collaborateur, $activite));
    }

    // =========================================================================
    // TASK VALIDATION
    // =========================================================================

    /** @test */
    public function cadre_can_validate_n1(): void
    {
        $projet   = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache    = $this->createTache($activite, validationN1: true);

        $activite->members()->attach($this->cadre->id, [
            'role'                 => 'cadre',
            'can_validate_results' => true,
        ]);

        $this->assertTrue($this->service->canValidateN1($this->cadre, $tache));
    }

    /** @test */
    public function collaborateur_cannot_validate_n1(): void
    {
        $projet   = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache    = $this->createTache($activite, validationN1: true);

        $activite->members()->attach($this->collaborateur->id, [
            'role'                 => 'collaborateur',
            'can_validate_results' => false,
        ]);

        $this->assertFalse($this->service->canValidateN1($this->collaborateur, $tache));
    }

    /** @test */
    public function manager_can_validate_n2_after_n1(): void
    {
        $projet   = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache    = $this->createTache($activite, validationN1: true, validationN2: true, n1ValidatedAt: now());

        $projet->members()->attach($this->manager->id, ['role' => 'manager']);

        $this->assertTrue($this->service->canValidateN2($this->manager, $tache));
    }

    /** @test */
    public function manager_cannot_validate_n2_before_n1(): void
    {
        $projet   = $this->createProjet();
        $activite = $this->createActivite($projet);
        // N1 not validated yet (validated_n1_at is null)
        $tache = $this->createTache($activite, validationN1: true, validationN2: true);

        $projet->members()->attach($this->manager->id, ['role' => 'manager']);

        $this->assertFalse($this->service->canValidateN2($this->manager, $tache));
    }

    /** @test */
    public function cadre_cannot_validate_n2(): void
    {
        $projet   = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache    = $this->createTache($activite, validationN1: true, validationN2: true, n1ValidatedAt: now());

        $this->assertFalse($this->service->canValidateN2($this->cadre, $tache));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function createProjet(): Projet
    {
        return Projet::create([
            'workspace_id'   => $this->workspace->id,
            'nom'            => 'Test Projet',
            'description'    => 'Test',
            'responsable_id' => $this->directeur->id,
            'date_debut'     => now(),
            'date_fin'       => now()->addMonth(),
            'visibility'     => 'team',
        ]);
    }

    private function createActivite(Projet $projet): Activite
    {
        return Activite::create([
            'projet_id'      => $projet->id,
            'nom'            => 'Test Activite',
            'description'    => 'Test',
            'responsable_id' => $this->cadre->id,
            'date_debut'     => now(),
            'date_fin'       => now()->addMonth(),
        ]);
    }

    private function createTache(
        Activite $activite,
        bool $validationN1 = false,
        bool $validationN2 = false,
        mixed $n1ValidatedAt = null,
    ): Tache {
        return Tache::create([
            'activite_id'          => $activite->id,
            'titre'                => 'Test Tache',
            'description'          => 'Test',
            'echeance'             => now()->addWeek(),
            'statut'               => 'a_faire',
            'priorite'             => 'moyenne',
            'validation_n1_required' => $validationN1,
            'validation_n2_required' => $validationN2,
            'validated_n1_at'      => $n1ValidatedAt,
        ]);
    }
}
