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
| 9 | Agent Sheet + Full Scoring | `feature/v2-task-9-agent-sheet` | ✅ | 2026-05-22 | 2026-05-23 | All 9 steps shipped on branch; 142 PHPUnit + 2 Dusk tests green. Pending merge into `jonas`. |
| 10 | Evaluation Dashboard + Global Task View | `feature/v2-task-10-dashboard` | ✅ | 2026-05-26 | 2026-05-26 | 216 PHPUnit + 2 Dusk tests green. Merged into `jonas` 2026-05-26. |
| 11 | Task Creation UX (wizard + intervenant picker) | `feature/v2-task-11-task-creation-ux` | ✅ | 2026-05-26 | 2026-05-26 | 221 PHPUnit + 3 Dusk tests green. Merged into `jonas` 2026-05-26. |
| 12 | Document Management per Project + Workspace | `feature/v2-task-12-document-management` | ✅ | 2026-05-26 | 2026-05-26 | 226 PHPUnit tests green. Merged into `jonas` 2026-05-26. |
| 13 | Subscription Modes + Trial Duration | `feature/v2-task-13-subscription` | ✅ | 2026-05-26 | 2026-05-26 | 253 PHPUnit tests green. |
| 14 | Platform Super Admin Dashboard | `feature/v2-task-14-platform-dashboard` | ✅ | 2026-05-26 | 2026-05-26 | 264 PHPUnit tests green. |
| 15 | Task List UX (table mode + activity shortcut) | `feature/v2-task-15-task-list-ux` | ✅ | 2026-05-26 | 2026-05-26 | 269 PHPUnit tests green. |
| 16 | PDF/Excel Export (evaluation sheet + project/task) | `feature/v2-task-16-export` | ✅ | 2026-05-26 | 2026-05-26 | CDC gaps B.1 + ST.7/E.2. 274 PHPUnit tests green. |
| — | CDC Hotfixes (R7 guard + audit-log endpoint + rate limiting + agent sheet §5) | `fix/cdc-hotfixes` | ✅ | 2026-05-26 | 2026-05-26 | Merged into `jonas` 2026-05-26. |
| — | Document management polish + notification fixes | `feature/design-system-v1` | ✅ | 2026-05-28 | 2026-05-28 | Version manager UX, sharing fixes, push toWebPush, WebPushChannel fallback, cadre upload, role values, expires_at cap. |
| — | PHPStan cleanup: larastan swap + 154 errors → 0 | `chore/test-coverage-expansion` | ✅ | 2026-05-29 | 2026-05-29 | nunomaduro→larastan, @property/@mixin on all 16 resources, @responseField annotations, 12+ real bugs fixed. 627 tests passing. |

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
- [x] Web Push notifications — **Task 8b** completed 2026-05-22 (`feature/v2-task-8b-web-push`). `minishlink/web-push` v10.0.3 installed, VAPID keys generated via `php artisan webpush:generate-vapid`, `WebPushChannel` written, `PushSubscriptionController` with subscribe/unsubscribe/vapid-key/index endpoints, `useWebPush.js` composable + `/sw-webpush.js` service worker, UI panel in `NotificationPreferences.vue`, `channelsFor()` extended with webpush channel gated by active subscription + high-signal event + `push_enabled` master switch. 13 feature tests in `WebPushSubscriptionTest`. Manual test guide `docs/testing/TASK_8B_TESTING.md`.
- [ ] PR / merge into `jonas`

