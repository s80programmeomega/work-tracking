# Plan: Superadmin Platform-Only Scoping + Password Reset UI

## Documentation Structure

All plan and progress docs live under `docs/superadmin-scoping/`:

```
docs/superadmin-scoping/
├── PLAN.md                  ← copy of this plan
├── PROGRESSION.md           ← step-by-step status table (updated after each step)
└── testing/
    ├── PHASE_1_BACKEND_SCOPING.md
    ├── PHASE_2_TEMP_ACCOUNTS.md
    ├── PHASE_3_WORKSPACE_ACCESS.md
    ├── PHASE_4_FRONTEND_ROUTER.md
    ├── PHASE_5_PASSWORD_FLOWS.md
    ├── PHASE_6_SYSTEM_OWNER.md
    └── PHASE_7_AUDIT_LOG.md
```

**First action on implementation start:** create this folder structure, write `PLAN.md` and a blank `PROGRESSION.md`, then proceed step by step — writing the corresponding testing guide after each phase is complete.

---

## Context

The superadmin role currently bypasses all workspace/tenant isolation via a blanket `Gate::before()` hook, unscoped controller queries, and frontend `isSuperAdmin ||` permission bypasses. This gives the platform operator silent read/write access to all customer data — a security and compliance risk for a multi-tenant SaaS.

**Decisions made:**
- **Permanent superadmin** → platform operations only (billing, suspension, user roles, stats). Zero workspace content access, ever. All actions fully logged to `admin_audit_logs`.
- **Temporary superadmin** → read-only access strictly to the workspaces the directeur explicitly selected. Every action (reads included) logged to `admin_audit_logs`, filterable by the directeur who created the account.
- **System owner** → same as permanent superadmin, untouchable and invisible.
- Superadmin lands on `/admin/dashboard` after login (page already exists)
- Superadmin accounts are **temporary** with a configurable duration and auto-suspend or auto-delete on expiry
- The `directeur` role can also create superadmin accounts (for emergency platform access)
- Password reset is already implemented in the backend but missing a UI entry point — needs to be surfaced properly

---

## Steps (in order)

### Step 1 — Backend: Remove `Gate::before()` superadmin bypass

**File:** `app/Providers/AuthServiceProvider.php` lines 42–47

Remove the entire `Gate::before()` block. Replace with explicit named Gates:

```php
Gate::define('platform.admin', fn(User $user) => $user->isSuperAdmin());
Gate::define('platform.manage-workspace', fn(User $user) => $user->isSuperAdmin());
Gate::define('platform.manage-users', fn(User $user) => $user->isSuperAdmin());
```

AdminController actions that currently rely on the blanket bypass should call `$this->authorize('platform.admin')`.

---

### Step 2 — Backend: WorkspaceController — block superadmin from workspace data

**File:** `app/Http/Controllers/Api/WorkspaceController.php`

- `index()` lines 40–52: Remove the `isSuperAdmin()` branch (`Workspace::query()`). Superadmin has no workspace membership so the standard branch returns an empty collection — correct and safe.
- `show()`: Confirm `userHasAccess()` has no superadmin bypass. No change needed if confirmed clean.

---

### Step 3 — Backend: Remove unscoped superadmin routes + controller branches

**Files:** `routes/api.php`, `app/Http/Controllers/Api/ProjetController.php`, `app/Http/Controllers/Api/ActiviteController.php`

- Delete the `Route::middleware(['super_admin'])` group in `routes/api.php` wrapping `ProjetController::index` (`GET /api/projets/list/all`) and `ActiviteController::index`.
- In `ProjetController::index()`: remove the method body or return 403 — superadmin has no business browsing customer projects.
- In `ActiviteController::index()`: remove the `if ($user->isSuperAdmin())` branch entirely.

---

### Step 4 — Backend: TacheController — remove unscoped branches

**File:** `app/Http/Controllers/Api/TacheController.php`

- `pending()` ~lines 280–282: Remove the `isSuperAdmin()` branch that returns all unscoped pending tasks across all workspaces.
- `overdue()` ~lines 327–336: Same treatment.

