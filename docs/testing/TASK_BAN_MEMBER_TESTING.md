# Testing — Workspace Ban/Member Management Feature

**Branch:** `feature/superadmin-scoping`
**Commit:** `e8458f0`
**Files under test:**
- `app/Http/Controllers/Api/WorkspaceController.php` — `banMember()`, `unbanMember()`
- `app/Policies/WorkspacePolicy.php` — `banMember()`, `viewMembers()`
- `app/Notifications/WorkspaceMemberBannedNotification.php`
- `app/Notifications/WorkspaceInvitationAcceptedNotification.php`
- `resources/js/pages/workspace/WorkspaceMembers.vue`
- `resources/js/components/layout/AppSidebar.vue` — "Gestion du workspace" section
- `routes/api.php` — `POST /api/workspaces/{workspace}/members/{user}/ban`, `DELETE` same URL

---

## Feature Summary

The workspace owner can manage members and ban/unban them from `/workspace/members`.

### Workspace Members page (`/workspace/members`)

Two tabs:
1. **Membres** — lists all workspace members with their role badge, status badge ("Actif" / "Banni"), and action buttons (Ban / Lever le ban depending on status). The owner cannot ban themselves.
2. **Invitations en attente** — lists pending invitations with Resend and Cancel actions.

### Sidebar — "Gestion du workspace" section

A new collapsible sidebar section **"Gestion du workspace"** appears for the `directeur` (workspace owner) only. It contains:
- **Membres & Invitations** → `/workspace/members`
- **Compte admin temporaire** → `/workspace/admin-account`

Superadmins do **not** see this section (they use the Administration section instead).

### Ban/Unban API

| Action | Method | URL | Permission |
|--------|--------|-----|-----------|
| Ban a member | `POST` | `/api/workspaces/{workspace}/members/{user}/ban` | `workspaces.ban_member` (owner only) |
| Unban a member | `DELETE` | `/api/workspaces/{workspace}/members/{user}/ban` | `workspaces.ban_member` (owner only) |

On ban: sets `banned_at`, `banned_by`, `ban_reason` on the `workspace_members` pivot. Sends `WorkspaceMemberBannedNotification` to the banned user and logs the action via `AdminAuditService`.

On unban: clears `banned_at`, `banned_by`, `ban_reason`.

---

## Prerequisites

1. Database seeded: `php artisan db:seed --class=RolePermissionSeeder`
2. Backend running: `php artisan serve`
3. Frontend built: `npm run build` (or `npm run dev`)
4. At least one workspace with the test user as owner and one or more members

---

## PHPUnit Feature Tests

**File:** `tests/Feature/Workspace/WorkspaceBanMemberTest.php`
**Run:** `php artisan test --compact --filter=WorkspaceBanMember`
**Result:** 11 tests, all pass.

| Test | Scenario | Expected |
|------|----------|----------|
| `owner_can_ban_a_member` | Owner bans a member with a reason | 200, `banned_at` set in DB |
| `ban_sends_notification_to_banned_user` | Owner bans a member | `WorkspaceMemberBannedNotification` sent to banned user |
| `owner_can_ban_without_reason` | No `ban_reason` in request | 200, `banned_at` set, `ban_reason` null |
| `non_owner_cannot_ban_a_member` | Manager tries to ban | 403 |
| `owner_cannot_ban_themselves` | Owner targets own ID | 422 |
| `cannot_ban_already_banned_member` | Ban already-banned pivot | 409 |
| `cannot_ban_non_member` | Target has no pivot row | 404 |
| `owner_can_unban_a_banned_member` | Unban call on banned user | 200, `banned_at` null in DB |
| `non_owner_cannot_unban_a_member` | Manager tries to unban | 403 |
| `cannot_unban_non_banned_member` | Unban on active member | 409 |
| `invitation_accepted_notifies_inviter` | Member accepts invitation | `WorkspaceInvitationAcceptedNotification` sent to inviter |

---

## Manual Test Cases

### TC-BAN-01 — Sidebar visibility (owner)

**Prerequisites:** Logged in as workspace owner (directeur).

**Steps:**
1. Open the application sidebar.
2. Look for the "Gestion du workspace" section.

**Expected:** "Gestion du workspace" section is visible with two entries: "Membres & Invitations" and "Compte admin temporaire".

---

### TC-BAN-02 — Sidebar hidden (non-owner)

**Prerequisites:** Logged in as a workspace manager or collaborateur.

**Steps:**
1. Open the application sidebar.

**Expected:** No "Gestion du workspace" section visible.

---

### TC-BAN-03 — Sidebar hidden (superadmin)

**Prerequisites:** Logged in as a superadmin (platform admin).

**Steps:**
1. Open the application sidebar.

**Expected:** No "Gestion du workspace" section (superadmin uses the "Administration" section instead).

