# Task 2 — Testing Guide: Subtask Data Model

## Prerequisites

- App running: `php artisan serve` + `npm run dev`
- DB migrated and seeded: `php artisan migrate:fresh --seed`

---

## Test Cases

### 1. sous_taches table exists with correct schema

```bash
php artisan tinker
```
```php
DB::select("DESCRIBE sous_taches");
```

**Expected:** Columns — `id`, `tache_id`, `responsable_id`, `titre`, `description`, `statut`, `progression`, `poids`, `date_echeance`, `validation_n0_required`, `validation_n1_required`, `validation_n2_required`, `ordre`, `created_at`, `updated_at`, `deleted_at`.

---

### 2. parent_tache_id removed from taches

```php
DB::select("DESCRIBE taches");
```

**Expected:** No `parent_tache_id` column in the result.

---

### 3. Create a sous-tache via Tinker

```php
$tache = App\Models\Tache::first();

$st = App\Models\SousTache::create([
    'tache_id' => $tache->id,
    'titre'    => 'Test sous-tâche',
    'poids'    => 40,
]);

return $st->id;
```

**Expected:** Returns a valid integer ID.

---

### 4. R2 weight violation throws exception

```php
$tache = App\Models\Tache::first();

// Create two sous-tâches with 60 + 30 = 90
App\Models\SousTache::create(['tache_id' => $tache->id, 'titre' => 'A', 'poids' => 60]);
App\Models\SousTache::create(['tache_id' => $tache->id, 'titre' => 'B', 'poids' => 30]);

// This should throw: 90 + 20 = 110 > 100
App\Models\SousTache::enforceWeights($tache->id, 20);
```

**Expected:** `InvalidArgumentException` with message from `sous_taches.errors.weights_sum_invalid`.

---

### 5. EN_RETARD and A_REFAIRE statuses exist

```php
return App\Enums\TacheStatut::EN_RETARD->value;   // 'en_retard'
return App\Enums\TacheStatut::A_REFAIRE->value;   // 'a_refaire'
```

**Expected:** Both return their string values without error.

---

### 6. SousTache relationship on Tache works

```php
$tache = App\Models\Tache::first();
return $tache->sousTaches()->count();
```

**Expected:** Integer (0 or more). No exception thrown.

---

## Negative Cases

- Creating a SousTache with `tache_id` pointing to a non-existent tache → DB foreign key error.
- Weights summing to > 100 → `InvalidArgumentException` (not silently ignored).
- A `collaborateur` without `is_responsable = true` on `tache_user` → `canCreateSousTache()` returns `false`.

---

## Cleanup

Nothing to reset — data stays for Task 3 testing.
