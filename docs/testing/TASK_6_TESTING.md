# Task 6 — Anti-Sabotage Bypass — Testing Guide

## Prerequisites

- `php artisan serve` and `npm run dev` running
- DB seeded: `php artisan migrate:fresh --seed`
- At least one workspace with a project, activity, and task set up
- Task has two assignees: one `is_responsable = true` (N0), one `is_responsable = false` (intervenant)
- Activity has a `responsable_id` set (this user acts as N1 validator)
- The intervenant has already submitted a result that was returned by N0 (result `statut = a_refaire`)

---

## Test Cases

### 1. Bypass button visibility

**Action:** Log in as the intervenant, open a task whose result has `statut = a_refaire`.

**Expected:** A "Submit directly to N1" button is visible on the result. The button is NOT visible if:
- The result `statut` is anything other than `a_refaire`
- The user is not the result's author
- `bypass_active` is already `true`

---

### 2. Activate bypass — happy path

**Action:** With `statut = a_refaire`, click "Submit directly to N1", enter a motif of at least 50 characters, confirm.

**Expected:**
- Result `statut` changes to `en_validation_n1`
- The bypass badge/indicator appears on the result
- The N1 validator (activity responsable) receives a notification (in-app + email)
- The email includes: result body, N0 comment, bypass motif
- An audit log entry appears with `action = bypass`

---

### 3. R5 — Motif too short (< 50 chars)

**Action:** Enter a motif shorter than 50 characters and submit.

**Expected:** HTTP 422. Error message: "Le motif du bypass doit contenir au moins 50 caractères."

---

### 4. R3 — Second bypass attempt (same submission)

**Action:** After bypass has been activated on a result, try to activate it again via the API:
```
POST /api/taches/{id}/resultats/{id}/activer-bypass
```

**Expected:** HTTP 409. Error message: "Le bypass a déjà été utilisé pour cette soumission."

**Verify:** The "Submit directly to N1" button is no longer visible in the UI after first use.

---

### 5. 403 — Non-author tries to activate bypass

**Action:** Log in as any user who is NOT the result's author, call `POST .../activer-bypass`.

**Expected:** HTTP 403.

---

### 6. 422 — Bypass on wrong statut

**Action:** Try to activate bypass on a result that is still `en_verification_n0` (not yet returned).

**Expected:** HTTP 422 — "Le résultat n'est pas dans le bon état pour cette action."

---

### 7. N1 context panel

**Action:** Log in as the N1 validator (activity responsable), open a task result that has bypass active.

**Expected:** A context panel is visible showing:
- The result content (resultats_obtenus, taux_realisation)
- The N0 comment (from `commentaire_n0`)
- The bypass motif (from `motif_bypass`)
- The full audit log timeline (submitted → returned → bypass)

**How to verify:** Check `GET /api/tache-resultats/{id}` response — it includes `bypass.active`, `bypass.motif`, `validation_n0.commentaire`, and `audit_logs` array.

---

### 8. escalades_abusives flag — 3 consecutive invalid bypasses

**Action:** Simulate 3 rounds where the intervenant activates bypass and N1 confirms N0 was right (calls `invaliderBypassN1` on the service, or via a future N1 decision endpoint):

```bash
php artisan tinker
$service = app(\App\Services\TacheResultatService::class);
$resultat = \App\Models\TacheResultat::find(X); // result in a_refaire
$n1 = \App\Models\User::find(Y);
$service->invaliderBypassN1($resultat, $n1);
```

After 3 calls (on 3 different results for the same user/task):

**Expected:**
- `tache_user.bypass_count` = 3 for that user on that task
- `tache_user.escalades_abusives` = true
- `EscaladesAbusivesNotification` sent to task `is_responsable` and workspace manager (in-app + email)
- Audit log entry with `action = bypass_invalide` for each round

---

## Negative Cases

| Scenario | Expected |
|---|---|
| Bypass motif = 49 chars | 422 |
| Bypass motif = 50 chars | 200 ✅ |
| Bypass already used + correct motif | 409 |
| User not logged in | 401 |
| Wrong task ID in URL | 404 |
| Result belongs to different task | 404 |

---

## Cleanup (optional)

To reset test data after manual testing:
```bash
php artisan migrate:fresh --seed
```
