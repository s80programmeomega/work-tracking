<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la gestion des membres d'un workspace.
 */
class MemberManagementTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * Le propriétaire voit le bouton "Inviter un membre" dans l'onglet membres.
     */
    public function test_owner_sees_invite_member_button_on_members_tab(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner, $workspace) {
            $this->signInAs($browser, $owner)
                ->visit("/workspaces/{$workspace->id}")
                ->waitFor('[dusk="workspace-tab-members"]', 15)
                ->click('[dusk="workspace-tab-members"]')
                ->waitFor('[dusk="invite-member-btn"]', 10)
                ->assertVisible('@invite-member-btn');
        });
    }

    /**
     * Cliquer sur "Inviter un membre" ouvre le modal d'invitation.
     */
    public function test_clicking_invite_opens_invite_modal(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner, $workspace) {
            $this->signInAs($browser, $owner)
                ->visit("/workspaces/{$workspace->id}")
                ->waitFor('[dusk="workspace-tab-members"]', 15)
                ->click('[dusk="workspace-tab-members"]')
                ->waitFor('[dusk="invite-member-btn"]', 10)
                ->click('@invite-member-btn')
                ->waitFor('[dusk="invite-email-input"]', 10)
                ->assertVisible('@invite-email-input')
                ->assertVisible('@invite-submit-btn');
        });
    }

    /**
     * Un utilisateur sans droits de gestion ne voit pas le bouton d'invitation.
     */
    public function test_non_manager_does_not_see_invite_button(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($member->id, ['role_id' => $cadreRoleId]);

        $this->browse(function (Browser $browser) use ($member, $workspace) {
            $this->signInAs($browser, $member)
                ->visit("/workspaces/{$workspace->id}")
                ->waitFor('[dusk="workspace-tab-members"]', 15)
                ->click('[dusk="workspace-tab-members"]')
                ->pause(2000)
                ->assertMissing('@invite-member-btn');
        });
    }
}
