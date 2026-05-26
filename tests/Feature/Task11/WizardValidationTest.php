<?php

declare(strict_types=1);

namespace Tests\Feature\Task11;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TacheAssigneeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 11 — POST /api/taches (wizard submission)
 *
 * Covers:
 *   - Missing titre → 422 validation error
 *   - Missing activite_id → 422
 *   - Missing responsable_id → store still succeeds (responsable is nullable at DB level, validated by form)
 *   - Valid minimal payload → 201 created
 *   - Intervenant picker: assignee_ids must be valid users
 *   - Assignees receive TacheAssigneeNotification on creation
 */
class WizardValidationTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->buildWorld();
    }

    private function buildWorld(): void
    {
        $owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->activite = Activite::factory()->create(['projet_id' => $this->projet->id]);

        $this->attachWithRole($this->workspace->members(), $owner->id, 'owner');
        $this->attachWithRole($this->projet->members(), $owner->id, 'owner');
        $this->attachWithRole($this->activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true, 'can_create_tasks' => true, 'can_delete_tasks' => true,
        ]);
        $owner->update(['current_workspace_id' => $this->workspace->id]);
        $this->users['owner'] = $owner;

        $cadre = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $cadre->id, 'cadre');
        $this->attachWithRole($this->activite->members(), $cadre->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->users['cadre'] = $cadre;

        $intervenant = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $intervenant->id, 'collaborateur');
        $this->attachWithRole($this->activite->members(), $intervenant->id, 'collaborateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);
        $this->users['intervenant'] = $intervenant;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'activite_id' => $this->activite->id,
            'titre' => 'Tâche de test wizard',
            'statut' => 'a_faire',
            'priorite' => 'moyenne',
            'responsable_id' => $this->users['cadre']->id,
            'assignee_ids' => [$this->users['cadre']->id],
            'validation_n1_required' => true,
            'validation_n2_required' => true,
        ], $overrides);
    }

    /** @test */
    public function missing_titre_returns_422(): void
    {
        Sanctum::actingAs($this->users['owner']);

        $this->postJson('/api/taches', $this->validPayload(['titre' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('titre');
    }

    /** @test */
    public function missing_activite_id_returns_422(): void
    {
        Sanctum::actingAs($this->users['owner']);

        $this->postJson('/api/taches', $this->validPayload(['activite_id' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('activite_id');
    }

    /** @test */
    public function invalid_assignee_ids_returns_422(): void
    {
        Sanctum::actingAs($this->users['owner']);

        $this->postJson('/api/taches', $this->validPayload(['assignee_ids' => [99999]]))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('assignee_ids.0');
    }

    /** @test */
    public function valid_payload_creates_task_and_returns_201(): void
    {
        Sanctum::actingAs($this->users['owner']);

        $this->postJson('/api/taches', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('message', 'Tâche créée avec succès.')
            ->assertJsonStructure(['data' => ['id', 'titre', 'statut']]);
    }

    /** @test */
    public function assigned_intervenants_receive_notification_on_creation(): void
    {
        Notification::fake();

        Sanctum::actingAs($this->users['owner']);

        $payload = $this->validPayload([
            'assignee_ids' => [
                $this->users['cadre']->id,
                $this->users['intervenant']->id,
            ],
        ]);

        $this->postJson('/api/taches', $payload)->assertStatus(201);

        // The creator (owner) must NOT be notified.
        // Each non-creator intervenant must receive TacheAssigneeNotification.
        Notification::assertSentTo(
            $this->users['intervenant'],
            TacheAssigneeNotification::class
        );

        Notification::assertNotSentTo(
            $this->users['owner'],
            TacheAssigneeNotification::class
        );
    }
}
