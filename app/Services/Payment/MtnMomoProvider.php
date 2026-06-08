<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur MTN Mobile Money — Collections (Request to Pay).
 *
 * Flux : token (Basic) → POST requesttopay (202, asynchrone) → résultat via
 * callback ET/OU GET requesttopay/{reference}. Le paiement est « push » :
 * l'utilisateur valide sur son téléphone, donc pas d'URL de redirection.
 *
 * Aucune valeur secrète n'est jamais journalisée.
 */
class MtnMomoProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return Payment::PROVIDER_MTN;
    }

    public function initiate(Payment $payment, string $payerPhone): PaymentResult
    {
        $token = $this->accessToken();
        if ($token === null) {
            return new PaymentResult(Payment::STATUS_FAILED, raw: ['error' => 'token']);
        }

        $config = config('payment.mtn_momo');

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                    'X-Reference-Id' => $payment->reference, // idempotence
                    'X-Target-Environment' => $config['target_environment'],
                    'X-Callback-Url' => $config['callback_url'],
                ])
                ->post($config['base_url'].'/collection/v1_0/requesttopay', [
                    'amount' => (string) $payment->amount,
                    'currency' => $payment->currency,
                    'externalId' => $payment->reference,
                    'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $payerPhone],
                    'payerMessage' => 'Abonnement Work Tracking',
                    'payeeNote' => 'Plan '.$payment->plan_id,
                ]);
        } catch (\Throwable $e) {
            Log::warning('MTN requesttopay échec réseau', ['payment' => $payment->reference, 'message' => $e->getMessage()]);

            return new PaymentResult(Payment::STATUS_FAILED, raw: ['error' => 'network']);
        }

        // 202 Accepted = la collecte est lancée ; le résultat arrivera plus tard.
        if ($response->status() === 202) {
            return new PaymentResult(Payment::STATUS_PENDING, providerReference: $payment->reference);
        }

        Log::warning('MTN requesttopay réponse inattendue', ['payment' => $payment->reference, 'status' => $response->status()]);

        return new PaymentResult(Payment::STATUS_FAILED, raw: ['http' => $response->status()]);
    }

    public function fetchStatus(Payment $payment): PaymentResult
    {
        $token = $this->accessToken();
        if ($token === null) {
            return new PaymentResult(Payment::STATUS_PENDING);
        }

        $config = config('payment.mtn_momo');

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $config['subscription_key'],
                    'X-Target-Environment' => $config['target_environment'],
                ])
                ->get($config['base_url'].'/collection/v1_0/requesttopay/'.$payment->reference);
        } catch (\Throwable $e) {
            Log::warning('MTN status échec réseau', ['payment' => $payment->reference, 'message' => $e->getMessage()]);

            return new PaymentResult(Payment::STATUS_PENDING);
        }

        $data = $response->json() ?? [];
        $mtnStatus = $data['status'] ?? 'PENDING';

        return new PaymentResult(
            status: $this->mapStatus($mtnStatus),
            providerReference: $data['financialTransactionId'] ?? null,
            raw: ['status' => $mtnStatus],
        );
    }

    /**
     * Récupère (et met en cache 50 min) un jeton d'accès OAuth.
     * Token MTN valide 1 h ; on prend une marge.
     */
    private function accessToken(): ?string
    {
        $config = config('payment.mtn_momo');

        return Cache::remember('payment.mtn.token', now()->addMinutes(50), function () use ($config): ?string {
            try {
                $response = Http::withBasicAuth($config['api_user'] ?? '', $config['api_key'] ?? '')
                    ->withHeaders(['Ocp-Apim-Subscription-Key' => $config['subscription_key']])
                    ->post($config['base_url'].'/collection/token/');
            } catch (\Throwable $e) {
                Log::error('MTN token échec', ['message' => $e->getMessage()]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /** Traduit le statut MTN vers nos constantes internes. */
    private function mapStatus(string $mtnStatus): string
    {
        return match (strtoupper($mtnStatus)) {
            'SUCCESSFUL' => Payment::STATUS_SUCCEEDED,
            'FAILED' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };
    }
}
