<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_submit_ticket(): void
    {
        Notification::fake();

        $user = User::factory()->create(['is_active' => true]);
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($user)->postJson('/api/support', [
            'category' => 'bug',
            'subject' => 'Problème de connexion',
            'message' => 'Je ne peux pas me connecter depuis ce matin.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'category' => 'bug',
            'status' => 'open',
        ]);

        Notification::assertSentTo($superAdmin, NewSupportTicketNotification::class);
    }

    /** @test */
    public function guest_cannot_submit_ticket(): void
    {
        $this->postJson('/api/support', [
            'category' => 'bug',
            'subject' => 'Test',
            'message' => 'Test message.',
        ])->assertStatus(401);
    }

    /** @test */
    public function ticket_submission_validates_required_fields(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->postJson('/api/support', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'subject', 'message']);
    }

    /** @test */
    public function ticket_submission_rejects_invalid_category(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->postJson('/api/support', [
            'category' => 'invalid',
            'subject' => 'Test',
            'message' => 'Test message.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    /** @test */
    public function user_can_list_own_tickets(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);

        SupportTicket::factory()->count(3)->create(['user_id' => $user->id]);
        SupportTicket::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->getJson('/api/support');

        $response->assertOk();
        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function super_admin_can_list_all_tickets(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);
        SupportTicket::factory()->count(5)->create();

        $this->actingAs($admin)->getJson('/api/admin/support')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_admin_cannot_access_admin_tickets(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->getJson('/api/admin/support')
            ->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_reply_and_update_ticket_status(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_super_admin' => true, 'is_active' => true]);
        $requester = User::factory()->create(['is_active' => true]);
        $ticket = SupportTicket::factory()->create(['user_id' => $requester->id, 'status' => 'open']);

        $this->actingAs($admin)->postJson("/api/admin/support/{$ticket->id}/reply", [
            'body' => 'Nous avons bien reçu votre ticket et le traitons.',
            'status' => 'in_progress',
        ])->assertOk();

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);
        $this->assertNotNull($ticket->first_responded_at);
        $this->assertDatabaseHas('support_ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'is_admin_reply' => true,
        ]);
        Notification::assertSentTo($requester, SupportTicketStatusChangedNotification::class);
    }

    /** @test */
    public function non_admin_cannot_reply_to_ticket(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $ticket = SupportTicket::factory()->create();

        $this->actingAs($user)->postJson("/api/admin/support/{$ticket->id}/reply", [
            'body' => 'Tentative de réponse non autorisée.',
            'status' => 'resolved',
        ])->assertStatus(403);
    }
}
