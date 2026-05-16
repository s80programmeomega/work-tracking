# Role & Permission System — Testing Guide

> This guide covers how to manually verify the DB-driven contextual permission system.
> For the architecture overview see `ROLES_AND_PERMISSIONS.md`.
> For the implementation plan see `ROLES_AND_PERMISSIONS_PLAN.md`.

---

## Seeded Test Users

Run `php artisan db:seed --class=RolePermissionSeeder` to get these users (password: `password`):

| Email | Global Role | Contextual Role in WS 1 |
|---|---|---|
| `superadmin@worktracking.com` | `super_admin` | bypasses all checks |
| `directeur@worktracking.com` | `directeur` | `owner` (workspace creator) |
| `manager@worktracking.com` | `utilisateur` | `manager` |
| `cadre@worktracking.com` | `utilisateur` | `cadre` |
| `collaborateur@worktracking.com` | `utilisateur` | `collaborateur` |
| `stagiaire@worktracking.com` | `utilisateur` | `stagiaire` |
| `observateur@worktracking.com` | `utilisateur` | `observateur` |
| `utilisateur@worktracking.com` | `utilisateur` | no workspace membership |

---

## Layer 1 — PHPUnit (fast, run always)

```bash
# Full suite
php artisan test --compact

# Just permission tests
php artisan test --compact tests/Unit/PermissionServiceTest.php
php artisan test --compact tests/Feature/PermissionsMatrixTest.php
php artisan test --compact tests/Feature/SousTacheModelTest.php
php artisan test --compact tests/Feature/N0ValidationCircuitTest.php
```

**What these cover:**
- `PermissionServiceTest` — 16 unit cases: super_admin bypass, owner inherits all, manager/cadre/collaborateur boundaries at workspace/project/activity/task level
- `PermissionsMatrixTest` — 17 HTTP endpoint cases: every role × every action (view, edit, submit, approve N0, validate N1/N2)
- `SousTacheModelTest` — subtask creation gated by `is_responsable` flag
- `N0ValidationCircuitTest` — full N0 approval circuit (submit → approve → renvoyer → transmettre)

---

## Layer 2 — Dusk Browser Tests (requires running server + ChromeDriver)

```bash
# Start Laravel server (if not running)
php artisan serve --host=127.0.0.1 --port=8000 &

# Start ChromeDriver
vendor/laravel/dusk/bin/chromedriver-linux --port=9515 &

# Run visibility tests
php artisan dusk tests/Browser/Permissions/RoleVisibilityTest.php
```

**What these cover:**
- `[dusk="submit-result-btn"]` visible to collaborateur and cadre (is_responsable), hidden for observateur
- `[dusk="edit-task-responsable-btn"]` visible only to is_responsable cadre

---

## Layer 3 — Manual API Testing

Use any HTTP client (curl, Insomnia, Postman). All endpoints require a Sanctum token:

```bash
# Get a token for a test user
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"collaborateur@worktracking.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")
```

### Permission Matrix by Endpoint

| Endpoint | owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|---|---|---|---|---|---|
| `GET /api/workspaces` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `POST /api/workspaces` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `PUT /api/workspaces/{id}` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `POST /api/workspaces/{id}/invite-members` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `GET /api/projets` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `POST /api/projets` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `PUT /api/projets/{id}` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `DELETE /api/projets/{id}` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `POST /api/activites` | ✅ | ✅ | ✅¹ | ❌ | ❌ | ❌ |
| `PUT /api/activites/{id}` | ✅ | ✅ | ✅¹ | ❌ | ❌ | ❌ |
| `POST /api/taches` | ✅ | ✅ | ✅¹ | ❌² | ❌ | ❌ |
| `PUT /api/taches/{id}` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `POST /api/taches/{id}/resultats` | ❌³ | ❌³ | ✅⁴ | ✅ | ✅ | ❌ |
| `POST /api/taches/{id}/resultats/{r}/approuver-n0` | ❌ | ❌ | ✅⁵ | ❌ | ❌ | ❌ |
| `POST /api/taches/{id}/resultats/{r}/valider-n1` | ✅ | ✅ | ✅¹ | ❌ | ❌ | ❌ |
| `POST /api/taches/{id}/resultats/{r}/valider-n2` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `POST /api/sous-taches` | ✅ | ✅ | ✅⁵ | ❌ | ❌ | ❌ |

