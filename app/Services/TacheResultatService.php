<?php

namespace App\Services;

use App\Jobs\TransmettreResultatAuN1Job;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Notifications\BypassActivatedNotification;
use App\Notifications\EscaladesAbusivesNotification;
use App\Notifications\ResultatApprouveN0Notification;
use App\Notifications\ResultatRenvoyeNotification;
use App\Notifications\ResultatSoumisN0Notification;
use App\Notifications\ResultatTransmisAutoNotification;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class TacheResultatService
{
    /**
     * Called when an intervenant submits a result.
     * Sets statut to en_verification_n0, notifies the task responsable, dispatches the 48h job.
     */
    public function soumettre(TacheResultat $resultat, User $actor): void
    {
        $resultat->update([
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
        ]);

        $this->writeAuditLog($resultat, $actor, 'soumis', [
            'taux_realisation' => $resultat->taux_realisation,
        ]);

        // Notify the task responsable (N0 actor)
        $responsable = $resultat->tache->assignees()
            ->wherePivot('is_responsable', true)
            ->first();

        if ($responsable && $responsable->id !== $actor->id) {
            $responsable->notify(new ResultatSoumisN0Notification($resultat, $actor));
        }

        // Dispatch the timeout job
        $timeoutHours = $resultat->tache->activite?->projet?->workspace?->getSetting('validation_timeout_hours', 48);
        $timeoutHours = max(24, min(168, (int) $timeoutHours));

        $job = new TransmettreResultatAuN1Job($resultat->id);
        dispatch($job->delay(now()->addHours($timeoutHours)));

        Log::info('Résultat soumis au N0', [
            'user_id' => $actor->id,
            'tache_resultat_id' => $resultat->id,
            'delay_hours' => $timeoutHours,
        ]);
    }

    /**
     * N0 approves: forwards result to N1 validation.
     */
    public function approuverN0(TacheResultat $resultat, User $actor): void
    {
        $resultat->update([
            'statut' => 'en_validation_n1',
            'action_n0' => 'approuve',
            'n0_actor_id' => $actor->id,
            'action_n0_le' => now(),
        ]);

        $this->writeAuditLog($resultat, $actor, 'approuve', [
            'taux_realisation' => $resultat->taux_realisation,
        ]);

        $resultat->user->notify(new ResultatApprouveN0Notification($resultat, $actor));

        Log::info('Résultat approuvé N0', [
            'user_id' => $actor->id,
            'tache_resultat_id' => $resultat->id,
        ]);
    }

    /**
     * N0 returns the result to the intervenant with a mandatory comment (R4: min 30 chars).
     */
    public function renvoyerN0(TacheResultat $resultat, User $actor, string $commentaire): void
    {
        if (mb_strlen(trim($commentaire)) < 30) {
            Log::warning('Commentaire N0 trop court', [
                'user_id' => $actor->id,
                'tache_resultat_id' => $resultat->id,
                'reason' => 'comment_too_short',
                'length' => mb_strlen(trim($commentaire)),
            ]);

            throw new \InvalidArgumentException(__('circuit_validation.errors.comment_too_short'));
        }

        $resultat->update([
            'statut' => 'a_refaire',
            'action_n0' => 'renvoye',
            'commentaire_n0' => $commentaire,
            'n0_actor_id' => $actor->id,
            'action_n0_le' => now(),
        ]);

        $this->writeAuditLog($resultat, $actor, 'renvoye', [
            'commentaire' => $commentaire,
        ]);

        $resultat->user->notify(new ResultatRenvoyeNotification($resultat, $actor, $commentaire));

        Log::info('Résultat renvoyé N0', [
            'user_id' => $actor->id,
            'tache_resultat_id' => $resultat->id,
        ]);
    }

    /**
     * Called by the timeout job when N0 has not acted within the deadline.
     */
    public function transmettreAuN1(TacheResultat $resultat): void
    {
        // If N0 already acted, the job is stale — skip
        if ($resultat->action_n0 !== null) {
            Log::info('TransmettreAuN1 ignoré — N0 a déjà agi', [
                'tache_resultat_id' => $resultat->id,
                'action_n0' => $resultat->action_n0,
            ]);

            return;
        }

        $systemUser = User::find($resultat->n0_actor_id ?? $resultat->user_id);

        $resultat->update([
            'statut' => 'en_validation_n1',
            'action_n0' => 'timeout',
            'action_n0_le' => now(),
        ]);

        $this->writeAuditLog($resultat, $systemUser, 'timeout', [
            'reason' => 'n0_inaction',
        ]);

        // Notify the task responsable that it was auto-forwarded
        $responsable = $resultat->tache->assignees()
            ->wherePivot('is_responsable', true)
            ->first();

        if ($responsable) {
            $responsable->notify(new ResultatTransmisAutoNotification($resultat));
        }

        Log::info('Résultat transmis automatiquement au N1 (timeout)', [
            'tache_resultat_id' => $resultat->id,
        ]);
    }

    /**
     * Assignee activates the anti-sabotage bypass after an unjustified N0 return.
     * R3: single-use per submission (HTTP 409).
     * R5: motif min 50 chars (HTTP 422).
     * Sets statut to en_validation_n1 with bypass_active flag.
     */
    public function activerBypass(TacheResultat $resultat, User $actor, string $motif): void
    {
        if (mb_strlen(trim($motif)) < 50) {
            Log::warning('Motif bypass trop court', [
                'user_id' => $actor->id,
                'tache_resultat_id' => $resultat->id,
                'reason' => 'motif_too_short',
                'length' => mb_strlen(trim($motif)),
            ]);

            throw new \InvalidArgumentException(__('circuit_validation.errors.motif_too_short'));
        }

        if ($resultat->bypass_active) {
            Log::warning('Bypass déjà utilisé', [
                'user_id' => $actor->id,
                'tache_resultat_id' => $resultat->id,
                'reason' => 'bypass_already_used',
            ]);

            throw new \DomainException(__('circuit_validation.errors.bypass_already_used'));
        }

        $resultat->update([
            'statut' => 'en_validation_n1',
            'bypass_active' => true,
            'motif_bypass' => $motif,
            'bypass_le' => now(),
        ]);

        $this->writeAuditLog($resultat, $actor, 'bypass', [
            'motif' => $motif,
        ]);

        // Notify the N1 validator (activity responsable)
        $n1 = $resultat->tache->activite?->responsable;
        if ($n1) {
            $n1->notify(new BypassActivatedNotification($resultat, $actor));
        }

        Log::info('Bypass anti-sabotage activé', [
            'user_id' => $actor->id,
            'tache_resultat_id' => $resultat->id,
        ]);
    }

    /**
     * Called when N1 invalidates a bypass (confirms N0 was right to return).
     * Increments bypass_count on tache_user pivot.
     * Sets escalades_abusives flag when 3 consecutive bypasses are invalidated.
     */
    public function invaliderBypassN1(TacheResultat $resultat, User $n1Actor): void
    {
        $tache = $resultat->tache;
        $auteur = $resultat->user;

        // Increment bypass_count on the pivot for the result author
        $pivot = $tache->assignees()->where('user_id', $auteur->id)->first()?->pivot;

        if (! $pivot) {
            return;
        }

        $newCount = ($pivot->bypass_count ?? 0) + 1;

        $tache->assignees()->updateExistingPivot($auteur->id, [
            'bypass_count' => $newCount,
        ]);

        $this->writeAuditLog($resultat, $n1Actor, 'bypass_invalide', [
            'consecutive_invalid_count' => $newCount,
        ]);

        if ($newCount >= 3) {
            $tache->assignees()->updateExistingPivot($auteur->id, [
                'escalades_abusives' => true,
            ]);

            Log::warning('Drapeau escalades_abusives activé', [
                'user_id' => $auteur->id,
                'tache_id' => $tache->id,
                'consecutive_invalid_bypasses' => $newCount,
            ]);

            // Notify task responsable and their manager
            $responsable = $tache->assignees()->wherePivot('is_responsable', true)->first();
            if ($responsable && $responsable->id !== $auteur->id) {
                $responsable->notify(new EscaladesAbusivesNotification($resultat, $auteur, $newCount));
            }

            $managerRoleId = Role::where('name', 'manager')->value('id');
            $manager = $managerRoleId
                ? $tache->activite?->projet?->workspace?->members()
                    ->wherePivot('role_id', $managerRoleId)
                    ->first()
                : null;
            if ($manager && $manager->id !== $responsable?->id) {
                $manager->notify(new EscaladesAbusivesNotification($resultat, $auteur, $newCount));
            }
        }
    }

    private function writeAuditLog(TacheResultat $resultat, User $actor, string $action, array $context = []): void
    {
        ValidationAuditLog::create([
            'tache_resultat_id' => $resultat->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
