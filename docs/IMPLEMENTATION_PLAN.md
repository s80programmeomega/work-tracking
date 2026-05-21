# Implementation Plan — Work Tracking v2.0

> **To resume a session:** Read `WORKING_GUIDELINES.md` and this file, then say "continue from Task N".

---

## Context

- **Queue:** switching `sync` → `database` driver (jobs table already migrated)
- **Broadcasting:** Laravel Reverb (self-hosted WebSocket)
- **Push notifications:** Web Push API (`push_subscriptions` table already exists)
- **Current validation:** N1 → N2 on `TacheResultat`, no N0 or bypass
- **Subtasks:** do not exist yet — built from scratch
- **Permissions:** `PermissionService` (backend) + composables (frontend)
- **Translations:** `lang/fr/` and `lang/en/`
- **Logging:** `spatie/laravel-activitylog` + `validation_audit_logs` + `Log::` facade

---

## Dependency Graph

```
T1 (Queue + Reverb) → T2 (Subtask Model) → T3 (Subtask API) → T4 (Subtask UI) → T5 (N0 + Timer)
                                                                                        ↓
T1 ──────────────────────────────────────────────────────────────────────────────→ T8 (Notifications)
                                                                T5 → T6 (Bypass) → T7 (Scores) → T8
                                                                                        T7 → T9 (Agent Sheet) → T10 (Dashboard) → T14 (Platform Dashboard)

T11 (Task Creation UX) — after T4
T12 (Document Management) — independent, can run in parallel
T13 (Subscription Modes) — independent, can run in parallel
```

---

## Task 0 — Permission Architecture Refactor (Policies + Runtime-Editable Roles)

**Branch:** `feature/v2-task-0-policies-refactor`

**Objective:** Replace the custom `PermissionService` + `abort_unless()` pattern with Laravel Policies backed by Spatie's DB-driven permissions. This makes role permissions editable at runtime via the admin UI (Task 14) and eliminates the risk of unprotected endpoints.

**Implementation:**
- Create `app/Policies/` with one policy per resource: `ProjetPolicy`, `ActivitePolicy`, `TachePolicy`, `DocumentPolicy`, `WorkspacePolicy`, `SousTachePolicy`
- Register all policies in `AuthServiceProvider::$policies`
- Add `Gate::before()` in `AuthServiceProvider::boot()` to bypass all checks for `super_admin`
- Seed contextual roles (`owner`, `manager`, `cadre`, `collaborateur`, `stagiaire`, `observateur`) into Spatie's `roles` table with their default permission sets
- Update `PermissionService` to be a **relationship helper** only (object-level checks: isResponsable, isMember, pivot flags) — no longer the authorization layer
- Update all controllers to replace `abort_unless($this->permissionService->canX(...))` with `$this->authorize('x', $model)`
- Role-level checks inside policies use `$user->hasPermissionTo('taches.edit')` (Spatie DB-driven)
- Object-level checks inside policies call `PermissionService` helper methods
- Update `RolePermissionSeeder` to seed all contextual roles with default permissions

**Architecture after refactor:**
```
Controller → $this->authorize('edit', $tache)
                ↓
           TachePolicy::edit(User, Tache)
                ↓
    $user->hasPermissionTo('taches.edit')   ← Spatie DB (runtime-editable)
    + PermissionService::isResponsable()    ← relationship check
```

**Permissions:**
- No new permissions — this task reorganises existing ones
- All existing permission strings from `RolePermissionSeeder` are preserved
- Contextual roles seeded into Spatie: `owner`, `manager`, `cadre`, `collaborateur`, `stagiaire`, `observateur`

**Notifications:** none

**Emails:** none

**Logging:**
- `Log::info()` on any Gate denial for auditing: handled automatically by Laravel's Gate events

**Translations:** none — no UI changes

**Tests:**
- Unit: each Policy method returns correct result for each role
- Feature: `super_admin` bypasses all policy checks
- Feature: existing controller endpoints still return correct 403s after refactor
- Run full test suite to confirm no regressions

**Demo:** A `cadre` user tries to delete a project → gets 403 via `ProjectPolicy::delete()`. Super admin does the same → succeeds via `Gate::before()` bypass.

**Commits:**
```
feat: seed contextual roles into Spatie roles table
feat: add ProjetPolicy, ActivitePolicy, TachePolicy, DocumentPolicy, WorkspacePolicy
feat: add Gate::before super_admin bypass in AuthServiceProvider
feat: register all policies in AuthServiceProvider
refactor: update PermissionService to relationship-helper only
refactor: replace abort_unless with authorize() in all controllers
test: add unit tests for all policy methods per role
test: run full regression suite post-refactor
```

---

## Task 1 — Configure Queue (database) + Laravel Reverb

**Branch:** `feature/v2-task-1-queue-reverb`

**Objective:** Set up the async infrastructure required by all subsequent tasks.

**Implementation:**
- Switch `QUEUE_CONNECTION=database` in `.env` and `.env.example`
- Install and configure Laravel Reverb (`php artisan install:broadcasting`)
- Add Reverb keys to `.env` and `.env.example`
- Install `laravel-echo` + `pusher-js` on the frontend
- Create `useEcho.js` composable for the WebSocket connection

**Permissions:** none — infrastructure only

**Notifications:** none — infrastructure only

**Emails:** none — infrastructure only

**Logging:** none — no domain actions in this task

**Translations:** none

**Tests:**
- Verify a dispatched job appears in the `jobs` table
- Verify a broadcasted event is received in the browser console

**Demo:** A test event broadcasted from Tinker is received in real time in the browser console.

**Commits:**
```
chore: switch queue driver to database
chore: install and configure Laravel Reverb
chore: install laravel-echo and pusher-js
feat: add useEcho composable for WebSocket connection
```

---

## Task 2 — Subtask Data Model

**Branch:** `feature/v2-task-2-subtask-model`

**Objective:** Create `sous_taches` and `sous_tache_user` tables with all business rules.

