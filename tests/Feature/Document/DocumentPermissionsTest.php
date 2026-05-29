<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de permissions sur un document :
 * grantPermission, revokePermission, shareWithUsers, listPermissions.
 */
class DocumentPermissionsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);

        $projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->document = Document::factory()->forProjet($projet)->create([
            'user_id' => $this->owner->id,
            'visibility' => 'private',
        ]);
    }

    // =========================================================================
    // GRANT PERMISSION
    // =========================================================================

    /** @test */
    public function owner_can_grant_permission_to_user(): void
    {
        Notification::fake();

        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/documents/{$this->document->id}/permissions/grant", [
                'user_id' => $targetUser->id,
                'can_view' => true,
                'can_download' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Permission accordée avec succès');

        $this->assertDatabaseHas('document_permissions', [
            'document_id' => $this->document->id,
            'permissionable_type' => User::class,
            'permissionable_id' => $targetUser->id,
            'can_view' => true,
        ]);
    }

    /** @test */
    public function non_owner_cannot_grant_permission(): void
    {
        $outsider = User::factory()->create();
        $targetUser = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson("/api/documents/{$this->document->id}/permissions/grant", [
                'user_id' => $targetUser->id,
                'can_view' => true,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // REVOKE PERMISSION
    // =========================================================================

    /** @test */
    public function owner_can_revoke_permission(): void
    {
        Notification::fake();

        $targetUser = User::factory()->create();

        // Grant first
        $this->actingAs($this->owner)
            ->postJson("/api/documents/{$this->document->id}/permissions/grant", [
                'user_id' => $targetUser->id,
                'can_view' => true,
            ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/documents/{$this->document->id}/permissions/revoke", [
                'user_id' => $targetUser->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Permission révoquée avec succès');
    }

    // =========================================================================
    // SHARE WITH USERS
    // =========================================================================

    /** @test */
    public function owner_can_share_document_with_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/documents/{$this->document->id}/share", [
                'user_ids' => [$userA->id, $userB->id],
                'permissions' => ['can_view' => true, 'can_download' => true],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    // =========================================================================
    // LIST PERMISSIONS
    // =========================================================================

    /** @test */
    public function owner_can_list_document_permissions(): void
    {
        Notification::fake();

        $targetUser = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson("/api/documents/{$this->document->id}/permissions/grant", [
                'user_id' => $targetUser->id,
                'can_view' => true,
            ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/{$this->document->id}/permissions");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_owner_cannot_list_permissions(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/documents/{$this->document->id}/permissions")
            ->assertForbidden();
    }
}
