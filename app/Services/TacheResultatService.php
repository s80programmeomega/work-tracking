<?php

namespace App\Services;

use App\Jobs\TransmettreResultatAuN1Job;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Notifications\ResultatApprouveN0Notification;
use App\Notifications\ResultatRenvoyeNotification;
use App\Notifications\ResultatSoumisN0Notification;
use App\Notifications\ResultatTransmisAutoNotification;
use Illuminate\Support\Facades\Log;

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