**Implementation:**
- Migration `create_sous_taches_table`: id, tache_id (FK), titre, responsable_id (FK), date_echeance, statut (enum), progression (0–100), poids (integer), validation flags (n0/n1/n2 required), ordre
- Migration `create_sous_tache_user_table`: sous_tache_id, user_id, can_edit, can_complete, statut_individuel, progression_individuelle
- Add `EN_RETARD` and `A_REFAIRE` to `TacheStatut` enum
- `SousTache` model with relationships, rule R1 (max depth 1 — HTTP 422), rule R2 (weights sum = 100% — HTTP 422)
- `SousTacheFactory` and `SousTacheSeeder`
- `SousTacheResource` Eloquent API Resource

**Permissions:**
- Add to `PermissionService`: `canViewSousTache`, `canCreateSousTache(User, Tache)`, `canEditSousTache`, `canDeleteSousTache`
- Rule: only `is_responsable` of parent task, or `cadre`/`manager` of the activity, can create subtasks
- Add to `RolePermissionSeeder` for roles `cadre`, `manager`, `owner`

**Logging:**
- `LogsActivity` trait on `SousTache` model
- `Log::warning()` on R1 violation: `['user_id', 'tache_id', 'reason' => 'max_depth_exceeded']`
- `Log::warning()` on R2 violation: `['user_id', 'tache_id', 'reason' => 'weights_sum_invalid', 'sum' => ...]`

**Notifications:** none — model layer only, no user-facing events yet

**Emails:** none

**Translations:**
- Create `lang/fr/sous_taches.php` and `lang/en/sous_taches.php`
- Keys: `status.*` (all 6 statuses), `errors.max_depth_exceeded`, `errors.weights_sum_invalid`

**Tests:**
- Unit: R1 violation → HTTP 422 with translated message
- Unit: R2 violation → HTTP 422 with translated message
- Unit: `collaborateur` without `is_responsable` → HTTP 403

**Demo:** `SousTache::create()` works; sub-subtask attempt returns HTTP 422 with translated message.

**Commits:**
```
feat: add sous_taches and sous_tache_user migrations
feat: add SousTache model with R1/R2 rules, factory, seeder
feat: add EN_RETARD and A_REFAIRE to TacheStatut enum
feat: add SousTacheResource API resource
feat: add SousTache permissions to PermissionService and seeder
feat: add sous_taches translation files (fr/en)
test: add unit tests for SousTache R1, R2, and permission rules
```

---

## Task 3 — Subtask CRUD API + Automatic Progress

**Branch:** `feature/v2-task-3-subtask-api`

**Objective:** REST endpoints for subtask management + automatic parent task progress calculation.

**Implementation:**
- `SousTacheController` in `app/Http/Controllers/Api/` with 5 endpoints:
  - `POST /taches/{id}/sous-taches`
  - `GET /taches/{id}/sous-taches`
  - `PUT /sous-taches/{id}`
  - `DELETE /sous-taches/{id}`
  - `POST /sous-taches/{id}/intervenants`
- `SousTacheService`: date validation, weight validation, cascade delete confirmation
- `SousTacheObserver`:
  - Recalculates `taches.taux_realisation` on every subtask update: `Σ(progression_i × poids_i / 100)`
  - If all mandatory subtasks are `termine` → parent auto-set to `termine`
  - If any subtask goes `en_retard` → parent auto-set to `en_retard`
  - Blocks manual status update on parent if subtasks exist (except `annule`) → HTTP 422
- Register routes in `api.php`

**Permissions:**
- Each endpoint checks `PermissionService` methods from Task 2
- Add `canAssignSousTacheIntervenant(User, SousTache)`: reserved for subtask's responsable or activity cadre
- Add to seeder and relevant roles

**Logging:**
- `Log::info()` on automatic parent status transition: `['tache_id', 'old_status', 'new_status', 'triggered_by' => 'observer']`
- `Log::warning()` when manual status update is blocked: `['user_id', 'tache_id', 'reason' => 'subtasks_exist']`

**Notifications:**
- `SousTacheAssigneeNotification` → sent to each intervenant when assigned to a subtask (in-app + email)
- `SousTacheOverdueNotification` → sent to subtask responsable when subtask passes its deadline (in-app)
- `TacheStatutAutoChangeNotification` → sent to parent task responsable when parent status auto-changes (in-app)

**Emails:**
- `SousTacheAssigneeNotification` → inline `MailMessage` (simple: subtask title, due date, action link)
- `SousTacheOverdueNotification` → in-app only, no email
- `TacheStatutAutoChangeNotification` → in-app only, no email

**Translations:**
- Add to `lang/fr/sous_taches.php` and `lang/en/sous_taches.php`:
  - `errors.date_exceeds_parent`, `errors.status_blocked`, `success.created`, `success.updated`, `success.deleted`

**Tests:**
- Feature: automatic progress recalculation with weighted subtasks (40/35/25)
- Feature: parent auto-set to `termine` when all subtasks done
- Feature: parent auto-set to `en_retard` when subtask overdue
- Feature: manual status update blocked → HTTP 422
- Feature: 403 for unauthorized roles on each endpoint

**Demo:** Create 3 subtasks with weights 40/35/25, update their progress → parent task updates automatically.

**Commits:**
```
feat: add SousTacheService with business logic
feat: add SousTacheController with CRUD endpoints
feat: add SousTacheObserver for automatic progress and status
feat: register sous-tache routes in api.php
feat: add canAssignSousTacheIntervenant permission
feat: add subtask CRUD translation keys (fr/en)
test: add feature tests for subtask CRUD and automatic progress
```

---

## Task 4 — Subtask UI in Task Detail View

**Branch:** `feature/v2-task-4-subtask-ui`

**Objective:** Subtask section in the task detail page + Kanban card indicators.

