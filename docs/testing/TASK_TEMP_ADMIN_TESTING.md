# Testing — Temporary Admin Account Feature

**Branch:** `feature/superadmin-scoping`
**Files under test:**
- `app/Http/Controllers/Api/AdminController.php` — `createTempAdmin()`, `sendTempAdminCredentials()`, `myTempSuperadmins()`, `terminate()`
- `app/Http/Controllers/Api/WorkspaceController.php` — `switch()` (auto-provision workspace_members)
- `app/Console/Commands/ExpireSuperAdminAccounts.php` — expiry cleanup
- `app/Notifications/TempAdminAccessGrantedNotification.php`
- `resources/js/pages/workspace/WorkspaceTempAdmin.vue`
- `database/migrations/2026_06_26_152034_add_is_temp_access_to_workspace_members.php`
- `routes/api.php` — `POST /api/admin/temp-admins`, `POST /api/admin/temp-admins/{user}/send-credentials`

---

## Feature Summary

A directeur can create a brand-new temporary superadmin account from the `/workspace/admin-account` page:
- Fills in **nom**, **email**, selects one or more **workspaces they own**, picks a **workspace role** (`observateur` / `cadre` / `manager`), and sets **expiry duration** + **expiry action**.
- On submit: a new `User` is created with `is_super_admin = true`, `admin_expires_at`, `admin_expiry_action`, and `created_by` set; Spatie role `super_admin` is synced; one `temporary_access` row per selected workspace is inserted with the chosen role name.
- **No notification is sent automatically** — credentials must be sent manually via the per-row "Envoyer les identifiants" button, which generates a new password and sends `TempAdminAccessGrantedNotification`.
- On **first workspace access** (`WorkspaceController::switch()`), the system auto-provisions a `workspace_members` row using the role from `temporary_access`, flagged `is_temp_access = true`.
- On **revocation or expiry**, both `temporary_access` and `workspace_members` rows with `is_temp_access = true` are deleted.
- Every action taken by a temp admin is traced via `spatie/laravel-activitylog`.

---

## PHPUnit Feature Tests

**File:** `tests/Feature/SuperAdmin/TempAdminTest.php`
**Run:** `./vendor/bin/phpunit tests/Feature/SuperAdmin/TempAdminTest.php --testdox`
**Result:** 15 tests, 33 assertions — all pass.

### Tests covered

| Test | Scenario | Expected |
|------|----------|----------|
| `directeur_can_create_temp_admin_for_own_workspace` | Happy path — directeur owns the workspace | 201, user created with `is_super_admin=true`, `temporary_access` row with `role=observateur` inserted |
| `directeur_can_grant_access_to_multiple_own_workspaces` | Two owned workspaces | 201, 2 `temporary_access` rows |
| `directeur_cannot_grant_access_to_workspace_they_do_not_own` | Workspace owned by someone else | 403, user not created |
| `create_temp_admin_requires_at_least_one_workspace` | `workspace_ids: []` | 422 with `workspace_ids` validation error |
| `create_temp_admin_fails_when_email_already_exists` | Duplicate email | 422 with `email` validation error |
| `creation_does_not_send_notification_automatically` | Happy path | `Notification::assertNothingSent()` |
| `unauthenticated_user_cannot_create_temp_admin` | No auth | 401 |
| `regular_user_cannot_create_temp_admin` | Role `utilisateur` | 403 |
| `directeur_can_send_credentials_for_own_temp_admin` | Happy path | 200, `TempAdminAccessGrantedNotification` sent |
| `sending_credentials_resets_the_password` | Send credentials | Password hash changes |
| `directeur_cannot_send_credentials_for_another_directeurs_temp_admin` | Cross-directeur | 403, no notification sent |
| `cannot_send_credentials_for_non_temp_admin` | Regular user targeted | 422 |
| `workspace_role_stored_in_temporary_access` *(new)* | `workspace_role: cadre` sent in request | `temporary_access.role = cadre` |
| `workspace_role_defaults_to_observateur` *(new)* | No `workspace_role` in request | `temporary_access.role = observateur` |
| `directeur_can_terminate_own_temp_admin` *(new)* | Revoke call | `temporary_access` + `workspace_members` (is_temp_access) rows deleted |

### Known issue — parallel test execution

