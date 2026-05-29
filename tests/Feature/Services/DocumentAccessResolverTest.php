<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use App\Services\DocumentAccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre DocumentAccessResolver : canView, canEdit, canDelete, canShare, canUpload
 * sur les différentes entités parentes (Projet, Activité, Tâche).
 */
class DocumentAccessResolverTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private DocumentAccessResolver $resolver;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->resolver = app(DocumentAccessResolver::class);

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);
    }

    private function makePrivateDocument(User $uploader): Document
    {
        return Document::factory()->forProjet($this->projet)->create([
            'user_id' => $uploader->id,
            'visibility' => 'private',
        ]);
    }

    // =========================================================================
    // canView
    // =========================================================================

    /** @test */
    public function uploader_can_view_own_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);

        $this->assertTrue($this->resolver->canView($this->owner, $doc));
    }

    /** @test */
    public function public_document_is_viewable_by_anyone(): void
    {
        $doc = Document::factory()->forProjet($this->projet)->public()->create([
            'user_id' => $this->owner->id,
        ]);

        $outsider = User::factory()->create();

        $this->assertTrue($this->resolver->canView($outsider, $doc));
    }

    /** @test */
    public function private_document_is_not_viewable_by_outsider(): void
    {
        $doc = $this->makePrivateDocument($this->owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->resolver->canView($outsider, $doc));
    }

    /** @test */
    public function super_admin_can_view_any_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $this->assertTrue($this->resolver->canView($superAdmin, $doc));
    }

    /** @test */
    public function projet_responsable_can_view_team_document(): void
    {
        $doc = Document::factory()->forProjet($this->projet)->create([
            'user_id' => User::factory()->create()->id,
            'visibility' => 'team',
        ]);

        $this->assertTrue($this->resolver->canView($this->owner, $doc));
    }

    // =========================================================================
    // canEdit / canDelete / canShare
    // =========================================================================

    /** @test */
    public function uploader_can_edit_own_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);

        $this->assertTrue($this->resolver->canEdit($this->owner, $doc));
    }

    /** @test */
    public function uploader_can_delete_own_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);

        $this->assertTrue($this->resolver->canDelete($this->owner, $doc));
    }

    /** @test */
    public function uploader_can_share_own_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);

        $this->assertTrue($this->resolver->canShare($this->owner, $doc));
    }

    /** @test */
    public function outsider_cannot_edit_private_document(): void
    {
        $doc = $this->makePrivateDocument($this->owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->resolver->canEdit($outsider, $doc));
    }

    // =========================================================================
    // canUpload
    // =========================================================================

    /** @test */
    public function projet_responsable_can_upload_to_projet(): void
    {
        $this->assertTrue(
            $this->resolver->canUpload($this->owner, Projet::class, $this->projet->id)
        );
    }

    /** @test */
    public function non_member_cannot_upload_to_projet(): void
    {
        $outsider = User::factory()->create();

        $this->assertFalse(
            $this->resolver->canUpload($outsider, Projet::class, $this->projet->id)
        );
    }

    /** @test */
    public function activite_responsable_can_upload_to_activite(): void
    {
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->assertTrue(
            $this->resolver->canUpload($this->owner, Activite::class, $activite->id)
        );
    }

    /** @test */
    public function tache_assignee_can_upload_to_tache(): void
    {
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $assignee = User::factory()->create();
        $roleId = Role::findByName('collaborateur', 'web')->id;
        $tache->assignees()->attach($assignee->id, ['can_edit' => true, 'can_complete' => true, 'role_id' => $roleId]);

        $this->assertTrue(
            $this->resolver->canUpload($assignee, Tache::class, $tache->id)
        );
    }
}
