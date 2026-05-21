# Task 7 — N1 Scores + Pending Validations Dashboard — Testing Guide

This guide walks Jonas through the manual verification of Task 7 before the branch is merged into `jonas`.

## Prerequisites

1. Backend running: `php artisan serve`
2. Frontend running: `npm run dev`
3. Queue worker running for notifications: `php artisan queue:work --once` (or leave it running)
4. Fresh DB with demo data: `php artisan migrate:fresh --seed`
5. Login as `directeur@worktracking.com` (password: `password`) — this user is the workspace owner and has both `EVALUATIONS_VIEW_PENDING` and `EVALUATIONS_VIEW_SCORE` permissions.

---

## Test Case 1 — Score N1 path: penalty for unjustified return

**Goal:** N1 validates a result that N0 had returned → responsable gets a -1.0 penalty.

**Steps:**
1. As `cadre@worktracking.com` (N0 actor for activity 5), navigate to a task with a submitted result (`/taches/<id>`).
2. Click "Renvoyer" with a 30+ char comment. The result statut moves to `a_refaire`.
3. Log out, log in as the activity's N1 (likely `directeur@worktracking.com` if you seeded with that as N1).
4. The intervenant resubmits — the result moves back to `en_validation_n1`.
5. As N1, navigate to the result and click "Valider".

**Expected:**
- The result is validated (statut = `en_validation_n2` or `valide` depending on N2 requirement).
- A row in `evaluation_scores` for the responsable (the N0 actor) with `critere = 'n1_validated_despite_return'` and `valeur = -1.0`.
- An audit row in `validation_audit_logs` with `action = 'n1_validated_despite_return'`.
- The responsable receives an in-app `ScoreUpdatedNotification` (no email).

**Verify in tinker:**
```bash
php artisan tinker --execute="
use App\Models\EvaluationScore;
echo EvaluationScore::latest()->first()->meta['decision'] . PHP_EOL;
echo EvaluationScore::latest()->first()->valeur . PHP_EOL;
"
```

---

## Test Case 2 — Score N1 path: bonus for confirmed return

**Goal:** N1 rejects a result that came via bypass → responsable gets a +1.0 bonus.

**Steps:**
1. Have an intervenant activate a bypass (per Task 6 flow). Result moves to `en_validation_n1` with `bypass_active = true`.
2. As N1, navigate to the result and click "Rejeter" with a comment.

**Expected:**
- The result is rejected (statut → `a_refaire`).
- A row in `evaluation_scores` for the responsable with `critere = 'n1_confirmed_return'` and `valeur = +1.0`.
- The assignee's `tache_user.bypass_count` increments by 1.
- If this is the 3rd cumulative invalid bypass, `escalades_abusives = true` is set + the responsable's manager receives an `EscaladesAbusivesNotification`.

---

## Test Case 3 — Score N1 path: no_impact on normal validation

**Goal:** N1 validates a normal submission (no N0 return, no bypass) → no score row is created.

**Steps:**
1. Have an intervenant submit a result that gets approved by N0 (no return).
2. As N1, click "Valider".

**Expected:**
- The result is validated.
- `evaluation_scores` row count is unchanged.
- No `n1_validated_despite_return` or `n1_confirmed_return` audit log row.

---

## Test Case 4 — Pending Validations Dashboard

**Goal:** The dashboard lists N1/N2 pending validations sorted by remaining deadline, with red badges on items < 24h.

**Steps:**
1. As `directeur@worktracking.com` (workspace owner — has `EVALUATIONS_VIEW_PENDING`), open the sidebar "Évaluations" → "Validations en attente". URL: `/validations/en-attente`.

**Expected:**
- Page renders with the four stat cards: "N1 en attente", "N2 en attente", "Urgents (< 24h)", "Total".
- Each card row shows the task title, project/activity, intervenant name.
- A red "⚠ Urgent (< 24h)" badge appears on rows where `hours_remaining < 24`.
- A "⚡ Bypass" badge appears when `had_bypass = true`.
- A "🚨 Escalades abusives" badge appears when the assignee has the flag set.
- Rows are sorted by `hours_remaining` ascending (most urgent first).
- Clicking a row navigates to `/taches/<id>`.

---

## Test Case 5 — Permission gate: collaborateur sees own score only

**Goal:** A non-privileged user (`collaborateur`) can only view their own score, not anyone else's.

**Steps:**
1. Login as `collaborateur@worktracking.com`.
2. Browser console / Postman:
   ```
   GET /api/evaluations/score
   GET /api/evaluations/score?user_id=<collaborateur_user_id>
   GET /api/evaluations/score?user_id=<other_user_id>
   ```

**Expected:**
- First two return 200 with the user's own total.
- Third returns 403 with `evaluation.errors.cannot_view_others_score`.

---

## Test Case 6 — Permission gate: dashboard 403 for non-privileged

**Goal:** A `collaborateur` or `stagiaire` cannot access the pending-validations dashboard.

**Steps:**
1. Login as `collaborateur@worktracking.com`.
2. Visit `/validations/en-attente` directly.

**Expected:**
- API returns 403; the dashboard displays the error message and no rows.
- (Optional) The sidebar link can be hidden via `canViewPendingValidations` from `useWorkspacePermissions` if you want a tighter UX — left visible for this iteration.

---

## Cleanup

```bash
php artisan migrate:fresh --seed
```

---

## Automated Coverage

- `tests/Feature/EvaluationScoreServiceTest.php` — 10 PHPUnit tests covering: penalty, bonus, no_impact, unknown decision, sum-of-deltas, validerN1 wiring, rejeterN1 + bypass_count tracking, dashboard 403 for collaborateur, dashboard 200 for workspace owner.
- `tests/Browser/Evaluation/PendingValidationsTest.php` — 1 Dusk test for the dashboard urgent-badge flow (Guide 16).
