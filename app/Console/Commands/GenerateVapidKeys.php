<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Task 8b — Génération des clés VAPID nécessaires au protocole Web Push.
 *
 * Les clés VAPID (Voluntary Application Server Identification) servent à
 * authentifier notre serveur auprès des services de push des navigateurs
 * (FCM pour Chrome/Edge, Mozilla autopush pour Firefox, Apple Push pour Safari).
 *
 * Cycle de vie :
 *   1. À la première installation, exécuter `php artisan webpush:generate-vapid`
 *      → la commande imprime un bloc à copier dans .env
 *   2. Stocker `VAPID_PUBLIC_KEY` et `VAPID_PRIVATE_KEY` dans .env
 *   3. NE PAS commiter .env. Les clés doivent rester secrètes.
 *   4. La clé publique est exposée au frontend via /api/webpush/vapid-key
 *      pour permettre l'inscription du navigateur. La clé privée reste serveur.
 *   5. Pour faire tourner les clés, regénérer puis invalider toutes les
 *      souscriptions existantes (les navigateurs devront se réabonner).
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:generate-vapid {--force : afficher les clés même si elles sont déjà présentes dans .env}';

    protected $description = 'Génère une paire de clés VAPID pour le Web Push (à copier dans .env)';

    public function handle(): int
    {
        // Détection d'une présence existante — éviter de remplacer des clés en
        // production par mégarde (les souscriptions existantes deviendraient
        // invalides dès le redémarrage). On laisse l'opérateur passer --force
        // s'il sait ce qu'il fait.
        if (! $this->option('force') && config('webpush.vapid.public_key')) {
            $this->warn('Des clés VAPID sont déjà configurées (VAPID_PUBLIC_KEY présente).');
            $this->warn('Réutilisez-les telles quelles, ou relancez avec --force pour en générer de nouvelles.');
            $this->warn('Attention : régénérer invalide TOUTES les souscriptions Web Push existantes.');

            return self::FAILURE;
        }

        $keys = VAPID::createVapidKeys();

        $this->info('Clés VAPID générées avec succès.');
        $this->newLine();
        $this->line('Copiez le bloc suivant dans votre fichier .env :');
        $this->newLine();
        $this->line('VAPID_SUBJECT=mailto:teams@cerdafrica.org');
        $this->line('VAPID_PUBLIC_KEY="'.$keys['publicKey'].'"');
        $this->line('VAPID_PRIVATE_KEY="'.$keys['privateKey'].'"');
        $this->newLine();
        $this->warn('Ces clés sont sensibles. NE PAS les committer ni les partager publiquement.');

        return self::SUCCESS;
    }
}
