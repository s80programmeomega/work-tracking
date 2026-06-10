# Phase 11A — Dashboard & Statistics Accuracy — Manual Testing Guide

Real period-over-period stat changes, Kanban project-status alignment, and admin overdue-count
accuracy. See [`docs/phase11-dashboard-profile/PLAN.md`](../phase11-dashboard-profile/PLAN.md)
(Part A) for the design.

---

## Prerequisites

- `php artisan serve` running, `npm run dev` (or a fresh `npm run build`)
- At least one user account that owns a workspace with some projects/activités/tâches
- A super-admin account for `/admin` checks
- The dashboard endpoint is cached ~60s and `/admin/stats` ~5min — run
  `php artisan cache:clear` between manual checks if you need fresh numbers immediately

---

## TC-1 — Stat cards show real (non-hardcoded) change percentages

**Steps:**
1. Log in, go to `/dashboard`
2. Inspect the "Projets actifs", "Taux de complétion", and "Tâches en retard" stat cards

**Expected:**
- The `change` percentage shown is no longer always `+12%` / `+5%` / `-2%` — it varies based on
  actual data (will show `+100%`/`0%`/etc. depending on your data; on a workspace with no
  month-old data it may show `+100%` or `0%`, which is correct given the "previous period had
  zero" case)

---

## TC-2 — "Projets actifs" change reflects projects created in the last month

**Steps:**
1. Note the current "Projets actifs" value and change %
2. Create a new active project
3. `php artisan cache:clear` (or wait ~60s), reload `/dashboard`

**Expected:**
- "Projets actifs" value increases by 1
- The `change` % increases accordingly (project created "now" counts in the current snapshot but
  not in the "1 month ago" snapshot)

---

## TC-3 — "Tâches en retard" change reflects newly-overdue tasks

**Steps:**
1. Create a task with `échéance` = yesterday, status `à faire` (not terminé)
2. `php artisan cache:clear`, reload `/dashboard`

**Expected:**
- "Tâches en retard" value includes this task
- `change` % reflects this task becoming overdue within the last month (it wasn't overdue 1
  month ago since its échéance is only 1 day in the past)

---

## TC-4 — Kanban columns show Active + Completed projects (ProjetStatus-aligned)

**Steps:**
1. Ensure you have at least one project with `status = active`, one `status = completed`, and one
   `status = archived`
2. Go to `/dashboard`, scroll to the Kanban board

**Expected:**
- Two columns: "Actif" and "Terminé" (i18n: `common.active` / `common.completed`)
- The `active` project appears in the "Actif" column
- The `completed` project appears in the "Terminé" column
- The `archived` project does **not** appear in either column (archived projects are excluded
  from "recent activity")
- No empty "En attente" column (the old hardcoded `pending` column, which never matched any real
  `ProjetStatus` value, is removed)

---

## TC-5 — No debug console output on dashboard load

**Steps:**
1. Open browser dev tools console
2. Go to `/dashboard`, let it load fully

**Expected:**
- No `"Dashboard"` / `"Dashboard value"` log lines appear (previously logged the entire
  `dashboardData` ref on every load)

---

## TC-6 — Admin "overdue" stat uses real overdue semantics

**Steps:**
1. As super-admin, go to `/admin` (platform dashboard / stats)
2. Create (or have seeded) a task with status `à faire` and `échéance` in the past, but whose
   stored `statut` is **not** `en_retard`
3. `php artisan cache:clear`, reload `/admin`

**Expected:**
- The "Tâches en retard" admin stat counts this task too (previously only tasks with the literal
  stored status `en_retard` were counted, undercounting tasks that are overdue but never had their
  status manually flagged)

---

## Automated Tests

| Suite | File | Coverage |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Dashboard/DashboardStatsTest.php` | Real `change`/`trend` for `projets_actifs`/`taux_completion`/`taches_en_retard`; `recent_projects` includes `active` + `completed`, excludes `archived` |
| PHPUnit Feature | `tests/Feature/Admin/PlatformDashboardTest.php::test_stats_overdue_uses_isoverdue_semantics_not_just_en_retard_status` | Admin `tasks.overdue` counts tasks overdue per `isOverdue()`/`scopeOverdue()`, not just `statut = en_retard` |

Run with:
```bash
php artisan test --compact tests/Feature/Dashboard/DashboardStatsTest.php tests/Feature/Admin/PlatformDashboardTest.php
```

---

## Cleanup

```bash
php artisan cache:clear
```

Remove any manually-created test projects/tasks used for TC-2/TC-3/TC-6.
