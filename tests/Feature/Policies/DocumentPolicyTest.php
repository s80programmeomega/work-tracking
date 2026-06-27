<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que DocumentPolicy applique correctement :
 * - le raccourci uploader (uploaded_by === user.id)
 * - le raccourci visibilité publique
 * - la délégation à ContextualPermissionGate pour les cas contextuels
 * pour les méthodes view, update, delete et share.
 */
class DocumentPolicyTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function makeDocument(User $uploader, string $visibility = 'team'): Document
    {
        return Document::create([
            'workspace_id' => $this->workspace->id,
            'documentable_type' => Projet::class,
            'documentable_id' => $this->projet->id,
            'nom' => 'test-doc.pdf',
            'nom_stockage' => 'test-doc-'.Str::uuid().'.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'taille' => 1024,
            'chemin' => 'documents/test.pdf',
            'disk' => 'local',
            'user_id' => $uploader->id,
            'visibility' => $visibility,
        ]);
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function uploader_can_always_view_their_document(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $document = $this->makeDocument($uploader, 'team');

        // raccourci uploaded_by — aucune vérification contextuelle
        $this->assertTrue($uploader->can('view', $document));
    }

    /** @test */
    public function public_document_is_viewable_by_anyone(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $document = $this->makeDocument($uploader, 'public');
        $outsider = User::factory()->create();

        // raccourci visibilité publique
        $this->assertTrue($outsider->can('view', $document));
    }

    /** @test */
    public function workspace_member_with_documents_view_can_view_team_document(): void
    {
        $uploader = $this->makeWsMember('manager');
        $document = $this->makeDocument($uploader, 'team');

        // cadre a documents.view — délégation à ContextualPermissionGate sur le projet
        $cadre = $this->makeWsMember('cadre');
        $this->assertTrue($cadre->can('view', $document));
    }

    /** @test */
    public function user_without_workspace_membership_cannot_view_team_document(): void
    {
        $uploader = $this->makeWsMember('manager');
        $document = $this->makeDocument($uploader, 'team');
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('view', $document));
    }

    // =========================================================================
    // UPDATE (ownership-only)
    // =========================================================================

    /** @test */
    public function uploader_can_update_their_document(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $document = $this->makeDocument($uploader);

        $this->assertTrue($uploader->can('update', $document));
    }

    /** @test */
    public function non_uploader_cannot_update_document_even_as_manager(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $manager = $this->makeWsMember('manager');
        $document = $this->makeDocument($uploader);

        // update est réservé à l'uploader — pas de délégation contextuelle
        $this->assertFalse($manager->can('update', $document));
    }

    // =========================================================================
    // DELETE (same ownership rule as update)
    // =========================================================================

    /** @test */
    public function uploader_can_delete_their_document(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $document = $this->makeDocument($uploader);

        $this->assertTrue($uploader->can('delete', $document));
    }

    /** @test */
    public function non_uploader_cannot_delete_document(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $manager = $this->makeWsMember('manager');
        $document = $this->makeDocument($uploader);

        $this->assertFalse($manager->can('delete', $document));
    }

    // =========================================================================
    // SHARE
    // =========================================================================

    /** @test */
    public function uploader_can_share_their_document(): void
    {
        $uploader = $this->makeWsMember('collaborateur');
        $document = $this->makeDocument($uploader);

        // raccourci uploader pour share aussi
        $this->assertTrue($uploader->can('share', $document));
    }

    /** @test */
    public function manager_workspace_member_can_share_document_via_contextual_gate(): void
    {
        $uploader = $this->makeWsMember('cadre');
        $manager = $this->makeWsMember('manager');
        $document = $this->makeDocument($uploader);

        // manager a documents.share via ContextualPermissionGate sur le projet
        $this->assertTrue($manager->can('share', $document));
    }

    /** @test */
    public function observateur_cannot_share_document(): void
    {
        $uploader = $this->makeWsMember('cadre');
        $observateur = $this->makeWsMember('observateur');
        $document = $this->makeDocument($uploader);

        // observateur n'a pas documents.share
        $this->assertFalse($observateur->can('share', $document));
    }

    // =========================================================================
    // EXPLICIT DocumentPermission overrides
    // =========================================================================

    private function grantExplicit(User $user, Document $document, array $flags): DocumentPermission
    {
        return DocumentPermission::create(array_merge([
            'document_id' => $document->id,
            'permissionable_type' => User::class,
            'permissionable_id' => $user->id,
            'can_view' => false,
            'can_download' => false,
            'can_edit' => false,
            'can_delete' => false,
            'can_share' => false,
        ], $flags));
    }

    /** @test */
    public function user_with_explicit_can_share_permission_can_share(): void
    {
        $sharer = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $this->grantExplicit($sharer, $document, ['can_share' => true]);

        $this->assertTrue($sharer->can('share', $document));
    }

    /** @test */
    public function user_without_can_share_permission_cannot_share(): void
    {
        $user = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $this->grantExplicit($user, $document, ['can_view' => true, 'can_share' => false]);

        $this->assertFalse($user->can('share', $document));
    }

    /** @test */
    public function user_with_explicit_can_edit_permission_can_update(): void
    {
        $editor = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $this->grantExplicit($editor, $document, ['can_edit' => true]);

        $this->assertTrue($editor->can('update', $document));
    }

    /** @test */
    public function user_with_explicit_can_delete_permission_can_delete(): void
    {
        $deleter = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $this->grantExplicit($deleter, $document, ['can_delete' => true]);

        $this->assertTrue($deleter->can('delete', $document));
    }

    /** @test */
    public function user_with_explicit_can_download_permission_can_download(): void
    {
        $downloader = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $this->grantExplicit($downloader, $document, ['can_download' => true]);

        $this->assertTrue($downloader->can('download', $document));
    }

    /** @test */
    public function expired_explicit_permission_does_not_grant_access(): void
    {
        $user = User::factory()->create();
        $uploader = $this->makeWsMember('cadre');
        $document = $this->makeDocument($uploader);

        $perm = $this->grantExplicit($user, $document, ['can_share' => true]);
        $perm->update(['expires_at' => now()->subDay()]);

        $this->assertFalse($user->can('share', $document));
    }
}