### Task 9
- [x] `EvaluationScoreService::calculerScore()` with 8-criteria weighted scoring (completion_rate, deadline_respect, result_quality, first_pass_validation, justified_returns, inactions, work_volume, team_coordination) — 10 unit tests in `EvaluationScoreServiceCalculerScoreTest`
- [x] Agent sheet page `resources/js/pages/evaluations/AgentSheet.vue` mounted at `/evaluations/personnel/:id/historique` (4 sections + period/statut filters + paginated lists + drill-down modal)
- [x] Return-quality donut (inline SVG) + escalations + unjustified-return alert banner indicators
- [x] Post-N2 immutability enforced (R6) — `Tache::isLockedPostN2()` + `TacheService::guardPostN2Immutability()` blocks update/delete/move/archive/unarchive/assignUser/unassignUser with `HttpException(422)`; 6 tests in `PostN2ImmutabilityTest`
- [x] Permissions `evaluations.view_fiche` + `evaluations.export_fiche` added to `Permission.php`, `Permission::all()`, `forRole()` (see PERMISSIONS_MATRIX changelog 2026-05-22 Task 9); `PermissionService::canViewFicheEvaluation` + `canExportFicheEvaluation` encode per-target scope (own / cadre→assignees / manager→activity / owner→workspace); mirror in `Permission.js` + `useWorkspacePermissions.js`; `WorkspaceController` user_permissions payload extended in 3 locations
- [x] Translation keys added (`evaluation.criteria.*` 8 criteria, `evaluation.sheet.sections.*`, `evaluation.sheet.filters.*`, `evaluation.sheet.indicators.*`, `evaluation.errors.immutable_post_n2` + 2 new error keys, `evaluation.notifications.sheet_ready.*` + `evaluation.notifications.unjustified_return_alert.*`) in `lang/fr/evaluation.php` and `lang/en/evaluation.php`
- [x] Notifications wired: `EvaluationSheetReadyNotification` (ShouldQueue, in-app + email via Blade template `emails/evaluation-sheet-ready/{fr,en}.blade.php`, dedup 5min) → agent; `InjustifiedReturnAlertNotification` (ShouldQueue, in-app + email inline MailMessage, fires only when `unjustified_alert` true) → workspace managers (fallback to owner). Both dispatched from `EvaluationController::agentSheet`. `NotificationService::wantsEmail/wantsWebPush` extended with the two new event keys.
- [x] Endpoints `GET /api/evaluations/personnel/{user}/score` (full sheet) + `GET /api/evaluations/personnel/{user}/historique` (4-section paginated history with filters) added to `EvaluationController`
- [x] Feature tests: 4 in `EvaluationAgentSheetEndpointTest` (own-200, collaborateur-403, observateur-403, observateur own read-only 200 with can_export=false); 6 in `PostN2ImmutabilityTest`; 10 in `EvaluationScoreServiceCalculerScoreTest`
- [x] Dusk: 2 tests in `tests/Browser/Evaluation/AgentSheetTest` (header+8 criteria+donut render; section tabs switch)
- [x] Documentation: `docs/testing/TASK_9_TESTING.md` manual test guide written; PROGRESSION + IMPLEMENTATION_PLAN + PERMISSIONS_MATRIX updated
- [ ] PR / merge into `jonas`

### Task 10
- [x] Evaluation dashboard page created (`resources/js/pages/evaluations/EvaluationDashboard.vue`)
- [x] Workspace-wide task view created (`resources/js/pages/workspace/WorkspaceTaches.vue`)
- [x] `GET /api/evaluations/tableau-de-bord` endpoint created (`EvaluationController::evaluationDashboard`)
- [x] `GET /api/workspace/taches` endpoint created (`TacheController::workspaceTaches`)
- [x] 3 permissions added (`evaluations.view_dashboard`, `evaluations.view_workspace_taches`, `taches.inline_edit`)
- [x] 2 notifications added (`AbusiveEscalationAlertNotification`, `HighInactionRateAlertNotification`)
- [x] Translation keys added (`lang/fr/evaluation.php` + `lang/en/evaluation.php`)
- [x] Sidebar + Vue router wired for both pages
- [x] PHPUnit: 12 tests in `tests/Feature/Task10/` (dashboard + workspace taches permission boundaries)
- [x] Dusk: 2 tests in `tests/Browser/Evaluation/EvaluationDashboardTest` (owner sees all sections; refresh after period change)
- [x] Documentation: PROGRESSION + PERMISSIONS_MATRIX updated; `docs/testing/TASK_10_TESTING.md` pending
- [ ] PR opened into `jonas`

### Task 11
- [x] `TacheCreateWizard.vue` step wizard created (4 steps: Informations, Assignation, Ressources, Validation)
- [x] `IntervenantPicker.vue` searchable multi-select created (locked chip for responsable, v-model array)
- [x] `TacheAssigneeNotification` (mail + database, queued) + `TacheResourcesNotification` (database) wired into TacheService
- [x] Blade email templates: `resources/views/emails/tache-assigned/{fr,en}.blade.php`
- [x] Translation keys added to `lang/fr/taches.php` and `lang/en/taches.php`
- [x] ActiviteDetail.vue + Taches.vue wired (wizard for create, TacheForm for edit)
- [x] PHPUnit: 5 tests in `tests/Feature/Task11/WizardValidationTest.php`
- [x] Dusk: 3 tests in `tests/Browser/Tasks/WizardTest.php`
- [x] Manual test guide: `docs/testing/TASK_11_TESTING.md`
- [x] 221 tests green. Merged into `jonas` 2026-05-26.

