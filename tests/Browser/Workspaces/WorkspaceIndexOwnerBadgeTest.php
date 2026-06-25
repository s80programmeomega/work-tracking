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
 * Tests Dusk pour le badge "Propriétaire" sur la page /workspaces.
 */
class WorkspaceIndexOwnerBadgeTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * Le propriétaire voit le badge "Propriétaire" sur sa carte workspace.
     */
    public function test_owner_badge_visible_on_owned_workspace_card(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/workspaces')
                ->waitFor('[dusk="owner-badge"]', 15)
                ->assertVisible('[dusk="owner-badge"]');
        });
    }

    /**
     * Un membre non propriétaire ne voit pas le badge "Propriétaire" sur la carte.
     */
    public function test_owner_badge_not_visible_on_member_workspace_card(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $member->update(['current_workspace_id' => $workspace->id]);

        $managerRole = Role::findByName('manager');
        $workspace->members()->attach($member->id, ['role_id' => $managerRole->id]);

        $this->browse(function (Browser $browser) use ($member) {
            $this->signInAs($browser, $member)
                ->visit('/workspaces')
                ->waitFor('.stagger-item', 15)
                ->assertMissing('[dusk="owner-badge"]');
        });
    }
}
