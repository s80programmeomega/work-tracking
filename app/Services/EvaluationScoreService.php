<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvaluationScore;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Notifications\ScoreUpdatedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Scoring service for N1 validation decisions on TacheResultats that came via
 * the N0 circuit (either a returned-and-resubmitted flow or a bypass flow).
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  WHAT IT DOES
 * ──────────────────────────────────────────────────────────────────────────
 *
 * For each N1 decision, this service:
 *   1. Resolves the "task responsable" (the user with `is_responsable = true`
 *      on the `tache_user` pivot) — this is who gets scored, not the assignee.
 *   2. Decides if the decision merits a penalty, a bonus, or no impact.
 *   3. Writes one row to `evaluation_scores` carrying the delta (`valeur`)
 *      and a `meta` JSON for traceability.
 *   4. Mirrors the decision to `validation_audit_logs` so the chronological
 *      trail per result is complete (see Task 5/6 audit log usage).
 *   5. Notifies the responsable so they see the score change in real-time.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  WHY SCORE THE RESPONSABLE INSTEAD OF THE ASSIGNEE
 * ──────────────────────────────────────────────────────────────────────────
 *
 * The assignee already gets evaluated under Task 9's 8-criterion sheet
 * (work quality, deadline respect, etc.). Task 7 specifically scores the
 * *gatekeeper* — the responsable who decided to return a result.
 *
 *   - If N1 reverses an N0 return → the responsable returned unjustifiably → PENALTY.
 *   - If N1 confirms an N0 return → the responsable returned correctly  → BONUS.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  WHY DELTAS PER ROW INSTEAD OF AGGREGATE COLUMNS
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Each decision = one row with the ±1.0 delta. We compute running totals at
 * read time via SUM(valeur). Three reasons this beats per-period aggregates:
 *   - Audit trail: every score change is reversible (delete one row).
 *   - Range flexibility: arbitrary date ranges become trivial sums.
 *   - Traceability: each row carries the decision context in `meta`.
 *
 * See Task 7 in docs/IMPLEMENTATION_PLAN.md and the design decisions captured
 * in the 2026-05-19 session.
 */
class EvaluationScoreService
{
    // ──────────────────────────────────────────────────────────────────────
    //  CRITERIA — stored in `evaluation_scores.critere`
    // ──────────────────────────────────────────────────────────────────────
    //
    // We use class constants instead of magic strings so:
    //   - typos surface as undefined-constant errors at the call site
    //   - tests use the same identifiers as production code (no drift)
    //   - find-usages tooling actually finds them (greppable)

    /** N1 approved a result that N0 had returned → responsable was wrong to return. */
    public const CRITERE_VALIDATED_DESPITE_RETURN = 'n1_validated_despite_return';

    /** N1 rejected a returned/bypassed result → responsable was right to return. */
    public const CRITERE_CONFIRMED_RETURN = 'n1_confirmed_return';

    // ──────────────────────────────────────────────────────────────────────
    //  SCORE DELTAS — locked at ±1.0 per session decision 2026-05-19
    // ──────────────────────────────────────────────────────────────────────
    //
    // Kept as constants so:
    //   - changing them later is a one-line update
    //   - tests reference the same constants (no hard-coded ±1.0 in assertions)
    //   - if we ever move to workspace-configurable magnitudes, only the
    //     match() expression below needs to switch from constants to settings.

    /** Negative delta applied when N1 reverses an N0 return. */
    public const PENALTY = -1.0;

    /** Positive delta applied when N1 confirms an N0 return. */
    public const BONUS = 1.0;