**Implementation:**
- `useSousTaches.js` composable: CRUD + assign intervenant
- `SousTacheList.vue`: ordered list, drag & drop to reorder, global progress bar, overdue indicator
- `SousTacheForm.vue`: quick creation form (titre, responsable from activity members, date, weight, validation options)
- Frontend validation: weights sum = 100%, date ≤ parent date
- Disable "Submit result" button on parent task if blocking subtasks exist (with explicit list in tooltip)
- Kanban card indicator: badge "2/3 ST" + mini progress bar; click to expand inline

**Permissions:**
- Add `canCreateSousTache` and `canAssignSousTacheIntervenant` to `useActivitePermissions.js` exports
- Hide creation form entirely for `observateur`
- Conditionally show/hide Edit/Delete buttons based on permissions

**Logging:** none — API calls already logged in Task 3

**Notifications:** none — UI layer only, notifications triggered by API already covered in Task 3

**Emails:** none

**Translations:**
- Add to `lang/fr/sous_taches.php` and `lang/en/sous_taches.php`:
  - Section titles, form placeholders, button labels, tooltip for disabled submit button

**Tests:**
- Feature: "Create subtask" button absent for `observateur`
- Feature: "Submit result" button disabled when blocking subtasks exist

**Demo:** Create 2 subtasks, reorder by drag & drop, watch parent progress update live.

**Commits:**
```
feat: add useSousTaches composable
feat: add SousTacheList and SousTacheForm components
feat: add subtask indicator to Kanban cards
feat: disable submit result button when blocking subtasks exist
feat: add subtask permissions to useActivitePermissions
feat: add subtask UI translation keys (fr/en)
test: add feature tests for subtask UI permissions
```

---

## Task 5 — Option C Validation Circuit — N0 + 48h Timer

**Branch:** `feature/v2-task-5-validation-n0`

**Objective:** Add the preliminary N0 check and the automatic 48h fallback timer.

**Implementation:**
- Migration: add to `tache_resultats`:
  - `statut` enum extended: `brouillon`, `en_verification_n0`, `en_validation_n1`, `en_validation_n2`, `valide`, `rejete`, `a_refaire`
  - `soumis_n0_le` (timestamp), `action_n0` (enum: approuve/renvoye/timeout), `commentaire_n0` (text), `n0_actor_id` (FK users)
- Migration: create `validation_audit_logs` table:
  - id, tache_resultat_id (FK), actor_id (FK), action (string), context (JSON), created_at — **no updated_at, no soft deletes (R6)**
- `TacheResultatService`:
  - `approuverN0(TacheResultat)`: sets status to `en_validation_n1`, writes audit log
  - `renvoyerN0(TacheResultat, string $comment)`: validates min 30 chars (R4 → HTTP 422), writes audit log
  - `transmettreAuN1(TacheResultat)`: called by job on timeout, writes audit log with `action = timeout`
- `TransmettreResultatAuN1Job`: dispatched with delay from workspace `validation_timeout_hours` setting (R8: 24–168h, default 48h); cancelled if N0 acts before timeout
- Notifications: `ResultatSoumisN0Notification` (to task responsable on submission), `ResultatTransmisAutoNotification` (to responsable on timeout)
- Add `validation_timeout_hours` to workspace settings (configurable per workspace)

**Permissions:**
- `canApprouverN0(User, TacheResultat)` and `canRenvoyerN0(User, TacheResultat)`: reserved for the task's `is_responsable`
- Add to `RolePermissionSeeder` for `collaborateur` with `is_responsable = true`
- Create or extend `useTachePermissions.js` with `canApprouverN0` and `canRenvoyerN0`
- Frontend: show Approve/Return buttons only when permission is true

**Logging:**
- Every N0 action (approve, return, timeout) → immutable row in `validation_audit_logs`
- `Log::info()` on job dispatch: `['tache_resultat_id', 'delay_hours', 'job_id']`
- `Log::info()` on job cancellation: `['tache_resultat_id', 'cancelled_by' => 'n0_action']`
- `Log::warning()` on R4 violation: `['user_id', 'tache_resultat_id', 'reason' => 'comment_too_short', 'length' => ...]`
- `Log::warning()` on permission denial: `['user_id', 'tache_resultat_id', 'action', 'reason' => 'unauthorized']`

**Notifications:**
- `ResultatSoumisN0Notification` → to task `is_responsable` when an intervenant submits a result (in-app + email)
- `ResultatRenvoyeNotification` → to the intervenant when N0 returns their result with a comment (in-app + email)
- `ResultatApprouveN0Notification` → to the intervenant when N0 approves and forwards to N1 (in-app)
- `ResultatTransmisAutoNotification` → to the task `is_responsable` when the 48h timer fires (in-app + email)
- All notifications also propagate up the hierarchy: cadre → manager → directeur (deduplication applied — implemented fully in Task 8)

**Emails:**
- `ResultatSoumisN0Notification` → inline `MailMessage` (result summary, taux_realisation, action link)
- `ResultatRenvoyeNotification` → Blade template `emails/resultat-renvoye/{fr|en}.blade.php` (includes N0 comment, resubmit instructions)
- `ResultatApprouveN0Notification` → in-app only, no email
- `ResultatTransmisAutoNotification` → inline `MailMessage` (timeout notice, action link to review)

**Translations:**
- Create `lang/fr/validation.php` and `lang/en/validation.php`:
  - `status.*` (all circuit statuses), `errors.comment_too_short`, `errors.unauthorized`, `notifications.*` (email templates)

**Tests:**
- Unit: job dispatched with correct delay from workspace setting
- Unit: job cancelled when N0 acts before timeout
- Feature: return without comment → HTTP 422 with translated message
- Feature: unauthorized user on N0 action → HTTP 403
- Dusk: submit result → statut badge updates to `en_verification_n0`; N0 approves → badge updates to `en_validation_n1`

**Bug fixed during testing:** `TacheResultatController::submit()` was calling `$resultat->submit()` (legacy model method) instead of `TacheResultatService::soumettre()` — job was never dispatched. Fixed and covered by existing feature tests.

**Demo:** Submit result → responsable notified → can approve or return → if no action after 48h, result auto-forwarded to N1.

