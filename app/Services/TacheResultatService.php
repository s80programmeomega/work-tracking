<?php

namespace App\Services;

use App\Events\Realtime\PendingValidationCountChanged;
use App\Events\Realtime\ResultatStatutChanged;
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
    public function __construct(
        protected EvaluationScoreService $scoreService,
        protected NotificationService $notificationService,
    ) {}

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

        if ($responsable) {
            // G2: garde-fou central remplace l'ancien if inline. Couvre
            // aussi le cas où le responsable serait l'acteur (auto-soumis).
            $this->notificationService->sendUnlessSelf(
                $responsable,
                $actor,
                new ResultatSoumisN0Notification($resultat, $actor)
            );
        }

        // Dispatch the timeout job
        $timeoutHours = $resultat->tache->activite?->projet?->workspace?->getSetting('validation_timeout_hours', 48);
        $timeoutHours = max(24, min(168, (int) $timeoutHours));

        $job = new TransmettreResultatAuN1Job($resultat->id);
        dispatch($job->delay(now()->addHours($timeoutHours)));

        $this->broadcastResultatChanged($resultat);

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

        // G2: si l'acteur N0 est aussi l'auteur du résultat (auto-approbation,
        // cas rare mais possible), on n'envoie pas la notification.
        $this->notificationService->sendUnlessSelf(
            $resultat->user,
            $actor,
            new ResultatApprouveN0Notification($resultat, $actor)
        );

        $this->broadcastResultatChanged($resultat);

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

        // G2: garde-fou anti-auto-notification (cas pathologique où N0 = auteur).
        $this->notificationService->sendUnlessSelf(
            $resultat->user,
            $actor,
            new ResultatRenvoyeNotification($resultat, $actor, $commentaire)
        );

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

        // Notify the N1 validator (activity responsable).
        // G2: garde-fou si l'acteur du bypass est lui-même le N1 (rare,
        // mais possible si un cadre est responsable d'une de ses activités).
        $n1 = $resultat->tache->activite?->responsable;
        if ($n1) {
            $this->notificationService->sendUnlessSelf(
                $n1,
                $actor,
                new BypassActivatedNotification($resultat, $actor)
            );
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

        // Scoring is NOT handled here — rejeterN1() owns the scoring decision
        // and may call this method to update bypass tracking + flag. Keeping
        // the score side-effect on the wrapper keeps the dispatch single-source.
    }

    /**
     * Task 7 wrapper: N1 validates a result.
     *
     * Delegates the model-level validation logic to TacheResultat::validateByN1
     * (which handles statut flags, notifications to author/validator/N2), then
     * decides the scoring impact based on the pre-validation state of the result:
     *
     *   - If the result was returned by N0 (action_n0 = 'renvoye') OR came via
     *     bypass → 'validated_despite_return' (PENALTY on the responsable).
     *   - Otherwise (normal submission, no N0 return, no bypass) → 'no_impact'.
     *
     * The decision lookup runs BEFORE validateByN1() because validateByN1 doesn't
     * change action_n0/bypass_active, but reading the snapshot up front makes the
     * scoring intent obvious to anyone tracing the flow.
     */
    public function validerN1(TacheResultat $resultat, User $n1Actor, ?string $commentaire = null): void
    {
        $decision = $this->resolveN1Decision($resultat, validate: true);

        $resultat->validateByN1($n1Actor, $commentaire);

        $this->scoreService->calculerImpactN1($resultat, $decision, $n1Actor);

        $this->broadcastResultatChanged($resultat);

        Log::info('N1 a validé le résultat', [
            'user_id' => $n1Actor->id,
            'tache_resultat_id' => $resultat->id,
            'decision' => $decision,
        ]);
    }

    /**
     * Task 7 wrapper: N1 rejects a result (confirms the N0 return).
     *
     * Delegates to TacheResultat::reject() (handles statut flags + notifications),
     * then triggers a BONUS for the responsable when the rejection confirms a
     * previous N0 return or invalidates a bypass attempt.
     */
    public function rejeterN1(TacheResultat $resultat, User $n1Actor, string $commentaire): void
    {
        $decision = $this->resolveN1Decision($resultat, validate: false);
        $hadBypass = (bool) $resultat->bypass_active;

        $resultat->reject($n1Actor, $commentaire, 'n1');

        // If the rejected result had bypass_active, update the assignee's
        // bypass_count and (when ≥ 3) flag escalades_abusives. The model's
        // reject() doesn't know about bypass; this service is the seam.
        if ($hadBypass) {
            $this->invaliderBypassN1($resultat, $n1Actor);
        }

        $this->scoreService->calculerImpactN1($resultat, $decision, $n1Actor);

        $this->broadcastResultatChanged($resultat);

        Log::info('N1 a rejeté le résultat', [
            'user_id' => $n1Actor->id,
            'tache_resultat_id' => $resultat->id,
            'decision' => $decision,
            'had_bypass' => $hadBypass,
        ]);
    }

    /**
     * Decide which scoring path applies to this N1 action.
     *
     * Returns one of:
     *   - 'validated_despite_return'  (penalty)  — N1 validates a returned/bypassed result
     *   - 'confirmed_return'          (bonus)    — N1 rejects a returned/bypassed result
     *   - 'no_impact'                            — normal flow, no N0 return, no bypass
     *
     * Why a separate method: the same rule is consulted by validerN1 AND
     * rejeterN1, so we centralise it. Also makes tests easier to write — you
     * can assert the decision string directly without going through the
     * full controller flow.
     */
    private function resolveN1Decision(TacheResultat $resultat, bool $validate): string
    {
        $cameViaN0Return = $resultat->action_n0 === 'renvoye' || (bool) $resultat->bypass_active;

        if (! $cameViaN0Return) {
            return 'no_impact';
        }

        return $validate ? 'validated_despite_return' : 'confirmed_return';
    }

    private function broadcastResultatChanged(TacheResultat $resultat): void
    {
        $resultat->loadMissing(['tache.activite.projet']);
        $workspaceId = $resultat->tache?->activite?->projet?->workspace_id;

        try {
            event(new ResultatStatutChanged($resultat));

            if ($workspaceId) {
                $validatorIds = collect([$resultat->validateur_n1_id, $resultat->validateur_n2_id])
                    ->filter()
                    ->values()
                    ->all();
                event(new PendingValidationCountChanged($workspaceId, $validatorIds));
            }
        } catch (\Throwable $e) {
            Log::warning('Resultat broadcast failed', ['error' => $e->getMessage()]);
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
