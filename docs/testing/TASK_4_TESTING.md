# Task 4 — Testing Guide: Subtask UI

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- DB migrated and seeded: `php artisan migrate:fresh --seed`
- Authenticated as `cadre@worktracking.com` (password: `password`) — has full sous-tâche permissions
- Tasks with sous-tâches seeded: tache IDs 1, 2, 4, 10, 11 (each has 3 sous-tâches)

---

## How to access sous-tâches

There are two entry points:

**A — Via the kanban modal (primary):**
1. Go to `/taches` → select an activité
2. Click any task card → modal opens
3. Scroll down in the modal to the **"Sous-tâches"** collapsible section (open by default)

**B — Via the full detail page:**
1. In the modal, click the **↗ icon** (top-right of modal header) → opens `/taches/{id}`
2. Click the **"Sous-tâches"** tab (second tab, between Détails and Assignés)

---

## Test Cases

### 1. Sous-tâches visible in modal

Open tache 1 ("Rédiger le cahier des charges fonctionnel") from the kanban:
- A "Sous-tâches" collapsible section appears in the modal body.
- 3 sous-tâches listed: "Interviews parties prenantes", "Rédaction du document", "Validation et signature".
- Each shows statut badge, poids, and deadline.

---

### 2. Sous-tâches tab on full detail page

Click ↗ in the modal header to open `/taches/1`:
- "Sous-tâches" tab is second in the tab bar.
- Clicking it shows the same 3 sous-tâches.

---

### 3. Weighted progress bar

On a task with poids > 0 on its sous-tâches:
- "Progression pondérée" bar appears above the list.
- Percentage = `Σ(poids_i × progression_i / 100)`.
- "Poids total alloué: X% / 100%" shown below.

---

### 4. Quick-complete toggle

Click the circle button on a sous-tâche with `statut ≠ termine`:
- Circle turns green with a checkmark.
- Statut badge updates to "Terminé".

Click it again:
- Reverts to "En cours".

---

### 5. Inline edit (statut + progression)

Click `⋮` → "Modifier" on any sous-tâche:
- Inline form appears below that item.
- Change statut to `en_cours`, progression to `50`.
- Click "Enregistrer" → changes saved, weighted progress bar updates.

---

### 6. Create a new sous-tâche

Click "Ajouter" button (top-right of the sous-tâches section):
- `SousTacheForm` appears.
- Fill: titre = "Test ST", poids = 5, date within parent echeance.
- Submit → sous-tâche appears in list; form resets.

---

### 7. R2: weights > 100% → 422

On tache 1 (already has 40+35+25 = 100% poids), try creating with poids = 10:
- Expected: error `sous_taches.errors.weights_sum_invalid` shown in form.

---

### 8. Date exceeds parent echeance → 422

Create a sous-tâche with `date_echeance` past the parent task echeance:
- Expected: error `sous_taches.errors.date_exceeds_parent`.
- Browser date picker also enforces the `max` attribute.

---

### 9. Delete a sous-tâche

Click `⋮` → "Supprimer":
- Confirmation dialog appears.
- On confirm: item removed from list, parent progress recalculates.

---

### 10. Kanban card indicator

On the kanban board:
- Task cards with sous-tâches show a badge like "3 ST".
- A mini progress bar below the badge reflects `taux_realisation`.

---

### 11. External link navigation

In the modal, click the ↗ icon (top-right, beside the expand button):
- Modal closes.
- Browser navigates to `/taches/{id}` (full detail page).

---

## Negative Cases

- Unauthenticated `POST /api/taches/{id}/sous-taches` → 401
- User with no activité membership → 403
- Empty titre submitted → client-side error, no request sent
- Login as `stagiaire@worktracking.com`: "Ajouter" button should not appear (stagiaire cannot create)

---

## Cleanup

Nothing required — seeded data is fine for the next task.
