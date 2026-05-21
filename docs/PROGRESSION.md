# Progression Tracker — Work Tracking v2.0

> Update this file at the end of every task. One row per task.

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Task Progress

| # | Task | Branch | Status | Started | Completed | Notes |
|---|---|---|---|---|---|---|
| 0 | Permission Architecture Refactor (Policies) | `feature/v2-task-0-policies-refactor` | ✅ | 2026-05-12 | 2026-05-12 | — |
| 1 | Queue (database) + Laravel Reverb | `feature/v2-task-1-queue-reverb` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 2 | Subtask Data Model | `feature/v2-task-2-subtask-model` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 3 | Subtask CRUD API + Auto Progress | `feature/v2-task-3-subtask-api` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 4 | Subtask UI | `feature/v2-task-4-subtask-ui` | ✅ | 2026-05-15 | 2026-05-18 | Branch existed but was never merged — restored 2026-05-18 |
| 5 | Validation N0 + 48h Timer | `feature/v2-task-5-validation-n0` | ✅ | 2026-05-15 | 2026-05-15 | — |
| 6 | Anti-Sabotage Bypass | `feature/v2-task-6-bypass` | ✅ | 2026-05-18 | 2026-05-18 | Merged into `jonas` 2026-05-19 |
| 7 | N1 Scores + Pending Validations | `feature/v2-task-7-scores-dashboard` | ✅ | 2026-05-19 | 2026-05-19 | Merged into `jonas` 2026-05-21 |
| 8 | Reverb + Web Push Notifications | `feature/v2-task-8-notifications` | ✅ | 2026-05-21 | 2026-05-21 | Real-time + dedup + hierarchy + daily digest done. Web Push (8b) deferred to dedicated PR. |
| 9 | Agent Sheet + Full Scoring | `feature/v2-task-9-agent-sheet` | ⬜ | — | — | — |
| 10 | Evaluation Dashboard + Global Task View | `feature/v2-task-10-dashboard` | ⬜ | — | — | — |
| 11 | Task Creation UX (wizard + intervenant picker) | `feature/v2-task-11-task-creation-ux` | ⬜ | — | — | — |
| 12 | Document Management per Project + Workspace | `feature/v2-task-12-document-management` | ⬜ | — | — | — |
| 13 | Subscription Modes + Trial Duration | `feature/v2-task-13-subscription` | ⬜ | — | — | — |
| 14 | Platform Super Admin Dashboard | `feature/v2-task-14-platform-dashboard` | ⬜ | — | — | — |

---

## Deliverables Checklist per Completed Task

### Task 0
- [x] Contextual roles seeded into Spatie `roles` table with default permissions
- [x] `ProjetPolicy`, `ActivitePolicy`, `TachePolicy`, `DocumentPolicy`, `WorkspacePolicy` created
- [x] `SousTachePolicy` stub created (populated in Task 2)
- [x] `Gate::before()` super_admin bypass registered in `AuthServiceProvider`
- [x] All policies registered in `AuthServiceProvider::$policies`
- [x] `PermissionService` refactored to relationship-helper only
- [x] All controllers updated: `abort_unless` → `$this->authorize()`
- [x] `RolePermissionSeeder` updated to seed contextual roles
- [x] All existing tests still passing
- [ ] PR opened into `jonas`

### Task 1
- [x] `QUEUE_CONNECTION=database` in `.env` and `.env.example`
- [x] Reverb installed and configured
- [x] `laravel-echo` + `pusher-js` installed
- [x] `useEcho.js` composable created
- [x] Tests passing
- [x] PR merged into `jonas`

### Task 2
- [x] Migration `create_sous_taches_table` applied
- [x] Migration `create_sous_tache_user_table` applied
- [x] `parent_tache_id` dropped from `taches` (data migrated to `sous_taches`)
- [x] `EN_RETARD` and `A_REFAIRE` added to `TacheStatut`
- [x] `SousTache` model with R1 + R2 rules
- [x] `SousTacheFactory` and `SousTacheSeeder` created
- [x] `SousTacheResource` created
- [x] `SousTachePolicy` created and registered in `AuthServiceProvider`
- [x] Permissions added (PermissionService + RolePermissionSeeder)
- [x] Translation files `lang/fr/sous_taches.php` and `lang/en/sous_taches.php` created
- [x] Bug fix: `soustaches` → `sousTaches` in ProjetController (2 occurrences)
- [x] `is_responsable` added to `assignees()` withPivot in Tache model
- [x] Tests passing (6 new tests)
- [ ] Frontend composable (`useTachePermissions.js`) — deferred to Task 4
- [x] PR merged into `jonas`

