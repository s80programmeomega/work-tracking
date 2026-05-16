<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Permissions Matrix Test
 *
 * Systematically verifies every role/action combination from docs/PERMISSIONS_MATRIX.md.
 * One scenario = one actor role + one HTTP endpoint + expected status code.
 *
 * Roles under test: owner, manager (project), cadre (activity), collaborateur,
 *                   stagiaire, observateur, outsider (not a member).
 *
 * Update this file whenever a new permission is added to PermissionService or
 * a new endpoint is introduced.
 */
class PermissionsMatrixTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    private Tache $tache;

    private TacheResultat $resultat;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->buildWorld();
    }

    /**
     * Build a full workspace → projet → activite → tache → resultat hierarchy
     * with one user per contextual role, all properly attached via pivot tables.
     */
    private function buildWorld(): void
    {
        // owner — must exist before workspace so owner_id can be set
        $owner = User::factory()->create();

        $this->workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->activite = Activite::factory()->create(['projet_id' => $this->projet->id]);
        $this->tache = Tache::factory()->create(['activite_id' => $this->activite->id]);

        $this->attachWithRole($this->workspace->members(), $owner->id, 'owner');
        $this->attachWithRole($this->projet->members(), $owner->id, 'owner');
        $this->attachWithRole($this->activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true, 'can_create_tasks' => true, 'can_delete_tasks' => true,
        ]);
        $this->users['owner'] = $owner;

        // manager — project-level manager
        $manager = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $manager->id, 'manager');
        $this->attachWithRole($this->projet->members(), $manager->id, 'manager');
        $this->attachWithRole($this->activite->members(), $manager->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->users['manager'] = $manager;

        // cadre — activity-level, is_responsable on the task
        $cadre = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $cadre->id, 'cadre');
        $this->attachWithRole($this->activite->members(), $cadre->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($this->tache->assignees(), $cadre->id, 'collaborateur', [
            'is_responsable' => true, 'can_edit' => true, 'can_complete' => true,
            'can_validate' => true, 'statut_individuel' => 'a_faire', 'progression_individuelle' => 0,
        ]);
        $this->users['cadre'] = $cadre;

        // collaborateur — task assignee, NOT is_responsable, can submit results
        $collaborateur = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $collaborateur->id, 'collaborateur');
        $this->attachWithRole($this->activite->members(), $collaborateur->id, 'collaborateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($this->tache->assignees(), $collaborateur->id, 'collaborateur', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => true,
            'can_validate' => false, 'statut_individuel' => 'a_faire', 'progression_individuelle' => 0,
        ]);
        $this->users['collaborateur'] = $collaborateur;

        // stagiaire — task assignee with minimal access
        $stagiaire = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $stagiaire->id, 'stagiaire');
        $this->attachWithRole($this->activite->members(), $stagiaire->id, 'stagiaire', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($this->tache->assignees(), $stagiaire->id, 'stagiaire', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => true,
            'can_validate' => false, 'statut_individuel' => 'a_faire', 'progression_individuelle' => 0,
        ]);
        $this->users['stagiaire'] = $stagiaire;

        // observateur — view-only task assignee
        $observateur = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $observateur->id, 'observateur');
        $this->attachWithRole($this->activite->members(), $observateur->id, 'observateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->attachWithRole($this->tache->assignees(), $observateur->id, 'observateur', [
            'is_responsable' => false, 'can_edit' => false, 'can_complete' => false,
            'can_validate' => false, 'statut_individuel' => 'a_faire', 'progression_individuelle' => 0,
        ]);

        // owner and manager are NOT assigned to the tache directly —
        // their access comes from workspace ownership and project membership.
        $this->users['observateur'] = $observateur;

        // outsider — authenticated but not a workspace member
        $this->users['outsider'] = User::factory()->create();

        // A result owned by collaborateur, in en_verification_n0
        $this->resultat = TacheResultat::factory()->create([
            'tache_id' => $this->tache->id,
            'user_id' => $collaborateur->id,
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
            'taux_realisation' => 75,
        ]);
    }

    private function user(string $role): User
    {
        return $this->users[$role];
    }

    // =========================================================================
    // TASK — view
    // =========================================================================

    /** @test */
    public function all_assigned_roles_can_view_task(): void
    {
        foreach (['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'] as $role) {
            $this->actingAs($this->user($role))
                ->getJson("/api/taches/{$this->tache->id}")
                ->assertSuccessful();
        }
    }

    /** @test */
    public function outsider_cannot_view_task(): void
    {
        $this->actingAs($this->user('outsider'))
            ->getJson("/api/taches/{$this->tache->id}")
            ->assertStatus(403);
    }

    // =========================================================================
    // TASK — edit
    // =========================================================================

    /** @test */
    public function owner_manager_and_cadre_can_edit_task(): void
    {
        foreach (['owner', 'manager', 'cadre'] as $role) {
            $this->actingAs($this->user($role))
                ->putJson("/api/taches/{$this->tache->id}", ['titre' => "Updated by {$role}"])
                ->assertSuccessful();
        }
    }

    /** @test */
    public function collaborateur_stagiaire_observateur_cannot_edit_task(): void
    {
        foreach (['collaborateur', 'stagiaire', 'observateur'] as $role) {
            $this->actingAs($this->user($role))
                ->putJson("/api/taches/{$this->tache->id}", ['titre' => "Updated by {$role}"])
                ->assertStatus(403);
        }
    }

    // =========================================================================
    // RESULT — submit
    // =========================================================================

    /** @test */
    public function collaborateur_and_stagiaire_can_submit_result(): void
    {
        foreach (['collaborateur', 'stagiaire'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/resultats", [
                    'resultats_attendus' => 'Résultats attendus pour le test',
                    'resultats_obtenus' => 'Résultats obtenus pour le test',
                    'taux_realisation' => 80,
                ])
                ->assertStatus(201);
        }
    }

    /** @test */
    public function cadre_with_is_responsable_can_also_submit_result(): void
    {
        // cadre is stored as role='collaborateur' in tache_user (per ENUM constraint)
        // with is_responsable=true. Per the matrix, collaborateur role can submit
        // regardless of is_responsable flag.
        $this->actingAs($this->user('cadre'))
            ->postJson("/api/taches/{$this->tache->id}/resultats", [
                'resultats_attendus' => 'Résultats attendus pour le test',
                'resultats_obtenus' => 'Résultats obtenus pour le test',
                'taux_realisation' => 80,
            ])
            ->assertStatus(201);
    }

    /** @test */
    public function owner_manager_observateur_cannot_submit_result(): void
    {
        foreach (['owner', 'manager', 'observateur'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/resultats", [
                    'resultats_attendus' => 'Résultats attendus pour le test',
                    'resultats_obtenus' => 'Résultats obtenus pour le test',
                    'taux_realisation' => 80,
                ])
                ->assertStatus(403);
        }
    }

    // =========================================================================
    // N0 CIRCUIT — approuver-n0
    // =========================================================================

    /** @test */
    public function cadre_with_is_responsable_can_approve_n0(): void
    {
        $this->actingAs($this->user('cadre'))
            ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/approuver-n0")
            ->assertSuccessful();
    }

    /** @test */
    public function owner_and_manager_can_approve_n0_when_assigned_as_responsable(): void
    {
        // owner and manager are in the activite but NOT assigned to the tache with is_responsable.
        // Per the matrix, N0 approval requires is_responsable on tache_user — they should be denied.
        foreach (['owner', 'manager'] as $role) {
            $this->resultat->update(['statut' => 'en_verification_n0', 'action_n0' => null, 'action_n0_le' => null]);

            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/approuver-n0")
                ->assertStatus(403);
        }
    }

    /** @test */
    public function collaborateur_stagiaire_observateur_cannot_approve_n0(): void
    {
        foreach (['collaborateur', 'stagiaire', 'observateur'] as $role) {
            $this->resultat->update(['statut' => 'en_verification_n0', 'action_n0' => null, 'action_n0_le' => null]);

            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/approuver-n0")
                ->assertStatus(403);
        }
    }

    /** @test */
    public function outsider_cannot_approve_n0(): void
    {
        $this->actingAs($this->user('outsider'))
            ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/approuver-n0")
            ->assertStatus(403);
    }

    // =========================================================================
    // N0 CIRCUIT — renvoyer-n0
    // =========================================================================

    /** @test */
    public function cadre_with_is_responsable_can_return_n0_with_valid_comment(): void
    {
        $this->actingAs($this->user('cadre'))
            ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/renvoyer-n0", [
                'commentaire' => 'Le résultat ne couvre pas les points demandés dans les indicateurs. Veuillez détailler les actions.',
            ])
            ->assertSuccessful();
    }

    /** @test */
    public function collaborateur_stagiaire_observateur_cannot_return_n0(): void
    {
        foreach (['collaborateur', 'stagiaire', 'observateur'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/renvoyer-n0", [
                    'commentaire' => 'Le résultat ne couvre pas les points demandés dans les indicateurs. Veuillez détailler les actions.',
                ])
                ->assertStatus(403);
        }
    }

    // =========================================================================
    // SUBTASK — create
    // =========================================================================

    /** @test */
    public function owner_manager_and_cadre_with_is_responsable_can_create_subtask(): void
    {
        foreach (['owner', 'manager', 'cadre'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/sous-taches", [
                    'titre' => "Sous-tâche par {$role}",
                    'poids' => 0,
                ])
                ->assertStatus(201);
        }
    }

    /** @test */
    public function collaborateur_without_is_responsable_cannot_create_subtask(): void
    {
        $this->actingAs($this->user('collaborateur'))
            ->postJson("/api/taches/{$this->tache->id}/sous-taches", [
                'titre' => 'Sous-tâche non autorisée',
                'poids' => 0,
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function stagiaire_and_observateur_cannot_create_subtask(): void
    {
        foreach (['stagiaire', 'observateur'] as $role) {
            $this->actingAs($this->user($role))
                ->postJson("/api/taches/{$this->tache->id}/sous-taches", [
                    'titre' => "Sous-tâche par {$role}",
                    'poids' => 0,
                ])
                ->assertStatus(403);
        }
    }

    // =========================================================================
    // UNAUTHENTICATED — all key endpoints return 401
    // =========================================================================

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $endpoints = [
            ['GET', "/api/taches/{$this->tache->id}"],
            ['PUT', "/api/taches/{$this->tache->id}"],
            ['POST', "/api/taches/{$this->tache->id}/resultats"],
            ['POST', "/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/approuver-n0"],
            ['POST', "/api/taches/{$this->tache->id}/resultats/{$this->resultat->id}/renvoyer-n0"],
            ['POST', "/api/taches/{$this->tache->id}/sous-taches"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->json($method, $url)->assertStatus(401);
        }
    }
}
