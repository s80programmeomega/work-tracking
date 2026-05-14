# Task 3 — Testing Guide: Subtask CRUD API + Automatic Progress

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- DB migrated and seeded: `php artisan migrate:fresh --seed`
- Authenticated as `directeur@worktracking.com` (password: `password`)

---

## Test Cases

### 1. List sous-tâches for a task

```
GET /api/taches/{tache_id}/sous-taches
Authorization: Bearer {token}
```

**Expected:** 200 with `data` array. Tasks seeded with sous-tâches return 3 items.

---

### 2. Create a sous-tâche

```
POST /api/taches/{tache_id}/sous-taches
{ "titre": "Test sous-tâche", "poids": 40 }
```

**Expected:** 201 with the created sous-tâche in `data`.

---

### 3. R2: Weights exceeding 100% → 422

```
POST /api/taches/{tache_id}/sous-taches  (after 60 + 30 already exist)
{ "titre": "Trop lourde", "poids": 20 }
```

**Expected:** 422 with the `sous_taches.errors.weights_sum_invalid` message.

---

### 4. Update a sous-tâche

```
PUT /api/sous-taches/{id}
{ "statut": "en_cours", "progression": 50 }
```

**Expected:** 200, parent task `taux_realisation` updates automatically.

---

### 5. Parent task auto-progress

Create 2 sous-tâches with poids 60/40. Complete the first (progression=100, statut=termine). Check parent task:

```php
// Tinker
$tache = App\Models\Tache::find({tache_id});
return $tache->taux_realisation; // should be 60
```

---

### 6. Parent task auto-set to `termine`

Complete all sous-tâches (statut=termine, progression=100). The parent task `statut` should auto-change to `termine` via the observer.

---

### 7. Parent task auto-set to `en_retard`

Set a sous-tâche statut to `en_retard`. Parent task should auto-change to `en_retard`.

---

### 8. Manual statut update blocked → 422

While sous-tâches exist, try updating the parent task statut directly:

```
PUT /api/taches/{id}
{ "statut": "termine" }
```

**Expected:** 422 with `sous_taches.errors.status_blocked` message.

---

### 9. Delete a sous-tâche

```
DELETE /api/sous-taches/{id}
```

**Expected:** 200. Record soft-deleted. Parent progress recalculates.

---

## Negative Cases

- Unauthorized user (not workspace member) on any endpoint → 403
- `date_echeance` exceeding parent task echeance → 422
- Weights > 100 on update → 422

---

## Cleanup

Nothing required — seeded data is fine for next task.
