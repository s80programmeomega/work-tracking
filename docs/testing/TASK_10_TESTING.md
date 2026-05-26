# Task 10 — Manual Testing Guide

> **Evaluation Dashboard + Workspace-Wide Task View**

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- Database seeded: `php artisan migrate:fresh --seed`
- At least one workspace with members of different roles (owner, manager, cadre, collaborateur)
- Optional: some `evaluation_scores` rows for meaningful scores in the dashboard

---

## TC-1: Evaluation Dashboard — Owner access

**Actor:** owner / super_admin  
**URL:** `/evaluations/tableau-de-bord`

1. Sign in as owner.
2. Navigate to the sidebar link "Tableau de bord évaluations" (under Évaluations).
3. **Expected:** Page loads with title "Tableau de bord évaluations".
4. **Expected:** Period filters (start date, end date) are visible and pre-filled with current month.
5. **Expected:** "Top performers" section renders (may be empty if no scores exist).
6. **Expected:** "Scores de l'équipe" section renders.
7. **Expected:** "Alertes" section renders with two sub-panels (Escalades abusives, Taux d'inaction).
8. Change the date range and click "Actualiser" → page re-fetches and updates.

---

## TC-2: Evaluation Dashboard — Manager scope isolation

**Actor:** manager  
**URL:** `/evaluations/tableau-de-bord`

1. Sign in as manager (project-level).
2. Navigate to `/evaluations/tableau-de-bord`.
3. **Expected:** Page loads (HTTP 200, sections visible).
4. **Expected:** Scores shown are limited to members of the manager's project — members from other projects in the same workspace are NOT listed.

---

## TC-3: Evaluation Dashboard — Cadre access

**Actor:** cadre  
**URL:** `/evaluations/tableau-de-bord`

1. Sign in as cadre.
2. Navigate to `/evaluations/tableau-de-bord`.
3. **Expected:** Page loads (HTTP 200).
4. **Expected:** Scores shown are limited to members of the cadre's activities.

---

## TC-4: Evaluation Dashboard — Collaborateur blocked

**Actor:** collaborateur / stagiaire  
**URL:** `/api/evaluations/tableau-de-bord`

1. Sign in as collaborateur.
2. Navigate to `/evaluations/tableau-de-bord` (via sidebar or direct URL).
3. **Expected:** Sidebar link is NOT visible (hidden by `canViewEvaluationDashboard`).
4. **Expected:** Direct API call `GET /api/evaluations/tableau-de-bord` → HTTP 403.

---

## TC-5: Workspace-Wide Task View — Owner access

**Actor:** owner / super_admin  
**URL:** `/workspace/taches`

1. Sign in as owner.
2. Navigate to the sidebar link "Toutes les tâches" (under the workspace section).
3. **Expected:** Page loads with title "Toutes les tâches".
4. **Expected:** Table lists all tasks across all projects in the workspace.
5. **Expected:** Total task count is displayed in the header.
6. Filter by "Statut" → table updates to show only tasks with that status.
7. Filter by "Projet" → table updates to show only tasks in that project.
8. Click "Effacer" → filters reset, all tasks shown.
9. Click a task row → navigates to the task detail page.
10. Pagination controls appear when more than 25 tasks exist.

---

## TC-6: Workspace-Wide Task View — Non-owner blocked

**Actor:** manager / cadre / collaborateur  
**URL:** `/workspace/taches`

1. Sign in as manager (or cadre, or collaborateur).
2. **Expected:** Sidebar link "Toutes les tâches" is NOT visible.
3. Direct API call `GET /api/workspace/taches` → HTTP 403.

---

## TC-7: Alerts — Abusive escalations

> Requires a tache_user row with `escalades_abusives = true`.

1. Sign in as owner.
2. Navigate to `/evaluations/tableau-de-bord`.
3. If any user has `escalades_abusives = true` on a task, the alert panel "Escalades abusives" shows their name + task title.
4. If none exist, panel shows "Aucune alerte active."

---

## TC-8: Alerts — High inaction rate

> Requires TacheResultat rows with `action_n0 = 'timeout'` representing ≥ 33% of results for a user.

1. Sign in as owner.
2. Navigate to `/evaluations/tableau-de-bord`.
3. If any user has ≥ 33% inaction rate, they appear in the "Taux d'inaction N0 élevé" panel with their rate displayed as a percentage.
4. If none exist, panel shows "Aucune alerte active."