---

### Step 5 — Backend: Projet model scopes

**File:** `app/Models/Projet.php`

- `scopeVisibleTo()` lines 264–300: Remove the `if ($user->isSuperAdmin()) { return; }` early exit.
- `scopeAccessibleBy()` lines 345–352: Remove the `if ($user && $user->isSuperAdmin()) { return $query; }` early exit.

---

### Step 6 — Backend: ContextualPermissionGate

**File:** `app/Permissions/ContextualPermissionGate.php`

Remove the `if ($user->isSuperAdmin()) { return true; }` check in `userCan()` and any redundant check in `resolveEffectivePermissions()`. Superadmin with no workspace membership will naturally be denied by the contextual check — no special case needed.

---

### Step 7 — Backend: Temporary superadmin accounts

**Migration (new):** Add to `users` table:
```php
$table->timestamp('admin_expires_at')->nullable();   // NULL = permanent (no expiry)
$table->enum('admin_expiry_action', ['suspend', 'delete'])->default('suspend');
```

**Scheduled job:** Create `app/Console/Commands/ExpireSuperAdminAccounts.php` — runs daily via the scheduler. For each user where `is_super_admin = true` AND `admin_expires_at <= now()`:
- If `admin_expiry_action = 'suspend'`: set `is_active = false`, `is_super_admin = false`, remove `super_admin` Spatie role
- If `admin_expiry_action = 'delete'`: soft-delete the user record (if `SoftDeletes` is used) or hard-delete

Register in `app/Console/Kernel.php`: `$schedule->command('admin:expire-accounts')->daily()`.

**AdminController:** Update `updateUserRole()` to accept and store `admin_expires_at` and `admin_expiry_action` fields when setting `is_super_admin = true`.

---

### Step 7b — Backend: System owner account (permanent, invisible, untouchable)

**Migration addition** (add to Step 7 migration):
```php
$table->boolean('is_system_owner')->default(false);
$table->unique('is_system_owner'); // only one system owner — enforced at DB level via partial unique
```
Note: a standard unique constraint would block all `false` rows. Use a **partial unique index** instead: `CREATE UNIQUE INDEX users_system_owner_unique ON users (is_system_owner) WHERE is_system_owner = true;` — enforced via a raw migration statement.

**Rules enforced at the model/controller layer:**
- `isSuperAdmin()` returns `true` if `is_system_owner = true` (system owner is always a superadmin)
- `AdminController::users()`, `myTempSuperadmins()`: exclude system owner from all listings (`WHERE is_system_owner = false`)
- `AdminController::updateUserRole()`, `terminate()`: if target user `is_system_owner = true` → abort 403 with message "Compte système protégé — modification impossible"
- `ExpireSuperAdminAccounts` command: skip any user where `is_system_owner = true`
- No API endpoint can set `is_system_owner = true` — the column is never in any `$fillable` array and never accepted as a request parameter

**Artisan command extension:** Add `--system-owner` flag to `admin:create-superadmin`. When set:
- Checks if a system owner already exists → if yes, abort with message
- Creates the user with `is_system_owner = true`, `is_super_admin = true`, no `admin_expires_at`
- This flag can only be used once ever (DB unique index enforces it)

---

### Step 8 — Backend: Artisan command `admin:create-superadmin` (production bootstrap)

**New file:** `app/Console/Commands/CreateSuperAdmin.php`

Signature: `admin:create-superadmin {--email=} {--name=} {--expires-in=30 : Days until account expires} {--expiry-action=suspend : suspend or delete}`

Behaviour:
- Validates email not already taken
- Creates user with `is_super_admin = true`, `syncRoles(['super_admin'])`, sets `admin_expires_at` to `now()->addDays($expiresIn)` and `admin_expiry_action`
- Generates a random 16-char temporary password, prints it **once** to terminal
- Issues a password-reset token so first login forces a password change
- Idempotent: if email already exists as superadmin, prints status and exits cleanly

---

### Step 9 — Backend: Temporary superadmin read-only workspace access

