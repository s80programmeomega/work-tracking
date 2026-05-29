<?php

declare(strict_types=1);

namespace Tests\Feature\Activite;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre la gestion des membres d'activité :
 * membres (list), addMember, updateMember, removeMember.
 */
class ActiviteMembersTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);
        $this->activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function makeProjectMember(string $wsRole = 'collaborateur'): User
    {
        $user = $this->makeWsMember($wsRole);
        $this->attachWithRole($this->projet->members(), $user->id, $wsRole);

        return $user;
    }

    private function addActiviteMember(User $user, string $role = 'collaborateur'): void
    {
        $this->attachWithRole($this->activite->membres(), $user->id, $role);
    }

    private function membersUrl(): string
    {
        return "/api/activites/{$this->activite->id}/members";
    }

    // =========================================================================
    // LIST MEMBRES
    // =========================================================================

    /** @test */
    public function project_member_can_list_activite_membres(): void
    {
        $member = $this->makeWsMember('collaborateur');
        $this->addActiviteMember($member);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/activites/{$this->activite->id}/membres");

        $response->assertOk()
            ->assertJsonIsArray();
    }

    // =========================================================================
    // ADD MEMBER
    // =========================================================================

    /** @test */
    public function responsable_can_add_project_member_to_activite(): void
    {
        Notification::fake();

        $projectMember = $this->makeProjectMember('collaborateur');

        $this->actingAs($this->owner)
            ->postJson($this->membersUrl(), [
                'user_id' => $projectMember->id,
                'role' => 'collaborateur',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Membre ajouté avec succès. Une notification a été envoyée.');

        $this->assertTrue(
            $this->activite->membres()->where('user_id', $projectMember->id)->exists()
        );
    }

    /** @test */
    public function adding_non_project_member_to_activite_returns_422(): void
    {
        Notification::fake();

        $wsOnlyMember = $this->makeWsMember('collaborateur');

        $this->actingAs($this->owner)
            ->postJson($this->membersUrl(), [
                'user_id' => $wsOnlyMember->id,
                'role' => 'collaborateur',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Le membre doit d\'abord être ajouté au projet');
    }

    /** @test */
    public function adding_already_existing_activite_member_returns_422(): void
    {
        Notification::fake();

        $projectMember = $this->makeProjectMember('collaborateur');
        $this->addActiviteMember($projectMember, 'collaborateur');

        $this->actingAs($this->owner)
            ->postJson($this->membersUrl(), [
                'user_id' => $projectMember->id,
                'role' => 'collaborateur',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ce membre est déjà assigné à l\'activité');
    }

    // =========================================================================
    // UPDATE MEMBER
    // =========================================================================

    /** @test */
    public function responsable_can_update_activite_member(): void
    {
        Notification::fake();

        $projectMember = $this->makeProjectMember('collaborateur');
        $this->addActiviteMember($projectMember, 'collaborateur');

        $this->actingAs($this->owner)
            ->putJson("{$this->membersUrl()}/{$projectMember->id}", ['role' => 'cadre'])
            ->assertOk()
            ->assertJsonPath('message', 'Permissions mises à jour. Une notification a été envoyée.');
    }

    /** @test */
    public function updating_non_activite_member_returns_404(): void
    {
        $projectMember = $this->makeProjectMember('collaborateur');

        $this->actingAs($this->owner)
            ->putJson("{$this->membersUrl()}/{$projectMember->id}", ['role' => 'cadre'])
            ->assertNotFound();
    }

    // =========================================================================
    // REMOVE MEMBER
    // =========================================================================

    /** @test */
    public function responsable_can_remove_activite_member(): void
    {
        $projectMember = $this->makeProjectMember('collaborateur');
        $this->addActiviteMember($projectMember, 'collaborateur');

        $this->actingAs($this->owner)
            ->deleteJson("{$this->membersUrl()}/{$projectMember->id}")
            ->assertOk();

        $this->assertFalse(
            $this->activite->membres()->where('user_id', $projectMember->id)->exists()
        );
    }
}
