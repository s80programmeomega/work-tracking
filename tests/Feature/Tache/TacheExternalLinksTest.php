<?php

declare(strict_types=1);

namespace Tests\Feature\Tache;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheExternalLink;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de gestion des liens externes d'une tâche :
 * addExternalLink, deleteExternalLink.
 */
class TacheExternalLinksTest extends TestCase
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
            'visibility' => 'public',
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

    private function makeLink(): TacheExternalLink
    {
        return TacheExternalLink::create([
            'tache_id' => $this->tache->id,
            'title' => 'Lien de référence',
            'url' => 'https://example.com/doc',
            'created_by' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // ADD EXTERNAL LINK
    // =========================================================================

    /** @test */
    public function authorized_user_can_add_external_link(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/external-links", [
                'title' => 'Documentation projet',
                'url' => 'https://docs.example.com/projet',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Lien ajouté avec succès');

        $this->assertDatabaseHas('tache_external_links', [
            'tache_id' => $this->tache->id,
            'url' => 'https://docs.example.com/projet',
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_add_external_link(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson("/api/taches/{$this->tache->id}/external-links", [
                'title' => 'Lien malveillant',
                'url' => 'https://evil.example.com',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function add_external_link_validates_url_format(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/external-links", [
                'title' => 'Lien invalide',
                'url' => 'pas-une-url',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function add_external_link_requires_title_and_url(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/external-links", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'url']);
    }

    // =========================================================================
    // DELETE EXTERNAL LINK
    // =========================================================================

    /** @test */
    public function authorized_user_can_delete_external_link(): void
    {
        $link = $this->makeLink();

        $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}/external-links/{$link->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Lien supprimé avec succès');

        $this->assertDatabaseMissing('tache_external_links', ['id' => $link->id]);
    }

    /** @test */
    public function deleting_link_from_different_tache_returns_404(): void
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

        $linkOnOtherTache = TacheExternalLink::create([
            'tache_id' => $otherTache->id,
            'title' => 'Autre lien',
            'url' => 'https://other.example.com',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/taches/{$this->tache->id}/external-links/{$linkOnOtherTache->id}")
            ->assertNotFound();
    }
}