When a directeur creates a temporary superadmin account, they may optionally grant it read-only access to a selected subset of their own workspaces. This reuses the existing `temporary_access` table infrastructure.

**Schema extension:** The existing `temporary_access` table stores `accessible_type` / `accessible_id` per resource. For workspace-level read access, add a new `accessible_type = 'App\Models\Workspace'` entry per selected workspace, with `role = 'readonly'` and `expires_at` matching the account's `admin_expires_at`.

**AdminController `updateUserRole()` / `CreateSuperAdmin` command:** Accept an optional `workspace_ids` array (must all be owned by the requesting directeur — validated server-side). For each selected workspace ID, create a `temporary_access` record:
```php
TemporaryAccess::create([
    'user_id'         => $newSuperAdmin->id,
    'accessible_type' => Workspace::class,
    'accessible_id'   => $workspaceId,
    'role'            => 'readonly',
    'permissions'     => ['can_view_workspace', 'can_view_projects', 'can_view_tasks'],
    'expires_at'      => $newSuperAdmin->admin_expires_at,
    'created_by'      => $directeur->id,
]);
```

**WorkspaceController `show()` / read endpoints:** When a superadmin requests a workspace, check `temporary_access` for a valid non-expired record before returning data. If found → return data in read-only mode (no write permissions). If not found → 403.

**Expiry cleanup:** The existing `ExpireSuperAdminAccounts` command (Step 7) should also delete associated `temporary_access` records when the account is suspended/deleted.

**Audit logging:** Every workspace read by a temporary superadmin via this mechanism must be logged via Spatie Activity Log with `causedBy($superAdmin)->performedOn($workspace)->log('Accès lecture temporaire')`.

---

### Step 9b — Backend: Temporary superadmin lifecycle management by directeur

A directeur must be able to list, view, and early-terminate the temporary superadmin accounts they created.

**Migration addition** (add to the Step 7 migration or a separate one):
```php
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
```
Set `created_by = auth()->id()` when a directeur (or superadmin) creates a temporary superadmin account.

**New AdminController endpoints** (all gated by `platform.operator`):

- `GET /api/admin/my-superadmins` — returns temporary superadmin accounts where `created_by = auth()->id()`, with their status, expiry, granted workspaces, and `is_active` flag. Superadmin calling this sees all; directeur sees only their own.
- `POST /api/admin/superadmins/{user}/terminate` — immediately applies the `admin_expiry_action` (suspend or delete) for that account. Authorization check: directeur can only terminate accounts where `created_by = auth()->id()`. Superadmin can terminate any. Deletes associated `temporary_access` records. Logs the termination with `causedBy(auth()->user())`.

**Frontend (admin panel):** Add a "Temporary Administrators" section to the admin users page (`AdminUsers.vue`) visible to both superadmin and directeur. For directeurs, scoped to their own accounts. Each row shows: name, email, expiry date, granted workspaces, status, and an "Early terminate" button that calls the terminate endpoint and removes the row on success.

---

### Step 10 — Backend: Allow `directeur` role to create superadmin accounts (and grant workspace access)

**File:** `app/Providers/AuthServiceProvider.php`

Add a `platform.operator` Gate that allows both superadmin and directeur to reach the user-role endpoint:

```php
Gate::define('platform.operator', fn(User $user) =>
    $user->isSuperAdmin() || $user->hasRole('directeur')
);
```

**File:** `routes/api.php`

Apply `platform.operator` middleware only to `admin.users.update-role`. All other `/admin/*` routes stay `super_admin` only.

**File:** `app/Http/Controllers/Api/AdminController.php` — `updateUserRole()`

- Accept optional `workspace_ids` array in the request (validated: each ID must be owned by the requesting directeur)
- When `is_super_admin = true` and `workspace_ids` provided → delegate to Step 9 logic to create `temporary_access` records
- Log the directeur's action distinctly from a superadmin performing the same action: `'granted_by_role' => auth()->user()->hasRole('directeur') ? 'directeur' : 'super_admin'`

---

### Step 10 — Frontend: Router — superadmin redirect + workspace route block

