<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur Orange Money — Web Payment.
 *
 * Flux : token (Basic) → POST webpayment → { pay_token, payment_url } →
 * on REDIRIGE l'utilisateur vers payment_url (saisie OTP côté Orange) →
 * résultat via notif_url ET/OU statut par pay_token.
 *
 * Les URLs exactes varient selon l'opérateur/pays : tout vient de la config.
 * Aucune valeur secrète n'est journalisée.
 */
class OrangeMoneyProvider implements PaymentProviderInterface
{
    public function key(): string
    {
        return Payment::PROVIDER_ORANGE;
    }

    public function initiate(Payment $payment, string $payerPhone): PaymentResult
    {
        $token = $this->accessToken();
        if ($token === null) {
            return new PaymentResult(Payment::STATUS_FAILED, raw: ['error' => 'token']);
        }

        $config = config('payment.orange_money');

        try {
            $response = Http::withToken($token)
                ->post($config['base_url'].'/orange-money-webpay/dev/v1/webpayment', [
                    'merchant_key' => $config['merchant_key'],
                    'currency' => $payment->currency,
                    'order_id' => $payment->reference,
                    'amount' => $payment->amount,
                    'return_url' => $config['return_url'],
                    'cancel_url' => $config['cancel_url'],
                    'notif_url' => $config['notif_url'],
                    'lang' => $config['lang'],
                    'reference' => $payment->reference,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Orange webpayment échec réseau', ['payment' => $payment->reference, 'message' => $e->getMessage()]);

            return new PaymentResult(Payment::STATUS_FAILED, raw: ['error' => 'network']);
        }

        $data = $response->json() ?? [];

        if (! empty($data['payment_url']) && ! empty($data['pay_token'])) {
            return new PaymentResult(
                status: Payment::STATUS_PENDING,
                redirectUrl: $data['payment_url'],
                providerToken: $data['pay_token'],
                raw: ['notif_token' => $data['notif_token'] ?? null],
            );
        }

        Log::warning('Orange webpayment réponse inattendue', ['payment' => $payment->reference, 'status' => $response->status()]);

        return new PaymentResult(Payment::STATUS_FAILED, raw: ['http' => $response->status()]);
    }

    public function fetchStatus(Payment $payment): PaymentResult
    {
        $token = $this->accessToken();
        if ($token === null || $payment->provider_token === null) {
            return new PaymentResult(Payment::STATUS_PENDING);
        }

        $config = config('payment.orange_money');

        try {
            $response = Http::withToken($token)
                ->get($config['base_url'].'/orange-money-webpay/dev/v1/transactionstatus', [
                    'order_id' => $payment->reference,
                    'amount' => $payment->amount,
                    'pay_token' => $payment->provider_token,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Orange status échec réseau', ['payment' => $payment->reference, 'message' => $e->getMessage()]);

            return new PaymentResult(Payment::STATUS_PENDING);
        }

        $data = $response->json() ?? [];
        $orangeStatus = $data['status'] ?? 'PENDING';

        return new PaymentResult(
            status: $this->mapStatus($orangeStatus),
            providerToken: $payment->provider_token,
            raw: ['status' => $orangeStatus],
        );
    }

    private function accessToken(): ?string
    {
        $config = config('payment.orange_money');

        return Cache::remember('payment.orange.token', now()->addMinutes(50), function () use ($config): ?string {
            try {
                $response = Http::withBasicAuth($config['consumer_key'] ?? '', $config['consumer_secret'] ?? '')
                    ->asForm()
                    ->post($config['token_url'], ['grant_type' => 'client_credentials']);
            } catch (\Throwable $e) {
                Log::error('Orange token échec', ['message' => $e->getMessage()]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /** Traduit le statut Orange vers nos constantes internes. */
    private function mapStatus(string $orangeStatus): string
    {
        return match (strtoupper($orangeStatus)) {
            'SUCCESS', 'SUCCESSFUL' => Payment::STATUS_SUCCEEDED,
            'FAILED', 'FAILURE' => Payment::STATUS_FAILED,
            'EXPIRED' => Payment::STATUS_EXPIRED,
            default => Payment::STATUS_PENDING,
        };
    }
}
