# Project Onboarding Guide

---

## 🔄 Session Resume

> **Starting a new session?** Read these files in order, then say "I've read the docs, resuming from Task N":
>
> 1. [`docs/SESSION_STATE.md`](./SESSION_STATE.md) — **start here** — current task, what's next, open questions
> 2. [`docs/WORKING_GUIDELINES.md`](./WORKING_GUIDELINES.md) — all conventions, rules, and tool usage
> 3. [`docs/IMPLEMENTATION_PLAN.md`](./IMPLEMENTATION_PLAN.md) — full v2.0 task breakdown with commits
> 4. [`docs/PROGRESSION.md`](./PROGRESSION.md) — task status and deliverables checklists
> 5. [`docs/PERMISSIONS_MATRIX.md`](./PERMISSIONS_MATRIX.md) — live permissions reference per role (update after every task that adds permissions)
>
> **Always commit, push to origin, and update `SESSION_STATE.md` at the end of every session** — the project lives on a USB drive.

---

> **For the AI agent reading this:** This document is your full briefing. Read it entirely before doing anything. It covers the project architecture, working conventions, and git workflow agreed with the developer (Jonas). Follow these conventions without needing to be re-explained.

---

## Working Conventions (AI ↔ Jonas)

- **Main branch:** `jonas` — never push to `main`
- **Branch strategy:**
  - `feature/<name>` — new features
  - `fix/<name>` — bug fixes
  - `adjustment/<name>` — tweaks/refactors
  - Always branch from `jonas`, PR back into `jonas`
- **Commit style:** `fix:`, `feat:`, `chore:` prefixes. Short, descriptive messages.
- **Before any feature:** read the relevant existing code first (Service + Controller + Composable + Page) to match patterns.
- **Minimal code:** write only what's needed, no over-engineering.
- **Language:** Jonas communicates in English. Code and commits in English.
- **This file is the memory:** update it at the end of each significant session with what was done and what's next.

---

## Current Status

- App installed and running locally
- All migrations applied and passing
- Branch `jonas` pushed to origin
- Roles & permissions refactor complete (see `docs/ROLES_AND_PERMISSIONS.md`)
- 6 branches pushed, pending PR into `jonas`

## Next Steps

- Open PRs for all 6 branches into `jonas`
- Write PHPUnit feature tests for PermissionService and auth flows
- Update remaining components to use `useProjetPermissions` and `useActivitePermissions`

---

## Project Overview: Work Tracking

This is a **SPA (Single Page Application)** — Laravel serves as a pure JSON API backend, Vue.js handles everything in the browser.

---

## Architecture in One Sentence

> A user belongs to **Workspaces** → each workspace has **Projets** → each projet has **Activités** → each activité has **Tâches** (tasks). Everything revolves around this 4-level hierarchy.

---

## Backend (Laravel)

**Entry point:** `public/index.php` → `bootstrap/app.php` → routes

**Two route files:**
- `routes/web.php` — catches ALL URLs and returns `resources/views/app.blade.php` (the Vue shell). Laravel doesn't handle any page routing.
- `routes/api.php` — all real logic lives here under `/api/*`, protected by `auth:sanctum`

**Auth:** Laravel Fortify (registration/login actions in `app/Actions/Fortify/`) + Sanctum (token-based API auth). The `AuthController` in `app/Http/Controllers/Api/` handles login/logout/me.

**Controllers are split in two:**
- `app/Http/Controllers/Api/` — the real API controllers (TacheController is 79KB — the biggest/most complex)
- `app/Http/Controllers/` — older/secondary controllers (Teams, Comments, Labels, etc.)

**Services layer** (`app/Services/`) — business logic is extracted here, controllers call services:
- `TacheService.php` — task lifecycle, assignment, validation
- `ProjetService.php` — project management
- `ActiviteService.php` — activity management
- `DocumentService.php` + `DocumentAccessResolver.php` — file management with permission checks
- `AccessManagementService.php` — temporary access grants
- `MemberRemovalService.php` — handles cascading removal of members

**Permissions:** `spatie/laravel-permission` package, DB-driven, with a **two-layer role model**.

