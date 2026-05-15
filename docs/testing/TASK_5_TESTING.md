# Task 5 — Testing Guide: N0 Validation Circuit + 48h Timer

## Prerequisites

- App running: `php artisan serve` + `php artisan queue:work`
- DB migrated and seeded: `php artisan migrate:fresh --seed`
- Authenticated as `cadre@worktracking.com` (password: `password`) — acts as N0 responsable
- A task where cadre is the `is_responsable` assignee
- A collaborateur (`collaborateur@worktracking.com`) who will submit the result

---

## Test Cases

### 1. Submit result → statut becomes `en_verification_n0`

As collaborateur, submit a result via the UI or:

```
POST /api/taches/{tache_id}/resultats
{ "resultats_attendus": "...", "resultats_obtenus": "...", "taux_realisation": 75 }
```

Then call:
```
POST /api/taches/{tache_id}/resultats/{resultat_id}/submit
```

**Expected:** `statut = en_verification_n0`, `soumis_n0_le` set, cadre receives in-app + email notification.

---

### 2. 48h timeout job dispatched

Check the `jobs` table after submit:

```sql
SELECT * FROM jobs WHERE payload LIKE '%TransmettreResultatAuN1Job%';
```

**Expected:** One pending job with the correct delay (~48h from now).

---

### 3. N0 approves → statut becomes `en_validation_n1`

As cadre (is_responsable):

```
POST /api/taches/{tache_id}/resultats/{resultat_id}/approuver-n0
```

**Expected:** 200, `statut = en_validation_n1`, `action_n0 = approuve`, audit log row inserted, collaborateur receives in-app notification.

---

### 4. N0 returns with too-short comment → 422

```
POST /api/taches/{tache_id}/resultats/{resultat_id}/renvoyer-n0
{ "commentaire": "Trop court" }
```

**Expected:** 422 with `circuit_validation.errors.comment_too_short` message. Statut unchanged.

---

### 5. N0 returns with valid comment → statut `a_refaire`

```
POST /api/taches/{tache_id}/resultats/{resultat_id}/renvoyer-n0
{ "commentaire": "Le résultat ne couvre pas les points demandés dans les indicateurs. Veuillez détailler les actions menées." }
```

**Expected:** 200, `statut = a_refaire`, `action_n0 = renvoye`, collaborateur receives email (Blade template with the N0 comment).

---

### 6. Timeout job skipped when N0 already acted

Manually run a job that was created before N0 acted on a result where `action_n0` is already set:

```php
// Tinker
$resultat = App\Models\TacheResultat::find(X);
$resultat->update(['action_n0' => 'approuve', 'statut' => 'en_validation_n1']);
app(App\Services\TacheResultatService::class)->transmettreAuN1($resultat);
// Check: statut stays en_validation_n1, no new audit log with action=timeout
```

**Expected:** No state change, `Log::info('TransmettreAuN1 ignoré...')` visible in logs.

---

### 7. Unauthorized user gets 403

As collaborateur (not is_responsable), call:

```
POST /api/taches/{tache_id}/resultats/{resultat_id}/approuver-n0
```

**Expected:** 403 with `circuit_validation.errors.unauthorized`.

---

### 8. Audit log is immutable

After any N0 action:

```sql
SELECT * FROM validation_audit_logs;
```

**Expected:** Each row has `id`, `tache_resultat_id`, `actor_id`, `action`, `context` (JSON), `created_at`. No `updated_at` column.

---

## Negative Cases

- Non-authenticated request → 401
- `renvoyer-n0` on a result not in `en_verification_n0` statut → 422
- `approuver-n0` on a result already approved → 422

---

## Cleanup

Nothing required — seeded data is fine for Task 6.
