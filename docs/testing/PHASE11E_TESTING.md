# Phase 11E Testing Guide — Activity Tabs + Users Management

## Prerequisites

- Database seeded with `RolePermissionSeeder`
- At least one owner user with a workspace containing 2+ members
- Super-admin user for admin panel tests

---

## B5 — Profile Activity Tab (real data)

### Test 1: Own activity tab loads without errors

**Steps:**
1. Log in as any user.
2. Navigate to `/profile`.
3. Click the "Activité" tab.

**Expected:** Spinner appears briefly, then either the activity timeline or the empty state ("Aucune activité récente"). No random mock data.

### Test 2: Activity list filters work

**Steps:**
1. Perform a few actions (create project, update task) to generate activity.
2. Navigate to `/profile` → Activité tab.
3. Try each time filter (Aujourd'hui, Cette semaine, Ce mois, Tout).
4. Try the type filter (Tâches, Projets, etc.).

**Expected:** List re-filters on each click; count badge updates correctly.

### Test 3: Refresh button reloads from API

**Steps:**
1. Navigate to `/profile` → Activité tab.
2. Click the refresh icon.

**Expected:** Spinner appears, list reloads from `GET /api/users/{id}/activity`.

### Negative: Other user's activity returns 403

**Steps:**
1. Log in as a non-super-admin user.
2. Try `GET /api/users/{other_user_id}/activity` (e.g. via browser console or Postman).

**Expected:** 403 Forbidden.

---

## B6a — Admin Users: per-user activity drill-down

### Test 4: Activity button appears per row

**Steps:**
1. Log in as super-admin.
2. Navigate to `/admin/users`.

**Expected:** Each user row has a purple "Activité" button.

### Test 5: Clicking Activity opens modal with ActivityLogTab

**Steps:**
1. Log in as super-admin.
2. Click "Activité" on any user row.

**Expected:** Modal opens with the activity log tab pre-filtered to that user (author filter locked, no autocomplete picker visible).

### Test 6: Closing modal dismisses it

**Steps:**
1. Open activity modal (step above).
2. Click the × button.

**Expected:** Modal closes cleanly.

---

## B6b — Workspace Users page

### Test 7: Owner can access /workspace/users

**Steps:**
1. Log in as workspace owner.
2. Navigate to `/workspace/users`.

**Expected:** Page loads with member table showing workspace members with name, email, role, joined date.

### Test 8: Sidebar "Utilisateurs" link is visible for owners

**Steps:**
1. Log in as workspace owner.
2. Inspect the sidebar.

**Expected:** "Utilisateurs" entry appears under the workspace section. Not visible to collaborateurs/stagiaires.

### Test 9: Search filters members

**Steps:**
1. Navigate to `/workspace/users`.
2. Type part of a member's name in the search box.

**Expected:** Table updates after 350ms debounce to show only matching members.

### Test 10: Activity button per row opens modal

**Steps:**
1. Navigate to `/workspace/users`.
2. Click "Activité" on any member.

**Expected:** Activity modal opens showing that member's activity log (causer locked to their name).

### Negative: Non-member gets 403 on endpoint

**Steps:**
1. Log in as any user not in the workspace.
2. `GET /api/workspaces/{id}/users`

**Expected:** 403 Forbidden.

### Negative: Collaborateur gets 403

**Steps:**
1. Log in as a collaborateur member of the workspace.
2. Navigate to `/workspace/users`.

**Expected:** 403 / redirect (no `WORKSPACES_VIEW_MEMBERS` permission for collaborateur).

---

## Cleanup

- No migrations to roll back.
- No seeded test data to remove (tests use `RefreshDatabase`).
