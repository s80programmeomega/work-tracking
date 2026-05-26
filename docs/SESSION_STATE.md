# Session State — Work Tracking v2.0

> **This file is updated at the END of every working session.**
> When resuming, read this file first — it tells you exactly where you are and what to do next.

---

## How to Resume

1. Read `docs/WORKING_GUIDELINES.md` (conventions + tools)
2. Read `docs/IMPLEMENTATION_PLAN.md` (full task details)
3. Read this file (current state)
4. Say: _"I've read the docs. Resuming from [Current Task] — [what's next]."_

---

## Current Session

**Date:** 2026-05-26
**Session goal:** Implement Task 10 (Evaluation Dashboard + Workspace-Wide Task View)
**Status:** Task 10 implementation complete on `feature/v2-task-10-dashboard`. 216 PHPUnit tests + 2 Dusk tests green. Pending merge into `jonas`.

---

## Current Task

**Task:** 10 — Evaluation Dashboard + Global Task View
**Branch:** `feature/v2-task-10-dashboard`
**Status:** Implementation complete. Ready to merge.

**What to do next:**
1. Merge `feature/v2-task-10-dashboard` into `jonas` (`git merge --no-ff`)
2. Push `jonas` to `origin`
3. Pick next task — **Task 11** (Task Creation UX: wizard + intervenant picker)

## Last Completed Task

**Task 10** — Evaluation Dashboard + Global Task View (2026-05-26)
- 3 new permissions: `evaluations.view_dashboard`, `evaluations.view_workspace_taches`, `taches.inline_edit` — wired into Permission.php + forRole() + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions (3 locations) + PermissionService (3 helpers)
- `EvaluationController::evaluationDashboard` — scope-aware: owner→all workspace members, manager→project members, cadre→activity members; top_performers (top 5 by score) + all scores + alerts (escalades_abusives + high_inaction_rate ≥ 33%)
- `TacheController::workspaceTaches` — owner/super_admin only; filterable by statut/projet_id/activite_id/assignee_id; paginated (25/page); TacheResource + eager loads
- Routes: `GET /api/evaluations/tableau-de-bord` + `GET /api/workspace/taches`
- 2 notifications: `AbusiveEscalationAlertNotification` (in-app + email) + `HighInactionRateAlertNotification` (in-app only)
- Translation keys in `lang/fr/evaluation.php` + `lang/en/evaluation.php` (dashboard section + workspace_tasks section)
- `EvaluationDashboard.vue` — period filter, top performers grid, all-scores table, alerts section (escalades_abusives + high_inaction_rate)
- `WorkspaceTaches.vue` — filter bar, sortable table, pagination, row click → taches.show
- AppSidebar + Vue router wired for both pages
- PHPUnit: 12 tests in `tests/Feature/Task10/` (dashboard 200/403 by role + manager scope isolation; workspace taches 200/403 by role + statut filter)
- Dusk: 2 tests in `tests/Browser/Evaluation/EvaluationDashboardTest` (owner sees all sections; period filter + refresh)
- PERMISSIONS_MATRIX.md: 3 new rows + 1 correction (cadre now ✅ for dashboard) + changelog entry
- **216 PHPUnit tests, all passing**

**Task 8b** — Web Push Notifications (2026-05-22, implementation complete)
- `composer require minishlink/web-push` v10.0.3 (free, MIT, pure PHP, no external service)
- `php artisan webpush:generate-vapid` command — génère les clés VAPID à coller dans `.env`
- `config/webpush.php` + .env.example block (VAPID_SUBJECT / VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY / TTL / urgency / VITE_VAPID_PUBLIC_KEY)
- `App\Notifications\Channels\WebPushChannel` — sérialise le payload, signe avec VAPID, gère 410/404 (soft-disable de la souscription)
- API `/webpush` : vapid-key (GET), subscribe (POST, upsert), unsubscribe (DELETE), subscriptions (GET, liste active du user)
- `NotificationService::channelsFor()` étendu : ajoute `WebPushChannel::class` quand le user a une souscription active ET `push_enabled = true` ET event high-signal (renvoye_n0/transmis_auto/bypass/escalades_abusives)
- Frontend : `public/sw-webpush.js` service worker (event 'push' → showNotification, 'notificationclick' → focus/openWindow) + `useWebPush.js` composable (permission flow, urlBase64ToUint8Array, subscribe/unsubscribe avec persistance backend)
- UI : panneau « Cet appareil » dans `NotificationPreferences.vue` quand `push_enabled` est on — boutons Activer/Désactiver, statut humain
- 13 feature tests in `WebPushSubscriptionTest` couvrant : vapid-key (200/503/401), subscribe (create/upsert/validation), unsubscribe (deactivate/no-op), index (filtre active + own), channelsFor() (with/without subscription, push_enabled false, low-signal events)
- 122 tests passing total, all green

