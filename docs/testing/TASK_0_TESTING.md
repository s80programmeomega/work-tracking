# Task 0 — User Testing Guide
## Permission Architecture Refactor (Policies + Gate)

> **Goal:** Verify that authorization still works correctly after replacing `abort_unless` with Laravel Policies, and that the `super_admin` bypass works as expected.

---

## Prerequisites

1. App running locally (`php artisan serve` + `npm run dev`)
2. Database seeded: `php artisan db:seed --class=RolePermissionSeeder`
3. Use the seeded test users (password: `password` for all):

| Email | Role |
|---|---|
| superadmin@worktracking.com | super_admin |
| directeur@worktracking.com | directeur (workspace owner) |
| manager@worktracking.com | manager |
| cadre@worktracking.com | cadre |
| collaborateur@worktracking.com | collaborateur |
| observateur@worktracking.com | observateur |

---

## Test Cases

**TC-01 — Super admin bypasses all checks**
1. Log in as `superadmin@worktracking.com`
2. Navigate to any project, activity, and task
3. Try to edit, delete, and manage members on each
- ✅ Expected: all actions succeed, no 403 errors

**TC-02 — Directeur has full control over their workspace**
1. Log in as `directeur@worktracking.com`
2. Navigate to the workspace they own
3. Try to create a project, invite a member, manage settings
- ✅ Expected: all actions succeed

**TC-03 — Manager can edit projects but not delete them**
1. Log in as `manager@worktracking.com` (must be a member of a project with `manager` role)
2. Try to edit a project → ✅ should succeed
3. Try to delete the project → ❌ should return 403

**TC-04 — Cadre can create and edit tasks but not delete projects**
1. Log in as `cadre@worktracking.com`
2. Navigate to an activity where they are a member with `cadre` role
3. Try to create a task → ✅ should succeed
4. Try to delete the project → ❌ should return 403

**TC-05 — Collaborateur cannot edit tasks without pivot flag**
1. Log in as `collaborateur@worktracking.com`
2. Navigate to a task they are assigned to (without `can_edit = true` on pivot)
3. Try to edit the task → ❌ should return 403
4. Try to submit a result → ✅ should succeed

**TC-06 — Observateur is read-only**
1. Log in as `observateur@worktracking.com`
2. Navigate to a project, activity, and task they are a member of
3. Try to edit anything → ❌ should return 403 on all write actions
4. View project/task details → ✅ should succeed

**TC-07 — Unauthenticated request is rejected**
1. Log out completely
2. Call any API endpoint directly (e.g. `GET /api/projets/1`)
- ❌ Expected: 401 Unauthorized

---

## Negative Cases

| Scenario | Expected |
|---|---|
| `collaborateur` tries to delete a project | 403 |
| `cadre` tries to validate N2 | 403 |
| `observateur` tries to create a task | 403 |
| `manager` tries to validate N1 | 403 (N1 is cadre only) |
| Any non-super_admin tries to access another workspace's data | 403 |

---

## Cleanup

No cleanup needed — these are read/write tests on seeded data. Re-seed if data gets corrupted:
```bash
php artisan migrate:fresh --seed
```
