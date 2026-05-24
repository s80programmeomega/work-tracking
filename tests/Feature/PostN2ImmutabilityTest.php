<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Services\TacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 9 step 4 — règle R6: dès qu'un résultat de la tâche est validé N2,
 * toute mutation sur la tâche doit lever HTTP 422.
 *
 * Tests:
 *   - isLockedPostN2() retourne true quand un résultat N2-validé existe.
 *   - updateTache() refuse une tâche verrouillée.
 *   - deleteTache() refuse une tâche verrouillée.
 *   - moveTache() refuse une tâche verrouillée.
 *   - archiveTache() refuse une tâche verrouillée.
 *   - assignUser() refuse une tâche verrouillée.
 *   - Une tâche dont AUCUN résultat n'est encore validé N2 reste mutable.
 */
class PostN2ImmutabilityTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private TacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->service = app(TacheService::class);
    }

    private function makeLockedTache(): Tache
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'titre' => 'Locked-'.uniqid(),
        ]);
        $user = User::factory()->create();
        $this->attachWithRole($tache->assignees(), $user->id, 'collaborateur');
        TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $user->id,
            'valide_par_n1' => true,
            'valide_par_n2' => true,
            'statut' => 'valide',
        ]);

        return $tache->fresh();
    }

    private function makeOpenTache(): Tache
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);

        return Tache::factory()->create([
            'activite_id' => $activite->id,
            'titre' => 'Open-'.uniqid(),
        ]);
    }

    /** @test */
    public function is_locked_post_n2_returns_true_when_a_result_is_n2_validated(): void
    {
        $this->assertTrue($this->makeLockedTache()->isLockedPostN2());
    }

    /** @test */
    public function is_locked_post_n2_returns_false_without_n2_validated_result(): void
    {
        $this->assertFalse($this->makeOpenTache()->isLockedPostN2());
    }

    /**
     * Vérifie qu'un callable lève bien une HttpException de status 422.
     * Plus précis que expectException() seul, qui ne couvre pas le status.
     */
    private function assertHttp422(callable $action): void
    {
        try {
            $action();
            $this->fail('Expected HttpException 422 was not thrown');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    /** @test */
    public function updating_a_locked_task_throws_422(): void
    {
        $this->assertHttp422(fn () => $this->service->updateTache($this->makeLockedTache(), ['titre' => 'X']));
    }

    /** @test */
    public function deleting_a_locked_task_throws_422(): void
    {
        $this->assertHttp422(fn () => $this->service->deleteTache($this->makeLockedTache()));
    }

    /** @test */
    public function archiving_a_locked_task_throws_422(): void
    {
        $this->assertHttp422(fn () => $this->service->archiveTache($this->makeLockedTache()));
    }

    /** @test */
    public function assigning_a_user_on_a_locked_task_throws_422(): void
    {
        $newUser = User::factory()->create();
        $this->assertHttp422(fn () => $this->service->assignUser($this->makeLockedTache(), ['user_id' => $newUser->id]));
    }
}