**Task 8** — Real-Time Notifications + Daily Digest (2026-05-21, merged into jonas)

Real-time + service helpers:
- `NotificationService::channelsFor(notifiable, eventType)` — resolves `['database', 'broadcast']` ± `'mail'` based on event signal level
- `NotificationService::dedupKey(eventType, tacheResultatId)` + `isDuplicate(user, eventType, tacheResultatId)` — 5-minute deduplication window via `data.dedup_key` in `notifications` table
- `NotificationService::notifyHierarchy(recipient, workspace, notification, eventType, tacheResultatId)` — fans out to direct recipient + workspace directeur (owner_id) + all workspace managers, dedup per user
- All 7 existing notifications (`ResultatSoumisN0/Renvoye/ApprouveN0/TransmisAuto`, `BypassActivated`, `EscaladesAbusives`, `ScoreUpdated`) use `channelsFor()` in `via()` and include `dedup_key` in `toArray()`
- Frontend: `useLiveNotifications.js` composable wraps `useEcho` and subscribes to `App.Models.User.{id}` private channel; wired into `App.vue` `onMounted`, surfaces toast on incoming notification
- Notification bell (`NotificationMenu.vue`) gets `dusk` attributes for Dusk test targeting
- `NOTIFICATIONS_MANAGE_PREFERENCES` permission across Permission.php + forRole (owner only) + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions (3 locations)
- PERMISSIONS_MATRIX.md: new Notification Permissions section + changelog row
- `phpunit.xml` adds `BROADCAST_DRIVER=log` + `BROADCAST_CONNECTION=log` so tests don't hit real Reverb

Daily digest (Task 8c — built in same PR):
- Migration `add_last_digest_sent_at_to_notification_preferences_table` (indexed timestamp column)
- `SendDailyDigest` command (`notifications:send-digest`) with `--dry-run` and `--user=` options
- Aggregation logic: filters by `digest_frequency != 'none'`, `digest_time <= now()`, `last_digest_sent_at` empty-or-before-today
- Quiet hours respected — users inside `quiet_hours_start..end` window skipped (midnight-wrapping windows handled)
- `DailyDigestMail` mailable + Blade templates `emails/daily-digest/{fr,en}.blade.php` (notifications grouped by type, top 5 per group, link to all)
- Translation keys `notifications.daily_digest.subject` (fr + en)
- Scheduled every 15 minutes in `app/Console/Kernel.php` with `withoutOverlapping(20)` + `onOneServer` + `runInBackground`

Tests: 11 feature in `NotificationServiceTest` + 8 feature in `SendDailyDigestCommandTest` + 2 Dusk in `NotificationBellTest` = **109 total, all passing**

**Deferred to Task 8b (Web Push, separate PR):**
- `minishlink/web-push` Composer package
- VAPID key generation + secure storage
- Service worker registration + browser permission UX
- `WebPushChannel` notification channel

**Task 7** — N1 Scores + Pending Validations Dashboard (merged 2026-05-21, commit `c891a6e`)
- Migration `create_evaluation_scores_table` (user_id, periode_start/end, critere, valeur, meta JSON + 3 indexes)
- `EvaluationScore` model with scopes `inPeriod` / `decidedBetween` + factory with `penalty`/`bonus`/`forUser` states
- `EvaluationScoreService::calculerImpactN1` — 3 paths (penalty / bonus / no_impact), writes evaluation_scores row + mirrors to validation_audit_logs + dispatches ScoreUpdatedNotification
- `EvaluationScoreService::totalForUser` — SUM helper with optional date range
- `TacheResultatService::validerN1` / `rejeterN1` — wrap model methods + call scoring service; `rejeterN1` calls `invaliderBypassN1` internally when bypass_active
- `TacheResultatController::validateN1` / `reject` routed through service
- `EvaluationController::pendingValidationsDashboard` — sorted by remaining deadline, urgent flag (< 24h), bypass/escalades_abusives badges
- `EvaluationController::userScore` — own-score for all roles, others gated by privileged permission
- Routes: `GET /evaluations/validations/en-attente`, `GET /evaluations/score`
- `ScoreUpdatedNotification` — database channel only (no email, per spec)
- Permissions: `EVALUATIONS_VIEW_PENDING` + `EVALUATIONS_VIEW_SCORE` across Permission.php + forRole + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions
- Translations `lang/{fr,en}/evaluation.php`
- Vue: `pages/evaluations/PendingValidations.vue` + `PendingRow.vue` + sidebar link
- WORKING_GUIDELINES Guide 17 (logs in French) added in same session
- 10 feature tests + 1 Dusk test (90 total, all passing)

---

## Recently Completed Tasks

