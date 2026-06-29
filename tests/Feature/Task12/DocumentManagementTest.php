<?php

declare(strict_types=1);

namespace Tests\Feature\Task12;

use App\Models\Document;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\DocumentSharedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 12 — Document Management
 *
 * Covers:
 *   - Project member can view documents → 200
 *   - Non-member document not exposed
 *   - Share-by-email sends DocumentSharedNotification
 *   - Non-owner blocked from workspace documents endpoint → 403
 *   - Owner can access workspace documents endpoint → 200
 */
class DocumentManagementTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private Projet $projet;

    private User $owner;

    private User $outsider;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->buildWorld();
    }

    private function buildWorld(): void
    {
        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $this->owner->id, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($this->projet->members(), $this->owner->id, 'owner');

        $this->outsider = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $this->outsider->id, 'collaborateur');

        $this->document = Document::create([
            'workspace_id' => $this->workspace->id,
            'documentable_type' => Projet::class,
            'documentable_id' => $this->projet->id,
            'nom' => 'test-doc.pdf',
            'nom_stockage' => 'test-doc-'.uniqid().'.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'taille' => 1024,
            'chemin' => 'documents/test-doc.pdf',
            'disk' => 'local',
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function project_member_can_view_project_documents(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/documents?'.http_build_query([
            'documentable_type' => Projet::class,
            'documentable_id' => $this->projet->id,
        ]))->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /** @test */
    public function non_member_cannot_view_project_documents(): void
    {
        Sanctum::actingAs($this->outsider);

        $response = $this->getJson('/api/documents?'.http_build_query([
            'documentable_type' => Projet::class,
            'documentable_id' => $this->projet->id,
        ]));

        if ($response->status() === 200) {
            $ids = collect($response->json('data'))->pluck('id');
            $this->assertNotContains($this->document->id, $ids->all());
        } else {
            $response->assertStatus(403);
        }
    }

    /** @test */
    public function share_by_email_sends_notification_to_external_email(): void
    {
        Notification::fake();

        Sanctum::actingAs($this->owner);

        $this->postJson("/api/documents/{$this->document->id}/share-by-email", [
            'email' => 'external@example.com',
        ])->assertStatus(200)
            ->assertJsonPath('success', true);

        Notification::assertSentOnDemand(
            DocumentSharedNotification::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'external@example.com'
        );
    }

    /** @test */
    public function non_owner_cannot_access_workspace_documents_endpoint(): void
    {
        Sanctum::actingAs($this->outsider);

        $this->getJson("/api/documents/workspace/{$this->workspace->id}")
            ->assertStatus(403);
    }

    /** @test */
    public function owner_can_access_workspace_documents_endpoint(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson("/api/documents/workspace/{$this->workspace->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }
}
