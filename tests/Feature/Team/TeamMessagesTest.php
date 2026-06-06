<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\TeamMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre TeamMessageController : index, store, addReaction.
 */
class TeamMessagesTest extends TestCase
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

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function authenticated_user_can_list_team_messages(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/teams/{$this->team->uuid}/messages")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function authenticated_user_can_send_message(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/messages", [
                'content' => 'Bonjour à toute l\'équipe !',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('team_messages', [
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'content' => 'Bonjour à toute l\'équipe !',
        ]);
    }

    /** @test */
    public function store_validates_required_content(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/messages", [])
            ->assertUnprocessable();
    }

    /** @test */
    public function user_can_reply_to_message(): void
    {
        $parentMessage = TeamMessage::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'content' => 'Message parent',
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/{$this->team->uuid}/messages", [
                'content' => 'Réponse au message',
                'reply_to_id' => $parentMessage->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    // =========================================================================
    // ADD REACTION
    // =========================================================================

    /** @test */
    public function user_can_add_reaction_to_message(): void
    {
        $message = TeamMessage::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'content' => 'Message avec réaction',
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/teams/messages/{$message->uuid}/reactions", [
                'emoji' => '👍',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function add_reaction_validates_emoji(): void
    {
        $message = TeamMessage::create([
            'team_id' => $this->team->id,
            'user_id' => $this->owner->id,
            'content' => 'Message test',
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/teams/messages/{$message->uuid}/reactions", [])
            ->assertUnprocessable();
    }
}