**Task 6** — Anti-Sabotage Bypass (merged 2026-05-19, commit `f6e98d2`)
- 2 migrations: `add_bypass_columns_to_tache_resultats`, `add_bypass_count_to_tache_user`
- `TacheResultatService`: `activerBypass` (R3 + R5), `invaliderBypassN1` (escalades_abusives flag at 3 consecutive)
- `TacheResultatController::activerBypass` — R3 checked before statut check (409 > 422 precedence)
- `RESULTATS_ACTIVER_BYPASS` permission: `Permission.php` + `forRole()` (collaborateur + stagiaire) + `Permission.js` + `useTachePermissions.js`
- `TacheResultatResource`: `bypass` block + `audit_logs` + `can_activer_bypass`
- `BypassActivatedNotification` (Blade email, fr + en) + `EscaladesAbusivesNotification` (inline)
- `circuit_validation.php` (fr + en): `success.bypass_active`, `bypass.*`, `notifications.bypass_active`, `notifications.escalades_abusives`
- 10 new tests in `BypassCircuitTest` — 80 total, all passing
- Workspace.php curly-quote bug fixed + `WorkspaceMembershipTest` (5 tests) added in same session

**Task 5** — N0 Validation Circuit + 48h Timer
- `ValidationAuditLog` model (immutable, R6)
- `TacheResultatService`: `soumettre`, `approuverN0`, `renvoyerN0`, `transmettreAuN1`
- `TransmettreResultatAuN1Job`: dispatched on submit, workspace-configured delay (default 48h), skips if N0 already acted
- 4 notifications: `ResultatSoumisN0`, `ResultatRenvoye` (Blade), `ResultatApprouveN0` (in-app), `ResultatTransmisAuto`
- `POST approuver-n0`, `POST renvoyer-n0` endpoints with R4 (min 30 chars)
- `useTachePermissions.js` created with `canApprouverN0`, `canRenvoyerN0`
- `circuit_validation.*` translation files (fr + en)
- 7 new tests, 48 total

**Task 3** — Subtask CRUD API + Automatic Progress
- `SousTacheService`, `SousTacheController` (5 endpoints), `SousTacheObserver`
- Weighted progress auto-recalculation on parent task
- Parent statut auto-change: all done → termine, any en_retard → en_retard
- Manual statut block when sous-taches exist (422)
- `canAssignSousTacheIntervenant` permission added
- 3 notifications (SousTacheAssignee, SousTacheOverdue, TacheStatutAutoChange)
- MySQL ENUM extended for taches.statut
- 10 new tests, 41 total, all passing

---

## Previously Completed

**Task 2** — Subtask Data Model
- 3 migrations: `create_sous_taches_table`, `create_sous_tache_user_table`, `drop_parent_tache_id_from_taches` (with data migration from self-referential pattern)
- `EN_RETARD` and `A_REFAIRE` added to `TacheStatut` enum
- `SousTache` model with R2 `enforceWeights()` rule, `LogsActivity`, `SoftDeletes`
- `SousTachePolicy` registered in `AuthServiceProvider`
- `SousTacheResource`, `SousTacheFactory`, `SousTacheSeeder` created
- `PermissionService`: 4 new methods (`canViewSousTache`, `canCreateSousTache`, `canEditSousTache`, `canDeleteSousTache`)
- `RolePermissionSeeder`: 4 new permissions, wired into all contextual roles
- Translation files: `lang/fr/sous_taches.php` + `lang/en/sous_taches.php`
- **Bug fix (ProjetController):** `soustaches` → `sousTaches` on 2 eager loads
- **Bug fix (Tache model):** `is_responsable` added to `assignees()` withPivot
- 6 new tests (R1 schema, R2 enforce/valid/unweighted, permission with/without is_responsable)
- 28 tests total, all passing

---

## Open PRs

| Branch | Task | Status |
|---|---|---|
| `feature/v2-task-1-queue-reverb` | Task 1 | Merged ✅ |
| `feature/v2-task-2-subtask-model` | Task 2 | Merged ✅ |
| `feature/v2-task-3-subtask-api` | Task 3 | Merged ✅ |
| `feature/v2-task-4-subtask-ui` | Task 4 | Merged ✅ (recovered 2026-05-18) |
| `feature/v2-task-5-validation-n0` | Task 5 | Merged ✅ (via `feature/v2-permission-architecture`) |
| `feature/v2-task-6-bypass` | Task 6 | Merged ✅ |
| `feature/v2-task-7-scores-dashboard` | Task 7 | Merged ✅ (commit `c891a6e`) |
| `feature/v2-task-8-notifications` | Task 8 (partial) | Pending Jonas testing |

---

## Pending Decisions / Open Questions

