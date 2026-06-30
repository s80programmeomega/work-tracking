<?php

declare(strict_types=1);

namespace Tests\Feature\FrontendAlignment;

use App\Models\Document;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\DocumentDeletedNotification;
use App\Notifications\DocumentPermissionGrantedNotification;
use App\Notifications\DocumentSharedNotification;
use App\Notifications\DocumentUploadedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verrouille la forme du payload des notifications « document_* » lues par le
 * frontend (composables/useNotifications.js + NotificationDetailModal.vue).
 *
 * Si l'une de ces clés disparaît ou change de nom, l'interface affichera des
 * cases vides — ce test échoue alors avant la régression visuelle.
 */
class DocumentNotificationShapeTest extends TestCase
{
    use RefreshDatabase;

    private User $recipient;

    private User $actor;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->actor = User::factory()->create(['nom' => 'Alice Auteure']);
        $this->recipient = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $this->actor->id]);
        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $this->actor->id,
        ]);

        $this->document = Document::create([
            'workspace_id' => $workspace->id,
            'documentable_type' => Projet::class,
            'documentable_id' => $projet->id,
            'nom' => 'plan-strategique.pdf',
            'nom_stockage' => 'plan-strategique-'.uniqid().'.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'taille' => 2048,
            'chemin' => 'documents/plan-strategique.pdf',
            'disk' => 'local',
            'user_id' => $this->actor->id,
        ]);
    }

    public function test_document_uploaded_payload_exposes_keys_consumed_by_frontend(): void
    {
        $data = (new DocumentUploadedNotification($this->document, $this->actor))
            ->toArray($this->recipient);

        $this->assertSame('document_uploaded', $data['type']);
        $this->assertSame($this->document->id, $data['document_id']);
        $this->assertSame($this->document->nom, $data['document_nom']);
        $this->assertSame($this->actor->nom_complet, $data['uploaded_by']);
        $this->assertArrayHasKey('dedup_key', $data);
        $this->assertNotEmpty($data['dedup_key']);
    }

    public function test_document_deleted_payload_exposes_keys_consumed_by_frontend(): void
    {
        $data = (new DocumentDeletedNotification($this->document->nom, $this->actor))
            ->toArray($this->recipient);

        $this->assertSame('document_deleted', $data['type']);
        $this->assertSame($this->document->nom, $data['document_nom']);
        $this->assertSame($this->actor->nom_complet, $data['deleted_by']);
        $this->assertArrayHasKey('dedup_key', $data);
    }

    public function test_document_shared_payload_exposes_keys_consumed_by_frontend(): void
    {
        $data = (new DocumentSharedNotification($this->document, $this->actor, 'external@example.com'))
            ->toArray($this->recipient);

        $this->assertSame('document_shared', $data['type']);
        $this->assertSame($this->document->id, $data['document_id']);
        $this->assertSame($this->document->nom, $data['document_nom']);
        $this->assertSame($this->actor->nom_complet, $data['shared_by']);
        $this->assertSame('external@example.com', $data['recipient']);
    }

    public function test_document_permission_granted_uses_document_shared_type_for_frontend_routing(): void
    {
        // Important : ce notifier émet aussi `type: document_shared` afin que
        // l'icône, la couleur et la branche UI du frontend l'attrapent comme un
        // partage standard. Si quelqu'un renomme le type, le badge retombera
        // sur la couleur grise par défaut.
        $data = (new DocumentPermissionGrantedNotification($this->document, $this->actor))
            ->toArray($this->recipient);

        $this->assertSame('document_shared', $data['type']);
        $this->assertSame($this->document->id, $data['document_id']);
        $this->assertSame($this->document->nom, $data['document_nom']);
        $this->assertSame($this->actor->nom_complet, $data['shared_by']);
        $this->assertSame($this->actor->id, $data['shared_by_id']);
    }
}
