<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Tests for ContextualPermissionGate — verifies the 4-level permission hierarchy:
 * super_admin > directeur (owner) > manager > cadre > collaborateur > stagiaire > observateur
 */
class PermissionServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private ContextualPermissionGate $gate;

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

        $this->gate = app(ContextualPermissionGate::class);

        // Seed roles (required by Spatie)
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        // Create users
        $this->superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->directeur = User::factory()->create();
        $this->manager = User::factory()->create();
        $this->cadre = User::factory()->create();
        $this->collaborateur = User::factory()->create();
        $this->observateur = User::factory()->create();
        $this->stranger = User::factory()->create();

        $this->directeur->assignRole(Role::DIRECTEUR->value);
        $this->manager->assignRole(Role::UTILISATEUR->value);
        $this->cadre->assignRole(Role::UTILISATEUR->value);
        $this->collaborateur->assignRole(Role::UTILISATEUR->value);
        $this->observateur->assignRole(Role::UTILISATEUR->value);

        // Create workspace owned by directeur
        $this->workspace = Workspace::create([
            'nom' => 'Test Workspace',
            'code' => 'TEST-001',
            'owner_id' => $this->directeur->id,
            'is_active' => true,
        ]);

        // Attach members with contextual role_id FKs
        $this->attachWithRole($this->workspace->members(), $this->directeur->id, 'owner');
        $this->attachWithRole($this->workspace->members(), $this->manager->id, 'manager');
        $this->attachWithRole($this->workspace->members(), $this->cadre->id, 'cadre');
        $this->attachWithRole($this->workspace->members(), $this->collaborateur->id, 'collaborateur');
        $this->attachWithRole($this->workspace->members(), $this->observateur->id, 'observateur');
    }

    // =========================================================================
    // WORKSPACE PERMISSIONS
    // =========================================================================

    /** @test */
    public function super_admin_without_workspace_membership_cannot_manage_workspace(): void
    {
        // Superadmin scoping: platform operators no longer have blanket workspace access.
        // A superadmin who is not a member of the workspace is denied, like any other user.
        $this->assertFalse($this->gate->userCan($this->superAdmin, Permission::WORKSPACES_MANAGE_SETTINGS, $this->workspace));
    }

    /** @test */
    public function directeur_can_manage_their_workspace(): void
    {
        $this->assertTrue($this->gate->userCan($this->directeur, Permission::WORKSPACES_MANAGE_SETTINGS, $this->workspace));
    }

    /** @test */
    public function manager_can_manage_workspace(): void
    {
        // Per seeder: manager role includes WORKSPACES_MANAGE_SETTINGS
        $this->assertTrue($this->gate->userCan($this->manager, Permission::WORKSPACES_MANAGE_SETTINGS, $this->workspace));
    }

    /** @test */
    public function manager_can_create_projects(): void
    {
        $this->assertTrue($this->gate->userCan($this->manager, Permission::WORKSPACES_CREATE_PROJECT, $this->workspace));
    }

    /** @test */
    public function cadre_cannot_create_projects_without_permission(): void
    {
        $this->assertFalse($this->gate->userCan($this->cadre, Permission::WORKSPACES_CREATE_PROJECT, $this->workspace));
    }

    /** @test */
    public function observateur_cannot_view_workspace_they_are_not_member_of(): void
    {
        $this->assertFalse($this->gate->userCan($this->stranger, Permission::WORKSPACES_VIEW, $this->workspace));
    }

    // =========================================================================
    // PROJECT PERMISSIONS
    // =========================================================================

    /** @test */
    public function manager_can_edit_project_they_are_member_of(): void
    {
        $projet = $this->createProjet();
        $this->attachWithRole($projet->members(), $this->manager->id, 'manager');

        $this->assertTrue($this->gate->userCan($this->manager, Permission::PROJETS_EDIT, $projet));
    }

    /** @test */
    public function collaborateur_cannot_edit_project(): void
    {
        $projet = $this->createProjet();
        $this->attachWithRole($projet->members(), $this->collaborateur->id, 'collaborateur');

        $this->assertFalse($this->gate->userCan($this->collaborateur, Permission::PROJETS_EDIT, $projet));
    }

    /** @test */
    public function directeur_and_manager_can_delete_project(): void
    {
        // Per seeder: both owner and manager roles include PROJETS_DELETE
        $projet = $this->createProjet();

        $this->assertTrue($this->gate->userCan($this->directeur, Permission::PROJETS_DELETE, $projet));
        $this->assertTrue($this->gate->userCan($this->manager, Permission::PROJETS_DELETE, $projet));
        $this->assertFalse($this->gate->userCan($this->collaborateur, Permission::PROJETS_DELETE, $projet));
    }

    // =========================================================================
    // ACTIVITY PERMISSIONS
    // =========================================================================

    /** @test */
    public function cadre_can_create_task_with_permission(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);

        $this->attachWithRole($activite->members(), $this->cadre->id, 'cadre', ['can_create_tasks' => true]);

        $this->assertTrue($this->gate->userCan($this->cadre, Permission::ACTIVITES_CREATE_TASK, $activite));
    }

    /** @test */
    public function collaborateur_cannot_create_task_without_permission(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);

        $this->attachWithRole($activite->members(), $this->collaborateur->id, 'collaborateur', ['can_create_tasks' => false]);

        $this->assertFalse($this->gate->userCan($this->collaborateur, Permission::ACTIVITES_CREATE_TASK, $activite));
    }

    // =========================================================================
    // TASK VALIDATION
    // =========================================================================

    /** @test */
    public function cadre_can_validate_n1(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache = $this->createTache($activite, validationN1: true);

        $this->attachWithRole($activite->members(), $this->cadre->id, 'cadre', ['can_validate_results' => true]);

        $this->assertTrue($this->gate->userCan($this->cadre, Permission::TACHES_VALIDATE_N1, $tache));
    }

    /** @test */
    public function collaborateur_cannot_validate_n1(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache = $this->createTache($activite, validationN1: true);

        $this->attachWithRole($activite->members(), $this->collaborateur->id, 'collaborateur', ['can_validate_results' => false]);

        $this->assertFalse($this->gate->userCan($this->collaborateur, Permission::TACHES_VALIDATE_N1, $tache));
    }

    /** @test */
    public function manager_can_validate_n2_after_n1(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache = $this->createTache($activite, validationN1: true, validationN2: true, n1ValidatedAt: now());

        $this->attachWithRole($projet->members(), $this->manager->id, 'manager');

        $this->assertTrue($this->gate->userCan($this->manager, Permission::TACHES_VALIDATE_N2, $tache));
    }

    /** @test */
    public function manager_has_validate_n2_permission_regardless_of_n1_state(): void
    {
        // The gate grants permissions by role — N1 precondition is enforced by the controller/policy,
        // not by the permission gate. A manager always has TACHES_VALIDATE_N2 as a role permission.
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache = $this->createTache($activite, validationN1: true, validationN2: true);

        $this->attachWithRole($projet->members(), $this->manager->id, 'manager');

        $this->assertTrue($this->gate->userCan($this->manager, Permission::TACHES_VALIDATE_N2, $tache));
    }

    /** @test */
    public function cadre_cannot_validate_n2(): void
    {
        $projet = $this->createProjet();
        $activite = $this->createActivite($projet);
        $tache = $this->createTache($activite, validationN1: true, validationN2: true, n1ValidatedAt: now());

        $this->assertFalse($this->gate->userCan($this->cadre, Permission::TACHES_VALIDATE_N2, $tache));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function createProjet(): Projet
    {
        return Projet::create([
            'workspace_id' => $this->workspace->id,
            'nom' => 'Test Projet',
            'description' => 'Test',
            'responsable_id' => $this->directeur->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonth(),
            'visibility' => 'team',
        ]);
    }

    private function createActivite(Projet $projet): Activite
    {
        return Activite::create([
            'projet_id' => $projet->id,
            'nom' => 'Test Activite',
            'description' => 'Test',
            'responsable_id' => $this->directeur->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonth(),
        ]);
    }

    private function createTache(
        Activite $activite,
        bool $validationN1 = false,
        bool $validationN2 = false,
        mixed $n1ValidatedAt = null,
    ): Tache {
        return Tache::create([
            'activite_id' => $activite->id,
            'titre' => 'Test Tache',
            'description' => 'Test',
            'echeance' => now()->addWeek(),
            'statut' => 'a_faire',
            'priorite' => 'moyenne',
            'validation_n1_required' => $validationN1,
            'validation_n2_required' => $validationN2,
            'validated_n1_at' => $n1ValidatedAt,
        ]);
    }
}
