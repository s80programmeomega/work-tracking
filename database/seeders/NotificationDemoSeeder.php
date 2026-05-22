<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Task 8 / 8b — peuple chaque utilisateur de démo avec:
 *   - une ligne notification_preferences (sinon `getOrCreateNotificationPreference()`
 *     génère du bruit au premier rendu de la page Préférences),
 *   - une souscription Web Push "fixture" pour les comptes qui en bénéficient
 *     le plus dans le scénario de test (cadre + manager), histoire que la
 *     route /api/webpush/subscriptions renvoie quelque chose dès la sortie
 *     de `migrate:fresh --seed`.
 *
 * Variantes (cf. docs/testing/TASK_8B_TESTING.md):
 *   - cadre         → souscription Chrome active
 *   - manager       → souscription Firefox active
 *   - collaborateur → master switch push désactivé (Cas 5)
 *   - stagiaire     → souscription désactivée (Cas 4 — endpoint expiré)
 *
 * Le seeder est idempotent: ré-exécutable sans dupliquer de lignes.
 */
class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cadre = User::where('email', 'cadre@worktracking.com')->first();
        $manager = User::where('email', 'manager@worktracking.com')->first();
        $collaborateur = User::where('email', 'collaborateur@worktracking.com')->first();
        $stagiaire = User::where('email', 'stagiaire@worktracking.com')->first();
        $observateur = User::where('email', 'observateur@worktracking.com')->first();
        $directeur = User::where('email', 'directeur@worktracking.com')->first();

        $users = array_filter([$cadre, $manager, $collaborateur, $stagiaire, $observateur, $directeur]);

        // Une ligne de préférences par défaut pour chaque utilisateur de démo
        // (push on, digest off, pas de quiet hours) — idempotent.
        foreach ($users as $user) {
            NotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                NotificationPreference::factory()->make(['user_id' => $user->id])->toArray()
            );
        }

        // Variante "push off" pour le collaborateur (Cas 5).
        if ($collaborateur) {
            $collaborateur->notificationPreference?->update(['push_enabled' => false]);
        }

        // Souscriptions Web Push de démo — seulement pour les profils
        // pertinents au scénario de validation N0.
        if ($cadre) {
            PushSubscription::updateOrCreate(
                [
                    'user_id' => $cadre->id,
                    'endpoint' => 'https://fcm.googleapis.com/fcm/send/demo-cadre-chrome',
                ],
                PushSubscription::factory()
                    ->forUser($cadre)
                    ->state([
                        'device_type' => 'desktop-chrome',
                        'endpoint' => 'https://fcm.googleapis.com/fcm/send/demo-cadre-chrome',
                    ])
                    ->make()
                    ->toArray()
            );
        }

        if ($manager) {
            PushSubscription::updateOrCreate(
                [
                    'user_id' => $manager->id,
                    'endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/demo-manager-firefox',
                ],
                PushSubscription::factory()
                    ->forUser($manager)
                    ->firefox()
                    ->state(['endpoint' => 'https://updates.push.services.mozilla.com/wpush/v2/demo-manager-firefox'])
                    ->make()
                    ->toArray()
            );
        }

        if ($stagiaire) {
            PushSubscription::updateOrCreate(
                [
                    'user_id' => $stagiaire->id,
                    'endpoint' => 'https://fcm.googleapis.com/fcm/send/demo-stagiaire-expired',
                ],
                PushSubscription::factory()
                    ->forUser($stagiaire)
                    ->inactive()
                    ->state(['endpoint' => 'https://fcm.googleapis.com/fcm/send/demo-stagiaire-expired'])
                    ->make()
                    ->toArray()
            );
        }

        $this->command?->info('NotificationDemoSeeder — préférences + souscriptions Web Push de démo créées');
    }
}
