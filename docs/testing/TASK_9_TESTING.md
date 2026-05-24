# Task 9 — Agent Evaluation Sheet + Full Scoring — Testing Guide

## Prerequisites

1. Backend: `php artisan serve`
2. Frontend: `npm run dev` (or `npm run build`)
3. Queue worker (for the two notifications): `php artisan queue:work`
4. Reverb (for in-app real-time): `php artisan reverb:start`
5. Fresh DB with demo data: `php artisan migrate:fresh --seed`

The `RolePermissionSeeder` and `WorkspaceSeeder` set up the six demo personas (superadmin, directeur, manager, cadre, collaborateur, stagiaire, observateur) you'll exercise the permission matrix against.

---

## Test Case 1 — Own sheet (any role)

**Goal:** any authenticated workspace member can open their own evaluation sheet.

**Steps:**
1. Sign in as `collaborateur@worktracking.com`.
2. Navigate to `/evaluations/personnel/{ownId}/historique` (paste the URL — Task 10 adds the menu entry).

**Expected:**
- Header shows the user identity (avatar initials, nom complet, email).
- "Score global" reads a percentage colored by band (≥ 75% green, ≥ 50% amber, < 50% red).
- "Qualité des renvois" donut renders with the justified-share label inside.
- The 8-criterion grid shows one card per criterion (completion_rate, deadline_respect, result_quality, first_pass_validation, justified_returns, inactions, work_volume, team_coordination), each with a progress bar and the "Valeur" + "Pondéré" pair.
- The 4 section tabs (Tâches dirigées / Sous-tâches dirigées / Tâches assignées / Sous-tâches assignées) are present; clicking each one switches the active section.

**If something is missing:**
- If the page redirects to `/unauthorized`, the test user is not a member of any workspace (no row in `workspace_members`). Verify via tinker:
  ```php
  \App\Models\Workspace::find($user->current_workspace_id)->members()->where('user_id', $user->id)->exists()
  ```
- If the donut shows but the criterion grid is empty, the API returned 200 but `criteria` was empty — check `EvaluationScoreService::calculerScore()` is not short-circuiting (e.g. `periode_start > periode_end`).

---

## Test Case 2 — Permission scopes (negative paths)

**Goal:** the 4 scope rules from `PermissionService::canViewFicheEvaluation` are enforced end-to-end.

**Setup:** workspace with `cadre@worktracking.com`, `collaborateur@worktracking.com`, `stagiaire@worktracking.com` and `observateur@worktracking.com` already in `workspace_members`.

| Scenario | Actor | Target | Expected |
|---|---|---|---|
| Own sheet | any | self | 200 + `data.meta.is_self = true` |
| Collaborateur reads someone else | collaborateur | another collaborateur | 403 "Vous n'avez pas la permission…" |
| Observateur reads someone else | observateur | collaborateur | 403 same |
| Observateur reads own | observateur | self | 200 + `data.meta.can_export = false` (read-only own) |
| Cadre reads their assignee | cadre | a collaborateur assigned to one of cadre's tasks | 200 |
| Manager reads agent in their activity | manager | any user in `activite.responsable_id = manager` chain | 200 |
| Owner reads anyone in workspace | owner | any workspace member | 200 |

You can step through these manually by swapping the logged-in user and visiting `/evaluations/personnel/{otherId}/historique`. The frontend's axios interceptor redirects 403 to `/unauthorized`, so a denied scope shows the "Accès non autorisé" page.

---

## Test Case 3 — Period filters

**Goal:** the period filter recalculates the score.

**Steps:**
1. Open your own sheet (Test Case 1).
2. Set "Du" to a date 90 days ago, "Au" to today, click "Appliquer".
3. Note the score change (more activity in the window → higher coverage).
4. Click "Réinitialiser" — period snaps back to the last-30-days default.

**Expected:**
- The URL stays the same (filters are query params, not path).
- Every section reloads (pagination resets to page 1).
- A second API call fires (`GET /api/evaluations/personnel/{id}/score?start=…&end=…`).

---

## Test Case 4 — Drill-down modal

**Goal:** clicking "Détails" on any section row opens a modal with the item's data.

**Steps:**
1. From the sheet, click "Détails" on a row in the "Tâches dirigées" tab.
2. The modal opens with statut, taux de réalisation, and échéance.
3. Click outside the modal or the X button to close.
4. Switch to the "Sous-tâches dirigées" tab and open a row — the modal also shows the coefficient-0.5 note.

**Expected:**
- The modal is keyboard-dismissible (Esc) and click-outside-dismissible.
- The note "Les sous-tâches contribuent au score à coefficient 0.5." appears only on subtask sections.

---

## Test Case 5 — Post-N2 immutability (R6) — HTTP 422

**Goal:** once a `TacheResultat` is validated at N2, any mutation on the parent task is refused with HTTP 422 + a French message.

**Setup (via tinker):**
```php
$tache = \App\Models\Tache::factory()->create(['titre' => 'Lock-test']);
// Add an assignee + result, then force valide_par_n2 = true.
\App\Models\TacheResultat::factory()->create([
    'tache_id' => $tache->id,
    'user_id' => 1,
    'valide_par_n1' => true,
    'valide_par_n2' => true,
    'statut' => 'valide',
]);
```