**File:** `resources/js/router/index.ts`

In the global navigation guard:

1. After auth, if `authStore.isSuperAdmin` and `to.name` is not an `admin.*` route → redirect to `{ name: 'admin.dashboard' }`.
2. Remove `isSuperAdmin` bypass from the `permissions` meta check (~line 897) — superadmin must not bypass role guards for workspace routes.
3. The existing `noWorkspace` redirect (line 909) already excludes superadmin — keep it, but add explicit redirect to `admin.dashboard` instead of falling through.
4. Guest redirect (line 916) after login: if user is superadmin, redirect to `admin.dashboard` instead of `workspaces.select`.

---

### Step 11 — Frontend: Remove `isSuperAdmin ||` bypasses from permission composables

**Pattern:** Replace `computed(() => isSuperAdmin.value || perms.value.can_xxx ?? false)` → `computed(() => perms.value.can_xxx ?? false)` across all 6 files.

Keep `isSuperAdmin` exported where it is used to conditionally show admin UI elements (e.g. sidebar menu groups). Remove only the data-permission bypasses.

**Files:**
- `resources/js/composables/useProjetPermissions.js` — ~11 computed bypasses
- `resources/js/composables/useTachePermissions.js`
- `resources/js/composables/useActivitePermissions.js`
- `resources/js/composables/useActivityPermissions.js`
- `resources/js/composables/useWorkspacePermissions.js`
- `resources/js/composables/useProjets.js`

---

### Step 12 — Frontend: Password reset UI

**Status:** Backend is fully implemented (`ResetPasswordController`, `/api/auth/forgot-password`, `/api/auth/reset-password`). Pages exist (`ForgotPassword.vue`, `ResetPassword.vue`). "Forgot password?" link exists on Signin. Router routes exist.

**Problem to investigate:** Why does the user not see the password reset flow? Possible causes:
- The "Forgot password?" link is hidden (CSS `opacity-0`, `hidden`, or conditional `v-if`)
- The link navigates to `/forgot-password` but the page has a rendering bug
- The form submits but the API response is not handled correctly (no success message shown)
- Email sending is not configured in production `.env`

**Action:** Read `ForgotPassword.vue` and `ResetPassword.vue` fully, check for rendering bugs, verify the API call chain, and fix whatever is broken. Do not rewrite the pages — fix what's there. Test the full flow: enter email → receive link → set new password → redirect to login.

---

### Step 13 — Tests

**PHPUnit Feature tests (new file):** `tests/Feature/SuperAdmin/SuperAdminScopingTest.php`
- Superadmin cannot access `GET /api/workspaces/{id}` for a workspace they don't own
- Superadmin gets empty array from `GET /api/workspaces`
- `GET /api/projets/list/all` returns 403 for superadmin
- Directeur can call `PATCH /api/admin/users/{user}/role`
- Non-directeur, non-superadmin gets 403 on `PATCH /api/admin/users/{user}/role`
- Expired superadmin account is suspended/deleted by the scheduled command

**Dusk Browser tests (new file):** `tests/Browser/Admin/SuperAdminScopingTest.php`
- Superadmin login redirects to `/admin/dashboard`
- Superadmin cannot navigate to `/workspaces/select` (redirected back to admin)
- Admin dashboard loads with platform stats visible

---

### Step 15 — Frontend: Wire up password change + password reset UI

Both flows are fully implemented on the backend. Both are orphaned from the UI.

**Password change (logged-in users):**
- `ChangePasswordModal.vue` exists at `resources/js/components/profile/ChangePasswordModal.vue` — complete with strength indicator, validation, and API call to `POST /api/users/change-password`
- `resources/js/pages/Others/UserProfile.vue` has a Security tab but does not import or render the modal
- Fix: import `ChangePasswordModal` into `UserProfile.vue` and add a "Change password" trigger button inside the Security tab

