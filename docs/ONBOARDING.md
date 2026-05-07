# Project Onboarding Guide

> **For the AI agent reading this:** This document is your full briefing. Read it entirely before doing anything. It covers the project architecture, working conventions, and git workflow agreed with the developer (Jonas). Follow these conventions without needing to be re-explained.

---

## Working Conventions (AI ↔ Jonas)

- **Branch strategy:** All work is on branch `jonas`. Features go on `jonas/feature/<name>`, fixes on `jonas/fix/<name>`. Always branch from `jonas`, PR back into `jonas`.
- **Never push to `main`** without explicit instruction.
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
- Fixes applied: duplicate migration, missing `can_view_all_projects` permission, `Documents.vue` route case

## Next Steps

- _(update this section each session)_

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

**Permissions:** `spatie/laravel-permission` package. Roles are defined in `app/Enums/Role.php`:
```
super_admin → admin → manager → member → viewer → cadre → stagiaire
```
Each role has a `permissions()` method listing what it can do. Policies in `app/Policies/` add object-level checks (e.g., "can this user edit *this specific* projet?").

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