```
┌─────────────────────────────────────────────────────────────────┐
│  LAYER 1 — Global roles (Spatie model_has_roles table)          │
│  Assigned to the User account itself; apply everywhere          │
│  Roles: super_admin, directeur, utilisateur                     │
└─────────────────────────────────────────────────────────────────┘
                              ⬇  combined with
┌─────────────────────────────────────────────────────────────────┐
│  LAYER 2 — Contextual roles (stored in pivot tables)            │
│  Scoped to a specific workspace/project/activity/task           │
│  Roles: owner, manager, cadre, collaborateur, stagiaire,        │
│         observateur, task_responsable (virtual)                 │
└─────────────────────────────────────────────────────────────────┘
```

**Where role assignments live:**

| Role kind             | Storage                                                  |
|---|---|
| `super_admin`         | `model_has_roles` row → bypasses everything via `Gate::before()` |
| `directeur`           | `model_has_roles` row, plus `workspaces.owner_id` for workspace ownership |
| `utilisateur`         | `model_has_roles` row (default for any registered user) |
| Contextual roles      | `workspace_members.role_id`, `projet_user.role_id`, `activite_user.role_id`, `tache_user.role_id` (all FK to Spatie roles) |
| `task_responsable`    | **Virtual** — derived from `tache_user.is_responsable = true`, not stored as a row |

**Authorization flow at runtime:**

```
Controller calls $this->authorize('action', $model)           ← Policy pattern
  OR
Controller calls $gate->userCan($user, Permission::X, $resource)  ← Direct gate pattern
        ↓
ContextualPermissionGate::userCan()
  ├── short-circuits if super_admin (Gate::before bypass)
  ├── walks the resource hierarchy upward:
  │     Tache → Activite → Projet → Workspace (collects role_ids from each pivot)
  ├── adds 'task_responsable' if tache_user.is_responsable = true
  ├── adds 'owner' if workspaces.owner_id matches
  ├── unions all permissions held by those roles (from Spatie's role_has_permissions)
  └── applies pivot-flag overrides (Permission::pivotOverrideMap)
        ↓
  returns true if requested permission is in the unioned set
```

**Key files:**

- [`app/Permissions/Permission.php`](app/Permissions/Permission.php) — single source of truth: every permission string constant + the default `forRole()` mapping (overridable at runtime via Spatie tables, future admin UI = Task 14)
- [`app/Permissions/ContextualPermissionGate.php`](app/Permissions/ContextualPermissionGate.php) — the hierarchy walker
- [`app/Policies/`](app/Policies/) — per-model CRUD policies (`ProjetPolicy`, `ActivitePolicy`, `TachePolicy`, `SousTachePolicy`, `DocumentPolicy`, `WorkspacePolicy`)
- [`resources/js/permissions/Permission.js`](resources/js/permissions/Permission.js) — frontend mirror of the constants
- `useWorkspacePermissions.js`, `useProjetPermissions.js`, `useActivitePermissions.js`, `useTachePermissions.js` — composables reading the pre-computed `user_permissions` payload from API resources
- [`docs/PERMISSIONS_MATRIX.md`](docs/PERMISSIONS_MATRIX.md) — live reference table per role × permission

**When to use which pattern:**

- **Per-model CRUD** (view/edit/delete on a Projet/Activite/Tache) → Policy via `$this->authorize('action', $model)`
- **Cross-cutting workspace-scoped action** (activate bypass, view pending validations dashboard, calculate score) → direct gate via `$gate->userCan($user, Permission::X, $workspace_or_resource)`

