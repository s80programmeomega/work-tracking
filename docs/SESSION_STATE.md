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
**Session goal:** Review and merge Task 0, implement Task 1 (Queue + Reverb)
**Status:** Complete — Task 0 merged, Task 1 implemented, 25 tests passing, branch pushed, PR pending

---

## Current Task

**Task:** 2 — Subtask Data Model
**Branch:** `feature/v2-task-2-subtask-model` _(not created yet)_
**Status:** Not started

**What to do next:**
1. Merge PR `feature/v2-task-1-queue-reverb` into `jonas`
2. Manually test using `docs/testing/TASK_1_TESTING.md`
3. Create branch `feature/v2-task-2-subtask-model` from `jonas`
4. Follow Task 2 in `IMPLEMENTATION_PLAN.md`

---

## Last Completed Task

**Task 1** — Queue (database) + Laravel Reverb
- `QUEUE_CONNECTION=database` set in `.env` and `.env.example`
- `BROADCAST_DRIVER=reverb` set in `.env` and `.env.example`
- `laravel/reverb` installed via composer, `reverb:install` run (publishes `config/reverb.php`, sets REVERB_* env vars)
- `laravel-echo` + `pusher-js` installed as devDependencies
- `bootstrap.js` updated: `window.Pusher = Pusher` set (required by Reverb's Pusher protocol)
- `useEcho.js` composable created: singleton Echo instance, connected/disconnected state reactive ref
- 3 new tests in `QueueAndBroadcastTest.php`, all passing
- Full suite: 25 tests passing

---

## Open PRs

| Branch | Task | Status |
|---|---|---|
| `feature/v2-task-1-queue-reverb` | Task 1 | Pending review |

---

## Pending Decisions / Open Questions

| # | Question | Context | Status |
|---|---|---|---|
| — | — | — | — |

---

## Environment Reminder

- Project path: `/media/jonas/Jonas/Work-traking` (USB drive — always push before leaving)
- DB: MySQL, database `work-tracking`
- Queue: `database` ✅
- Broadcasting: `reverb` ✅ — run `php artisan reverb:start` to start the WebSocket server
- Run backend: `php artisan serve`
- Run frontend: `npm run dev`
- Run WebSocket server: `php artisan reverb:start`
- Run tests: `php artisan test --compact`

---

## Session Log

| Date | Tasks worked on | Outcome |
|---|---|---|
| 2026-05-11 | Planning | Created IMPLEMENTATION_PLAN.md, WORKING_GUIDELINES.md, PROGRESSION.md, SESSION_STATE.md. Updated ONBOARDING.md. Ready to start Task 1. |
| 2026-05-13 | Task 0 bug fixes | Fixed Pinia readonly conflict, UserResource missing current_workspace_id, hasAccess super_admin bypass, sidebar projet_count fallback, workspace/project card cursor, navigateToWorkspace now calls selectWorkspace. Added 6 factories, expanded seeder to 2 workspaces + 45 tasks. Refactored ProjetService to use scopeInWorkspace. All tests passing. |
| 2026-05-14 | Task 0 review + merge, Task 1 | Reviewed Task 0: 22 tests passing, all abort_unless replaced, one intentional abort_unless in DocumentController (pre-upload polymorphic check — no policy applicable). Fixed PROGRESSION.md doc discrepancy (SousTachePolicy not created yet). Merged Task 0 into jonas. Implemented Task 1: queue=database, Reverb installed, laravel-echo+pusher-js, useEcho.js composable, 3 new tests. 25 tests passing. |