    /**
     * Record the scoring impact of an N1 decision on the parent-task responsable.
     *
     * Three call paths (the caller picks one and passes the matching $decision):
     *
     *   'validated_despite_return'
     *      N1 approved a TacheResultat whose N0 status was 'renvoye' or which
     *      came via bypass. This is the penalty path: responsable returned
     *      unjustifiably. → row written with valeur = PENALTY.
     *
     *   'confirmed_return'
     *      N1 rejected a TacheResultat that was returned/bypassed. This is the
     *      bonus path: responsable's return decision was upheld.
     *      → row written with valeur = BONUS.
     *
     *   'no_impact'
     *      N1 approved a result that arrived via a normal flow (no N0 return,
     *      no bypass). Nothing to score here — work-quality scoring belongs to
     *      Task 9, not Task 7. → no row written, no audit log, no notification.
     *
     * The 'no_impact' branch exists so callers in TacheResultatService can
     * always invoke this method without first deciding whether scoring applies.
     * Keeps the call site simple and the dispatch logic centralized.
     *
     * @return EvaluationScore|null The created score row, or null if no_impact / no responsable.
     */
    public function calculerImpactN1(TacheResultat $resultat, string $decision, User $n1Actor): ?EvaluationScore
    {
        // Fast path — caller invoked us but the decision doesn't trigger scoring.
        // Returning null lets the caller distinguish "ran with no effect" from
        // "ran and produced a score row."
        if ($decision === 'no_impact') {
            return null;
        }

        // Find who to penalize/reward. If a task has no responsable (data
        // integrity edge case), we don't want to crash the N1 validation —
        // we just skip the scoring and emit a warning for ops to investigate.
        $responsable = $this->resolveTaskResponsable($resultat);
        if (! $responsable) {
            Log::warning('Impact score N1 ignoré — tâche sans responsable', [
                'tache_resultat_id' => $resultat->id,
                'tache_id' => $resultat->tache_id,
                'decision' => $decision,
                'reason' => 'no_task_responsable',
            ]);

            return null;
        }

        // Map the decision to a (critere, valeur) pair via match().
        //
        // match() is PHP 8's strict-comparison switch that returns a value.
        // Notice the `default => throw ...` arm: unknown decision strings fail
        // loudly rather than silently producing a score-less call — this is
        // important defense-in-depth against caller typos and future drift.
        [$critere, $valeur] = match ($decision) {
            'validated_despite_return' => [self::CRITERE_VALIDATED_DESPITE_RETURN, self::PENALTY],
            'confirmed_return' => [self::CRITERE_CONFIRMED_RETURN, self::BONUS],
            default => throw new \InvalidArgumentException("Décision N1 inconnue: {$decision}"),
        };

        // Write the score row.
        //
        // The period defaults to the calendar month of the decision. We use
        // ->toDateString() to coerce Carbon → 'Y-m-d' because the DB column
        // is `date` not `datetime` — passing a Carbon directly would write the
        // whole timestamp and break SUM-by-period queries.
        //
        // `meta` carries the full decision context inline so downstream views
        // (Task 9 evaluation sheet, dispute resolution, audit reports) don't
        // need JOINs to display "why was this score awarded."
        $score = EvaluationScore::create([
            'user_id' => $responsable->id,
            'periode_start' => now()->startOfMonth()->toDateString(),
            'periode_end' => now()->endOfMonth()->toDateString(),
            'critere' => $critere,
            'valeur' => $valeur,
            'meta' => [
                'tache_resultat_id' => $resultat->id,
                'tache_id' => $resultat->tache_id,
                'decision' => $decision,
                'n1_actor_id' => $n1Actor->id,
                'n0_actor_id' => $resultat->n0_actor_id,
                'had_bypass' => (bool) $resultat->bypass_active,
            ],
        ]);

        // Mirror the decision in validation_audit_logs.
        //
        // Why duplicate? Because validation_audit_logs is the canonical
        // chronological log per TacheResultat (it records soumis → approuve →
        // renvoye → bypass → bypass_invalide). The score row is a side-effect;
        // the audit row is the event. They live separately so we can purge
        // scores (e.g., recalculate for a period) without losing history.
        ValidationAuditLog::create([
            'tache_resultat_id' => $resultat->id,
            'actor_id' => $n1Actor->id,
            'action' => "n1_{$decision}",
            'context' => [
                'critere' => $critere,
                'valeur' => $valeur,
                'responsable_id' => $responsable->id,
            ],
        ]);

        // In-app notification so the responsable sees their score moved.
        // Email policy lives in the Notification class (Guide 12).
        $responsable->notify(new ScoreUpdatedNotification($score));

        Log::info('Score N1 enregistré', [
            'user_id' => $responsable->id,
            'critere' => $critere,
            'valeur' => $valeur,
            'tache_resultat_id' => $resultat->id,
            'triggered_by' => 'n1_validation',
        ]);

        return $score;
    }

    /**
     * Sum of all score deltas for a user within a date range.
     *
     * This is the read-side counterpart to calculerImpactN1. The dashboard
     * (Task 7) and evaluation sheet (Task 9) call this; tests call it to
     * assert the running total after a sequence of decisions.
     *
     * Defaults to the current calendar month if no range is given — matches
     * the default period semantics used at write time.
     *
     * Returns float because PHP's SUM over a DECIMAL column gives a string
     * by default with the Eloquent QueryBuilder; the explicit cast normalises
     * the type for callers.
     *
     * @param  string|null  $start  ISO date 'Y-m-d' (defaults to start of current month)
     * @param  string|null  $end  ISO date 'Y-m-d' (defaults to end of current month)
     */
    public function totalForUser(User $user, ?string $start = null, ?string $end = null): float
    {
        $start = $start ?? now()->startOfMonth()->toDateString();
        $end = $end ?? now()->endOfMonth()->toDateString();

        return (float) EvaluationScore::query()
            ->where('user_id', $user->id)
            ->inPeriod($start, $end)
            ->sum('valeur');
    }

    /**
     * Resolve the task responsable from a result.
     *
     * The responsable is identified by the `is_responsable = true` flag on
     * the `tache_user` pivot (set when the task was assigned, per Task 2/3).
     * Encapsulated as a private helper because it's the same lookup we'll
     * need in dashboard queries — kept here so the rule lives in one place.
     */
    private function resolveTaskResponsable(TacheResultat $resultat): ?User
    {
        return $resultat->tache->assignees()
            ->wherePivot('is_responsable', true)
            ->first();
    }
}