**Commits:**
```
feat: add N0 columns and extended statut enum to tache_resultats
feat: create validation_audit_logs table (immutable)
feat: add approuverN0, renvoyerN0, transmettreAuN1 to TacheResultatService
feat: add TransmettreResultatAuN1Job with configurable delay
feat: add ResultatSoumisN0 and ResultatTransmisAuto notifications
feat: add validation_timeout_hours to workspace settings
feat: add N0 permissions to PermissionService, seeder, useTachePermissions
feat: add validation translation files (fr/en)
test: add unit tests for N0 job dispatch and cancellation
test: add feature tests for N0 circuit validation
```

---

## Task 6 — Option C Validation Circuit — Anti-Sabotage Bypass

**Branch:** `feature/v2-task-6-bypass`

**Objective:** Allow an assignee to escalate past an unjustified return.

**Implementation:**
- Migration: add to `tache_resultats`:
  - `bypass_active` (boolean, default false), `motif_bypass` (text), `bypass_le` (timestamp), `bypass_count` (integer, default 0)
- `TacheResultatService::activerBypass(TacheResultat, string $motif)`:
  - Validates motif min 50 chars (R5 → HTTP 422)
  - Enforces single-use per submission (R3 → HTTP 409 if already used)
  - Sets status to `en_validation_n1` with escalation flag
  - Writes immutable row to `validation_audit_logs`
- Enriched N1 view: "Validation Context" panel showing:
  - Assignee's result
  - N0 comment (if return occurred)
  - Bypass motif (if escalation)
  - Full submission history with timestamps
- `escalades_abusives` flag on `tache_user`: set when 3 consecutive bypasses are invalidated by N1

**Permissions:**
- `canActiverBypass(User, TacheResultat)`: reserved for the result's author (`tache_resultats.user_id`), only when status = `renvoye`
- Add to `RolePermissionSeeder` for `collaborateur` and `stagiaire`
- Add `canActiverBypass` to `useTachePermissions.js`
- Frontend: "Submit directly to N1" button visible only when `canActiverBypass` and status = `renvoye`

**Logging:**
- Every bypass activation → immutable row in `validation_audit_logs`: `[tache_resultat_id, actor_id, action => 'bypass', motif, created_at]`
- Every N1 decision (validate despite return / confirm return) → immutable row in `validation_audit_logs`
- `Log::warning()` on R3 violation: `['user_id', 'tache_resultat_id', 'reason' => 'bypass_already_used']`
- `Log::warning()` on R5 violation: `['user_id', 'tache_resultat_id', 'reason' => 'motif_too_short', 'length' => ...]`
- `Log::warning()` when `escalades_abusives` flag is set: `['user_id', 'tache_id', 'consecutive_invalid_bypasses' => 3]`

**Notifications:**
- `BypassActivatedNotification` → to the N1 validator (cadre) when an intervenant activates a bypass (in-app + email, includes bypass motif)
- `EscaladesAbusivesNotification` → to the task `is_responsable` and their manager when the `escalades_abusives` flag is set (in-app + email)
- `N1DecisionNotification` → to the intervenant after N1 validates or rejects (in-app + email)
- `N1DecisionNotification` → to the task `is_responsable` after N1 decision (bonus/penalty applied) (in-app)

**Emails:**
- `BypassActivatedNotification` → Blade template `emails/bypass-activated/{fr|en}.blade.php` (includes result, N0 comment, bypass motif, full history)
- `EscaladesAbusivesNotification` → inline `MailMessage` (flag notice, bypass count, action link)
- `N1DecisionNotification` (to intervenant) → inline `MailMessage` (validated or rejected, N1 comment, next steps)
- `N1DecisionNotification` (to responsable) → in-app only, no email

**Translations:**
- Add to `lang/fr/validation.php` and `lang/en/validation.php`:
  - `bypass.submit_label`, `bypass.motif_placeholder`, `errors.motif_too_short`, `errors.bypass_already_used`, `bypass.abusive_escalations_label`, `bypass.n1_context_panel_title`

**Tests:**
- Feature: second bypass attempt → HTTP 409 with translated message
- Feature: `escalades_abusives` flag set after 3 consecutive invalid bypasses
- Feature: N1 context panel shows correct data (result + N0 comment + bypass motif)

**Demo:** Assignee receives a return → activates bypass with motif → N1 sees full context with escalation flag.

**Commits:**
```
feat: add bypass columns to tache_resultats
feat: add activerBypass to TacheResultatService with R3 and R5 rules
feat: add escalades_abusives flag logic
feat: add N1 validation context panel
feat: add canActiverBypass permission to PermissionService, seeder, useTachePermissions
feat: add bypass translation keys to validation files
test: add feature tests for bypass single-use and escalades_abusives
```

---

## Task 7 — Post-N1 Score Calculation + Pending Validations Dashboard

**Branch:** `feature/v2-task-7-scores-dashboard`

**Objective:** Auto-calculate penalties/bonuses after each N1 validation + build the validation tracking page.

**Implementation:**
- `EvaluationScoreService::calculerImpactN1(TacheResultat, string $decision)`:
  - N1 validates despite N0 return → penalty on task responsable score
  - N1 confirms N0 return → bonus on task responsable score
  - N1 invalidates bypass → increment `bypass_count`; if 3 consecutive → set `escalades_abusives` flag
- Migration: create `evaluation_scores` table: id, user_id (FK), periode (string), critere (string), valeur (decimal), meta (JSON), created_at, updated_at
- Page `/validations/en-attente`:
  - List sorted by remaining deadline (most urgent first)
  - Red badge if deadline < 24h
  - Columns: task, assignee, status, deadline, bypass flag
  - Accessible to `cadre` (their N1 tasks), `manager` (their N2 projects), `owner`/`directeur`

**Permissions:**
- `canViewValidationsEnAttente(User, Workspace)`: `cadre`, `manager`, `owner`, `directeur`
- `canViewEvaluationScore(User, targetUser, Workspace)`: user sees own score; `cadre` sees their assignees'; `manager` sees their activity; `owner` sees all
- Add both to `RolePermissionSeeder` and `useWorkspacePermissions.js`