| # | Question | Context | Status |
|---|---|---|---|
| 1 | Frontend composable for SousTache permissions | `useTachePermissions.js` update deferred to Task 4 | Closed (Task 4 merged) |
| 2 | Per-task drill-down modal on evaluation sheet | Jonas's UX request 2026-05-19: each task row in the agent sheet should open a modal listing its sous-tâches with progression + per-sous-tâche score contributions. Implementation belongs in **Task 9** (`evaluation_scores.meta` already supports this; no schema change). Captured in Task 9 spec. | Captured for Task 9 |

---

## Environment Reminder

- Project path: `/media/jonas/Jonas/Work-traking` (USB drive — always push before leaving)
- DB: MySQL, database `work-tracking`
- Queue: `database` ✅
- Broadcasting: `reverb` ✅ — run `php artisan reverb:start` to start WebSocket server
- Remotes: `origin` = your repo (`s80programmeomega`), `client` = client repo (frozen until paid)
- Run backend: `php artisan serve`
- Run frontend: `npm run dev`
- Run tests: `php artisan test --compact`

---

## Session Log

| Date | Tasks worked on | Outcome |
|---|---|---|
| 2026-05-11 | Planning | Created IMPLEMENTATION_PLAN.md, WORKING_GUIDELINES.md, PROGRESSION.md, SESSION_STATE.md. Updated ONBOARDING.md. |
| 2026-05-13 | Task 0 bug fixes | Fixed Pinia readonly conflict, UserResource, hasAccess bypass, sidebar fallbacks. Added 6 factories, expanded seeder. |
| 2026-05-14 | Task 0 review + merge, Task 1, Task 2 | Reviewed Task 0 (22 tests, all clean). Merged into jonas. Task 1: queue=database, Reverb, useEcho.js. Task 2: sous_taches table, SousTache model with R1/R2, policy, resource, factory, seeder, permissions, translations. Fixed ProjetController bug (soustaches→sousTaches) and Tache assignees pivot (is_responsable missing). 28 tests passing. |
| 2026-05-15 | Task 4 bugs, Task 5 testing + fix, Dusk setup | Fixed role ENUM mismatch (6 files), ST badge missing from kanban/list (6 queries), SousTacheList modal self-contained refactor. Task 5: fixed submit() not calling TacheResultatService::soumettre() — job now dispatched. Installed Laravel Dusk, fixed 3 fragile migration rollbacks, wrote WorkTrackingTestCase base + AuthenticationTest (3 browser tests passing). |
| 2026-05-18 | Task 6, Task 4 recovery, bug fixes | Implemented Task 6 (anti-sabotage bypass): migrations, `activerBypass` + `invaliderBypassN1` services, 2 notifications + Blade emails (fr/en), 10 feature tests. Recovered orphan `feature/v2-task-4-subtask-ui` branch and merged into jonas. Fixed `Workspace.php` curly quotes, sidebar dropdown stuck/double-active, three task-detail sub-tab crashes (`/users`→`/members`, defensive `?? []`), ST badge missing from kanban (added `withCount('sousTaches')` in `TacheService`). 80 tests passing. |
| 2026-05-19 | Task 6 merge | Merged `feature/v2-task-6-bypass` into `jonas` (`--no-ff`, commit `f6e98d2`). Pushed `jonas` to `origin`. Updated SESSION_STATE, PROGRESSION, IMPLEMENTATION_PLAN. |
| 2026-05-19 | Task 7 implementation | Implemented EvaluationScoreService (penalty/bonus/no_impact paths), pending-validations dashboard endpoint + Vue page, permissions, translations. Added Guide 17 (logs in French). Wired TacheResultatService::validerN1/rejeterN1 into the controller. 10 feature tests + 1 Dusk test. 90 tests passing. |
| 2026-05-21 | Task 7 merge + Task 8 partial | Merged feature/v2-task-7-scores-dashboard into jonas (commit c891a6e, --no-ff) + pushed. Implemented Task 8 partial scope: NotificationService channelsFor/dedupKey/isDuplicate/notifyHierarchy, broadcast wired into 7 existing notifications via channelsFor, useLiveNotifications.js composable + App.vue subscription, NOTIFICATIONS_MANAGE_PREFERENCES permission, dusk attributes on NotificationMenu. Web Push and daily digest deferred to dedicated follow-up PRs. 11 feature tests + 2 Dusk tests. 101 tests passing. |
| 2026-05-26 | fix/bug-batch merge + Task 10 | Merged fix/bug-batch (G1–G10, G12; G11 skipped) into jonas. Cut feature/v2-task-10-dashboard. Implemented: 3 permissions, EvaluationController::evaluationDashboard, TacheController::workspaceTaches, 2 routes, 2 notifications, translation keys, EvaluationDashboard.vue, WorkspaceTaches.vue, sidebar + router. 12 PHPUnit feature tests + 2 Dusk tests. 216 tests passing. |
