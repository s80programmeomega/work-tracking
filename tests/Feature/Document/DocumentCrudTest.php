<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints CRUD du document :
 * store, index, show, update, destroy, versions.
 */
class DocumentCrudTest extends TestCase
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
        $this->workspace = Workspace::factory()->paid()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeDocument(string $ignored = ''): Document
    {
        return Document::factory()->forProjet($this->projet)->create([
            'user_id' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function projet_responsable_can_upload_document(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('rapport.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->owner)
            ->postJson('/api/documents', [
                'documentable_type' => Projet::class,
                'documentable_id' => $this->projet->id,
                'files' => [$file],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('documents', [
            'documentable_type' => Projet::class,
            'documentable_id' => $this->projet->id,
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function non_member_cannot_upload_document(): void
    {
        Storage::fake('local');

        $outsider = User::factory()->create();
        $file = UploadedFile::fake()->create('evil.pdf', 100, 'application/pdf');

        $this->actingAs($outsider)
            ->postJson('/api/documents', [
                'documentable_type' => Projet::class,
                'documentable_id' => $this->projet->id,
                'files' => [$file],
            ])
            ->assertForbidden();
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/documents', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documentable_type', 'documentable_id', 'files']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function owner_can_list_documents_for_entity(): void
    {
        $this->makeDocument('team');

        $response = $this->actingAs($this->owner)
            ->getJson('/api/documents?documentable_type='.urlencode(Projet::class).'&documentable_id='.$this->projet->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function owner_can_show_document(): void
    {
        $doc = $this->makeDocument();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/{$doc->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function outsider_cannot_show_private_document(): void
    {
        $doc = $this->makeDocument('private');
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/documents/{$doc->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function owner_can_update_document(): void
    {
        $doc = $this->makeDocument();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/documents/{$doc->id}", [
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
        ]);
    }

    /** @test */
    public function outsider_cannot_update_document(): void
    {
        $doc = $this->makeDocument('public');
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->putJson("/api/documents/{$doc->id}", [
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function owner_can_destroy_document(): void
    {
        $doc = $this->makeDocument();

        $this->actingAs($this->owner)
            ->deleteJson("/api/documents/{$doc->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function outsider_cannot_destroy_document(): void
    {
        $doc = $this->makeDocument('public');
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->deleteJson("/api/documents/{$doc->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // VERSIONS
    // =========================================================================

    /** @test */
    public function owner_can_list_document_versions(): void
    {
        $doc = $this->makeDocument();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/documents/{$doc->id}/versions");

        $response->assertOk();
    }
}
