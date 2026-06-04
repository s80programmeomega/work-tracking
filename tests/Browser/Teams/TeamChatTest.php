<?php

namespace Tests\Browser\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la messagerie d'équipe.
 *
 * Vérifie :
 *  - La page équipe se charge et l'onglet Discussion s'affiche
 *  - L'envoi d'un message l'ajoute à la liste
 *  - Les boutons modifier/supprimer apparaissent au survol (messages propres)
 */
class TeamChatTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_chat_tab_renders_for_team_member(): void
    {
        $owner = User::factory()->create(['is_super_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

        $this->browse(function (Browser $browser) use ($owner, $team) {
            $this->signInAs($browser, $owner)
                ->visit("/teams/{$team->uuid}")
                ->waitFor('[dusk="chat-tab"]', 8)
                ->assertVisible('[dusk="chat-tab"]');
        });
    }

    public function test_sending_message_appears_in_list(): void
    {
        $owner = User::factory()->create(['is_super_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

        $this->browse(function (Browser $browser) use ($owner, $team) {
            $this->signInAs($browser, $owner)
                ->visit("/teams/{$team->uuid}")
                ->waitFor('[dusk="chat-input"]', 8)
                ->type('@chat-input', 'Message de test automatisé')
                ->click('@chat-send-btn')
                ->waitFor('[dusk="chat-message"]', 5)
                ->assertSee('Message de test automatisé');
        });
    }

    public function test_own_message_shows_edit_delete_buttons_on_hover(): void
    {
        $owner = User::factory()->create(['is_super_admin' => true]);
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner->id, ['role' => 'owner', 'joined_at' => now()]);

        $this->browse(function (Browser $browser) use ($owner, $team) {
            $this->signInAs($browser, $owner)
                ->visit("/teams/{$team->uuid}")
                ->waitFor('[dusk="chat-input"]', 8)
                ->type('@chat-input', 'Message pour tester les actions')
                ->click('@chat-send-btn')
                ->waitFor('[dusk="chat-message"]', 5)
                ->mouseover('[dusk="chat-message"]')
                ->assertPresent('[dusk="message-edit-btn"]')
                ->assertPresent('[dusk="message-delete-btn"]');
        });
    }
}