**Password reset (unauthenticated users):**
- `ForgotPassword.vue` and `ResetPassword.vue` exist in `resources/js/pages/Auth/`
- "Forgot password?" link exists on `Signin.vue` but the flow may be broken (unknown cause)
- Fix: read both pages fully, trace the API call to `POST /api/auth/forgot-password` and `POST /api/auth/reset-password`, identify and fix whatever blocks completion (missing success state, broken redirect, missing i18n key, etc.)
- Do not rewrite the pages — fix only what is broken

---

### Step 13b — Backend: Admin audit log (`admin_audit_logs`)

**Purpose:** A dedicated audit trail for all platform-admin operator actions — separate from the Spatie activity log (which tracks workspace business events). Used for compliance and directeur oversight of temporary superadmins they created.

**Migration (new):** `database/migrations/xxxx_create_admin_audit_logs_table.php`

```php
Schema::create('admin_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
    $table->string('actor_type');          // 'permanent_superadmin' | 'temporary_superadmin' | 'system_owner' | 'directeur'
    $table->string('action');              // e.g. 'workspace.suspend', 'user.role_updated', 'workspace.read', 'stats.read'
    $table->string('target_type')->nullable();   // 'workspace', 'user', etc.
    $table->unsignedBigInteger('target_id')->nullable();
    $table->json('context')->nullable();   // extra details: old/new values, IP, workspace name, etc.
    $table->string('ip_address', 45)->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // directeur who created temp SA
    $table->timestamp('created_at');
    $table->index(['actor_id', 'created_at']);
    $table->index(['created_by', 'created_at']); // directeur can filter to their own SA accounts
});
```

**Model:** `app/Models/AdminAuditLog.php` — simple Eloquent model, no soft deletes (audit logs must never be deleted), no `updated_at`.

**Service:** `app/Services/AdminAuditService.php` — single static method `log(User $actor, string $action, ?Model $target = null, array $context = [])`. Determines `actor_type` from `$actor->is_system_owner` / `$actor->admin_expires_at` / role. Captures IP from `request()->ip()`. Called from controllers — never inline.

**What gets logged (all AdminController actions):**

| Action | `action` key | Read or Write |
|---|---|---|
| `GET /api/admin/stats` | `stats.read` | Read |
| `GET /api/admin/workspaces` | `workspaces.list` | Read |
| `GET /api/admin/users` | `users.list` | Read |
| `GET /api/admin/my-superadmins` | `superadmins.list` | Read |
| `POST /api/admin/workspaces/{id}/suspend` | `workspace.suspend` | Write |
| `POST /api/admin/workspaces/{id}/reactivate` | `workspace.reactivate` | Write |
| `POST /api/admin/workspaces/{id}/extend-trial` | `workspace.extend_trial` | Write |
| `PATCH /api/admin/users/{id}/role` | `user.role_updated` | Write |
| `POST /api/admin/superadmins/{id}/terminate` | `superadmin.terminated` | Write |
| Temporary SA reads workspace via `WorkspaceController::show()` | `workspace.read` | Read |
| Temporary SA reads project within granted workspace | `projet.read` | Read |
| Temporary SA reads task within granted workspace | `tache.read` | Read |

**Frontend — Admin Audit Log page:** Add a new route `/admin/audit-log` (`admin.audit-log`) accessible to permanent superadmin and system owner only (not directeur, not temporary SA). Shows a filterable table: actor name, action, target, timestamp, IP. Directeur gets a scoped view at `/admin/my-audit-log` showing only logs where `created_by = auth()->id()` (actions by their temporary SAs).

**Temporary SA sub-resource logging:** When a temporary superadmin accesses a project, task, or document within a granted workspace, the relevant controller (`ProjetController::show()`, `TacheController::show()`, etc.) must call `AdminAuditService::log()` when `$user->isSuperAdmin() && $user->admin_expires_at !== null`.

---

### Step 14 — Backend: Lock Horizon + Pulse to superadmin only

**Horizon** (`app/Providers/HorizonServiceProvider.php`):
Currently uses `optional($user)->is_super_admin` (raw column). Change to `$user?->isSuperAdmin()` to also cover the Spatie role fallback and remain consistent with the rest of the app:
```php
Gate::define('viewHorizon', fn($user = null) => (bool) $user?->isSuperAdmin());
```
This covers both permanent and temporary superadmins. When a temporary account expires and `is_super_admin` is stripped by the scheduler, they lose Horizon access automatically.

