# Phase 11C — Session Management — Manual Testing Guide

Real session list from Sanctum tokens, functional `logoutAllDevices()`, and inactivity-timeout
picker. See [`docs/phase11-dashboard-profile/PLAN.md`](../phase11-dashboard-profile/PLAN.md)
(Part B — B1) for the design.

**Key design note:** `AuthService::issueToken()` deletes all previous `auth_token` tokens on
every login — there is at most one active session per user at any time. "Logout all devices"
therefore behaves identically to a normal logout (there are no other devices to revoke).

---

## Prerequisites

- `php artisan serve` running, `npm run build` (or `npm run dev`) done
- Logged-in user account
- Browser dev tools (Network tab)

---

## TC-1 — Active sessions list shows the current token

**Steps:**
1. Go to `/profile` → "Sécurité" tab → scroll to "Sessions actives" section

**Expected:**
- One row appears with label "Cet appareil" + green "Actif" badge
- "Connecté depuis" shows the login timestamp
- "Dernière activité" shows the last API call time (or "Jamais utilisé" if token was never used
  after creation)

---

## TC-2 — Sessions list fetches from the real API

**Steps:**
1. Open Network tab, go to the Security tab in profile
2. Watch for a `GET /api/users/sessions` call

**Expected:**
- Request fires on mount with status 200
- Response has `{ success: true, data: [...] }` shape
- The returned token has `is_current: true`

---

## TC-3 — Revoke button triggers logout

**Steps:**
1. On the Sessions section, click "Révoquer" on the current session row
2. Confirm the dialog

**Expected:**
- User is logged out and redirected to `/signin`
- The Sanctum token is deleted server-side (logging back in issues a new token)

---

## TC-4 — "Logout from all devices" triggers logout

**Steps:**
1. Click "Déconnexion de tous les appareils" button
2. Confirm

**Expected:**
- Same as TC-3 — user is logged out (single-session model means "all devices" = current device)

---

## TC-5 — Inactivity timeout picker works

**Steps:**
1. Select a different timeout (e.g. "15 minutes")
2. Stay idle

**Expected:**
- After the configured inactivity duration the session-timeout warning appears, then auto-logout

---

## TC-6 — Stagger animation on sessions list

**Steps:**
1. Hard-reload the page and navigate to the Security tab

**Expected:**
- Session rows appear with the staggered CSS delay animation (each row fades/slides in
  sequentially, ~50ms between items)

---

## Automated Tests

| Suite | File | Coverage |
|---|---|---|
| PHPUnit Feature | `tests/Feature/UserSessionsTest.php` | 401 for unauthenticated; 200 + correct shape for authenticated; `is_current` flag; cross-user isolation; only `auth_token`-named tokens returned |

Run with:
```bash
php artisan test --compact tests/Feature/UserSessionsTest.php
```

---

## Cleanup

None — no test data created beyond normal login.