### Task 3
- [x] `SousTacheService` created (create, update, delete, assignIntervenant)
- [x] `SousTacheController` created (5 endpoints: index, store, update, destroy, assignIntervenant)
- [x] `SousTacheObserver` created and registered in `AppServiceProvider`
- [x] Routes registered in `api.php`
- [x] `canAssignSousTacheIntervenant` permission added to `PermissionService` and seeder
- [x] Translation keys added (fr/en): date_exceeds_parent, status_blocked, unauthorized, intervenant_assigned, notifications.assigned
- [x] 3 notifications created: `SousTacheAssigneeNotification`, `SousTacheOverdueNotification`, `TacheStatutAutoChangeNotification`
- [x] Migration: extend `taches.statut` MySQL ENUM with `en_retard` and `a_refaire`
- [x] Manual statut block on parent Tache when sous-taches exist (model `updating` hook)
- [x] Tests passing (10 new tests, 41 total)
- [ ] PR merged into `jonas`

### Task 4
- [x] `useSousTaches.js` composable created (self-contained CRUD, owns its own data)
- [x] `SousTacheList.vue` created (ordered list, inline edit, quick-complete toggle, weighted progress bar)
- [x] `SousTacheForm.vue` created (quick-create with poids remaining, date max, validation flags)
- [x] Kanban card indicator added (count badge + mini progress bar on TacheCard)
- [ ] Submit result button disabled when blocking subtasks — deferred (not yet implemented)
- [x] Permissions added to `useActivitePermissions.js` (`canCreateSousTache`, `canAssignSousTacheIntervenant`)
- [x] Translation keys added (`ui.*` section in fr/en sous_taches.php)
- [x] `SousTacheList` integrated in `TacheDetail.vue` (Sous-tâches tab)
- [x] `SousTacheList` integrated in `TacheDetailModal.vue` (compact + detailed view tab)
- [x] Bug fix: stale `'responsable'`/`'collaborator'` role values replaced across 6 files
- [x] Merged into `jonas` 2026-05-18 (was previously unmerged)

