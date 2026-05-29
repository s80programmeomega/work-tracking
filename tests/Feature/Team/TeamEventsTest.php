<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamEventController : index, store, show, update, destroy.
 */
class TeamEventsTest extends TestCase
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

    private function makeEvent(): TeamEvent
    {
        $event = TeamEvent::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'title' => 'Réunion hebdomadaire',
            'type' => 'meeting',
            'start_date' => now()->addDay(),
        ]);
        $event->attendees()->attach($this->owner->id);

        return $event;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function authenticated_user_can_list_team_events(): void
    {
        $this->makeEvent();

        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/events")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['events']);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_create_event(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/events", [
                'title' => 'Sprint review',
                'type' => 'meeting',
                'start_date' => now()->addDays(3)->toDateTimeString(),
                'end_date' => now()->addDays(3)->addHours(2)->toDateTimeString(),
                'location' => 'Salle de conférence A',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Événement créé avec succès');

        $this->assertDatabaseHas('team_events', [
            'team_id' => $this->team->id,
            'title' => 'Sprint review',
        ]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/events", [])
            ->assertUnprocessable();
    }

    /** @test */
    public function store_validates_event_type(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/events", [
                'title' => 'Test',
                'type' => 'invalid_type',
                'start_date' => now()->addDay()->toDateTimeString(),
            ])
            ->assertUnprocessable();
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function authenticated_user_can_view_event(): void
    {
        $event = $this->makeEvent();

        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['event']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_update_event(): void
    {
        Notification::fake();
        $event = $this->makeEvent();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/teams/{$this->team->uuid}/events/{$event->id}", [
                'title' => 'Titre mis à jour',
                'type' => 'deadline',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_events', [
            'id' => $event->id,
            'title' => 'Titre mis à jour',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function authenticated_user_can_delete_event(): void
    {
        $event = $this->makeEvent();

        $this->actingAs($this->owner)
            ->deleteJson("/api/teams/{$this->team->uuid}/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('team_events', ['id' => $event->id]);
    }
}