**Logging:**
- Every score calculation → row in `evaluation_scores` with full `meta` JSON (inputs, formula, result)
- `Log::info()` on score update: `['user_id', 'critere', 'old_value', 'new_value', 'triggered_by' => 'n1_validation']`
- `Log::error()` if calculation fails: `['user_id', 'tache_resultat_id', 'error']`

**Notifications:**
- `ScoreUpdatedNotification` → to the user whose score changed after N1 validation (in-app only — score changes are silent, no email)
- `ValidationUrgentNotification` → to N1/N2 validators when a pending validation has < 24h remaining (in-app + email)

**Emails:**
- `ScoreUpdatedNotification` → in-app only, no email
- `ValidationUrgentNotification` → inline `MailMessage` (task name, deadline, action link)

**Translations:**
- Create `lang/fr/evaluation.php` and `lang/en/evaluation.php`:
  - `criteria.*` (all criteria names), `dashboard.columns.*`, `dashboard.alerts.*`

**Tests:**
- Unit: penalty scenario (N1 validates despite return)
- Unit: bonus scenario (N1 confirms return)
- Unit: `escalades_abusives` flag trigger
- Feature: `collaborateur` cannot see other users' scores → HTTP 403

**Demo:** Validate a result with bypass → task responsable's score updates → visible in pending validations page.

**Commits:**
```
feat: create evaluation_scores table
feat: add EvaluationScoreService with N1 impact calculation
feat: add pending validations dashboard page
feat: add canViewValidationsEnAttente and canViewEvaluationScore permissions
feat: add evaluation translation files (fr/en)
test: add unit tests for EvaluationScoreService scenarios
test: add feature tests for evaluation score permissions
```

---

## Task 8 — Real-Time Notifications (Reverb + Web Push)

**Branch:** `feature/v2-task-8-notifications`

**Objective:** Every circuit step triggers in-app real-time notifications and Web Push.

**Implementation:**
- Reverb channel: `private-user.{id}` for personal notifications
- `ValidationNotificationEvent` broadcasted on the user's private channel
- Update `useNotifications.js` to listen via Echo; notification badge updates without page refresh
- Web Push via existing service worker (`push_subscriptions` table already exists) for out-of-app notifications
- Notification hierarchy: cadre notified → manager also notified → directeur also notified
- Deduplication: suppress duplicate via `tache_resultat_id + user_id + event_type` check before dispatch
- Per-user notification preferences: configurable daily email digest (uses existing `notification_preferences` table)

**Permissions:**
- `canReceiveNotification(User, string $event_type)`: checks `notification_preferences` table
- `canManageNotificationPreferences(User, Workspace)`: `owner`/`directeur` only for workspace-level preferences
- Add `canManageNotificationPreferences` to `RolePermissionSeeder` and `useWorkspacePermissions.js`

**Logging:**
- `Log::info()` on each notification dispatched: `['recipient_id', 'event_type', 'channel' => 'websocket|push|email']`
- `Log::info()` when duplicate suppressed: `['recipient_id', 'event_type', 'reason' => 'deduplicated']`
- `Log::error()` on Web Push delivery failure: `['recipient_id', 'event_type', 'error']`

**Notifications:** This task IS the notification infrastructure. It wires up all notifications defined in Tasks 3, 5, 6, 7 to real-time delivery:
- In-app badge via Reverb WebSocket (`private-user.{id}` channel)
- Web Push via service worker for out-of-app delivery
- Email digest (daily, configurable per user)
- Hierarchy propagation: every notification bubbles up cadre → manager → directeur
- Deduplication: same `tache_resultat_id + user_id + event_type` not sent twice

**Emails:**
- All email templates defined in Tasks 3, 5, 6, 7 are wired through `NotificationService::channelsFor()` here
- Daily digest email → Blade template `emails/daily-digest/{fr|en}.blade.php` (lists all pending actions grouped by type)
- `NotificationService::channelsFor($notifiable, $eventType)` resolves channels from `notification_preferences` table

**Translations:**
- HTML email templates in `resources/views/emails/` (FR and EN versions)
- Create `lang/fr/notifications.php` and `lang/en/notifications.php`: Web Push titles and bodies, preference UI labels

**Tests:**
- Feature: same event not dispatched twice to same user (deduplication)
- Feature: notification preferences respected (user with email disabled does not receive email)

**Demo:** Submit result → notification badge increments in real time for the task responsable, no page refresh.

**Commits:**
```
feat: add ValidationNotificationEvent and Reverb private channel
feat: update useNotifications composable to listen via Echo
feat: add Web Push notifications for circuit events
feat: add notification deduplication logic
feat: add canManageNotificationPreferences permission
feat: add notification email templates (fr/en)
feat: add notifications translation files (fr/en)
test: add feature tests for notification deduplication and preferences
```

---

## Task 9 — Evaluation Engine — Agent Sheet + Full Scoring

**Branch:** `feature/v2-task-9-agent-sheet`

**Objective:** Per-agent evaluation sheet with all 8 weighted criteria from the CDC.

**Implementation:**
- `EvaluationService::calculerScore(User, string $periode)`: calculates all 8 criteria:

| Criterion | Weight | Note |
|---|---|---|
| Completion rate | 20% | Tasks + subtasks done / total assigned |
| Deadline respect | 20% | Deliveries before deadline / total |
| Result quality | 15% | Average N1 score on validated results |
| First-pass validation | 15% | Validated N1 without reject or bypass / total |
| Justified returns (responsable) | 10% | Returns confirmed by N1 / total returns |
| Inactions (responsable) | 5% | Timeout ratio (48h auto-forwards) |
| Work volume | 10% | Tasks + subtasks processed in period |
| Team coordination | 5% | Assignees who completed their part |

  - Subtasks counted at coefficient 0.5
