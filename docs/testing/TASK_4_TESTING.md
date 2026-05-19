# Task 4 — Testing Guide: Subtask UI in Task Detail View

> **Note:** Task 4 is not yet implemented. This guide documents what must be verified once the UI is built.
> Update the "Not started" marker in PROGRESSION.md and tick deliverables when complete.

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- DB migrated and seeded: `php artisan migrate:fresh --seed`
- At least one task exists with a few subtasks (use the API or seeders to create them)
- At least one subtask assigned to a user

---

## Test Cases

### 1. Subtask list visible in task detail

**Action:** Open a task that has subtasks.

**Expected:**
- A "Sous-tâches" section is visible in the task detail page
- Each subtask shows: titre, responsable, due date, progression, statut
- A global progress bar reflects the weighted average of all subtask progressions
- Subtasks past their `date_echeance` show an overdue indicator (red)

---

### 2. Create a subtask (authorised user)

**Action:** Log in as a `cadre` or `manager`, open a task, click "Add sous-tâche".

**Expected:**
- `SousTacheForm` opens with fields: titre, responsable (from activity members), date_echeance, poids (weight), validation options
- After saving, the new subtask appears in the list
- The global progress bar updates

**Negative:** An `observateur` should NOT see the create button.

---

### 3. R2 — Weight validation (sum must equal 100%)

**Action:** Create two subtasks with weights 40 and 40 (sum = 80).

**Expected:** Frontend validation prevents save with message indicating weights must sum to 100%.

**Action:** Set weights to 60 and 40.

**Expected:** Save succeeds.

---

### 4. Date validation (date ≤ parent task echeance)

**Action:** Set a subtask `date_echeance` to a date AFTER the parent task's `echeance`.

**Expected:** Frontend validation error: "La date ne peut pas dépasser l'échéance de la tâche parente."

---

### 5. Drag & drop reordering

**Action:** Drag a subtask to a different position in the list.

**Expected:**
- The order updates visually
- `ordre` field is persisted after the drag (verify via API: `GET /api/taches/{id}/sous-taches`)

---

### 6. Kanban card indicator

**Action:** View the Kanban board for an activity.

**Expected:**
- Each task card with subtasks shows a badge "X/Y ST" (e.g. "2/3 ST")
- A mini progress bar is visible under the badge
- Clicking the badge expands an inline subtask summary

---

### 7. "Submit result" button disabled when blocking subtasks exist

**Action:** Open a task where one or more subtasks are still in `a_faire` or `en_cours` statut and are set as blocking.

**Expected:**
- The "Submit result" / "Soumettre résultat" button is disabled
- A tooltip lists the blocking subtasks by name

**Action:** Mark all blocking subtasks as `termine`.

**Expected:** The submit button becomes enabled.

---

### 8. Permissions — edit/delete buttons conditional

**Action:** Log in as a `collaborateur` (not the subtask's responsable), view the subtask.

**Expected:** Edit and Delete buttons are hidden.

**Action:** Log in as the subtask's `responsable` or a `cadre`.

**Expected:** Edit and Delete buttons are visible.

---

## Negative Cases

| Scenario | Expected |
|---|---|
| `observateur` opens task with subtasks | List visible, no create/edit/delete buttons |
| Submit result with blocking subtask active | Button disabled, tooltip explains why |
| Subtask date > parent task date | Frontend validation error |
| Weights sum ≠ 100% | Frontend validation error |

---

## Cleanup

```bash
php artisan migrate:fresh --seed
```
