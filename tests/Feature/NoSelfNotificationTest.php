<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Services\NotificationService;
use App\Services\TacheResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Régression G2 du bug-batch — l'acteur d'une action ne doit jamais
 * recevoir lui-même la notification déclenchée par cette action.
 *
 * Deux niveaux:
 *   1. Test direct du helper NotificationService::sendUnlessSelf —
 *      garde-fou central.
 *   2. Tests d'intégration sur les chemins du circuit de validation
 *      (N0 approve/renvoye, N1 validate, N2 validate, bypass) qui
 *      vérifient que la table `notifications` ne contient AUCUNE row
 *      où notifiable_id = actor_id après l'action.
 *
 * Les 3 notifications *Confirmee* (N1, N2, Rejet) ont été supprimées —
 * elles étaient pure auto-notification du validateur envers lui-même.
 */
class NoSelfNotificationTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private NotificationService $notif;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->notif = app(NotificationService::class);
    }

    // ── Helper direct ───────────────────────────────────────────────

    /** @test */
    public function send_unless_self_suppresses_notification_when_actor_equals_notifiable(): void
    {
        $user = User::factory()->create();

        $this->notif->sendUnlessSelf($user, $user, $this->fakeNotification('valide_n1'));

        $this->assertSame(0, $user->notifications()->count(),
            'Pas de row notifications si actor === notifiable.');
    }

    /** @test */
    public function send_unless_self_sends_when_actor_differs(): void
    {
        $actor = User::factory()->create();
        $recipient = User::factory()->create();

        $this->notif->sendUnlessSelf($recipient, $actor, $this->fakeNotification('valide_n1'));

        $this->assertSame(1, $recipient->notifications()->count(),
            'La notification doit partir quand actor !== notifiable.');
        $this->assertSame(0, $actor->notifications()->count(),
            'L\'acteur ne doit jamais recevoir la notification.');
    }

    /** @test */
    public function send_unless_self_sends_when_actor_is_null(): void
    {
        // Cas système / scheduler — actor null, on envoie sans condition.
        $recipient = User::factory()->create();

        $this->notif->sendUnlessSelf($recipient, null, $this->fakeNotification('transmis_auto'));

        $this->assertSame(1, $recipient->notifications()->count());
    }

    // ── Intégration sur les chemins métier ─────────────────────────

    /** @test */
    public function n0_approve_does_not_notify_actor_when_actor_is_author(): void
    {
        // Cas pathologique mais possible: l'auteur du résultat est aussi
        // responsable N0 (auto-approbation).
        [$tache, $author, $resultat] = $this->makeSubmittedResult();

        $tache->assignees()->updateExistingPivot($author->id, ['is_responsable' => true]);

        app(TacheResultatService::class)->approuverN0($resultat, $author);

        $this->assertNoSelfNotification($author);
    }

    /** @test */
    public function n0_renvoye_does_not_notify_actor_when_actor_is_author(): void
    {
        [$tache, $author, $resultat] = $this->makeSubmittedResult();
        $tache->assignees()->updateExistingPivot($author->id, ['is_responsable' => true]);

        app(TacheResultatService::class)->renvoyerN0(
            $resultat,
            $author,
            str_repeat('Ce résultat doit être refait pour les raisons suivantes ', 2)
        );

        $this->assertNoSelfNotification($author);
    }

    /** @test */
    public function n1_validate_does_not_notify_self_when_validator_is_author(): void
    {
        [, $author, $resultat] = $this->makeSubmittedResult();

        // L'auteur est aussi N1. validateByN1 ne doit lui envoyer NI le
        // ResultatValideN1 (auto), NI un ConfirmeeN1 (supprimé).
        $resultat->validateByN1($author, 'auto-valide');

        $this->assertNoSelfNotification($author);
    }

    /** @test */
    public function n2_validate_does_not_notify_self_when_validator_is_author(): void
    {
        [, $author, $resultat] = $this->makeSubmittedResult(n2Required: true);

        // Passer par N1 d'abord (un tiers). C'est LÉGITIME que l'auteur
        // reçoive ici ResultatValideN1 — N1 n'est pas lui.
        $n1 = User::factory()->create();
        $resultat->validateByN1($n1, 'N1 OK');
        $resultat->refresh();
        $countAfterN1 = $author->notifications()->count();

        // L'auteur s'auto-valide N2 (cas de bord). Aucune notification ne
        // doit s'ajouter (ResultatValideN2 supprimée par sendUnlessSelf,
        // et ValidationN2Confirmee a été supprimée tout court).
        $resultat->validateByN2($author, 'auto-N2');

        $this->assertSame(
            $countAfterN1,
            $author->notifications()->count(),
            'validateByN2($author) ne doit pas ajouter de notification pour l\'auteur.'
        );
    }

    /** @test */
    public function bypass_does_not_notify_actor_when_actor_is_n1(): void
    {
        [$tache, $author, $resultat] = $this->makeSubmittedResult();

        $resultat->update(['statut' => 'a_refaire', 'action_n0' => 'renvoye']);
        $tache->activite->update(['responsable_id' => $author->id]);

        app(TacheResultatService::class)->activerBypass(
            $resultat,
            $author,
            str_repeat('Motif détaillé du bypass — au moins 50 caractères. ', 2)
        );

        $this->assertNoSelfNotification($author);
    }

    // ── Helpers internes ───────────────────────────────────────────

    private function makeSubmittedResult(bool $n2Required = false): array
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'validation_n1_required' => true,
            'validation_n2_required' => $n2Required,
        ]);
        $author = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($tache->assignees(), $author->id, 'collaborateur');

        $resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $author->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
        ]);

        return [$tache->fresh(), $author, $resultat];
    }

    private function assertNoSelfNotification(User $actor): void
    {
        $this->assertSame(
            0,
            $actor->notifications()->count(),
            'L\'acteur '.$actor->id.' ne devrait JAMAIS recevoir de notification pour sa propre action.'
        );
    }

    /**
     * Notification minimaliste qui exerce uniquement le canal database.
     * Évite de dépendre des classes réelles (qui appellent channelsFor()
     * et touchent les préférences de notification).
     */
    private function fakeNotification(string $eventType): Notification
    {
        return new class($eventType) extends Notification
        {
            public function __construct(public string $eventType) {}

            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return ['type' => $this->eventType, 'test' => true];
            }
        };
    }
}
