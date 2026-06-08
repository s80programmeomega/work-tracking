<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Provisionne un API User + API Key MTN MoMo dans le bac à sable (sandbox).
 *
 * MTN exige, en plus de la clé d'abonnement (subscription key), un « API User »
 * (UUID que l'on choisit) et une « API Key » (générée par MTN pour cet utilisateur).
 * Cette commande effectue les 3 appels et affiche les valeurs à coller dans .env.
 *
 * Aucune valeur secrète n'est journalisée ; la clé d'abonnement est passée en
 * argument (jamais stockée dans le code).
 *
 * Usage : php artisan momo:provision-sandbox <subscription_key>
 */
class ProvisionMomoSandbox extends Command
{
    protected $signature = 'momo:provision-sandbox
        {subscription_key : Votre Primary Subscription Key (produit Collections)}
        {--callback=https://webhook.site/ : Hôte de callback enregistré chez MTN (hostname réel, pas un .test/IP)}';

    protected $description = 'Crée un API User + API Key MTN MoMo sandbox et affiche les valeurs .env';

    public function handle(): int
    {
        $subKey = (string) $this->argument('subscription_key');
        $base = (string) config('payment.mtn_momo.base_url', 'https://sandbox.momodeveloper.mtn.com');
        $referenceId = (string) Str::uuid();          // = futur PAYMENT_MOMO_API_USER
        // MTN exige un hostname RÉEL (pas un .test ni une IP) : un host placeholder
        // peut provoquer INTERNAL_PROCESSING_ERROR (« Wallet Platform not reachable »).
        $callbackHost = parse_url((string) $this->option('callback'), PHP_URL_HOST) ?: 'webhook.site';

        $this->info("Base sandbox : {$base}");
        $this->line("API User (UUID généré) : {$referenceId}");
        $this->line("providerCallbackHost enregistré : {$callbackHost}");

        // 1) Créer l'API user.
        $create = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $subKey,
            'X-Reference-Id' => $referenceId,
            'Content-Type' => 'application/json',
        ])->post("{$base}/v1_0/apiuser", ['providerCallbackHost' => $callbackHost]);

        if ($create->status() !== 201) {
            $this->error('Échec création API user (HTTP '.$create->status().'). Vérifiez la subscription key.');
            $this->line($create->body());

            return self::FAILURE;
        }
        $this->info('✓ API user créé.');

        // 2) Générer l'API key pour cet utilisateur.
        $keyResp = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $subKey,
        ])->post("{$base}/v1_0/apiuser/{$referenceId}/apikey");

        if ($keyResp->status() !== 201) {
            $this->error('Échec génération API key (HTTP '.$keyResp->status().').');
            $this->line($keyResp->body());

            return self::FAILURE;
        }
        $apiKey = (string) $keyResp->json('apiKey');
        $this->info('✓ API key générée.');

        // 3) Afficher les valeurs à coller dans .env (PAS de journalisation).
        $this->newLine();
        $this->line('────────── À coller dans votre .env ──────────');
        $this->line('PAYMENT_MOMO_SUBSCRIPTION_KEY='.$subKey);
        $this->line('PAYMENT_MOMO_API_USER='.$referenceId);
        $this->line('PAYMENT_MOMO_API_KEY='.$apiKey);
        $this->line('PAYMENT_MOMO_TARGET_ENV=sandbox');
        $this->line('PAYMENT_MOMO_CURRENCY=EUR   # le sandbox MTN n’accepte que EUR');
        $this->line('──────────────────────────────────────────────');
        $this->newLine();
        $this->warn('Puis : php artisan config:clear  et mettez PAYMENT_FAKE=false');

        return self::SUCCESS;
    }
}
