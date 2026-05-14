# Task 4 — Testing Guide: Subtask UI

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- DB migrated and seeded: `php artisan migrate:fresh --seed`
- Authenticated as `directeur@worktracking.com` (password: `password`)
- Open a task that has sous-tâches seeded (check Tinker: `App\Models\Tache::has('sousTaches')->first()->id`)

---

## Test Cases

### 1. Sous-tâches tab visible on task detail

Navigate to `/taches/{id}`. Verify:
- A "Sous-tâches" tab appears between "Détails" and "Assignés".
- Clicking it shows the `SousTacheList` component.
- Seeded sous-tâches appear with their titre, statut badge, poids, and deadline.

---

### 2. Weighted progress bar

On a task with multiple sous-tâches with poids > 0:
- The "Progression pondérée" bar should appear above the list.
- The percentage should match `Σ(poids_i × progression_i / 100)`.
- "Poids total alloué: X% / 100%" should reflect the sum.

---

### 3. Quick-complete toggle

Click the circle button on a sous-tâche with `statut = 'a_faire'`:
- Button turns green with a checkmark.
- Statut badge changes to "Terminé".
- Parent task `taux_realisation` updates (check Détails tab).

Click again on a "Terminé" sous-tâche:
- Statut reverts to "En cours".

---

### 4. Inline edit (statut + progression)

Click the `⋮` menu on any sous-tâche → "Modifier":
- An inline edit form appears below that item.
- Change statut to `en_cours` and progression to `75`.
- Click "Enregistrer" → changes persist, parent task progress updates.

---

### 5. Create a new sous-tâche

On the Sous-tâches tab, click "Ajouter":
- `SousTacheForm` appears.
- Fill: titre = "Test ST", poids = 20, date within parent echeance.
- Submit → sous-tâche appears in list; form resets.

---

### 6. R2 enforced on create: weights > 100% → 422

On a task that already has 80% poids allocated, try creating a sous-tâche with poids = 30:
- Expected: error message appears in the form (`sous_taches.errors.weights_sum_invalid`).

---

### 7. Date exceeds parent echeance → 422

Try creating a sous-tâche with `date_echeance` past the parent task's echeance:
- Expected: error message (`sous_taches.errors.date_exceeds_parent`).
- The date input should also enforce `max` attribute in the browser.

---

### 8. Delete a sous-tâche

Click `⋮` → "Supprimer" on any sous-tâche:
- Confirmation dialog appears.
- On confirm: item removed from list, parent progress recalculates.

---

### 9. Kanban card indicator

Navigate to the Kanban board for an activité whose tasks have sous-tâches:
- Task cards with `sous_taches_count > 0` show a badge like "3 ST".
- A mini progress bar reflects the task's `taux_realisation`.

---

### 10. Observateur cannot create

Log in as a user with `observateur` role on the activité:
- Navigate to a task in that activité.
- "Ajouter" button should not appear on the Sous-tâches tab.

---

## Negative Cases

- Unauthenticated request to `POST /api/taches/{id}/sous-taches` → 401
- User with no activité membership → 403 on any sous-tâche endpoint
- Empty titre on form → client-side validation error (no request sent)

---

## Cleanup

Nothing required — seeded data is fine for the next task.
