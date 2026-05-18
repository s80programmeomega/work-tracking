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

**Date:** 2026-05-18
**Session goal:** Pre-merge checks, bug fixes, Task 6 implementation, Task 4 recovery and merge
**Status:** Task 6 complete + Task 4 restored. 80 tests passing. Subtask UI back on all branches. Pending: manual bypass testing by Jonas, then merge task-6 into jonas.

---

## Current Task

**Task:** 6 — Anti-Sabotage Bypass (manual testing by Jonas in progress)
**Branch:** `feature/v2-task-6-bypass`
**Status:** Implementation complete — pending Jonas manual testing before merge into `jonas`

**What to do next:**
1. Jonas tests Task 6 manually using `docs/testing/TASK_6_TESTING.md`
2. Merge `feature/v2-task-6-bypass` into `jonas` (local `git merge --no-ff`)
3. Push `jonas` to `origin`
4. Create `feature/v2-task-7-scores-dashboard` from `jonas`

---

## Last Completed Task

**Task 4** — Subtask UI (recovered and merged 2026-05-18)
- Branch `feature/v2-task-4-subtask-ui` was complete but never merged into `jonas`
- Restored: ST badge on kanban cards, `SousTacheList.vue`, `SousTacheForm.vue`, `useSousTaches.js`, sous-taches tab in task detail modal + modal compact view
- Conflict resolution on merge: kept perm-arch (HEAD) for services/controllers, merged sous-tache permission exports into `useActivitePermissions.js`, kept both Guide 15 and Guide 16 in WORKING_GUIDELINES
- Now available on both `jonas` and `feature/v2-task-6-bypass`

**Task 6** — Anti-Sabotage Bypass
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
| `feature/v2-task-3-subtask-api` | Task 3 | Pending review |

---

## Pending Decisions / Open Questions

| # | Question | Context | Status |
|---|---|---|---|
| 1 | Frontend composable for SousTache permissions | `useTachePermissions.js` update deferred to Task 4 | Open |

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
