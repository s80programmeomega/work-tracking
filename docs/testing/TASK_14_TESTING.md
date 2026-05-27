# Task 14 — Manual Testing Guide

Platform super admin dashboard: stats, workspace management, user list, trial extension, suspend/reactivate.

---

## Prerequisites

- A super admin account (`is_super_admin = true`)
- At least 3 workspaces (mix of trial/paid, active/inactive)
- At least 2 regular users
- Mail configured (or check logs for email content)

---

## TC-1 — Non-super-admin cannot access admin pages

**Steps:**
1. Log in as a regular user (not super admin)
2. Navigate to `/admin/dashboard`

**Expected:**
- Redirected to 404 error page (Vue router guard fires)
- `GET /api/admin/stats` returns 403

---

## TC-2 — Admin sidebar section hidden for regular users

**Steps:**
1. Log in as a regular user
2. Inspect the sidebar

**Expected:**
- No "Administration" section appears in the sidebar

---

## TC-3 — Super admin sees admin sidebar section

**Steps:**
1. Log in as super admin
2. Inspect the sidebar

**Expected:**
- "Administration" section visible with items: Platform Dashboard, Workspaces, Utilisateurs

---

## TC-4 — Admin dashboard loads with stats

**Steps:**
1. Log in as super admin
2. Navigate to `/admin/dashboard`

**Expected:**
- Page renders with stat cards: Total Workspaces, Active, Trial, Paid, Expired Trials, Expiring Soon
- User stats: Total Users, Active (last 30 days)
- Recent workspaces table shows up to 10 entries with owner name, mode badge, status

---

## TC-5 — Admin workspaces page with filters

**Steps:**
1. Navigate to `/admin/workspaces`
2. Search for a workspace by partial name
3. Filter by `subscription_mode = trial`
4. Filter by `is_active = false`

**Expected:**
- Table updates on each filter change
- Results match the applied filter
- Pagination controls appear when more than 20 workspaces exist

---

## TC-6 — Extend trial for a workspace

**Steps:**
1. On `/admin/workspaces`, find a trial workspace
2. Click "Prolonger l'essai" (or equivalent action)
3. Enter `60` days and confirm

**Expected:**
- `POST /api/admin/workspaces/{id}/extend-trial` returns 200
- Response `data` includes `trial_expired: false`
- Database: `trial_duration_days` updated to 60
- Workspace owner receives in-app + email notification of type `trial_extended`
- Log line: `Essai prolongé par super-admin` with admin_id, old_duration, new_duration

---

## TC-7 — Suspend a workspace

**Steps:**
1. On `/admin/workspaces`, find an active workspace
2. Click "Suspendre"
3. Enter a reason ("Policy violation") and confirm

**Expected:**
- `POST /api/admin/workspaces/{id}/suspend` returns 200
- Database: `is_active` set to `false`
- Workspace owner receives in-app + email notification of type `workspace_suspended`
- Log line: `Workspace suspendu par super-admin` with reason

---

## TC-8 — Reactivate a suspended workspace

**Steps:**
1. On `/admin/workspaces`, find an inactive workspace (is_active = false)
2. Click "Réactiver"

**Expected:**
- `POST /api/admin/workspaces/{id}/reactivate` returns 200
- Database: `is_active` set to `true`
- Workspace status badge updates in the table
- Log line: `Workspace réactivé par super-admin`

---

## TC-9 — Admin users page with search

**Steps:**
1. Navigate to `/admin/users`
2. Search for a user by partial name or email

**Expected:**
- Table filters to matching users
- Each row shows: name, email, super_admin badge (if applicable), current workspace, last login, registration date

---

## TC-10 — Extend trial validation (bad input)

**Steps:**
1. Attempt `POST /api/admin/workspaces/{id}/extend-trial` with `{ "trial_duration_days": 0 }`

**Expected:**
- Response 422 with validation error: `trial_duration_days` must be at least 1

---

## TC-11 — Suspend without reason (optional field)

**Steps:**
1. Suspend a workspace without entering a reason

**Expected:**
- Suspension succeeds (reason is nullable)
- Notification sent without a reason line in the email body

---

## Automated Tests

| Suite | File | Count |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Admin/PlatformDashboardTest.php` | 11 |

Run with:
```bash
php artisan test --compact tests/Feature/Admin/
```