**To add a new permission** (Guide 4's 5 steps):
1. Add the constant to `Permission::all()` and `Permission::forRole($roleName)` in `Permission.php`
2. Permission string lands in DB on next `db:seed --class=RolePermissionSeeder`
3. Mirror the constant in `resources/js/permissions/Permission.js`
4. Expose it on the relevant composable (`useWorkspacePermissions.js` etc.)
5. **Update `docs/PERMISSIONS_MATRIX.md`** (the row + the changelog table) in the same commit (Guide 15 is a hard gate)

**Key Models and their relationships:**
```
Workspace  →  has many Projets, Users (members)
Projet     →  belongs to Workspace, has many Activites, Users (pivot: projet_user)
Activite   →  belongs to Projet, has many Taches, Users (pivot: activite_user)
Tache      →  belongs to Activite, has many Users (pivot: tache_user), Comments, Documents, Attachments, TacheResultats
TacheResultat → the result/deliverable submitted by an assignee, goes through N1→N2 validation workflow
```

**Activity log:** `spatie/laravel-activitylog` — automatically tracks model changes.

---

## Frontend (Vue 3)

**Entry point:** `resources/js/app.js` → mounts `App.vue` with Pinia + Vue Router + i18n + Toast

**Router:** `resources/js/router/index.ts` — client-side routing, all routes require `auth` meta. The router guard checks `authStore` before allowing navigation.

**State management (Pinia stores):**
- `authStore.js` — current user, token, workspace context (the most important store)
- `projetStore.js`, `tacheStore.js`, `activiteStore.js` — CRUD state for core entities
- `userStore.js`, `labelStore.js`, `labelTemplateStore.js`

**Composables** (`resources/js/composables/`) — reusable logic extracted from components:
- `useWorkspace.js` — workspace switching, member management
- `useProjets.js` — project CRUD + member invitations
- `useActivites.js`, `useTaches.js` — activity/task operations
- `useWorkspacePermissions.js` — permission checks in the UI
- `useDocuments.js`, `useComments.js`, `useNotifications.js`

**Pages** (`resources/js/pages/`) — one file per route. Organized by domain: `projets/`, `taches/`, `documents/`, `workspaces/`, `labels/`, etc.

**UI stack:** TailwindCSS 4 + AdminLTE 3 + Heroicons + Lucide icons + ApexCharts + FullCalendar

---

## Key Workflows to Understand

**1. Task validation flow (most complex):**
```
Assignee submits TacheResultat → N1 (responsable_n1) validates → N2 (responsable_n2) validates → task marked complete
```
This is handled by `TacheResultatController` + `EvaluationController` + multiple Notifications.

After N2, the task becomes **immutable** (rule R6 — Task 9): `TacheService::guardPostN2Immutability()` throws an `HttpException(422)` on every mutation method (update/delete/move/archive/(un)assignUser). The check is centralised in the service so all controllers inherit it for free; the predicate `Tache::isLockedPostN2()` returns true as soon as any `TacheResultat` of the task has `valide_par_n2 = true`.

**2. Workspace invitation flow:**
```
Admin sends invite (token email) → recipient clicks link → frontend calls /api/workspace-invitations/{token}/accept → user joins workspace
```
Same pattern exists for Projet invitations (`ProjetInvitation`).

**3. Temporary access:**
The `temporary_access` table lets admins grant time-limited access to a resource (Projet/Activite/Tache) with a specific role, managed by `AccessManagementService`.

---

## Things to Watch Out For

- **Duplicate/copy files exist:** `authStore copy.js`, `activiteStore copy.js`, `MemberRemovalService copy.php`, `api copy.php` — these are dead files, ignore them.
- **Two controller namespaces:** `Api\TacheController` (the real one, 79KB) vs `TacheController` (older, 7KB). Always use the `Api\` ones.
- **Auth store is the active one:** `authStore.js` (30KB) is the real one. `auth.js` (12KB) is an older version.
- **Migrations are additive:** The schema evolved over many migrations. The final shape of a table is the sum of its migration + all `add_*` migrations on top of it.

---

## Where to Start When Adding a Feature

1. **DB change?** → new migration in `database/migrations/`
2. **Backend logic?** → add to the relevant Service, call from Controller
3. **New API endpoint?** → add route in `routes/api.php`, create/update Controller in `app/Http/Controllers/Api/`
4. **Frontend?** → add composable logic in `composables/`, create/update page in `pages/`, add route in `router/index.ts`
5. **Permissions?** → add permission string to `RolePermissionSeeder` AND to `Role::permissions()` for the relevant roles

---

## Dev Commands

```bash
# Backend
php artisan serve          # start Laravel
php artisan migrate        # run migrations
php artisan db:seed        # seed data

# Frontend (separate terminal)
npm run dev                # start Vite HMR dev server
npm run build              # production build
```

The app runs on Laravel's server, Vite proxies assets in dev mode. Both must be running simultaneously during development.