`php artisan test` with multiple test files in the same run can cause MySQL deadlocks (error 1213) on `migrate:fresh`. This is a test environment concurrency issue, not a code bug. Run this file alone or use `./vendor/bin/phpunit` (single process) to avoid it.

---

## Dusk Browser Tests

**File:** `tests/Browser/Admin/TempAdminTest.php`
**Run:** `php artisan dusk tests/Browser/Admin/TempAdminTest.php`
**Screenshots:** `tests/Browser/screenshots/temp-admin/`

### Tests covered

**File:** `tests/Browser/Admin/TempAdminTest.php`

| Test | Scenario |
|------|----------|
| `test_page_loads_for_directeur` | `/workspace/admin-account` loads for a directeur, "open-promote-modal" button visible |
| `test_submit_button_disabled_until_all_fields_filled` | Submit disabled on empty form, still disabled after only nom is filled |
| `test_create_temp_admin_shows_success_message` | Full form fill + workspace checkbox → submit → success message visible |
| `test_send_credentials_button_visible_on_admin_row` | Existing temp admin row has `send-credentials-{id}` button |
| `test_terminate_button_visible_on_admin_row` | Existing temp admin row has `terminate-admin-{id}` button |
| `test_non_directeur_sees_access_denied` | Regular user sees no "open-promote-modal" button |

### Custom permissions toggle (section C)

**File:** `tests/Browser/Admin/TempAdminCustomPermissionsTest.php`

| Test | Scenario |
|------|----------|
| `test_toggle_is_off_by_default_and_panel_hidden` | Modal opens with toggle off; role select + permissions panel both absent |
| `test_enabling_toggle_shows_role_selector_and_panel` | Clicking toggle shows role selector and permissions panel |
| `test_disabling_toggle_hides_panel_again` | Toggle on then off → panel disappears again |
| `test_role_change_updates_prechecked_permissions` | Switch from observateur→cadre; `workspaces.invite_member` becomes checked |
| `test_creation_with_unchanged_permissions_stores_no_custom` | Toggle on but permissions left at role defaults → `temporary_access.custom_permissions` is NULL |
| `test_creation_with_custom_permissions_stores_them` | Toggle on, cadre role, uncheck `workspaces.invite_member` → JSON stored in DB, `workspaces.view` present, `workspaces.invite_member` absent |
| `test_custom_badge_visible_for_admin_with_custom_permissions` | Row for admin with custom_permissions shows amber "custom" badge |
| `test_no_custom_badge_for_admin_without_custom_permissions` | Row for standard admin has no `custom-badge-{id}` element |

---

## Bugs found and fixed during testing

| Bug | Root cause | Fix |
|-----|-----------|-----|
| `updated_at` column not found in `temporary_access` | Table has no `updated_at` column; controller + test helpers were inserting it | Removed `'updated_at' => now()` from controller and both test helpers |
| Feature tests deadlocked (MySQL error 1213) | `php artisan test` runs multiple `migrate:fresh` in parallel against the same test DB | Run with `./vendor/bin/phpunit` (single process); not a code issue |
| Temp admin `/unauthorized` on every resource | `WorkspaceController::switch()` was not provisioning a `workspace_members` row → no Spatie role assigned | Auto-insert `workspace_members` row on switch, using role from `temporary_access.role`, flagged `is_temp_access=true` |
| `isSuperAdmin()` returned false for temp admins | `isSuperAdmin()` PHP method intentionally returns false for expirable accounts | Use raw `$user->is_super_admin` column for temp-admin detection in controllers |
| Workspace picker empty for temp admin | `getUserWorkspaces()` used `isSuperAdmin()` (false) so branch never fired; also called wrong endpoint `/user-workspaces` instead of `/workspaces/user-workspaces` | Fixed method to use raw `is_super_admin`; fixed Vue endpoint |
| `myTempSuperadmins()` response had stale `role='readonly'` filter | Old filter excluded rows when role changed to `observateur/cadre/manager` | Removed `->where('role', 'readonly')` filter; added `workspace_role` field from `temporary_access` |
| Terminate endpoint left orphaned `workspace_members` rows | Cleanup only deleted `temporary_access` and didn't touch provisioned memberships | Added `workspace_members` cleanup with `is_temp_access=true` filter to both `terminate()` and `ExpireSuperAdminAccounts` command |
