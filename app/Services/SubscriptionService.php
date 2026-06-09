<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Plan;
use App\Models\Projet;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function isPaid(Workspace $workspace): bool
    {
        return $workspace->subscription_mode === 'paid'
            || $workspace->subscription_status === 'active';
    }

    /**
     * Plan effectivement appliqué pour les limites :
     *  - abonnement payant actif → le plan souscrit ;
     *  - sinon (essai/lapsed/verrouillé/aucun plan) → le plan gratuit de repli.
     * Permet à canAddMember/canUpload* de lire les limites du bon plan.
     */
    public function effectivePlan(Workspace $workspace): ?Plan
    {
        if ($this->isPaid($workspace) && $workspace->plan_id) {
            return $workspace->plan;
        }

        return Plan::free();
    }

    /**
     * Verrou dur : accès totalement révoqué (HTTP 402).
     * Posé explicitement via subscription_status = 'locked'.
     */
    public function isLocked(Workspace $workspace): bool
    {
        return $workspace->subscription_status === 'locked';
    }

    /**
     * Active un abonnement payant suite à un paiement confirmé (Phase 9).
     * C'est la transition pending → active : pose le plan, le mode payant,
     * le statut actif et la date de fin de période.
     */
    public function activateFromPayment(Workspace $workspace, Plan $plan): void
    {
        $periodDays = $plan->billing_period === 'yearly' ? 365 : 30;

        $workspace->forceFill([
            'plan_id' => $plan->id,
            'subscription_mode' => 'paid',
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays($periodDays),
        ])->save();
    }

    /**
     * Réconcilie le statut quand un essai/abonnement payant a expiré :
     * bascule en 'lapsed' (rétrogradé au plan gratuit, accès limité).
     * Le verrou dur ('locked') reste une décision séparée (super_admin / Phase 9).
     */
    public function reconcileStatus(Workspace $workspace): void
    {
        if ($this->isLocked($workspace)) {
            return;
        }

        // Abonnement payant dont la période est échue → lapsed.
        if ($workspace->subscription_status === 'active'
            && $workspace->subscription_ends_at
            && $workspace->subscription_ends_at->isPast()) {
            $workspace->forceFill([
                'subscription_status' => 'lapsed',
                'subscription_mode' => 'trial', // n'est plus payant
            ])->save();

            return;
        }

        // Essai échu → lapsed.
        if (in_array($workspace->subscription_status, ['trial', 'free'], true)
            && $this->isTrialExpired($workspace)) {
            $workspace->forceFill(['subscription_status' => 'lapsed'])->save();
        }
    }

    /**
     * Lit une limite numérique : priorité au plan effectif (-1 = illimité),
     * repli sur la valeur de config si aucun plan disponible.
     */
    private function planLimit(Workspace $workspace, string $planColumn, string $configKey, int $configDefault): int
    {
        $plan = $this->effectivePlan($workspace);
        if ($plan) {
            return (int) $plan->{$planColumn};
        }

        return (int) config($configKey, $configDefault);
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
        $max = $this->planLimit($workspace, 'max_members', 'subscription.free_max_members', 5);
        if ($max === -1) {
            return true; // illimité
        }

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
        $maxMb = $this->planLimit($workspace, 'max_file_size_mb', 'subscription.free_max_file_size_mb', 2);
        if ($maxMb === -1) {
            return true; // illimité
        }

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
        $maxMb = $this->planLimit($workspace, 'max_storage_mb', 'subscription.free_max_storage_mb', 100);
        if ($maxMb === -1) {
            return true; // illimité
        }

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
        $plan = $this->effectivePlan($workspace);
        // Perf : réutilise members_count si le workspace a été chargé avec
        // withCount('members') (listes admin), au lieu d'une requête par ligne.
        $memberCount = $workspace->members_count ?? $workspace->members()->count();
        $maxMembers = $this->planLimit($workspace, 'max_members', 'subscription.free_max_members', 5);

        return [
            'subscription_mode' => $workspace->subscription_mode,
            'subscription_status' => $workspace->subscription_status,
            'subscription_ends_at' => $workspace->subscription_ends_at?->toISOString(),
            'is_locked' => $this->isLocked($workspace),
            'plan' => $plan ? [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'nom_fr' => $plan->nom_fr,
                'nom_en' => $plan->nom_en,
                'is_free' => $plan->is_free,
                'price' => $plan->price,
                'currency' => $plan->currency,
            ] : null,
            'trial_started_at' => $workspace->trial_started_at?->toISOString(),
            'trial_duration_days' => $workspace->trial_duration_days,
            'trial_expired' => $this->isTrialExpired($workspace),
            'remaining_trial_days' => $this->getRemainingTrialDays($workspace),
            'expiring_soon' => $this->isExpiringSoon($workspace),
            'limits' => [
                'members' => ['current' => $memberCount, 'max' => $maxMembers, 'reached' => $maxMembers !== -1 && $memberCount >= $maxMembers],
                'file_size_mb' => $this->planLimit($workspace, 'max_file_size_mb', 'subscription.free_max_file_size_mb', 2),
                'storage_mb' => $this->planLimit($workspace, 'max_storage_mb', 'subscription.free_max_storage_mb', 100),
            ],
        ];
    }
}
