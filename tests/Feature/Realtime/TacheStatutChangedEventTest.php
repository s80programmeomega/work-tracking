<?php

declare(strict_types=1);

namespace Tests\Feature\Realtime;

use App\Events\Realtime\TacheStatutChanged;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class TacheStatutChangedEventTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /** @test */
    public function moving_a_tache_dispatches_tache_statut_changed_event(): void
    {
        Event::fake([TacheStatutChanged::class]);

        $workspace = Workspace::factory()->create();
        $owner = User::factory()->create();
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'a_faire',
            'position' => 1,
        ]);

        $this->actingAs($owner)->postJson("/api/taches/{$tache->id}/move", [
            'statut' => 'en_cours',
            'position' => 1,
        ]);

        Event::assertDispatched(TacheStatutChanged::class, function ($event) use ($tache) {
            return $event->tache->id === $tache->id;
        });
    }

    /** @test */
    public function tache_statut_changed_event_broadcasts_on_workspace_channel(): void
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $event = new TacheStatutChanged($tache);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertStringContainsString("workspace.{$workspace->id}", $channels[0]->name);
    }

    /** @test */
    public function tache_statut_changed_event_includes_correct_payload(): void
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'en_cours',
            'taux_realisation' => 50,
        ]);

        $event = new TacheStatutChanged($tache);
        $payload = $event->broadcastWith();

        $this->assertEquals($tache->id, $payload['tache_id']);
        $this->assertEquals(50, $payload['taux_realisation']);
    }
}
