# Progression — Superadmin Platform-Only Scoping

**Branch:** `feature/superadmin-scoping`
**Reference plan:** `docs/superadmin-scoping/PLAN.md`

---

## Progress Table

| # | Step | File(s) | Status | Notes |
|---|---|---|---|---|
| 1 | Remove `Gate::before()` bypass; add named platform Gates | `AuthServiceProvider.php` | ✅ Done | Added `platform.admin`, `platform.manage-workspace`, `platform.manage-users`, `platform.operator`, `viewHorizon`, `viewPulse` gates |
| 2 | `WorkspaceController::index()` — remove isSuperAdmin branch | `WorkspaceController.php` | ✅ Done | Superadmin with no workspace membership now returns empty collection |
| 3 | Remove unscoped SA routes (`projets/list/all` + `activites`) | `routes/api.php`, `ProjetController.php`, `ActiviteController.php` | ✅ Done | Routes deleted; both controller methods return 403 |
| 4 | `TacheController` — remove unscoped `pending()` + `overdue()` branches | `TacheController.php` | ✅ Done | Both methods now always scope to the current user |
| 5 | `Projet` model — remove isSuperAdmin early-exits in 2 scopes | `Projet.php` | ✅ Done | `scopeVisibleTo` + `scopeAccessibleBy` cleaned |
| 6 | `ContextualPermissionGate` — remove isSuperAdmin bypass | `ContextualPermissionGate.php` | ✅ Done | `userCan()` no longer short-circuits for superadmin |
| 7 | Migration — admin_expires_at, admin_expiry_action, created_by, is_system_owner | new migration | ✅ Done | MariaDB partial-index workaround: `is_system_owner` nullable; unique index rejects duplicate TRUE values |
| 7b | System owner — invisibility + protection in model/controllers | `User.php`, `AdminController.php` | ✅ Done | `isSuperAdmin()` covers system owner; `isSystemOwner()` + `isSuperAdminExpired()` added; `users()` hides system owner; `updateUserRole()` blocks it with 403 |
| 15 | Lock Horizon + Pulse to `isSuperAdmin()` | `AuthServiceProvider.php` | ✅ Done | `viewHorizon` + `viewPulse` gates added in Step 1 |
| 8 | Artisan command `admin:create-superadmin` (with `--system-owner` flag) | `app/Console/Commands/CreateSuperAdmin.php` | ✅ Done | `--expires-in`, `--expiry-action`, `--system-owner` flags; idempotent; prints temp password once |
| 8b | Artisan command `admin:expire-accounts` + scheduler | `ExpireSuperAdminAccounts.php`, `Kernel.php` | ✅ Done | `--dry-run` flag; cleans `temporary_access` records; daily schedule |
| 9 | Temporary SA read-only workspace access via `temporary_access` | `WorkspaceController.php`, `AdminController.php` | ✅ Done | SA with `admin_expires_at` scoped to `temporary_access` grants only |
| 9b | `AdminController` — `myTempSuperadmins()` + `terminate()` + `AdminUsers.vue` section | `AdminController.php`, `AdminUsers.vue` | ✅ Done | `myTempSuperadmins()` + `terminate()` done; directeur sees own; SA sees all |
| 10 | `directeur` can create SA accounts (`platform.operator` gate) | `AuthServiceProvider.php`, `routes/api.php`, `AdminController.php` | ✅ Done | `platform.operator` gate; directeur-scoped workspace validation in `updateUserRole()` |
| 11 | Router — redirect SA to `admin.dashboard`; remove SA permission bypass | `router/index.ts` | ✅ Done | SA redirected to `admin.dashboard`; `isSuperAdmin ||` removed from `meta.permissions` check and `noWorkspace` guard |
| 12 | Remove `isSuperAdmin\|\|` bypasses from 6 permission composables | 6 composable files | ✅ Done | All data-permission bypasses removed from `useProjetPermissions`, `useActivitePermissions`, `useTachePermissions`, `useActivityPermissions`, `useWorkspacePermissions`, `useProjets`; `isSuperAdmin` kept exported for UI visibility only; `fetchAllProjets` no longer calls deleted `/projets/list/all` |
| 13 | Wire `ChangePasswordModal` into `UserProfile` Security tab | `UserProfile.vue` | ✅ Done | Button card added above danger zone; modal mounted; i18n keys added (en + fr) |
| 14 | Fix password reset flow (`ForgotPassword.vue` + `ResetPassword.vue`) | 2 Vue pages | ✅ Done | i18n added (fr+en); success state + 2s redirect delay in ResetPassword; dusk attrs; `$toast?.` safety guards in ChangePasswordModal |
| 13b | Admin audit log: migration, model, service, frontend page | new files | ⏳ Pending | |
| 16 | PHPUnit + Dusk tests | `tests/Feature/SuperAdmin/`, `tests/Browser/Admin/` | ✅ Done | SuperAdminScopingTest (6 Dusk), ChangePasswordModalTest (6 Dusk, incl. success path); 4 PHPUnit tests updated; router noWorkspace exempts SA; rate limiting disabled in dusk.local; signInAs() uses isSuperAdmin() |
| 17 | Pint + Larastan + npm build + full test suite | — | ⏳ Pending | |

---

## Legend

- ✅ Done
- 🔄 In progress
- ⏳ Pending
- ❌ Blocked

---

## Session Log

| Date | Session | Progress |
|---|---|---|
| 2026-06-25 | Planning | Plan finalized, folder structure created |
| 2026-06-25 | Implementation | Steps 1–8b complete: Gate::before removed, all unscoped SA data access paths closed, migration applied, User model updated, system owner protection, Artisan commands created |
| 2026-06-25 | Implementation | Steps 9–12 complete: temporary_access scoping, AdminController endpoints, platform.operator gate, router guards, all frontend permission composable bypasses removed |