### Task 12
- [x] Project-level document endpoints already existed; gated `workspaceDocuments` to owner-only
- [x] `ProjetDocuments.vue` page already existed
- [x] `DOCUMENTS_MANAGE_WORKSPACE` permission added to Permission.php + forRole() + all()
- [x] `WorkspaceDocuments.vue` page created; router entry uncommented
- [x] `can_manage_workspace_documents` added to WorkspaceController user_permissions (3 locations)
- [x] Document permission keys added to ProjetResource + useProjetPermissions.js + useWorkspacePermissions.js
- [x] `POST /api/documents/{id}/share-by-email` endpoint added (DocumentSharedNotification email-only)
- [x] `DocumentUploadedNotification` (in-app, project cadre+manager) wired into store()
- [x] `DocumentDeletedNotification` (in-app, project responsable) wired into destroy()
- [x] Translation keys added to `lang/fr/documents.php` and `lang/en/documents.php`
- [x] PHPUnit: 5 tests in `tests/Feature/Task12/DocumentManagementTest.php`
- [x] 226 tests green. Merged into `jonas` 2026-05-26.

### CDC Hotfixes (fix/cdc-hotfixes)

Gaps identified in CDC compliance review 2026-05-26 against `CDC_WorkTracking_v2.pdf` Rev.3.

- [x] R7 backend guard: `TacheResultatService::soumettre()` aborts 422 when mandatory sous-taches not terminal (CDC Section 7 / Module ST.5)
- [x] `GET /api/audit-logs/validation/{tache}` endpoint — CDC Section 6 API list (table exists, no standalone endpoint)
- [x] Rate limiting verified — `throttle:60,1` already in `RouteServiceProvider` ✅ (no change needed)
- [x] Agent sheet 5th section: résultats soumis avec statut validation — CDC Module E.2 (`submitted_results` section added)

### Task 15 (feature/v2-task-15-task-list-ux)

CDC gaps A.10–A.13.

- [x] "Voir toutes les tâches" router-link in `ActiviteDetail.vue` header → `/taches?activite={id}` (A.10)
- [x] `filterAssignee` ref defaults to `'me'`; `filteredTasks` computed filters by assignee/responsable_id (A.11)
- [x] `TacheTable.vue` component created; `currentView` defaults to `'table'` in `Taches.vue` (A.12)
- [x] Inline edit on statut/priorité/échéance cells: click → select/input → `PATCH /api/taches/{id}` → row updates (A.13)
- [x] `PATCH /api/taches/{id}` route added (mirrors PUT, uses same `update()` controller method)
- [x] `statut` validation extended to include `en_retard` and `a_refaire` (was missing from update validation)
- [x] `route.query.activite` read in `onMounted` to pre-select activity from deep-link
- [x] Manual test guide: `docs/testing/TASK_15_TESTING.md`
- [x] PHPUnit: 5 tests in `tests/Feature/Task15/TaskListUxTest.php`
- [x] **269 PHPUnit tests, all passing.**
- [ ] PR opened into `jonas`

### Task 16 (feature/v2-task-16-export)

CDC gaps B.1 + ST.7/E.2.

- [x] PDF export of evaluation sheet — `GET /api/evaluations/personnel/{user}/export-pdf` (CDC B.1)
- [x] Excel export of workspace task list — `GET /api/workspace/taches/export-excel` (CDC ST.7 / E.2)
- [x] `WorkspaceTachesExport` class (10-column, blue header, auto-size, filter-aware)
- [x] Blade template `resources/views/exports/agent-sheet.blade.php` (score_global, 8 criteria bars, task tables)
- [x] "Exporter PDF" button wired in `AgentSheet.vue`; "Exporter Excel" button wired in `WorkspaceTaches.vue`
- [x] Permission gates: `canExportFicheEvaluation` for PDF; owner-only for Excel
- [x] PHPUnit: 5 tests in `tests/Feature/Task16/ExportTest.php`
- [x] Manual test guide: `docs/testing/TASK_16_TESTING.md`
- [x] **274 PHPUnit tests, all passing.**
- [ ] PR opened into `jonas`

