<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamAnnouncementController : index, store, update, destroy.
 */
class TeamAnnouncementsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create(['is_super_admin' => true]);
        $this->team = Team::factory()->create(['owner_id' => $this->owner->id]);
    }

    private function makeAnnouncement(): TeamAnnouncement
    {
        return TeamAnnouncement::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'title' => 'Annonce importante',
            'content' => 'Contenu de l\'annonce',
            'priority' => 'normal',
            'published_at' => now()->subMinute(),
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function authenticated_user_can_list_announcements(): void
    {
        $this->makeAnnouncement();

        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/announcements")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['announcements']);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_create_announcement(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/announcements", [
                'title' => 'Réunion d\'équipe',
                'content' => 'Réunion lundi à 9h',
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_announcements', [
            'team_id' => $this->team->id,
            'title' => 'Réunion d\'équipe',
        ]);
    }

    /** @test */
    public function store_validates_required_title_and_content(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/announcements", [])
            ->assertUnprocessable();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_update_announcement(): void
    {
        $announcement = $this->makeAnnouncement();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}/announcements/{$announcement->id}", [
                'title' => 'Titre mis à jour',
                'content' => 'Nouveau contenu',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_announcements', [
            'id' => $announcement->id,
            'title' => 'Titre mis à jour',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function authenticated_user_can_delete_announcement(): void
    {
        $announcement = $this->makeAnnouncement();

        $this->actingAs($this->owner)
            ->deleteJson("/api/teams/{$this->team->uuid}/announcements/{$announcement->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('team_announcements', ['id' => $announcement->id]);
    }
}
