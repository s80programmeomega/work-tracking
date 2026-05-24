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
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Régression — bug de Mai 2026: validateByN1() et validateByN2() mettaient
 * à jour les flags booléens valide_par_n1/n2 mais ne touchaient PAS la
 * colonne `statut` (enum). Conséquence:
 *   - le dashboard "validations en attente N2" (filtre statut='en_validation_n2')
 *     restait vide après une validation N1 réussie,
 *   - la règle R6 (Task 9) qui combine isLockedPostN2() ET les requêtes sur
 *     statut='valide' divergeait selon la lecture.
 *
 * Ces tests verrouillent les transitions explicites pour empêcher la
 * régression de revenir.
 */
class ValidationStatutTransitionTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        // Évite que les notifications queue-only (mail/Reverb) ne polluent
        // les tests — on teste les transitions de données, pas les canaux.
        Notification::fake();
    }

    private function makeContext(bool $n2Required = true): array
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'validation_n1_required' => true,
            'validation_n2_required' => $n2Required,
        ]);
        $author = User::factory()->create();
        $this->attachWithRole($tache->assignees(), $author->id, 'collaborateur');

        $resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $author->id,
            'statut' => 'en_validation_n1',
        ]);

        return compact('tache', 'author', 'resultat');
    }

    /** @test */
    public function validate_by_n1_sets_statut_to_en_validation_n2_when_n2_required(): void
    {
        ['resultat' => $resultat] = $this->makeContext(n2Required: true);
        $n1 = User::factory()->create();

        $resultat->validateByN1($n1, 'OK pour moi');

        $resultat->refresh();
        $this->assertSame('en_validation_n2', $resultat->statut);
        $this->assertTrue($resultat->valide_par_n1);
        $this->assertFalse($resultat->valide_par_n2);
    }

    /** @test */
    public function validate_by_n1_sets_statut_to_valide_when_no_n2_required(): void
    {
        ['resultat' => $resultat] = $this->makeContext(n2Required: false);
        $n1 = User::factory()->create();

        $resultat->validateByN1($n1, 'OK final');

        $resultat->refresh();
        $this->assertSame('valide', $resultat->statut);
        $this->assertTrue($resultat->valide_par_n1);
    }

    /** @test */
    public function validate_by_n2_sets_statut_to_valide(): void
    {
        ['resultat' => $resultat] = $this->makeContext(n2Required: true);
        $n1 = User::factory()->create();
        $n2 = User::factory()->create();

        $resultat->validateByN1($n1, 'N1 OK');
        $resultat->refresh();

        $resultat->validateByN2($n2, 'N2 OK');
        $resultat->refresh();

        $this->assertSame('valide', $resultat->statut);
        $this->assertTrue($resultat->valide_par_n1);
        $this->assertTrue($resultat->valide_par_n2);
    }

    /** @test */
    public function n2_pending_query_picks_up_results_after_n1_validation(): void
    {
        // Le bug original: ce filtre renvoyait toujours 0 row, même après
        // une validation N1 réussie sur une tâche avec N2 requis.
        ['resultat' => $resultat] = $this->makeContext(n2Required: true);
        $n1 = User::factory()->create();

        $this->assertSame(
            0,
            TacheResultat::where('statut', 'en_validation_n2')->count(),
            'Avant validation N1, aucun résultat ne devrait être en attente N2.'
        );

        $resultat->validateByN1($n1, 'go');

        $this->assertSame(
            1,
            TacheResultat::where('statut', 'en_validation_n2')->count(),
            'Après validation N1 (avec N2 requis), le résultat doit apparaître dans la file N2.'
        );
    }

    /** @test */
    public function n2_pending_query_returns_correct_user_id_after_n1_validation(): void
    {
        // G4: vérifie que la row en_validation_n2 appartient bien à l'auteur du résultat,
        // pas au validateur N1 — scoping bug potentiel si user_id était mal propagé.
        ['resultat' => $resultat, 'author' => $author] = $this->makeContext(n2Required: true);
        $n1 = User::factory()->create();

        $resultat->validateByN1($n1, 'OK');

        $pending = TacheResultat::where('statut', 'en_validation_n2')->first();

        $this->assertNotNull($pending, 'Une row en_validation_n2 doit exister après validateByN1.');
        $this->assertSame($author->id, $pending->user_id, 'La row doit appartenir à l\'auteur du résultat, pas au validateur N1.');
        $this->assertSame($n1->id, $pending->validateur_n1_id, 'validateur_n1_id doit pointer vers le validateur N1.');
    }

    /** @test */
    public function n2_pending_query_does_not_include_unrelated_users_results(): void
    {
        // G4: scope leak — un résultat en_validation_n2 d'un autre auteur ne doit pas
        // polluer la file d'un autre contexte. Crée deux résultats indépendants.
        ['resultat' => $resultat1] = $this->makeContext(n2Required: true);
        ['resultat' => $resultat2] = $this->makeContext(n2Required: true);

        $n1a = User::factory()->create();
        $n1b = User::factory()->create();

        $resultat1->validateByN1($n1a, 'OK A');
        $resultat2->validateByN1($n1b, 'OK B');

        $this->assertSame(
            2,
            TacheResultat::where('statut', 'en_validation_n2')->count(),
            'Les deux résultats doivent être en file N2 indépendamment.'
        );

        // Chaque row a un user_id distinct
        $userIds = TacheResultat::where('statut', 'en_validation_n2')->pluck('user_id');
        $this->assertCount(2, $userIds->unique(), 'Les deux rows doivent appartenir à des auteurs différents.');
    }
}
