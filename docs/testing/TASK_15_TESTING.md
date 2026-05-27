# Task 15 — Manual Testing Guide

Task List UX: table view as default, assignee filter, "Voir toutes les tâches" shortcut, inline editing.

---

## Prerequisites

- A workspace with at least one project, one activity, and several tasks
- Tasks assigned to at least two different users
- Logged in as a user with edit permission on the tasks

---

## TC-1 — Task list opens in table view by default (A.12)

**Steps:**
1. Navigate to `/taches`

**Expected:**
- Page loads with the **Tableau** toggle active (highlighted)
- Tasks are displayed in a table (columns: Titre, Statut, Priorité, Échéance, Assigné à, Avancement)
- Not kanban columns

---

## TC-2 — Assignee filter defaults to "Mes tâches" (A.11)

**Steps:**
1. Navigate to `/taches` with an activity selected that has tasks for multiple users

**Expected:**
- Filter dropdown shows "Mes tâches" selected by default
- Only tasks where the current user is an assignee or responsable are shown

---

## TC-3 — Switching filter to "Toutes les tâches" shows all

**Steps:**
1. On `/taches`, change the filter dropdown to "Toutes les tâches"

**Expected:**
- All tasks for the selected activity are shown (not filtered by user)

---

## TC-4 — View toggle switches between Tableau / Kanban / Liste

**Steps:**
1. Click the **Kanban** toggle button
2. Click the **Liste** toggle button
3. Click the **Tableau** toggle button

**Expected:**
- Each click switches the content area to the corresponding view
- Active toggle button is highlighted

---

## TC-5 — "Voir toutes les tâches" shortcut from ActiviteDetail (A.10)

**Steps:**
1. Navigate to an activity detail page (`/activites/{id}`)
2. Click **"Voir toutes les tâches"** in the header

**Expected:**
- Browser navigates to `/taches?activite={id}`
- The activity is pre-selected in the activity dropdown
- Tasks for that activity are shown immediately (no extra selection needed)

---

## TC-6 — Inline edit: statut (A.13)

**Steps:**
1. On `/taches` in table view, find a task you can edit
2. Click on the statut badge (e.g. "À faire")

**Expected:**
- A `<select>` dropdown appears in place of the badge
- Select "En cours" and click away (or change value)
- Badge updates to "En cours" without page reload
- `PATCH /api/taches/{id}` called with `{ statut: "en_cours" }`
- Database updated

---

## TC-7 — Inline edit: priorité (A.13)

**Steps:**
1. Click on the priorité badge of an editable task (e.g. "Moyenne")
2. Select "Critique" from the dropdown

**Expected:**
- Badge updates to "Critique" with red styling
- `PATCH /api/taches/{id}` called with `{ priorite: "critique" }`

---

## TC-8 — Inline edit: échéance (A.13)

**Steps:**
1. Click on the échéance date of an editable task
2. Select a new date in the date picker
3. Click away

**Expected:**
- Date updates in the table row
- `PATCH /api/taches/{id}` called with `{ echeance: "YYYY-MM-DD" }`

---

## TC-9 — Inline edit not available without edit permission

**Steps:**
1. Log in as a user who is not a responsable/manager/owner (e.g. a pure observer)
2. Navigate to `/taches` in table view
3. Click on a statut badge

**Expected:**
- No dropdown appears (click has no effect)
- `tache.permissions.can_edit` is false for this user

---

## TC-10 — PATCH blocked server-side for unauthorized user

**Steps:**
1. As an unauthorized user, send `PATCH /api/taches/{id}` with `{ statut: "termine" }`

**Expected:**
- Response 403 Forbidden

---

## Automated Tests

| Suite | File | Count |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Task15/TaskListUxTest.php` | 5 |

Run with:
```bash
php artisan test --compact tests/Feature/Task15/
```
