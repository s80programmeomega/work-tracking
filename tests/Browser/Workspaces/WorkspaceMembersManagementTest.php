<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Dusk tests — Workspace Members Management (/workspace/members).
 *
 * Vérifie :
 *  - Le propriétaire voit l'entrée "Gestion des membres" dans la sidebar
 *  - Un membre non-propriétaire ne voit pas l'entrée
 *  - Le propriétaire peut accéder à /workspace/members
 *  - Un non-propriétaire voit le message d'accès refusé
 *  - Le propriétaire peut bannir un membre (badge "Banni" apparaît)
 *  - Le propriétaire peut lever un bannissement (badge revient à "Actif")
 */
class WorkspaceMembersManagementTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'workspace-members';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function makeOwnerWithWorkspace(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $workspace->addMember($owner, 'owner');

        return [$owner, $workspace];
    }

    private function addMember(Workspace $workspace, string $role = 'collaborateur'): User
    {
        $user = User::factory()->create();
        $workspace->addMember($user, $role);

        return $user;
    }

    // =========================================================================
    // Sidebar visibility
    // =========================================================================

    public function test_owner_sees_workspace_members_sidebar_entry(): void
    {
        [$owner] = $this->makeOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner);

            $browser->screenshot("{$this->screenshotDir}/01-sidebar-owner");

            $browser->assertSee('Gestion des membres');
        });
    }

    public function test_non_owner_does_not_see_workspace_members_sidebar_entry(): void
    {
        [$_unused, $workspace] = $this->makeOwnerWithWorkspace();
        $member = $this->addMember($workspace, 'manager');
        $member->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($member) {
            $this->signInAs($browser, $member);

            $browser->screenshot("{$this->screenshotDir}/02-sidebar-non-owner");

            $browser->assertDontSee('Gestion des membres');
        });
    }

    // =========================================================================
    // Page access
    // =========================================================================

    public function test_owner_can_access_workspace_members_page(): void
    {
        [$owner] = $this->makeOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner);

            $browser->visit('/workspace/members')
                ->waitForText('Gestion des membres', 10)
                ->screenshot("{$this->screenshotDir}/03-page-owner");

            $browser->assertSee('Gestion des membres');
        });
    }

    public function test_non_owner_sees_access_denied_on_members_page(): void
    {
        [$_unused, $workspace] = $this->makeOwnerWithWorkspace();
        $member = $this->addMember($workspace, 'manager');
        $member->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($member) {
            $this->signInAs($browser, $member);

            $browser->visit('/workspace/members')
                ->waitForText('Accès restreint', 10)
                ->screenshot("{$this->screenshotDir}/04-page-access-denied");

            $browser->assertSee('Accès restreint');
        });
    }

    // =========================================================================
    // Ban flow
    // =========================================================================

    public function test_owner_can_ban_a_member_via_ui(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $target = $this->addMember($workspace);

        $this->browse(function (Browser $browser) use ($owner, $target) {
            $this->signInAs($browser, $owner);

            $browser->visit('/workspace/members')
                ->waitForText($target->nom, 15)
                ->screenshot("{$this->screenshotDir}/05-before-ban");

            // Clic sur "Bannir" pour ce membre
            $browser->with("tr:contains('{$target->email}')", function ($row) {
                $row->press('Bannir');
            });

            // Confirmation dans la modale
            $browser->waitForText('Confirmer le bannissement', 10)
                ->screenshot("{$this->screenshotDir}/06-ban-modal");

            $browser->press('Confirmer le bannissement')
                ->waitForText('Banni', 10)
                ->screenshot("{$this->screenshotDir}/07-after-ban");

            $browser->assertSee('Banni');
        });

        // Vérification en DB
        $pivot = DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $target->id)
            ->first();

        $this->assertNotNull($pivot->banned_at);
    }

    // =========================================================================
    // Unban flow
    // =========================================================================

    public function test_owner_can_unban_a_banned_member_via_ui(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $target = $this->addMember($workspace);

        // Ban en DB directement pour ne pas dépendre du test précédent
        DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $target->id)
            ->update([
                'banned_at' => now(),
                'banned_by' => $owner->id,
                'ban_reason' => 'Test préalable',
            ]);

        $this->browse(function (Browser $browser) use ($owner, $target) {
            $this->signInAs($browser, $owner);

            $browser->visit('/workspace/members')
                ->waitForText($target->nom, 15)
                ->screenshot("{$this->screenshotDir}/08-before-unban");

            $browser->with("tr:contains('{$target->email}')", function ($row) {
                $row->press('Lever le ban');
            });

            $browser->waitForText('Actif', 10)
                ->screenshot("{$this->screenshotDir}/09-after-unban");

            $browser->assertSee('Actif');
        });

        $pivot = DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $target->id)
            ->first();

        $this->assertNull($pivot->banned_at);
    }
}
