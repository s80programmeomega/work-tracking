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
**Session goal:** Tasks 0 review + merge, Task 1, Task 2
**Status:** Complete — Tasks 0, 1, 2 done. 28 tests passing. Branches pushed, PRs pending.

---

## Current Task

**Task:** 3 — Subtask CRUD API + Automatic Progress
**Branch:** `feature/v2-task-3-subtask-api` _(not created yet)_
**Status:** Not started

**What to do next:**
1. Merge PRs for Task 1 and Task 2 into `jonas`
2. Manually test Task 1 using `docs/testing/TASK_1_TESTING.md`
3. Manually test Task 2 using `docs/testing/TASK_2_TESTING.md`
4. Create branch `feature/v2-task-3-subtask-api` from `jonas`
5. Follow Task 3 in `IMPLEMENTATION_PLAN.md`

---

## Last Completed Task

**Task 2** — Subtask Data Model
- 3 migrations: `create_sous_taches_table`, `create_sous_tache_user_table`, `drop_parent_tache_id_from_taches`
- `EN_RETARD` and `A_REFAIRE` added to `TacheStatut` enum
- `SousTache` model with R2 `enforceWeights()`, `LogsActivity`, `SoftDeletes`
- `SousTachePolicy` registered in `AuthServiceProvider`
- `SousTacheResource`, `SousTacheFactory`, `SousTacheSeeder` created
- `PermissionService`: 4 new methods for SousTache CRUD
- `RolePermissionSeeder`: 4 new permissions, wired into all contextual roles
- Translation files: `lang/fr/sous_taches.php` + `lang/en/sous_taches.php`
- Bug fix: `soustaches` → `sousTaches` in ProjetController (2 occurrences)
- Bug fix: `is_responsable` added to `assignees()` withPivot in Tache model
- WorkspaceSeeder updated: first task of each activity gets 3 weighted sous-tâches (40/35/25)
- SousTacheSeeder registered in DatabaseSeeder
- 6 new tests, 28 total, all passing

---

## Open PRs

| Branch | Task | Status |
|---|---|---|
| `feature/v2-task-1-queue-reverb` | Task 1 | Pending review |
| `feature/v2-task-2-subtask-model` | Task 2 | Pending review |

---

## Pending Decisions / Open Questions

| # | Question | Context | Status |
|---|---|---|---|
| 1 | `useTachePermissions.js` SousTache permissions | Deferred from Task 2 to Task 4 | Open |

---

## Environment Reminder

- Project path: `/media/jonas/Jonas/Work-traking` (USB drive — always push before leaving)
- DB: MySQL, database `work-tracking`
- Queue: `database` ✅
- Broadcasting: `reverb` ✅ — run `php artisan reverb:start`
- Remotes: `origin` = your repo (`s80programmeomega`), `client` = client repo (frozen until paid)
- Run backend: `php artisan serve`
- Run frontend: `npm run dev`
- Run tests: `php artisan test --compact`

---

## Session Log

| Date | Tasks worked on | Outcome |
|---|---|---|
| 2026-05-11 | Planning | Created IMPLEMENTATION_PLAN.md, WORKING_GUIDELINES.md, PROGRESSION.md, SESSION_STATE.md. |
| 2026-05-13 | Task 0 bug fixes | Fixed Pinia readonly, UserResource, hasAccess bypass, sidebar fallbacks. Added 6 factories, expanded seeder. |
| 2026-05-14 | Tasks 0 review + merge, 1, 2 | Task 0 reviewed and merged. Task 1: queue=database, Reverb, useEcho.js. Task 2: sous_taches table, SousTache model/policy/resource, R1/R2 rules, permissions, translations, seeder. 28 tests passing. |
