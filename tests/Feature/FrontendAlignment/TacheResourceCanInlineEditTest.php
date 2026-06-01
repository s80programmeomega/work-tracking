<?php

declare(strict_types=1);

namespace Tests\Feature\FrontendAlignment;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que les permissions exposées par TacheResource et par TacheController@show
 * incluent `can_inline_edit` — clé consommée par TacheTable, TacheDetail,
 * SousTacheList et DetailedTaskView pour gérer l'autorisation des éditions en
 * ligne (statut, priorité, échéance, description…).
 *
 * Sans cette clé, le frontend retomberait silencieusement sur `can_edit` et
 * contournerait la séparation introduite en Task 11.
 */
class TacheResourceCanInlineEditTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    private function seedWorld(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->attachWithRole($projet->members(), $owner->id, 'owner');

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $owner->id,
        ]);

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'a_faire',
        ]);

        return compact('owner', 'workspace', 'projet', 'activite', 'tache');
    }

    public function test_tache_show_endpoint_exposes_can_inline_edit_for_owner(): void
    {
        ['owner' => $owner, 'tache' => $tache] = $this->seedWorld();

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/taches/{$tache->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'permissions' => ['can_inline_edit'],
            ],
            'additional_info' => [
                'permissions' => ['can_inline_edit'],
            ],
        ]);

        $this->assertTrue($response->json('data.permissions.can_inline_edit'));
        $this->assertTrue($response->json('additional_info.permissions.can_inline_edit'));
    }

    public function test_can_inline_edit_is_false_for_user_without_role(): void
    {
        ['tache' => $tache] = $this->seedWorld();

        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $response = $this->getJson("/api/taches/{$tache->id}");

        // Soit 403 (refus d'accès), soit 200 avec can_inline_edit = false ;
        // dans les deux cas le frontend doit savoir que l'édition en ligne est
        // refusée.
        if ($response->status() === 200) {
            $this->assertFalse($response->json('data.permissions.can_inline_edit'));
        } else {
            $response->assertStatus(403);
        }
    }
}