**Pulse** (`app/Providers/AuthServiceProvider.php` or a dedicated `PulseServiceProvider`):
No `viewPulse` gate is currently defined — Pulse falls back to local-only. Add an explicit gate:
```php
Gate::define('viewPulse', fn($user = null) => (bool) $user?->isSuperAdmin());
```
The `Authorize` middleware in `config/pulse.php` will then use this gate in all environments.

---

### Step 16 — Pre-commit gates

1. `vendor/bin/pint --dirty --format agent`
2. `php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M`
3. `npm run build`
4. `php artisan test --compact`

---

## Files touched

| File | Change |
|------|--------|
| `app/Providers/AuthServiceProvider.php` | Remove Gate::before; add named Gates |
| `app/Http/Middleware/SuperAdminMiddleware.php` | Add platform.operator gate for directeur |
| `app/Http/Controllers/Api/WorkspaceController.php` | Remove isSuperAdmin branch in index() |
| `app/Http/Controllers/Api/ProjetController.php` | Remove/guard superadmin-all method |
| `app/Http/Controllers/Api/ActiviteController.php` | Remove isSuperAdmin branch |
| `app/Http/Controllers/Api/TacheController.php` | Remove unscoped branches in pending() + overdue() |
| `app/Http/Controllers/Api/AdminController.php` | Accept admin_expires_at + admin_expiry_action in updateUserRole() |
| `app/Models/Projet.php` | Remove isSuperAdmin early-exits in 2 scopes |
| `app/Permissions/ContextualPermissionGate.php` | Remove isSuperAdmin bypass |
| `app/Console/Commands/CreateSuperAdmin.php` | **New** — production bootstrap command |
| `app/Console/Commands/ExpireSuperAdminAccounts.php` | **New** — daily expiry scheduler (also cleans temporary_access records) |
| `app/Console/Kernel.php` | Register daily schedule for ExpireSuperAdminAccounts |
| `database/migrations/xxxx_add_admin_expiry_to_users.php` | **New** — admin_expires_at, admin_expiry_action, created_by, is_system_owner columns + partial unique index |
| `app/Http/Controllers/Api/WorkspaceController.php` (show) | Check temporary_access for valid read-only grant before returning data |
| `app/Http/Controllers/Api/AdminController.php` (new methods) | `myTempSuperadmins()` + `terminate()` endpoints |
| `resources/js/pages/admin/AdminUsers.vue` | Add "Temporary Administrators" section with early-terminate action |
| `routes/api.php` | Remove SA-only projet/activite routes; adjust role route middleware |
| `resources/js/router/index.ts` | Redirect SA to admin; remove SA permission bypass |
| `resources/js/composables/useProjetPermissions.js` | Remove isSuperAdmin\|\| bypasses |
| `resources/js/composables/useTachePermissions.js` | Same |
| `resources/js/composables/useActivitePermissions.js` | Same |
| `resources/js/composables/useActivityPermissions.js` | Same |
| `resources/js/composables/useWorkspacePermissions.js` | Same |
| `resources/js/composables/useProjets.js` | Check + remove if applicable |
| `resources/js/pages/Auth/ForgotPassword.vue` | Fix whatever blocks the flow |
| `resources/js/pages/Auth/ResetPassword.vue` | Fix whatever blocks the flow |
| `resources/js/pages/Others/UserProfile.vue` | Import + wire ChangePasswordModal into Security tab |
| `app/Providers/HorizonServiceProvider.php` | Tighten gate to use `isSuperAdmin()` method |
| `app/Providers/AuthServiceProvider.php` | Add `viewPulse` gate locked to `isSuperAdmin()` |
| `database/migrations/xxxx_create_admin_audit_logs_table.php` | **New** — audit log table |
| `app/Models/AdminAuditLog.php` | **New** — audit log model |
| `app/Services/AdminAuditService.php` | **New** — `log()` helper called from all admin controllers |
| `app/Http/Controllers/Api/AdminController.php` (all actions) | Add `AdminAuditService::log()` calls |
| `app/Http/Controllers/Api/WorkspaceController.php` (show) | Log temp SA workspace reads |
| `app/Http/Controllers/Api/ProjetController.php` (show) | Log temp SA projet reads |
| `app/Http/Controllers/Api/TacheController.php` (show) | Log temp SA tache reads |
| `resources/js/pages/admin/AdminAuditLog.vue` | **New** — platform audit log page (permanent SA only) |
| `resources/js/pages/admin/DirecteurAuditLog.vue` | **New** — scoped audit log for directeur |
| `tests/Feature/SuperAdmin/SuperAdminScopingTest.php` | **New** |
| `tests/Browser/Admin/SuperAdminScopingTest.php` | **New** |

