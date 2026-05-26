<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Projet;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function isPaid(Workspace $workspace): bool
    {
        return $workspace->subscription_mode === 'paid';
    }

    public function isTrialExpired(Workspace $workspace): bool
    {
        if ($this->isPaid($workspace)) {
            return false;
        }

        if (! $workspace->trial_started_at) {
            return false;
        }

        $duration = $workspace->trial_duration_days ?? config('subscription.trial_duration_days', 30);

        return $workspace->trial_started_at->addDays($duration)->isPast();
    }

    public function getRemainingTrialDays(Workspace $workspace): int
    {
        if ($this->isPaid($workspace)) {
            return PHP_INT_MAX;
        }

        if (! $workspace->trial_started_at) {
            return 0;
        }

        $duration = $workspace->trial_duration_days ?? config('subscription.trial_duration_days', 30);
        $expiresAt = $workspace->trial_started_at->addDays($duration);
        $remaining = (int) now()->diffInDays($expiresAt, false);

        return max(0, $remaining);
    }

    public function isExpiringSoon(Workspace $workspace): bool
    {
        $warningDays = config('subscription.expiry_warning_days', 7);

        return ! $this->isPaid($workspace)
            && ! $this->isTrialExpired($workspace)
            && $this->getRemainingTrialDays($workspace) <= $warningDays;
    }

    public function canAddMember(Workspace $workspace): bool
    {
        if ($this->isPaid($workspace)) {
            return true;
        }

        $max = config('subscription.free_max_members', 5);
        $current = $workspace->members()->count();

        if ($current >= $max) {
            Log::warning('Limite membres atteinte', [
                'workspace_id' => $workspace->id,
                'limit_type' => 'max_members',
                'current_value' => $current,
                'max_value' => $max,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param  int  $sizeBytes  Taille du fichier en octets
     */
    public function canUploadFile(Workspace $workspace, int $sizeBytes): bool
    {
        if ($this->isPaid($workspace)) {
            return true;
        }

        $maxMb = config('subscription.free_max_file_size_mb', 2);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($sizeBytes > $maxBytes) {
            Log::warning('Limite taille fichier atteinte', [
                'workspace_id' => $workspace->id,
                'limit_type' => 'max_file_size',
                'current_value' => $sizeBytes,
                'max_value' => $maxBytes,
            ]);

            return false;
        }

        return true;
    }

    public function canUploadStorage(Workspace $workspace, int $sizeBytes): bool
    {
        if ($this->isPaid($workspace)) {
            return true;
        }

        $maxMb = config('subscription.free_max_storage_mb', 100);
        $maxBytes = $maxMb * 1024 * 1024;

        // Calcule le stockage déjà utilisé via les documents du workspace
        $usedBytes = Document::query()
            ->whereHasMorph(
                'documentable',
                [Projet::class],
                fn ($q) => $q->where('workspace_id', $workspace->id)
            )
            ->sum('file_size') ?? 0;

        if (($usedBytes + $sizeBytes) > $maxBytes) {
            Log::warning('Limite stockage atteinte', [
                'workspace_id' => $workspace->id,
                'limit_type' => 'max_storage',
                'current_value' => $usedBytes + $sizeBytes,
                'max_value' => $maxBytes,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Retourne un résumé de l'état d'abonnement pour l'API / frontend.
     */
    public function summary(Workspace $workspace): array
    {
        $memberCount = $workspace->members()->count();
        $maxMembers = config('subscription.free_max_members', 5);

        return [
            'subscription_mode' => $workspace->subscription_mode,
            'trial_started_at' => $workspace->trial_started_at?->toISOString(),
            'trial_duration_days' => $workspace->trial_duration_days,
            'trial_expired' => $this->isTrialExpired($workspace),
            'remaining_trial_days' => $this->getRemainingTrialDays($workspace),
            'expiring_soon' => $this->isExpiringSoon($workspace),
            'limits' => [
                'members' => ['current' => $memberCount, 'max' => $maxMembers, 'reached' => $memberCount >= $maxMembers],
                'file_size_mb' => config('subscription.free_max_file_size_mb', 2),
                'storage_mb' => config('subscription.free_max_storage_mb', 100),
            ],
        ];
    }
}
