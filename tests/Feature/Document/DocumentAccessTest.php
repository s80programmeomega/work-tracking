<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Phase 1 — accès aux documents sans colonne `visibility`.
 * L'accès repose sur les enregistrements `document_permissions`
 * et les politiques de rôle contextuel.
 */
class DocumentAccessTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($this->projet->members(), $this->owner->id, 'manager');

        $this->document = Document::factory()->forProjet($this->projet)->create([
            'user_id' => $this->owner->id,
        ]);
    }

    private function grantPermission(User $user, array $flags = []): DocumentPermission
    {
        return DocumentPermission::create(array_merge([
            'document_id' => $this->document->id,
            'permissionable_type' => User::class,
            'permissionable_id' => $user->id,
            'can_view' => true,
            'can_download' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_share' => false,
        ], $flags));
    }

    // =========================================================================
    // COLONNE VISIBILITY ABSENTE
    // =========================================================================

    /** @test */
    public function documents_table_has_no_visibility_column(): void
    {
        $columns = Schema::getColumnListing('documents');
        $this->assertNotContains('visibility', $columns);
    }

    // =========================================================================
    // UPLOADER (owner) PEUT VOIR SON DOCUMENT
    // =========================================================================

    /** @test */
    public function document_uploader_can_view_document(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertOk();
    }

    // =========================================================================
    // MANAGER PEUT VOIR TOUT DOCUMENT DU WORKSPACE
    // =========================================================================

    /** @test */
    public function manager_can_view_any_document(): void
    {
        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($manager, 'manager');

        $this->actingAs($manager)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertOk();
    }

    // =========================================================================
    // OUTSIDER (hors workspace) NE PEUT PAS VOIR
    // =========================================================================

    /** @test */
    public function outsider_not_in_workspace_cannot_view_document(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // PERMISSION EXPLICITE CAN_VIEW DONNE ACCÈS
    // =========================================================================

    /** @test */
    public function user_with_explicit_can_view_permission_can_view_document(): void
    {
        $collab = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($collab, 'collaborateur');

        $this->grantPermission($collab, ['can_view' => true]);

        $this->actingAs($collab)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertOk();
    }

    // =========================================================================
    // EXPIRED PERMISSION NE DONNE PAS ACCÈS
    // =========================================================================

    /** @test */
    public function expired_permission_does_not_grant_view_access(): void
    {
        // Créer un utilisateur extérieur au workspace (pas de rôle workspace qui donne documents.view)
        $outsider = User::factory()->create();

        $this->grantPermission($outsider, [
            'can_view' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($outsider)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // PERMISSION CAN_DOWNLOAD CONTRÔLE LE TÉLÉCHARGEMENT
    // =========================================================================

    /** @test */
    public function user_with_can_download_can_download_document(): void
    {
        $collab = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($collab, 'collaborateur');

        $this->grantPermission($collab, ['can_view' => true, 'can_download' => true]);

        // Le fichier n'existe pas sur disque → 404 côté stockage,
        // mais ce qui importe c'est que la policy ne bloque pas (403).
        $response = $this->actingAs($collab)
            ->getJson("/api/documents/{$this->document->id}/download");

        $this->assertNotEquals(403, $response->status(), 'La policy ne doit pas bloquer un utilisateur avec can_download=true');
    }

    /** @test */
    public function outsider_without_can_download_cannot_download(): void
    {
        $outsider = User::factory()->create();

        $this->grantPermission($outsider, ['can_view' => true, 'can_download' => false]);

        $this->actingAs($outsider)
            ->getJson("/api/documents/{$this->document->id}/download")
            ->assertForbidden();
    }

    // =========================================================================
    // DOCUMENT RESOURCE NE CONTIENT PAS DE CHAMP VISIBILITY
    // =========================================================================

    /** @test */
    public function document_resource_has_no_visibility_field(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/{$this->document->id}")
            ->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('visibility', $data);
    }
}
