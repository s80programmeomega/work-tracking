# Step A Testing — Multi-Session Support

## What was changed

| # | Change | Detail |
|---|--------|--------|
| A1 | Removed single-session deletion from `issueToken()` | Logging in on a second device no longer kills the first session |
| A2 | Added `AuthService::logoutAll()` | Deletes all `auth_token` rows for the current user |
| A3 | Added `AuthController::logoutAll()` | `POST /api/auth/logout-all` |
| A4 | Added `AuthController::revokeSession()` | `DELETE /api/auth/sessions/{tokenId}` — validates ownership, sends notification if not current |
| A5 | Added `SessionRevokedNotification` | Mail + database notification on remote session revocation |
| A6 | Fixed `SessionSettings.vue::revokeSession()` | Per-session logic: current → logout, other → revokeSession API call |
| A7 | Fixed `authStore::logoutAllDevices()` | Now calls real API instead of just `logout()` |
| A8 | Added `authStore::revokeSession(sessionId)` | Calls revoke API; redirects if current session was revoked |

---

## Manual Test Cases

### TC-A-01 — Login on two devices keeps both sessions

**Steps:**
1. Sign in on Browser A (e.g. Chrome).
2. Sign in on Browser B (e.g. Firefox / incognito) with the same credentials.
3. Return to Browser A and open Profile → Security → Sessions actives.

**Expected:** Two session rows appear in the list.

---

### TC-A-02 — "Déconnecter tous les appareils" kills all sessions

**Steps:**
1. Sign in on two browsers (Chrome + Firefox).
2. In Browser A, open Profile → Security → click "Déconnecter tous les appareils".
3. Confirm in the dialog.
4. Switch to Browser B and try to navigate.

**Expected:** Browser A redirects to `/signin`. Browser B gets a 401 on the next API call and also redirects to `/signin`.

---

### TC-A-03 — Revoking a non-current session removes it from the list

**Steps:**
1. Sign in on Browser A and Browser B.
2. In Browser A, click "Révoquer" on the row that is NOT marked "Actif".
3. Confirm.

**Expected:** The row disappears from Browser A's sessions list. Browser A stays logged in. Browser B loses its session on next request.

---

### TC-A-04 — Revoking current session logs out this device

**Steps:**
1. Sign in and open Profile → Security.
2. Click "Révoquer" on the row marked "● Actif".
3. Confirm.

**Expected:** Redirect to `/signin`.

---

### TC-A-05 — Session revocation sends email notification

**Steps:**
1. Sign in on Browser A and Browser B.
2. In Browser A, revoke Browser B's session.
3. Check the user's email inbox (or database notifications table).

**Expected:** A "Une session a été révoquée" email/notification is delivered. No notification when revoking your own session.

---

### TC-A-06 — PHPUnit suite (7 tests)

Run: `php artisan test --compact tests/Feature/MultiSessionTest.php`

Expected: 7 passed.

---

### TC-A-07 — Cannot revoke another user's session (security gate)

**Steps:**
1. Create two accounts. Sign in as User A.
2. Via DevTools, obtain User B's token ID from the sessions list (if accessible).
3. Attempt `DELETE /api/auth/sessions/{userB_tokenId}` with User A's Bearer token.

**Expected:** 404 response. User B's session is untouched.
