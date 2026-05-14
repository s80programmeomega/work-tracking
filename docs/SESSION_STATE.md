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

**Date:** 2026-05-14
**Session goal:** Task 0 review + merge, Task 1, Task 2
**Status:** Complete — Tasks 0, 1, 2 done. 28 tests passing. Branches pushed, PRs pending.

---

## Current Task

**Task:** 5 — Validation N0 + 48h Timer
**Branch:** `feature/v2-task-5-validation-n0` _(not created yet)_
**Status:** Not started

**What to do next:**
1. Manually test Task 4 using `docs/testing/TASK_4_TESTING.md`
2. Open PR `feature/v2-task-4-subtask-ui` into `jonas`
3. Create branch `feature/v2-task-5-validation-n0` from `jonas`
4. Follow Task 5 in `IMPLEMENTATION_PLAN.md`

---

## Last Completed Task

**Task 4** — Subtask UI
- `useSousTaches.js`: self-contained CRUD composable (owns data, no broken await-emit)
- `SousTacheForm.vue`: quick-create form with poids remaining, date max, validation flags
- `SousTacheList.vue`: ordered list, inline edit, quick-complete toggle, weighted progress bar, badges
- `TacheDetail.vue`: Sous-tâches tab (second position)
- `TacheDetailModal.vue`: Sous-tâches collapsible in compact view + tab in detailed view; external link icon to full page
- `TacheCard.vue`: "N ST" badge + mini progress bar
- `useActivitePermissions.js`: `canCreateSousTache`, `canAssignSousTacheIntervenant`
- `TacheResource`: `can_update`, `can_delete`, `can_create_subtask` added to permissions block
- Bug fix: stale `'responsable'`/`'collaborator'`/`'assignee'` role values in 6 files (post-Task-0 ENUM)
- Bug fix: `can_create_subtask` added to `TacheController::show()` permissions payload
- Submit result button disable (blocking subtasks) deferred to Task 5
- 41 tests passing

---

## Previously Completed

**Task 3** — Subtask CRUD API + Automatic Progress
- `SousTacheService`, `SousTacheController` (5 endpoints), `SousTacheObserver`
- Weighted progress auto-recalculation on parent task
- Parent statut auto-change: all done → termine, any en_retard → en_retard
- Manual statut block when sous-taches exist (422)
- `canAssignSousTacheIntervenant` permission added
- 3 notifications, MySQL ENUM extended, 10 new tests, 41 total

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
| `feature/v2-task-4-subtask-ui` | Task 4 | Pending review — includes role ENUM bug fixes |

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