- Page `/evaluations/personnel/{id}/historique`: 4 sections (directed tasks, directed subtasks, assignee tasks, assignee subtasks), combinable filters (period, role, status, project), 20 items/page
- **Task drill-down modal:** each task row in the sheet has a "details" action that opens a modal showing (a) the parent task's score impact + reason (drawn from `evaluation_scores.meta`), (b) the task's sous-tâches with progression, statut, responsable and individual score contributions at coefficient 0.5. Captured 2026-05-19 per Jonas's UX request — `evaluation_scores.meta` is the data source; no schema change needed.
- "Return quality" indicator: donut chart (justified vs unjustified), alert if unjustified rate > 40%
- "Escalations" indicator: red badge if `escalades_abusives` flag active, detail per bypass
- Post-N2 immutability (R6): block all modifications to the task in the sheet → HTTP 422
- Update endpoint `GET /evaluations/personnel/{id}/score`

**Permissions:**
- `canViewFicheEvaluation(User, targetUser)`: user sees own; `cadre` sees direct assignees'; `manager` sees activity; `owner`/`directeur` sees all
- `canExportFicheEvaluation`: same rules as `canViewFicheEvaluation`
- `observateur` and `stagiaire`: read-only, own sheet only
- Add to `RolePermissionSeeder` and `useWorkspacePermissions.js`

**Logging:**
- `Log::info()` on full score recalculation: `['user_id', 'periode', 'score', 'criteria_breakdown']`
- `Log::warning()` on post-N2 modification attempt: `['user_id', 'tache_id', 'reason' => 'immutable_post_n2']`
- `Log::warning()` when unjustified return rate > 40%: `['user_id', 'rate', 'periode']`
- `Log::info()` on sheet export: `['exported_by', 'target_user_id', 'periode', 'format']`

**Notifications:**
- `EvaluationSheetReadyNotification` → to the agent when their evaluation sheet is generated/updated for a period (in-app + email)
- `InjustifiedReturnAlertNotification` → to the manager when a responsable's unjustified return rate exceeds 40% (in-app + email)

**Emails:**
- `EvaluationSheetReadyNotification` → Blade template `emails/evaluation-sheet-ready/{fr|en}.blade.php` (score summary, criteria breakdown, period, action link)
- `InjustifiedReturnAlertNotification` → inline `MailMessage` (rate, user name, action link to sheet)

**Translations:**
- Add to `lang/fr/evaluation.php` and `lang/en/evaluation.php`:
  - `criteria.*` (all 8 names), `sheet.sections.*`, `sheet.filters.*`, `errors.immutable_post_n2`

**Tests:**
- Unit: each of the 8 scoring criteria calculated correctly
- Feature: post-N2 modification attempt → HTTP 422 with translated message
- Feature: `observateur` cannot view another user's sheet → HTTP 403

**Demo:** Open agent sheet → see global score + per-criterion breakdown + task/subtask history for a given period.

**Commits:**
```
feat: add EvaluationService with full 8-criteria scoring
feat: add agent evaluation sheet page with 4 sections
feat: add return quality and escalations indicators
feat: enforce post-N2 immutability on evaluation sheet
feat: add canViewFicheEvaluation and canExportFicheEvaluation permissions
feat: update evaluation translation files with scoring criteria
test: add unit tests for all 8 scoring criteria
test: add feature tests for evaluation sheet permissions and immutability
```

---

## Task 10 — Evaluation Dashboard + Workspace-Wide Task View

**Branch:** `feature/v2-task-10-dashboard`

**Objective:** Evaluation summary dashboard and global task view for the workspace owner.

**Implementation:**
- Page `/evaluations/dashboard`: scores by team/period, top performers, alerts (abusive escalations, high inaction rate)
- Page `/workspace/taches`: global view of all workspace tasks for the directeur
  - Default filter = all tasks
  - Filterable by project / activity / status / assignee
  - Table mode with inline editing per task
- `GET /evaluations/dashboard` endpoint with period aggregates

**Permissions:**
- `canViewEvaluationDashboard(User, Workspace)`: `manager` sees their project scope; `owner`/`directeur` sees full workspace
- `canViewWorkspaceTaches(User, Workspace)`: `owner`/`directeur` only
- `canInlineEditTache(User, Tache)`: any role with `canEditTask` on that specific task
- Add all 3 to `RolePermissionSeeder` and `useWorkspacePermissions.js`
- Frontend: hide "Global workspace view" sidebar link for unauthorized roles

**Logging:**
- `Log::info()` on dashboard data fetch: `['user_id', 'workspace_id', 'periode', 'scope']`
- `Log::warning()` on unauthorized access attempt to `/workspace/taches`: `['user_id', 'workspace_id', 'reason' => 'insufficient_role']`
- `Log::info()` on inline edit: `['user_id', 'tache_id', 'field', 'old_value', 'new_value']`

**Notifications:**
- `AbusiveEscalationAlertNotification` → to the directeur/owner when a user's `escalades_abusives` flag is set (in-app + email)
- `HighInactionRateAlertNotification` → to the manager when a responsable's inaction rate is high (in-app)

**Emails:**
- `AbusiveEscalationAlertNotification` → inline `MailMessage` (user name, bypass count, action link to dashboard)
- `HighInactionRateAlertNotification` → in-app only, no email

**Translations:**
- Add to `lang/fr/evaluation.php` and `lang/en/evaluation.php`:
  - `dashboard.*` (top performers, alerts, period labels), `workspace_tasks.columns.*`, `workspace_tasks.filters.*`

**Tests:**
- Feature: `cadre` gets HTTP 403 on `/workspace/taches`
- Feature: `manager` only sees their project scope in the dashboard

**Demo:** Directeur opens evaluation dashboard → sees full team scores → filters by period and project → accesses workspace-wide task view.

**Commits:**
```
feat: add evaluation dashboard page with team scores and alerts
feat: add workspace-wide task view with table mode and inline editing
feat: add GET /evaluations/dashboard endpoint
feat: add canViewEvaluationDashboard, canViewWorkspaceTaches, canInlineEditTache permissions
feat: hide global workspace view link for unauthorized roles
feat: update evaluation translation files with dashboard labels
test: add feature tests for dashboard and workspace task view permissions
```

