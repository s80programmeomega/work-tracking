<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvaluationScore;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Notifications\ScoreUpdatedNotification;
use Illuminate\Support\Facades\DB;
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
        // G2: guard contre l'auto-notification quand le responsable est
        // lui-même l'acteur N1 (cas de bord rare mais possible).
        app(NotificationService::class)
            ->sendUnlessSelf($responsable, $n1Actor, new ScoreUpdatedNotification($score));

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

    // ──────────────────────────────────────────────────────────────────────
    //  TASK 9 — 8-CRITERION EVALUATION SHEET
    // ──────────────────────────────────────────────────────────────────────
    //
    // Weights sum to 100. Subtasks count at coefficient 0.5 wherever a count
    // mixes tasks and subtasks (criteria 1, 2, 7 — see per-criterion comments).
    //
    // Each criterion produces a value in [0..1] BEFORE weighting; the final
    // global score is the weighted sum, also in [0..1]. Callers can multiply
    // by 100 for percentage display — kept as a unit fraction internally so
    // we don't lose precision through repeated multiplication/division.
    //
    // Returning the breakdown (per-criterion raw values) alongside the global
    // score is intentional: the evaluation sheet (Vue page) displays every
    // criterion individually, and the unjustified-return-rate indicator
    // reuses one of the criteria directly. One service call, one DB pass,
    // no second roundtrip.
    //
    // Weights here are duplicated from the implementation plan; if they ever
    // change, update both this constant array AND docs/IMPLEMENTATION_PLAN.md
    // and the changelog row in docs/PERMISSIONS_MATRIX.md. We deliberately
    // don't pull from a config file — these weights are part of the product
    // spec, not an operational setting.

    public const CRITERION_COMPLETION = 'completion_rate';

    public const CRITERION_DEADLINE = 'deadline_respect';

    public const CRITERION_QUALITY = 'result_quality';

    public const CRITERION_FIRST_PASS = 'first_pass_validation';

    public const CRITERION_JUSTIFIED_RETURNS = 'justified_returns';

    public const CRITERION_INACTIONS = 'inactions';

    public const CRITERION_VOLUME = 'work_volume';

    public const CRITERION_COORDINATION = 'team_coordination';

    /**
     * Weighted criteria → fraction (must sum to 1.0).
     *
     * Public so tests can iterate over the same source of truth as the
     * service itself — no hard-coded weights in test assertions.
     */
    public const CRITERIA_WEIGHTS = [
        self::CRITERION_COMPLETION => 0.20,
        self::CRITERION_DEADLINE => 0.20,
        self::CRITERION_QUALITY => 0.15,
        self::CRITERION_FIRST_PASS => 0.15,
        self::CRITERION_JUSTIFIED_RETURNS => 0.10,
        self::CRITERION_INACTIONS => 0.05,
        self::CRITERION_VOLUME => 0.10,
        self::CRITERION_COORDINATION => 0.05,
    ];

    /** Subtasks weigh half a task in mixed counts (criteria 1, 2, 7). */
    public const SUBTASK_COEFFICIENT = 0.5;

    /**
     * Volume cap used to normalise criterion 7 into [0..1].
     *
     * "Work volume" is a count; to be comparable with the other criteria it
     * needs a soft ceiling. 50 weighted units (tasks + 0.5 × subtasks) over
     * a typical month is heavy but realistic; users beyond that get max
     * volume credit. The ceiling is tuned per workspace later if needed.
     */
    public const VOLUME_CEILING = 50.0;

    /**
     * Threshold above which an unjustified-return rate triggers the manager
     * alert notification (and the red banner on the sheet).
     */
    public const UNJUSTIFIED_RETURN_ALERT_THRESHOLD = 0.40;

    /**
     * Compute the full 8-criterion evaluation sheet for a user over a period.
     *
     * Period defaults to the last 30 days (rolling window) when no range is
     * given — matches the agent-sheet UI default and is cheap to recompute
     * on every page load. Use explicit dates when generating a periodic
     * report (e.g. monthly summary mail).
     *
     * Returned shape (consumed by EvaluationSheetResource + the Vue page):
     *
     *   [
     *     'periode_start' => 'Y-m-d',
     *     'periode_end'   => 'Y-m-d',
     *     'score_global'  => 0.78,  // weighted sum, [0..1]
     *     'criteria'      => [
     *       'completion_rate'        => ['raw' => 0.92, 'weight' => 0.20, 'weighted' => 0.184, 'meta' => [...]],
     *       'deadline_respect'       => [...],
     *       ...
     *     ],
     *     'indicators'    => [
     *       'unjustified_return_rate' => 0.15,                 // for the donut chart
     *       'unjustified_alert'       => false,                // > 40% threshold
     *       'escalades_abusives'      => false,                // any tache_user row with the flag
     *     ],
     *   ]
     *
     * Each criterion is computed by a dedicated private method so the
     * implementation reads top-down like the spec table, and unit tests can
     * exercise each criterion in isolation (Guide 18-adjacent: small,
     * targeted assertions per criterion rather than one mega-test).
     */
    public function calculerScore(User $user, ?string $start = null, ?string $end = null): array
    {
        $start = $start ?? now()->subDays(30)->toDateString();
        $end = $end ?? now()->toDateString();

        $criteria = [
            self::CRITERION_COMPLETION => $this->scoreCompletionRate($user, $start, $end),
            self::CRITERION_DEADLINE => $this->scoreDeadlineRespect($user, $start, $end),
            self::CRITERION_QUALITY => $this->scoreResultQuality($user, $start, $end),
            self::CRITERION_FIRST_PASS => $this->scoreFirstPassValidation($user, $start, $end),
            self::CRITERION_JUSTIFIED_RETURNS => $this->scoreJustifiedReturns($user, $start, $end),
            self::CRITERION_INACTIONS => $this->scoreInactions($user, $start, $end),
            self::CRITERION_VOLUME => $this->scoreWorkVolume($user, $start, $end),
            self::CRITERION_COORDINATION => $this->scoreTeamCoordination($user, $start, $end),
        ];

        // Weighted sum — each criterion's raw [0..1] value times its weight,
        // summed into a [0..1] global score. Decoupling this from the
        // per-criterion methods means the weights can be re-tuned without
        // touching any of the counting logic.
        $globalScore = 0.0;
        $breakdown = [];
        foreach ($criteria as $key => $payload) {
            $weight = self::CRITERIA_WEIGHTS[$key];
            $weighted = $payload['raw'] * $weight;
            $globalScore += $weighted;
            $breakdown[$key] = [
                'raw' => round($payload['raw'], 4),
                'weight' => $weight,
                'weighted' => round($weighted, 4),
                'meta' => $payload['meta'] ?? [],
            ];
        }

        // Two indicators surface on the sheet header (donut + red badge).
        // Computed here so the page renders in one resource call.
        $unjustifiedRate = $this->unjustifiedReturnRate($user, $start, $end);
        $hasEscalades = $this->hasEscaladesAbusives($user);

        if ($unjustifiedRate > self::UNJUSTIFIED_RETURN_ALERT_THRESHOLD) {
            // Loud signal for ops: the responsable is returning too aggressively.
            // Manager-facing notification is dispatched by EvaluationController
            // after this method returns — we only log here so the service stays
            // side-effect-free for read-side calls (tests, dashboards).
            Log::warning('Taux de renvois injustifiés au-dessus du seuil', [
                'user_id' => $user->id,
                'rate' => $unjustifiedRate,
                'threshold' => self::UNJUSTIFIED_RETURN_ALERT_THRESHOLD,
                'periode_start' => $start,
                'periode_end' => $end,
            ]);
        }

        Log::info('Fiche évaluation calculée', [
            'user_id' => $user->id,
            'periode_start' => $start,
            'periode_end' => $end,
            'score_global' => round($globalScore, 4),
            'criteria_breakdown' => array_map(fn ($c) => $c['raw'], $breakdown),
        ]);

        return [
            'periode_start' => $start,
            'periode_end' => $end,
            'score_global' => round($globalScore, 4),
            'criteria' => $breakdown,
            'indicators' => [
                'unjustified_return_rate' => round($unjustifiedRate, 4),
                'unjustified_alert' => $unjustifiedRate > self::UNJUSTIFIED_RETURN_ALERT_THRESHOLD,
                'escalades_abusives' => $hasEscalades,
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Per-criterion implementations — each returns ['raw' => float, 'meta' => array]
    // ──────────────────────────────────────────────────────────────────────
    //
    // Convention:
    //   - 'raw' ∈ [0..1] BEFORE weighting (1.0 = perfect)
    //   - When the denominator is 0 (user had nothing assigned), we return
    //     1.0 rather than 0.0 — penalising someone for not having work
    //     would be unfair. The 'meta' carries the denominator so the UI
    //     can disclose "N/A" if it wants.
    //   - All queries are scoped by `created_at`/`updated_at` in the
    //     period window; the exact column per criterion is chosen so the
    //     denominator matches the spec intent (e.g. tasks STARTED in the
    //     period for volume, results SUBMITTED in the period for quality).

    /**
     * Criterion 1: (assigned tasks done + assigned subtasks done × 0.5) /
     *              (total tasks + total subtasks × 0.5).
     */
    private function scoreCompletionRate(User $user, string $start, string $end): array
    {
        $tachesTotal = $user->taches()
            ->whereBetween('tache_user.created_at', [$start, $end.' 23:59:59'])
            ->count();

        $tachesDone = $user->taches()
            ->whereBetween('tache_user.created_at', [$start, $end.' 23:59:59'])
            ->wherePivot('statut_individuel', 'termine')
            ->count();

        $sousTachesTotal = DB::table('sous_tache_user')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->count();

        $sousTachesDone = DB::table('sous_tache_user')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->where('statut_individuel', 'termine')
            ->count();

        $totalWeighted = $tachesTotal + $sousTachesTotal * self::SUBTASK_COEFFICIENT;
        $doneWeighted = $tachesDone + $sousTachesDone * self::SUBTASK_COEFFICIENT;

        return [
            'raw' => $totalWeighted > 0 ? $doneWeighted / $totalWeighted : 1.0,
            'meta' => [
                'taches_total' => $tachesTotal,
                'taches_done' => $tachesDone,
                'sous_taches_total' => $sousTachesTotal,
                'sous_taches_done' => $sousTachesDone,
            ],
        ];
    }

    /**
     * Criterion 2: results submitted on or before tache.echeance / total submitted.
     *
     * Counts only results submitted in the period (soumis_le between dates).
     * "Submitted before deadline" = soumis_le ≤ tache.echeance. A task with
     * NULL echeance is treated as "no deadline to miss" → counts as on-time.
     */
    private function scoreDeadlineRespect(User $user, string $start, string $end): array
    {
        $resultats = TacheResultat::query()
            ->where('user_id', $user->id)
            ->whereBetween('soumis_le', [$start, $end.' 23:59:59'])
            ->with('tache:id,echeance')
            ->get();

        $total = $resultats->count();
        $onTime = $resultats->filter(function (TacheResultat $r) {
            // Pas d'échéance → on considère le résultat comme livré dans les temps
            // (impossible de "rater" une échéance qui n'existe pas).
            $echeance = $r->tache?->echeance;
            if (! $echeance) {
                return true;
            }

            return $r->soumis_le && $r->soumis_le->lte($echeance);
        })->count();

        return [
            'raw' => $total > 0 ? $onTime / $total : 1.0,
            'meta' => [
                'total_submitted' => $total,
                'on_time' => $onTime,
            ],
        ];
    }

    /**
     * Criterion 3: result quality — proxied by N1 validation rate.
     *
     * The original spec says "Average N1 score on validated results" but
     * the schema doesn't carry a numeric N1 score (validation is boolean
     * + optional commentaire). The closest available proxy is
     *   (results validated at N1) / (results submitted)
     * which captures the same intent: high-quality submissions are
     * accepted, low-quality ones are returned. Re-tune later if a
     * dedicated note_n1 column is added.
     */
    private function scoreResultQuality(User $user, string $start, string $end): array
    {
        $submitted = TacheResultat::query()
            ->where('user_id', $user->id)
            ->whereBetween('soumis_le', [$start, $end.' 23:59:59'])
            ->count();

        $validated = TacheResultat::query()
            ->where('user_id', $user->id)
            ->whereBetween('soumis_le', [$start, $end.' 23:59:59'])
            ->where('valide_par_n1', true)
            ->count();

        return [
            'raw' => $submitted > 0 ? $validated / $submitted : 1.0,
            'meta' => [
                'submitted' => $submitted,
                'validated_n1' => $validated,
                'proxy_used' => 'validation_rate', // honesty for the audit trail
            ],
        ];
    }

    /**
     * Criterion 4: results validated at N1 without ever being returned at N0
     *              and without going through bypass / total validated.
     *
     * "First pass" = the result moved soumis → valide_n1 cleanly. Any row
     * with action_n0 in ('renvoye') or bypass_active = 1 fails the criterion.
     */
    private function scoreFirstPassValidation(User $user, string $start, string $end): array
    {
        $validated = TacheResultat::query()
            ->where('user_id', $user->id)
            ->whereBetween('soumis_le', [$start, $end.' 23:59:59'])
            ->where('valide_par_n1', true)
            ->count();

        $firstPass = TacheResultat::query()
            ->where('user_id', $user->id)
            ->whereBetween('soumis_le', [$start, $end.' 23:59:59'])
            ->where('valide_par_n1', true)
            ->where(function ($q) {
                $q->whereNull('action_n0')->orWhere('action_n0', '!=', 'renvoye');
            })
            ->where('bypass_active', false)
            ->count();

        return [
            'raw' => $validated > 0 ? $firstPass / $validated : 1.0,
            'meta' => [
                'validated_n1' => $validated,
                'first_pass' => $firstPass,
            ],
        ];
    }

    /**
     * Criterion 5: for tasks where this user was responsable — fraction of
     * N0 returns that N1 later confirmed. Penalises "trigger-happy" returns.
     *
     * Source: evaluation_scores rows with critere = 'n1_confirmed_return'
     * or 'n1_validated_despite_return' where this user is the
     * scored responsable (Task 7 already writes these rows).
     */
    private function scoreJustifiedReturns(User $user, string $start, string $end): array
    {
        $scores = EvaluationScore::query()
            ->where('user_id', $user->id)
            ->whereIn('critere', [
                self::CRITERE_CONFIRMED_RETURN,
                self::CRITERE_VALIDATED_DESPITE_RETURN,
            ])
            ->inPeriod($start, $end)
            ->get();

        $totalReturns = $scores->count();
        $confirmed = $scores->where('critere', self::CRITERE_CONFIRMED_RETURN)->count();

        return [
            'raw' => $totalReturns > 0 ? $confirmed / $totalReturns : 1.0,
            'meta' => [
                'total_returns_judged' => $totalReturns,
                'confirmed_by_n1' => $confirmed,
            ],
        ];
    }

    /**
     * Criterion 6: for tasks where this user was responsable — fraction of
     * N0 results that DID NOT time out (no 48h auto-forward).
     *
     * action_n0 = 'timeout' marks the auto-forward (set by TacheResultatService
     * when the N0 acted past the 48h window). We score the *absence* of
     * timeouts: more timeouts = lower score.
     */
    private function scoreInactions(User $user, string $start, string $end): array
    {
        // Tâches dont l'utilisateur est le responsable N0 (is_responsable=true).
        $taskIds = $user->taches()
            ->wherePivot('is_responsable', true)
            ->pluck('taches.id');

        if ($taskIds->isEmpty()) {
            return ['raw' => 1.0, 'meta' => ['as_responsable_total' => 0]];
        }

        $totalN0Decisions = TacheResultat::query()
            ->whereIn('tache_id', $taskIds)
            ->whereNotNull('action_n0')
            ->whereBetween('action_n0_le', [$start, $end.' 23:59:59'])
            ->count();

        $timeouts = TacheResultat::query()
            ->whereIn('tache_id', $taskIds)
            ->where('action_n0', 'timeout')
            ->whereBetween('action_n0_le', [$start, $end.' 23:59:59'])
            ->count();

        return [
            'raw' => $totalN0Decisions > 0 ? 1.0 - ($timeouts / $totalN0Decisions) : 1.0,
            'meta' => [
                'n0_decisions_total' => $totalN0Decisions,
                'timeouts' => $timeouts,
            ],
        ];
    }

    /**
     * Criterion 7: normalised work volume processed in period.
     *
     * Raw weighted count = tasks processed + 0.5 × subtasks processed.
     * "Processed" = pivot row created in window OR statut moved to 'termine'
     * in window — we use created_at for simplicity (matches the spec's
     * "processed in period" intent for a fresh assignment).
     *
     * Normalised by VOLUME_CEILING so the criterion stays in [0..1]: a
     * user who processes ≥ ceiling units gets the full 1.0; below, it's
     * a linear ratio.
     */
    private function scoreWorkVolume(User $user, string $start, string $end): array
    {
        $taches = $user->taches()
            ->whereBetween('tache_user.created_at', [$start, $end.' 23:59:59'])
            ->count();

        $sousTaches = DB::table('sous_tache_user')
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end.' 23:59:59'])
            ->count();

        $weighted = $taches + $sousTaches * self::SUBTASK_COEFFICIENT;
        $normalised = min(1.0, $weighted / self::VOLUME_CEILING);

        return [
            'raw' => $normalised,
            'meta' => [
                'taches' => $taches,
                'sous_taches' => $sousTaches,
                'weighted_total' => $weighted,
                'ceiling' => self::VOLUME_CEILING,
            ],
        ];
    }

    /**
     * Criterion 8: for tasks where this user was responsable — average
     * fraction of co-assignees who reached 'termine'. Rewards responsables
     * who effectively coordinate their teams.
     */
    private function scoreTeamCoordination(User $user, string $start, string $end): array
    {
        $taches = $user->taches()
            ->wherePivot('is_responsable', true)
            ->whereBetween('tache_user.created_at', [$start, $end.' 23:59:59'])
            ->with('assignees')
            ->get();

        if ($taches->isEmpty()) {
            return ['raw' => 1.0, 'meta' => ['tasks_as_responsable' => 0]];
        }

        // Pour chaque tâche, on calcule la fraction d'assignés (responsable
        // exclu) ayant terminé. Moyenne ensuite sur l'ensemble des tâches.
        $ratios = $taches->map(function ($tache) use ($user) {
            $coAssignees = $tache->assignees->where('id', '!=', $user->id);
            if ($coAssignees->isEmpty()) {
                // Pas de co-assignés → coordination N/A → on considère que la
                // tâche est neutre (pas de pénalité pour absence d'équipe).
                return 1.0;
            }
            $done = $coAssignees->filter(fn ($u) => $u->pivot->statut_individuel === 'termine')->count();

            return $done / $coAssignees->count();
        });

        return [
            'raw' => (float) $ratios->avg(),
            'meta' => [
                'tasks_as_responsable' => $taches->count(),
            ],
        ];
    }

    /**
     * Unjustified-return rate (used by indicator + alert threshold).
     *
     * Reuses the same data as criterion 5 but inverted: fraction of returns
     * judged by N1 that turned out to be unjustified (N1 reversed the N0).
     */
    private function unjustifiedReturnRate(User $user, string $start, string $end): float
    {
        $scores = EvaluationScore::query()
            ->where('user_id', $user->id)
            ->whereIn('critere', [
                self::CRITERE_CONFIRMED_RETURN,
                self::CRITERE_VALIDATED_DESPITE_RETURN,
            ])
            ->inPeriod($start, $end)
            ->get();

        $totalReturns = $scores->count();
        if ($totalReturns === 0) {
            return 0.0;
        }
        $unjustified = $scores->where('critere', self::CRITERE_VALIDATED_DESPITE_RETURN)->count();

        return $unjustified / $totalReturns;
    }

    /**
     * Whether the user has the escalades_abusives flag set on any of their
     * task assignments (3 consecutive invalidated bypasses, see Task 6).
     *
     * Returned as a single boolean for the red-badge indicator.
     */
    private function hasEscaladesAbusives(User $user): bool
    {
        return $user->taches()
            ->wherePivot('escalades_abusives', true)
            ->exists();
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