---

## What does NOT change

- `AdminController` platform endpoints (stats, workspaces list with aggregate data, user list, suspend/reactivate/trial) — legitimate operator data, stays as-is
- Sidebar `isSuperAdmin` checks for showing/hiding admin menu items — UI only, not data bypass
- `SuperAdminMiddleware` — stays, protects `/admin/*`
- `RolePermissionSeeder` superadmin creation — kept for dev/test seeding
- The admin dashboard page (`AdminDashboard.vue`) — already correct, no changes needed

---

## Verification

1. Login as superadmin → lands on `/admin/dashboard`, not workspace picker
2. Attempt to navigate to `/workspaces/select` as superadmin → redirected back to `/admin/dashboard`
3. `GET /api/workspaces` as superadmin → `{ data: [] }`
4. `GET /api/projets/list/all` as superadmin → 403
5. Login as regular workspace owner → all existing permissions work normally (no regression)
6. Login as directeur → can call `PATCH /api/admin/users/{user}/role` to promote someone to superadmin
7. `php artisan admin:create-superadmin --email=test@test.com --name=Test --expires-in=7` → user created, temporary password printed, expiry set 7 days out
8. Run `ExpireSuperAdminAccounts` command with an expired account → account suspended or deleted per setting, associated `temporary_access` records deleted
8b. Directeur creates temporary superadmin, selects 2 of their 3 workspaces → superadmin can read those 2, gets 403 on the third, all reads appear in activity log
8c. After account expiry, the 2 `temporary_access` records are also cleaned up
8d. Directeur views "Temporary Administrators" list → sees only accounts they created
8e. Directeur clicks "Early terminate" on an account → account immediately suspended/deleted, `temporary_access` records removed, row disappears from the list
8f. Directeur cannot terminate a superadmin account created by someone else (403)
8g. System owner does not appear in any admin user listing
8h. Attempting to role-change or terminate the system owner via API → 403
8i. Running `admin:create-superadmin --system-owner` twice → second run aborts with clear message
8j. `ExpireSuperAdminAccounts` command skips the system owner even if somehow `admin_expires_at` were set
9. Password reset: click "Forgot password?" on login → enter email → receive link → set new password → redirected to login with success message
10. Password change: go to Profile → Security tab → "Change password" button opens modal → enter current + new password → success toast shown → can log in with new password
10b. Permanent superadmin suspends a workspace → entry appears in `/admin/audit-log` with action `workspace.suspend`, actor name, timestamp, IP
10c. Temporary superadmin reads a project in a granted workspace → entry appears in directeur's `/admin/my-audit-log` with action `projet.read`
10d. Temporary superadmin tries to read a workspace NOT in their grant → 403, no audit log entry created
10e. Directeur visits `/admin/my-audit-log` → sees only their own temporary SA actions, not other admins'
10f. Permanent superadmin visits `/admin/audit-log` → sees all operator actions across all actors
11. Access `/horizon` as a regular workspace user → 403
12. Access `/pulse` as a regular workspace user → 403
13. Access `/horizon` and `/pulse` as superadmin (permanent or temporary with valid expiry) → dashboard loads
14. `php artisan test --compact` — all tests pass