### Task 5
- [x] N0 columns added to `tache_resultats` (statut, soumis_n0_le, action_n0, commentaire_n0, n0_actor_id, action_n0_le)
- [x] `validation_audit_logs` table created (immutable — no updated_at, R6)
- [x] `ValidationAuditLog` model created
- [x] `TacheResultatService` created: `soumettre`, `approuverN0`, `renvoyerN0`, `transmettreAuN1`
- [x] `TransmettreResultatAuN1Job` created (dispatched on submit, skips if N0 already acted)
- [x] `ResultatSoumisN0Notification`, `ResultatRenvoyeNotification`, `ResultatApprouveN0Notification`, `ResultatTransmisAutoNotification` created
- [x] Blade template: `resources/views/emails/resultat-renvoye/{fr,en}.blade.php`
- [x] `validation_timeout_hours` added to `Workspace::getDefaultSettings()` (default 48h)
- [x] `canApprouverN0`, `canRenvoyerN0` added to `PermissionService`
- [x] `resultats.approuver_n0`, `resultats.renvoyer_n0` added to `RolePermissionSeeder`
- [x] `useTachePermissions.js` created with `canApprouverN0`, `canRenvoyerN0`
- [x] `TacheResultatResource`: `statut`, `validation_n0` block, `can_approuver_n0`, `can_renvoyer_n0`
- [x] Translation files `lang/fr/circuit_validation.php` + `lang/en/circuit_validation.php` (separate from Laravel's validation.php)
- [x] `TacheResultatController::submit()` wired to `TacheResultatService::soumettre()` — job now dispatched on submit
- [x] 7 new tests passing (48 total)
- [ ] PR opened into `jonas`

### Dusk (Browser Testing)
- [x] `laravel/dusk` installed (`^8.6`)
- [x] ChromeDriver installed and matched to Chrome 148
- [x] `.env.dusk.local` created — points to `work-tracking-dusk` dedicated test database
- [x] `tests/Browser/WorkTrackingTestCase.php` base class — `signInAs()` (token injection) + `signInViaUi()` helpers
- [x] `tests/Browser/Auth/AuthenticationTest.php` — 3 tests passing (sign in, wrong password, redirect guard)
- [x] `dusk="email"`, `dusk="password"`, `dusk="login-button"` attributes added to `Signin.vue`
- [x] Fragile `down()` rollback migrations fixed (`2025_10_14_labels`, `2025_10_24_team_id`, `2025_11_25_tache_resultats`)
- [x] Run: `php artisan serve` + `php artisan dusk`

### Task 6
- [x] Bypass columns added to `tache_resultats` (`bypass_active`, `motif_bypass`, `bypass_le`, `bypass_count`)
- [x] `escalades_abusives` + `bypass_count` added to `tache_user`
- [x] `activerBypass` added to `TacheResultatService` (R3 + R5 + audit log + BypassActivatedNotification)
- [x] `invaliderBypassN1` added to `TacheResultatService` (escalades_abusives flag at 3 consecutive)
- [x] N1 context panel data exposed via `TacheResultatResource` (`bypass` block + `audit_logs`)
- [x] `RESULTATS_ACTIVER_BYPASS` permission: `Permission.php`, `forRole()`, `Permission.js`, `useTachePermissions.js`
- [x] Translation keys: `success.bypass_active`, `bypass.*`, `notifications.bypass_active`, `notifications.escalades_abusives` (fr + en)
- [x] `BypassActivatedNotification` + Blade email `emails/bypass-activated/{fr,en}.blade.php`
- [x] `EscaladesAbusivesNotification` (inline MailMessage)
- [x] 10 tests passing (`BypassCircuitTest`) — 80 total
- [ ] PR opened into `jonas`

### Task 7
- [x] `evaluation_scores` table created (user_id, periode_start/end, critere, valeur, meta JSON + composite indexes)
- [x] `EvaluationScore` model with date/decimal/array casts + `inPeriod` / `decidedBetween` scopes
- [x] `EvaluationScoreFactory` with `penalty`, `bonus`, `forUser` states
- [x] `EvaluationScoreService::calculerImpactN1` — three paths: validated_despite_return (PENALTY -1.0), confirmed_return (BONUS +1.0), no_impact (null)
- [x] `EvaluationScoreService::totalForUser` — SUM helper, defaults to current month
- [x] `TacheResultatService::validerN1` / `rejeterN1` wrappers — call model + EvaluationScoreService + invaliderBypassN1 when bypass
- [x] `TacheResultatController::validateN1` / `reject` routed through the service
- [x] `EvaluationController::pendingValidationsDashboard` — sorted by remaining deadline, urgent flag, bypass + escalades_abusives badges
- [x] `EvaluationController::userScore` — own-score for all roles, others gated by EVALUATIONS_VIEW_PENDING
- [x] `ScoreUpdatedNotification` (database channel only — no email per spec)
- [x] Permissions `EVALUATIONS_VIEW_PENDING` + `EVALUATIONS_VIEW_SCORE` in `Permission.php` + `forRole()` + `Permission.js` + `useWorkspacePermissions.js` + WorkspaceController user_permissions payload
- [x] Translation files `lang/fr/evaluation.php` and `lang/en/evaluation.php` created (errors, criteria, dashboard, notifications)
- [x] Vue page `pages/evaluations/PendingValidations.vue` + `PendingRow.vue` component + sidebar link
- [x] WORKING_GUIDELINES Guide 17 (logs in French) added in same session
- [x] 10 feature tests (EvaluationScoreServiceTest) + 1 Dusk test (PendingValidationsTest) — 90 total, all passing
- [x] PERMISSIONS_MATRIX.md changelog row added
- [ ] PR / merge into `jonas`

### Task 8
- [x] Broadcast wired into all 7 N0/Bypass/Score/Escalades notifications via `NotificationService::channelsFor()`
- [x] `useLiveNotifications.js` composable subscribes to `App.Models.User.{id}` private channel and surfaces toasts
- [x] `useEcho.js` reused from Task 1 (no changes needed)
- [x] Deduplication logic: `NotificationService::isDuplicate()` + `dedup_key` field on every notification's `toArray()`
- [x] Hierarchy propagation: `NotificationService::notifyHierarchy()` walks workspace owner + managers, dedup per recipient
- [x] `canManageNotificationPreferences` permission: `Permission.php` + `forRole('owner')` only + `Permission.js` + `useWorkspacePermissions.js` + `WorkspaceController.user_permissions` (3 locations)
- [x] phpunit.xml gets `BROADCAST_DRIVER=log` so tests don't hit real Reverb
- [x] 11 feature tests in `NotificationServiceTest` + 2 Dusk tests in `NotificationBellTest` — 101 total passing
- [x] PERMISSIONS_MATRIX.md gains a Notification Permissions section + changelog row
- [x] Daily digest: migration `add_last_digest_sent_at_to_notification_preferences_table` + `SendDailyDigest` command (15-min schedule, dry-run + user filter options) + `DailyDigestMail` mailable + Blade templates `emails/daily-digest/{fr,en}.blade.php` + quiet-hours respect
- [x] `app/Console/Kernel.php` schedules `notifications:send-digest` every 15 min with `withoutOverlapping(20)` + `onOneServer` + `runInBackground`
- [x] Translation files `lang/{fr,en}/notifications.php` (digest subject only — runtime notifications use `circuit_validation.*`)
- [x] 8 feature tests in `SendDailyDigestCommandTest` covering: send to eligible, skip when empty/before-time/already-sent/quiet-hours/frequency-none, --dry-run, --user filter
- [ ] Web Push notifications — **deferred to Task 8b** (`feature/v2-task-8b-web-push`). Needs `minishlink/web-push` composer package + VAPID keys + service worker registration. `push_subscriptions` table already exists.
- [ ] PR / merge into `jonas`

### Task 9
- [ ] `EvaluationService` with 8-criteria scoring created
- [ ] Agent sheet page created (4 sections)
- [ ] Return quality and escalations indicators added
- [ ] Post-N2 immutability enforced
- [ ] Permissions added
- [ ] Translation keys added
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 10
- [ ] Evaluation dashboard page created
- [ ] Workspace-wide task view created
- [ ] `GET /evaluations/dashboard` endpoint created
- [ ] Permissions added
- [ ] Translation keys added
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 11
- [ ] `TacheCreateWizard.vue` step wizard created (4 steps)
- [ ] `IntervenantPicker.vue` searchable multi-select created
- [ ] Resource notification on task creation implemented
- [ ] Translation keys added to `lang/fr/taches.php` and `lang/en/taches.php`
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 12
- [ ] Project-level document endpoints created (list, upload, access, delete, share)
- [ ] `ProjetDocuments.vue` page created
- [ ] Workspace-level document endpoints created
- [ ] `WorkspaceDocuments.vue` page created
- [ ] Permissions added (PermissionService + Seeder + composables)
- [ ] Translation keys added to `lang/fr/documents.php` and `lang/en/documents.php`
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 13
- [ ] `subscription_mode` and trial columns added to `workspaces`
- [ ] `config/subscription.php` created with defaults
- [ ] `SubscriptionService` created
- [ ] `CheckSubscriptionLimits` middleware created
- [ ] Trial expiry banner added to frontend
- [ ] `canManageSubscription` permission added
- [ ] Translation files `lang/fr/subscription.php` and `lang/en/subscription.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 14
- [ ] `/admin/dashboard` page created
- [ ] `/admin/workspaces` page created
- [ ] `/admin/users` page created
- [ ] `GET /admin/stats` endpoint created
- [ ] All `/admin/*` routes protected by `super_admin` middleware
- [ ] `canAccessPlatformDashboard` permission added
- [ ] Translation files `lang/fr/admin.php` and `lang/en/admin.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

---

## Bugs Encountered

> Log non-blocking bugs here so they are not forgotten.

| # | Description | File(s) | Severity | Status |
|---|---|---|---|---|
| 1 | `TacheController@show` returns the raw `$tache` Eloquent model instead of `new TacheResource($tache)`. As a result, **none** of the Resource-only fields ever reach the frontend on a task detail page load: `my_result`, `all_results`, `permissions`, `validation_status`, `bypass.*`, `audit_logs`, the new `dusk` permissions payload, etc. This silently breaks the bypass UI (TacheResultsTab depends on `tache.my_result`) and Dusk tests targeting that area. The fix is one line: `'data' => $tache,` → `'data' => new TacheResource($tache),`. **Discovered:** 2026-05-21 during `chore/dusk-catchup`. Flagged but not fixed in that PR (scope). | `app/Http/Controllers/Api/TacheController.php:799` | High (silently degrades feature surface — bypass UI, validation badges, audit trail) | Open |
