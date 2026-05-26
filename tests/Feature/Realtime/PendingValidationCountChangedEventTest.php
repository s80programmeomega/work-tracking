<?php

declare(strict_types=1);

namespace Tests\Feature\Realtime;

use App\Events\Realtime\PendingValidationCountChanged;
use App\Events\Realtime\ResultatStatutChanged;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class PendingValidationCountChangedEventTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /** @test */
    public function pending_validation_count_changed_event_broadcasts_on_workspace_channel(): void
    {
        $workspace = Workspace::factory()->create();

        $event = new PendingValidationCountChanged($workspace->id, [42]);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertStringContainsString("workspace.{$workspace->id}", $channels[0]->name);
    }

    /** @test */
    public function pending_validation_count_changed_event_includes_correct_payload(): void
    {
        $workspace = Workspace::factory()->create();

        $event = new PendingValidationCountChanged($workspace->id, [1, 2, 3]);
        $payload = $event->broadcastWith();

        $this->assertEquals($workspace->id, $payload['workspace_id']);
        $this->assertEquals([1, 2, 3], $payload['notify_user_ids']);
    }

    /** @test */
    public function resultat_statut_changed_event_broadcasts_on_workspace_channel(): void
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id]);

        $event = new ResultatStatutChanged($resultat);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertStringContainsString("workspace.{$workspace->id}", $channels[0]->name);
    }
}