**Steps:**
1. From the UI, open the task detail page for `$tache`.
2. Try to rename it (or move it in the kanban, or archive it).

**Expected:**
- The API returns HTTP 422 with body `{ "message": "Cette tâche est verrouillée: elle a été validée au niveau N2 et ne peut plus être modifiée." }`.
- The UI surfaces the toast (no silent failure).
- `storage/logs/laravel.log` contains a `WARNING` line: `Tentative de modification d'une tâche verrouillée post-N2`.

This is also covered automatically by `tests/Feature/PostN2ImmutabilityTest` (6 tests).

---

## Test Case 6 — EvaluationSheetReady notification

**Goal:** when an agent visits (or has visited) their sheet, an `EvaluationSheetReadyNotification` is dispatched to them — but only once per 5-minute window.

**Steps:**
1. Sign in as `collaborateur@worktracking.com`.
2. Open your own sheet.
3. Watch:
   - The notification bell badge increments by 1 within ~1s.
   - Mailpit (or `storage/logs/laravel.log` if mail driver is `log`) shows the email with subject "Votre fiche d'évaluation est prête" — Blade template `emails/evaluation-sheet-ready/fr.blade.php`.
4. Reload the sheet within 5 minutes — no new notification (dedup).
5. Wait 5+ minutes, reload — a second notification is queued.

**Expected:**
- The in-app notification row has `data.type = 'evaluation_sheet_ready'` and a `dedup_key` of the form `evaluation_sheet_ready:{userId}:{5minWindow}`.
- The email shows the score as a colored % in the central panel.

---

## Test Case 7 — InjustifiedReturnAlert notification

**Goal:** when an agent's unjustified-return rate exceeds 40%, the workspace manager is alerted.

**Setup (via tinker, requires Task 7 score rows):**
```php
// Make 3 N1 decisions where N1 reversed an N0 return (= unjustified)
// out of 5 total decisions → rate = 60%.
$agent = \App\Models\User::where('email', 'cadre@worktracking.com')->first();
// Use EvaluationScoreFactory::penalty() to seed unjustified rows
\App\Models\EvaluationScore::factory()->penalty()->forUser($agent)->count(3)->create();
\App\Models\EvaluationScore::factory()->bonus()->forUser($agent)->count(2)->create();
```

**Steps:**
1. Sign in as `manager@worktracking.com` and open the agent's sheet.

**Expected:**
- "Au-dessus du seuil (40%)" red badge appears beside the return-quality donut.
- The manager (the actor in this case — and any other `manager` in the workspace) receives an in-app + email notification. Subject: "Alerte: taux de renvois injustifiés élevé". Body includes the agent's name, the rate, and the period.
- The notification's `dedup_key` is `unjustified_return_alert:{agentId}:{5minWindow}` so revisits within the window do not re-alert.

If no manager is found in `workspace_members`, the alert falls back to the workspace owner — confirmed via tinker by emptying the manager seat and reloading.

---

## Test Case 8 — Export button (UI stub)

**Goal:** the "Exporter" button is visible only to roles with `EVALUATIONS_EXPORT_FICHE`.

**Steps:**
1. As `cadre@worktracking.com`: open the sheet of one of your assignees — the "Exporter" button is visible.
2. As `observateur@worktracking.com`: open your own sheet — no "Exporter" button.

**Expected:**
- The check is driven by `data.meta.can_export` in the API response.
- Clicking "Exporter" today triggers a `window.alert("Export à venir — endpoint dédié à brancher.")` — the dedicated export endpoint lands in a follow-up task (Task 10). This is documented in IMPLEMENTATION_PLAN.md → Task 9 → Deviations.

---

## Automated coverage

- **Unit** (`tests/Unit/Services/EvaluationScoreServiceCalculerScoreTest.php`): 10 tests asserting each of the 8 weighted criteria, score_global aggregation, period defaults.
- **Feature** (`tests/Feature/EvaluationAgentSheetEndpointTest.php`): 4 tests covering own-200, collaborateur-403, observateur-403, observateur-own-200-read-only.
- **Feature** (`tests/Feature/PostN2ImmutabilityTest.php`): 6 tests asserting `isLockedPostN2` + every mutation method (update/delete/archive/assignUser/…) throws `HttpException` with status 422.
- **Dusk** (`tests/Browser/Evaluation/AgentSheetTest.php`): 2 tests covering render of header + 8 criteria + donut, and section-tab switching.

Run with:
```bash
php artisan test --compact --filter="EvaluationAgentSheet|PostN2Immutability|EvaluationScoreServiceCalculerScore"
php artisan dusk --filter=AgentSheetTest
```

---

## Known follow-ups

- The agent sheet page still hard-codes French strings — switching every call site to `$t('evaluation.sheet.*')` is a chore that doesn't change behavior; tracked for a future pass.
- Export endpoint + the `Log::info()` on sheet export will land in Task 10 (Evaluation Dashboard) since the export path is shared with the workspace-wide report.
- The `unjustified_return_rate` threshold (40%) is hard-coded in `EvaluationScoreService`. If you want it tunable per workspace, expose it through `Workspace::getSetting()` like the validation timeout already is.
