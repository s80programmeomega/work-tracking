<?php

declare(strict_types=1);

namespace Tests\Feature\Tache;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de gestion des pièces jointes d'une tâche :
 * addAttachments, getAttachments, deleteAttachment.
 */
class TacheAttachmentsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Tache $tache;

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

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeAttachment(): TacheAttachment
    {
        return TacheAttachment::create([
            'tache_id' => $this->tache->id,
            'file_name' => 'test_file.jpg',
            'file_path' => 'tache-attachments/test_file.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'original_name' => 'test_file.jpg',
            'uploaded_by' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // ADD ATTACHMENTS
    // =========================================================================

    /** @test */
    public function authorized_user_can_add_attachment(): void
    {
        Storage::fake('uploads');

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/attachments", [
                'files' => [$file],
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Fichiers ajoutés avec succès')
            ->assertJsonStructure(['data']);

        $this->assertDatabaseHas('tache_attachments', [
            'tache_id' => $this->tache->id,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_add_attachment(): void
    {
        Storage::fake('uploads');

        $outsider = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($outsider)
            ->postJson("/api/taches/{$this->tache->id}/attachments", [
                'files' => [$file],
            ])
            ->assertForbidden();
    }

    /** @test */
    public function add_attachment_with_no_files_returns_empty_data(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/attachments", [])
            ->assertCreated()
            ->assertJsonPath('data', []);
    }

    // =========================================================================
    // GET ATTACHMENTS
    // =========================================================================

    /** @test */
    public function authorized_user_can_list_attachments(): void
    {
        $this->makeAttachment();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/taches/{$this->tache->id}/attachments");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // DELETE ATTACHMENT
    // =========================================================================

    /** @test */
    public function authorized_user_can_delete_attachment(): void
    {
        Storage::fake('uploads');

        $attachment = $this->makeAttachment();

        $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}/attachments/{$attachment->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Fichier supprimé avec succès');

        $this->assertDatabaseMissing('tache_attachments', ['id' => $attachment->id]);
    }

    /** @test */
    public function deleting_attachment_from_different_tache_returns_404(): void
    {
        $otherActivite = Activite::factory()->create([
            'projet_id' => $this->tache->activite->projet_id,
            'responsable_id' => $this->owner->id,
        ]);

        $otherTache = Tache::factory()->create([
            'activite_id' => $otherActivite->id,
            'responsable_id' => $this->owner->id,
            'titre' => 'Autre tâche',
        ]);

        $attachmentOnOtherTache = TacheAttachment::create([
            'tache_id' => $otherTache->id,
            'file_name' => 'other.jpg',
            'file_path' => 'tache-attachments/other.jpg',
            'file_size' => 512,
            'mime_type' => 'image/jpeg',
            'original_name' => 'other.jpg',
            'uploaded_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}/attachments/{$attachmentOnOtherTache->id}")
            ->assertNotFound();
    }
}