---

### TC-BAN-04 — Navigate to Members page

**Prerequisites:** Logged in as workspace owner.

**Steps:**
1. Click "Membres & Invitations" in the sidebar.

**Expected:** Navigates to `/workspace/members`. Page shows two tabs: "Membres" and "Invitations en attente". Member list loads with each member showing their role badge and an "Actif" status badge.

---

### TC-BAN-05 — Access denied for non-owner

**Prerequisites:** Logged in as workspace manager.

**Steps:**
1. Navigate directly to `/workspace/members`.

**Expected:** Page shows an "Accès restreint" (restricted access) message instead of the member list.

---

### TC-BAN-06 — Happy path ban

**Prerequisites:** Logged in as workspace owner. At least one other member exists in the workspace.

**Steps:**
1. Navigate to `/workspace/members`.
2. In the "Membres" tab, find a member row.
3. Click the "Bannir" button on that row.
4. A confirmation modal appears — enter an optional ban reason.
5. Click "Confirmer le bannissement".

**Expected:**
- Modal closes.
- The member's status badge changes from "Actif" to "Banni".
- The action button changes to "Lever le ban".
- A success toast appears.
- In the database, the `workspace_members` row has `banned_at` set, `banned_by = owner_id`, `ban_reason` populated.
- The banned user receives a `WorkspaceMemberBannedNotification` (in-app and email).

---

### TC-BAN-07 — Ban without reason

**Prerequisites:** Same as TC-BAN-06.

**Steps:**
1. Click "Bannir" on a member row.
2. Leave the reason field empty.
3. Click "Confirmer le bannissement".

**Expected:** Ban succeeds (reason field is optional). `ban_reason` is null in DB.

---

### TC-BAN-08 — Owner cannot ban themselves

**Prerequisites:** Logged in as workspace owner.

**Steps:**
1. Navigate to `/workspace/members`.

**Expected:** The owner's own row has no "Bannir" button (or the button is disabled/absent).

**Verify via API:** `POST /api/workspaces/{id}/members/{owner_id}/ban` returns 422.

---

### TC-BAN-09 — Happy path unban

**Prerequisites:** Logged in as workspace owner. At least one member is currently banned.

**Steps:**
1. Navigate to `/workspace/members`.
2. Find a member with the "Banni" badge.
3. Click "Lever le ban".

**Expected:**
- The status badge changes from "Banni" to "Actif".
- The action button changes to "Bannir".
- A success toast appears.
- In the database, `banned_at`, `banned_by`, `ban_reason` are all null on the `workspace_members` row.

---

### TC-BAN-10 — Pending invitations tab

**Prerequisites:** Logged in as workspace owner. At least one pending invitation exists.

**Steps:**
1. Navigate to `/workspace/members`.
2. Click the "Invitations en attente" tab.

**Expected:** Pending invitations are listed with the invitee's email, the invite date, and two buttons: "Renvoyer" (resend) and "Annuler".

---

### TC-BAN-11 — Resend invitation

**Prerequisites:** Same as TC-BAN-10.

**Steps:**
1. On the "Invitations en attente" tab, click "Renvoyer" on any invitation row.

**Expected:** A success toast appears. The invitation's `invited_at` timestamp is updated in the database.

---

### TC-BAN-12 — Cancel invitation

**Prerequisites:** Same as TC-BAN-10.

**Steps:**
1. On the "Invitations en attente" tab, click "Annuler" on any invitation row.

**Expected:** The invitation row disappears from the list. The `workspace_invitations` record is deleted from the database.

---

## Negative Cases

| Case | How to trigger | Expected |
|------|---------------|----------|
| Non-owner tries to ban via API | Send `POST .../ban` as manager | 403 Forbidden |
| Non-owner tries to unban via API | Send `DELETE .../ban` as manager | 403 Forbidden |
| Owner bans themselves via API | Target own user ID | 422 Unprocessable |
| Ban already-banned member via API | Ban twice | 409 Conflict |
| Unban non-banned member via API | Unban without prior ban | 409 Conflict |
| Ban non-member via API | Target user not in workspace | 404 Not Found |
| Non-owner navigates to `/workspace/members` | Direct URL visit | "Accès restreint" shown in UI |

---

## Bugs Fixed During Implementation

| Bug | Root cause | Fix |
|-----|-----------|-----|
| `banMember()` received empty `User` model | Route parameter named `{user}` but controller param was `$target` — Laravel implicit binding couldn't match | Renamed controller parameter from `$target` to `$user` |
| `created_by` not stored on new temp superadmin accounts | `updateUserRole()` checked `$validated['created_by']` which is never in the request; should use `$actor->id` | Fixed to always store `created_by = $actor->id` when `is_super_admin = true` |
