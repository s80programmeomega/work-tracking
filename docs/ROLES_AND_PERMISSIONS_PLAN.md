# Roles & Permissions Refactor — Implementation Plan

## Problem Statement
The current permission system has overlapping layers (global Spatie roles + workspace JSON permissions + project/activity/task pivots) that don't coordinate. Policies have dead code and bugs. Auto-workspace creation on signup prevents users from controlling their first workspace. Role names don't match the organizational context.

## Requirements
1. Remove auto-workspace creation on signup
2. Set default role to `utilisateur` for new users
3. Implement new role hierarchy with clear separation between global and contextual roles
4. Fix broken policy methods
5. Align validation workflow with new roles (cadre=N1, manager=N2)
6. Keep subtask feature in mind (responsable_tache via `is_responsable` flag)

## New Role Structure

**Global roles (in `Role` enum):**
- `super_admin` — platform admin, bypasses all checks
- `directeur` — workspace owner
- `utilisateur` — default on signup, can only create workspace

**Contextual roles (stored in pivots):**
- `manager` — N2 validator, sees all workspace projects
- `cadre` — N1 validator, manages activities
- `collaborateur` — executes tasks
- `stagiaire` — intern, limited access
- `observateur` — read-only
- `responsable_tache` — (not a role, but `is_responsable=true` on `tache_user`)

**Validation Flow:**
```
collaborateur/stagiaire submits result
  → cadre validates (N1)
    → manager validates (N2)
      → complete
```

---

## Task Breakdown

### ✅ Task 1: Update Role enum and remove auto-workspace creation
**Branch:** `adjustment/roles-refactor-phase1`

**Changes:**
- Update `app/Enums/Role.php`: new cases, update permissions()
- Update `app/Services/AuthService.php`: remove workspace auto-creation
- Update `database/seeders/RolePermissionSeeder.php`: new role seeds

**Tests:** Register new user → no workspace, role is `utilisateur`

---

### Task 2: Create migration for contextual roles in pivots
**Branch:** `adjustment/roles-refactor-phase1` (same branch)

**Changes:**
- Migration: add role columns to pivots if missing
- Verify `tache_user.is_responsable` exists

**Tests:** Migration runs without errors

---

### Task 3: Fix broken policy methods
**Branch:** `fix/policy-bugs`

**Changes:**
- `TachePolicy::create()`: remove `return true;`
- `ActivitePolicy::create()`: fix undefined variable
- Add `declare(strict_types=1);` to all policies

**Tests:** Unit tests for policy methods

---

### Task 4: Update validation workflow to use new roles
**Branch:** `feature/validation-workflow-update`

**Changes:**
- Update `TachePolicy`: validateN1/N2 use contextual roles
- Update `TacheResultatController`, `EvaluationController`

**Tests:** Feature tests for N1/N2 validation

---

### Task 5: Update workspace creation to assign directeur role
**Branch:** `feature/workspace-directeur-role`

**Changes:**
- `WorkspaceController::store()`: assign directeur on creation
- Update `Workspace` model methods

**Tests:** Create workspace → user becomes directeur

---

### Task 6: Audit controllers for missing authorization
**Branch:** `fix/authorization-audit`

**Changes:**
- Grep all controllers, add missing `$this->authorize()`
- Document in `docs/AUTHORIZATION_AUDIT.md`

**Tests:** Feature tests for protected endpoints

---

### Task 7: Update frontend permission composables
**Branch:** `feature/frontend-permissions-update`

**Changes:**
- Update `useWorkspacePermissions.js`
- Create `useProjetPermissions.js`, `useActivitePermissions.js`
- Update `authStore.js` for utilisateur state

**Tests:** Manual UI testing

---

### Task 8: Update seeders and create test users
**Branch:** `chore/test-data-seeders`

**Changes:**
- Update `RolePermissionSeeder.php` with test users
- Create workspace with members in all roles

**Tests:** Run seeder, login as each user

---

### Task 9: Run Laravel Pint and update documentation
**Branch:** (all branches before merge)

**Changes:**
- Run `./vendor/bin/pint --dirty`
- Update `docs/ONBOARDING.md`
- Create `docs/ROLES_AND_PERMISSIONS.md`

**Tests:** Pint passes, docs reviewed

---

## Notes
- Keep Spatie package, use only for super_admin bypass
- Don't remove old role values from DB yet — data migration later
- Subtask feature builds on `is_responsable`, not part of this refactor
