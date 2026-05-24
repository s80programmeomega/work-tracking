<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Régression PROGRESSION bug #1 — GET /api/taches/{id} renvoyait le modèle
 * Eloquent brut au lieu de TacheResource. Conséquence: tous les champs
 * injectés par la resource (my_result, validation, validation_status,
 * permissions, bypass.*, can_activer_bypass...) étaient absents du payload
 * front, ce qui invisibilisait le bouton "Soumettre résultat" et le bandeau
 * bypass.
 *
 * Ce test verrouille la forme de la réponse pour empêcher la régression.
 */
class TacheControllerShowResourceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /** @test */
    public function show_endpoint_returns_resource_shape_not_raw_model(): void
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($tache->assignees(), $user->id, 'collaborateur');

        // Un résultat individuel pour que my_result soit non-null sur cet acteur.
        TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $user->id,
            'statut' => 'en_validation_n1',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/taches/{$tache->id}");

        $response->assertOk();

        $data = $response->json('data');

        $this->assertIsArray($data, 'data should be the serialised resource array');

        // ── Champs Resource-only (absents si on renvoyait le modèle brut) ──
        $this->assertArrayHasKey('my_result', $data,
            'my_result manquant: TacheResource n\'a pas été appliquée.');

        $this->assertArrayHasKey('validation', $data,
            'validation manquant: la structure imbriquée vient de TacheResource.');

        $this->assertArrayHasKey('assignees', $data,
            'assignees (forme resource) manquant.');

        // Bug originel: le modèle brut exposait statut_label, statut_color, etc.
        // La resource les conserve aussi mais réorganise la structure de
        // validation (n1_validated_at, n2_validated_at, status...).
        $this->assertArrayHasKey('n1_validated_at', $data['validation'],
            'validation.n1_validated_at: forme attendue de TacheResource.');

        // my_result doit exposer le statut + bypass attendus par TacheResultsTab.
        $this->assertArrayHasKey('statut', $data['my_result']);
        $this->assertArrayHasKey('bypass', $data['my_result']);
        $this->assertArrayHasKey('validation_n0', $data['my_result']);
        $this->assertSame('en_validation_n1', $data['my_result']['statut']);
    }

    /** @test */
    public function show_endpoint_keeps_additional_info_block(): void
    {
        // additional_info doit rester côte à côte de data — le front en
        // dépend pour stats / permissions / breadcrumb. La régression du
        // bug PROGRESSION #1 ne doit pas supprimer cette clé en passant.
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $user = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($tache->assignees(), $user->id, 'collaborateur');

        Sanctum::actingAs($user);

        $this->getJson("/api/taches/{$tache->id}")
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'additional_info' => [
                    'stats' => ['assignees_count', 'resultats_count', 'sous_taches_count'],
                    'permissions' => ['can_update', 'can_delete', 'can_validate_n1', 'can_validate_n2'],
                    'breadcrumb' => ['workspace', 'projet', 'activite'],
                ],
            ]);
    }
}
