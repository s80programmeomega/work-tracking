<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de requête sur les documents :
 * recent, myDocuments, sharedWithMe, workspaceDocuments, search, workspaceStats.
 */
class DocumentQueriesTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);
    }

    private function makeDocument(string $visibility = 'private'): Document
    {
        return Document::factory()->forProjet($this->projet)->create([
            'user_id' => $this->owner->id,
            'visibility' => $visibility,
        ]);
    }

    // =========================================================================
    // RECENT
    // =========================================================================

    /** @test */
    public function user_can_get_recent_documents(): void
    {
        $this->makeDocument('public');

        $response = $this->actingAs($this->owner)
            ->getJson('/api/documents/recent');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // MY DOCUMENTS
    // =========================================================================

    /** @test */
    public function user_can_get_my_documents(): void
    {
        $this->makeDocument();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/documents/my-documents');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // SHARED WITH ME
    // =========================================================================

    /** @test */
    public function user_can_get_shared_with_me_documents(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/documents/shared-with-me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // WORKSPACE DOCUMENTS
    // =========================================================================

    /** @test */
    public function workspace_owner_can_list_workspace_documents(): void
    {
        $this->makeDocument('team');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/workspace/{$this->workspace->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function non_admin_member_cannot_list_workspace_documents(): void
    {
        $member = User::factory()->create();
        $this->workspace->addMember($member, 'collaborateur');

        $this->actingAs($member)
            ->getJson("/api/documents/workspace/{$this->workspace->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // WORKSPACE STATS
    // =========================================================================

    /** @test */
    public function workspace_owner_can_view_workspace_document_stats(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/workspace/{$this->workspace->id}/stats");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // SEARCH
    // =========================================================================

    /** @test */
    public function user_can_search_documents(): void
    {
        Document::factory()->forProjet($this->projet)->create([
            'user_id' => $this->owner->id,
            'nom' => 'rapport-annuel.pdf',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/documents/search?query=rapport');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }
}