### Task 13
- [x] Migration: `subscription_mode`, `trial_started_at`, `trial_duration_days` added to `workspaces`
- [x] `config/subscription.php` created with env-driven defaults (duration, member limit, file size, storage, warning days)
- [x] `Workspace` model: 3 fillable fields + casts + auto-init `trial_started_at` in `boot()::creating()`
- [x] `SubscriptionService` created: `isPaid`, `isTrialExpired`, `getRemainingTrialDays`, `isExpiringSoon`, `canAddMember`, `canUploadFile`, `canUploadStorage`, `summary`
- [x] `CheckSubscriptionLimits` middleware registered as `subscription.limits`, takes `$limitType` param, bypasses super_admin
- [x] `subscription.limits:add_member` applied to invite route; `subscription.limits:upload_file` applied to document store route
- [x] `Permission::SUBSCRIPTION_MANAGE` constant + `all()` + `Permission.js` + `useWorkspacePermissions.js` (canManageSubscription)
- [x] `WorkspaceController::show()`: `can_manage_subscription` key in user_permissions (3 locations) + `subscription_summary` in response
- [x] `GET /api/workspaces/{id}/subscription` endpoint (lightweight summary for banner)
- [x] `PATCH /api/workspaces/{id}/subscription` endpoint (super_admin only — configure trial duration/mode)
- [x] `TrialExpiringNotification`, `TrialExpiredNotification`, `SubscriptionLimitReachedNotification` (all ShouldQueue, channelsFor-routed)
- [x] `NotificationService::wantsEmail()` + `wantsWebPush()` updated with 3 new high-signal subscription events
- [x] Translation files `lang/fr/subscription.php` + `lang/en/subscription.php` (trial, limits, errors, notifications sections)
- [x] `TrialBanner.vue` component — amber/red banner, dismissible, shown when trial expiring or expired
- [x] `AdminLayout.vue`: TrialBanner mounted above content area, changes workspace-reactively
- [x] `WorkspaceFactory`: 3 states added — `paid()`, `trialExpired()`, `trialExpiringSoon(daysLeft)`
- [x] PHPUnit: 13 tests in `SubscriptionServiceTest` (unit logic) + 8 tests in `SubscriptionMiddlewareTest` (HTTP middleware + API endpoints)
- [x] Manual test guide: `docs/testing/TASK_13_TESTING.md`
- [x] **253 PHPUnit tests, all passing.**
- [ ] PR opened into `jonas`

### Task 14
- [x] `AdminController` created: `stats`, `workspaces`, `users`, `extendTrial`, `suspendWorkspace`, `reactivateWorkspace`
- [x] All `/api/admin/*` routes protected by `super_admin` middleware (prefix group in `api.php`)
- [x] `TrialExtendedNotification` + `WorkspaceSuspendedNotification` (both ShouldQueue, channelsFor-routed)
- [x] `NotificationService::wantsEmail()` + `wantsWebPush()` updated with `trial_extended` + `workspace_suspended` high-signal events
- [x] Translation files `lang/fr/admin.php` + `lang/en/admin.php` (dashboard, workspaces, users, actions, notifications sections)
- [x] `AdminDashboard.vue` — 6 workspace + 2 user stat cards, recent workspaces table, quick links
- [x] `AdminWorkspaces.vue` — paginated table with search/mode/status filters, extend-trial modal, suspend modal, reactivate button
- [x] `AdminUsers.vue` — paginated table with search filter
- [x] `SubscriptionBadge.vue` component (trial=amber, paid=green, free=gray)
- [x] Vue router: 4 admin routes with `requiresSuperAdmin` meta + `beforeEach` guard → redirects non-super-admins to 404
- [x] AppSidebar: Administration section (3 items) gated by `superAdminOnly`, auto-hidden for non-admins
- [x] PHPUnit: 11 tests in `PlatformDashboardTest` (403/200 by role, stats structure, notification assertions via `Notification::fake()`)
- [x] Manual test guide: `docs/testing/TASK_14_TESTING.md`
- [x] **264 PHPUnit tests, all passing.**
- [ ] PR opened into `jonas`

---

## Bugs Encountered

> Log non-blocking bugs here so they are not forgotten.

| # | Description | File(s) | Severity | Status |
|---|---|---|---|---|
| 1 | `TacheController@show` returns the raw `$tache` Eloquent model instead of `new TacheResource($tache)`. As a result, **none** of the Resource-only fields ever reach the frontend on a task detail page load: `my_result`, `all_results`, `permissions`, `validation_status`, `bypass.*`, `audit_logs`, the new `dusk` permissions payload, etc. This silently breaks the bypass UI (TacheResultsTab depends on `tache.my_result`) and Dusk tests targeting that area. The fix is one line: `'data' => $tache,` → `'data' => new TacheResource($tache),`. **Discovered:** 2026-05-21 during `chore/dusk-catchup`. Flagged but not fixed in that PR (scope). | `app/Http/Controllers/Api/TacheController.php:799` | High (silently degrades feature surface — bypass UI, validation badges, audit trail) | **Closed 2026-05-24** on `fix/bug-batch` G1 — wrapped in `new TacheResource($tache)`; regression test `tests/Feature/TacheControllerShowResourceTest.php` locks the response shape. |