**Notes:**
1. Only when assigned to the activity with the relevant boolean pivot flag (`can_create_tasks`, `can_edit_activity`, etc.)
2. Unless `can_create_tasks = true` on the activity pivot (per-row override)
3. Owner/manager have no `taches.submit_result` permission — it's task-assignee-only
4. Cadre with `is_responsable = true` on `tache_user` can submit their own result
5. Only when `is_responsable = true` on `tache_user`

### Quick curl tests

```bash
# Should return 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/workspaces \
  -H "Authorization: Bearer $TOKEN"

# Should return 403 for collaborateur trying to edit a project
curl -s -o /dev/null -w "%{http_code}" -X PUT http://localhost:8000/api/projets/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"nom":"hack"}'

# Super admin: always 200 on everything
TOKEN_SA=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@worktracking.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")

curl -s -o /dev/null -w "%{http_code}" -X DELETE http://localhost:8000/api/projets/1 \
  -H "Authorization: Bearer $TOKEN_SA"
```

---

## Layer 4 — Runtime Editability Test

The point of the DB-driven system is that permission changes take effect **immediately** with no code deploy. Verify it:

```sql
-- Step 1: Confirm cadre can currently edit tasks (returns 200)
-- (use cadre token above)

-- Step 2: Remove taches.edit from cadre role
DELETE FROM role_has_permissions
WHERE role_id = (SELECT id FROM roles WHERE name = 'cadre')
  AND permission_id = (SELECT id FROM permissions WHERE name = 'taches.edit');

-- Step 3: Same request now returns 403 — no restart needed

-- Step 4: Restore
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'cadre' AND p.name = 'taches.edit';
```

---

## Layer 5 — `user_permissions` Object (Frontend Integration)

Every API resource response includes a pre-computed `permissions` or `user_permissions` object. Verify the correct keys are present and match the matrix above:

```bash
# Task permissions object
curl -s http://localhost:8000/api/taches/1 \
  -H "Authorization: Bearer $TOKEN" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(json.dumps(d.get('data',d).get('permissions'), indent=2))"

# Activity user_permissions
curl -s http://localhost:8000/api/activites/1 \
  -H "Authorization: Bearer $TOKEN" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(json.dumps(d.get('data',d).get('user_permissions'), indent=2))"

# Project user_permissions
curl -s http://localhost:8000/api/projets/1 \
  -H "Authorization: Bearer $TOKEN" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(json.dumps(d.get('data',d).get('user_permissions'), indent=2))"
```

Expected keys for `tache.permissions`:
```json
{
  "can_view": true,
  "can_edit": false,
  "can_complete": true,
  "can_validate_n1": false,
  "can_validate_n2": false,
  "can_move_my_card": true,
  "can_submit_result": true,
  "can_approve_n0": false,
  "can_create_subtask": false
}
```

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| `Column not found: role` | A `withPivot()` still requests the dropped `role` column | Replace `'role'` with `'role_id'` in that `withPivot` call |
| `Attempt to read property "role_id" on null` | `->pivot` is null — user loaded via `whereHas` without pivot | Load through the relationship with `withPivot(['role_id'])` |
| `whereIn('workspace_members.role', ...)` error | A scope or query uses raw role column | Replace with `whereIn('role_id', fn($q) => $q->select('id')->from('roles')->whereIn('name', [...]))` |
| Super admin gets 403 | `isSuperAdmin()` check missing or `Gate::before` not firing | Verify `AuthServiceProvider` registers `Gate::before`; `ContextualPermissionGate::userCan` also has an `isSuperAdmin()` guard |
| Permission change not taking effect | Role permission cache not cleared | The gate uses per-request cache (`$rolePermissionCache`); a fresh request always re-reads from DB |