---

## Task 11 — Task Creation UX Improvements

**Branch:** `feature/v2-task-11-task-creation-ux`

**Objective:** Replace the confusing tabbed task creation form with a step-by-step wizard, improve the intervenant picker, and notify assignees of associated resources.

**Implementation:**
- Replace tabbed `TacheCreate` form with `TacheCreateWizard.vue`:
  - Step 1: Basic info (titre, description, objectif, priorité, échéance)
  - Step 2: Assignment — responsable + intervenants via `IntervenantPicker.vue` (searchable multi-select from workspace/activity members, replaces the textarea)
  - Step 3: Resources (documents, photos, liens externes)
  - Step 4: Validation options (N1/N2 required, sous-tâches)
  - Progress indicator showing current step; cannot skip steps
- On task creation, notify each assigned intervenant of associated resources via existing notification system

**Permissions:**
- No new permissions — uses existing `canCreateTask` from `useActivitePermissions.js`
- Step 3 resource upload respects existing document permissions

**Logging:**
- `Log::info()` on task created via wizard: `['user_id', 'tache_id', 'intervenants_count', 'resources_count']`

**Notifications:**
- `TacheAssigneeNotification` → to each intervenant when assigned via the wizard (in-app + email, includes attached resources list)
- `TacheResourcesNotification` → to each intervenant listing documents/photos/links attached to the task (in-app + email)

**Emails:**
- `TacheAssigneeNotification` → Blade template `emails/tache-assigned/{fr|en}.blade.php` (task details, resources list, due date, action link)
- `TacheResourcesNotification` → inline `MailMessage` (resource names and links)

**Translations:**
- Add to `lang/fr/taches.php` and `lang/en/taches.php`:
  - `wizard.steps.*` (step titles), `wizard.intervenant_picker.*` (placeholder, no results), `wizard.resources.notification_sent`

**Tests:**
- Feature: wizard cannot be submitted without completing all required steps
- Feature: intervenant picker filters members correctly
- Feature: assigned intervenants receive resource notification on task creation

**Demo:** Create a task via the wizard — each step is clear, intervenants selected from a searchable list, assignees receive a notification listing attached resources.

**Commits:**
```
feat: add TacheCreateWizard step-by-step form component
feat: add IntervenantPicker searchable multi-select component
feat: notify intervenants of associated resources on task creation
feat: add wizard translation keys (fr/en)
test: add feature tests for wizard validation and intervenant picker
```

---

## Task 12 — Document Management per Project + Workspace

**Branch:** `feature/v2-task-12-document-management`

**Objective:** Full document management at project level (add, list, access, delete, share by email) and workspace level (full control for owner).

**Implementation:**
- Project-level endpoints:
  - `GET /projets/{id}/documents` — list
  - `POST /projets/{id}/documents` — upload
  - `GET /projets/{id}/documents/{docId}` — access/download
  - `DELETE /projets/{id}/documents/{docId}` — delete
  - `POST /projets/{id}/documents/{docId}/share` — share by email
  - Page `ProjetDocuments.vue`
- Workspace-level endpoints:
  - `GET /workspaces/{id}/documents` — list all workspace documents
  - Full CRUD for `owner`/`directeur` only
  - Page `WorkspaceDocuments.vue`
- Reuse existing `DocumentService` and `Document` model

**Permissions:**
- `canViewProjetDocuments(User, Projet)`: all project members
- `canUploadProjetDocument(User, Projet)`: `cadre`, `manager`, `owner`
- `canDeleteProjetDocument(User, Projet, Document)`: uploader or `manager`/`owner`
- `canShareProjetDocument(User, Projet)`: `cadre`, `manager`, `owner`
- `canManageWorkspaceDocuments(User, Workspace)`: `owner`/`directeur` only
- Add all to `RolePermissionSeeder`, `useProjetPermissions.js`, `useWorkspacePermissions.js`

**Logging:**
- `Log::info()` on document share: `['user_id', 'document_id', 'shared_to_email']`
- `Log::warning()` on unauthorized delete attempt: `['user_id', 'document_id', 'reason' => 'unauthorized']`

**Notifications:**
- `DocumentSharedNotification` → to the recipient email when a document is shared (email only — external recipient may not have an account)
- `DocumentUploadedNotification` → to project members with `cadre`/`manager` role when a new document is uploaded to their project (in-app)
- `DocumentDeletedNotification` → to the project responsable when a document is deleted (in-app)

**Emails:**
- `DocumentSharedNotification` → inline `MailMessage` (document name, download link, expiry if applicable) — email only, no in-app
- `DocumentUploadedNotification` → in-app only, no email
- `DocumentDeletedNotification` → in-app only, no email

**Translations:**
- Add to `lang/fr/documents.php` and `lang/en/documents.php`:
  - `actions.*` (upload, download, delete, share), `share.email_subject`, `share.email_body`, `errors.*`

**Tests:**
- Feature: project member can view documents, non-member gets HTTP 403
- Feature: share by email sends mail with correct link
- Feature: non-owner cannot access workspace documents → HTTP 403

**Demo:** Open a project → Documents tab → upload a file, share it by email, delete it.

**Commits:**
```
feat: add project-level document endpoints and ProjetDocuments page
feat: add workspace-level document management and WorkspaceDocuments page
feat: add document share by email
feat: add document permissions to PermissionService, seeder, composables
feat: add document translation keys (fr/en)
test: add feature tests for document management permissions and share
```

---

## Task 13 — Subscription Modes + Trial Duration

**Branch:** `feature/v2-task-13-subscription`

**Objective:** Implement free trial limits (1 workspace, 5 members, 2MB uploads, storage cap) and configurable trial duration.

**Implementation:**
- Migration: add to `workspaces`: `subscription_mode` (enum: `trial`, `paid`), `trial_started_at` (timestamp), `trial_duration_days` (integer)
- `config/subscription.php`: `trial_duration_days` default (30), `free_max_members` (5), `free_max_file_size_mb` (2), `free_max_storage_mb`
- `SubscriptionService`: `isTrialExpired(Workspace)`, `canAddMember(Workspace)`, `canUploadFile(Workspace, int $sizeBytes)`, `getRemainingTrialDays(Workspace)`
- Middleware `CheckSubscriptionLimits`: applied to relevant routes, returns HTTP 403 with clear message when limit exceeded
- Admin setting: configurable trial duration per workspace (super admin only)
- Frontend: trial expiry banner when < 7 days remaining; disable actions when limit reached with explanatory message

**Permissions:**
- `canManageSubscription(User, Workspace)`: `super_admin` only
- Add to `RolePermissionSeeder`

**Logging:**
- `Log::warning()` when a limit is hit: `['workspace_id', 'limit_type', 'current_value', 'max_value']`
- `Log::info()` on trial expiry: `['workspace_id', 'expired_at']`

**Notifications:**
- `TrialExpiringNotification` → to the workspace `directeur` when trial has 7 days remaining (in-app + email)
- `TrialExpiredNotification` → to the workspace `directeur` when trial expires (in-app + email)
- `SubscriptionLimitReachedNotification` → to the workspace `directeur` when a limit is hit (in-app + email, specifies which limit)

**Emails:**
- `TrialExpiringNotification` → Blade template `emails/trial-expiring/{fr|en}.blade.php` (days remaining, current usage vs limits, upgrade CTA)
- `TrialExpiredNotification` → Blade template `emails/trial-expired/{fr|en}.blade.php` (expiry notice, what's blocked, upgrade CTA)
- `SubscriptionLimitReachedNotification` → inline `MailMessage` (which limit, current value, max value, upgrade CTA)

**Translations:**
- Create `lang/fr/subscription.php` and `lang/en/subscription.php`:
  - `trial.banner`, `trial.expires_in`, `trial.expired`, `limits.*`, `errors.*`

**Tests:**
- Feature: adding 6th member to free workspace → HTTP 403 with translated message
- Feature: uploading file > 2MB on free workspace → HTTP 403
- Feature: trial expiry banner shown when < 7 days remaining
- Unit: `isTrialExpired()` returns correct value

**Demo:** Free workspace tries to add a 6th member → blocked with clear message. Super admin configures trial duration.

**Commits:**
```
feat: add subscription_mode and trial columns to workspaces
feat: add subscription config file with defaults
feat: add SubscriptionService with limit checks
feat: add CheckSubscriptionLimits middleware
feat: add trial expiry banner to frontend
feat: add canManageSubscription permission
feat: add subscription translation files (fr/en)
test: add feature tests for subscription limits
test: add unit tests for SubscriptionService
```

---

## Task 14 — Platform Super Admin Dashboard

**Branch:** `feature/v2-task-14-platform-dashboard`

**Objective:** Global platform management dashboard for super admins — overview of all workspaces, users, subscriptions, and system health.

**Implementation:**
- Page `/admin/dashboard`: total workspaces (active/trial/expired), total users, subscription stats, recent activity log, workspaces approaching trial expiry (< 7 days)
- Page `/admin/workspaces`: list all workspaces with status, member count, trial info, actions (extend trial, suspend)
- Page `/admin/users`: list all users with role, workspace, last login
- `GET /admin/stats` endpoint returning platform aggregates
- All `/admin/*` routes protected by `super_admin` middleware

**Permissions:**
- `canAccessPlatformDashboard(User)`: `super_admin` only
- All `/admin/*` routes protected by `super_admin` check in middleware
- Add to `RolePermissionSeeder`
- Frontend: admin nav link hidden for all non-`super_admin` roles

**Logging:**
- `Log::info()` on every admin action (extend trial, suspend workspace): `['admin_id', 'action', 'target_workspace_id']`
- `Log::warning()` on unauthorized access attempt to `/admin/*`: `['user_id', 'reason' => 'not_super_admin']`

**Notifications:**
- `WorkspaceSuspendedNotification` → to the workspace `directeur` when their workspace is suspended by a super admin (in-app + email)
- `TrialExtendedNotification` → to the workspace `directeur` when their trial is extended by a super admin (in-app + email)

**Emails:**
- `WorkspaceSuspendedNotification` → Blade template `emails/workspace-suspended/{fr|en}.blade.php` (reason if provided, contact info, appeal instructions)
- `TrialExtendedNotification` → inline `MailMessage` (new expiry date, action link)

**Translations:**
- Create `lang/fr/admin.php` and `lang/en/admin.php`:
  - `dashboard.*` (section titles, stats labels), `workspaces.*`, `users.*`, `actions.*`

**Tests:**
- Feature: non-`super_admin` accessing `/admin/dashboard` → HTTP 403
- Feature: platform stats endpoint returns correct aggregates

**Demo:** Super admin opens `/admin/dashboard` → sees all workspaces, user count, trial expirations, and recent activity.

**Commits:**
```
feat: add platform admin dashboard page with stats
feat: add admin workspaces and users management pages
feat: add GET /admin/stats endpoint
feat: protect all /admin/* routes with super_admin middleware
feat: add canAccessPlatformDashboard permission
feat: add admin translation files (fr/en)
test: add feature tests for platform dashboard permissions and stats
```

---

## Current Task

> **Update this section at the end of every session.**

- Last completed task: **Task 7** — N1 Scores + Pending Validations Dashboard (`feature/v2-task-7-scores-dashboard`) — implementation complete 2026-05-19, awaiting Jonas manual testing per `docs/testing/TASK_7_TESTING.md`, then merge
- Next task: **Task 8** — Real-Time Notifications (Reverb + Web Push)
- Branch to create: `feature/v2-task-8-notifications` (from `jonas`, after Task 7 merge)

### Dusk note (applies to all remaining tasks)
Each task must include at least one Dusk browser test covering its main user flow. Place tests under `tests/Browser/<FeatureArea>/`. Follow Guide 16 in `WORKING_GUIDELINES.md`.
