# Step C Testing — Session Refresh Fix

## What was fixed

| # | Bug | Fix |
|---|-----|-----|
| C1 | `refreshToken()` store action didn't update `localStorage.token_expires_at` | Added `localStorage.setItem('token_expires_at', expires_at)` after refresh |
| C2 | Clicking refresh with an already-expired token triggered a 401 then logout | Added expired-token guard in `refreshSession()` — calls `logout()` directly |
| C3 | `AuthService::refreshToken()` always issued 24h tokens, losing remember-me | Now detects old token expiry and issues 15-day token if remember-me was active |

---

## Manual Test Cases

### TC-C-01 — "Actualiser la session" keeps the user logged in

**Steps:**
1. Sign in normally.
2. Open Profile → Security tab.
3. Click "Actualiser la session".

**Expected:** User stays on the page (no redirect to `/signin`). A new token is issued silently. `localStorage.auth_token` changes value in DevTools → Application → Local Storage.

---

### TC-C-02 — `token_expires_at` is updated after refresh

**Steps:**
1. Open DevTools → Application → Local Storage → note the value of `token_expires_at`.
2. Click "Actualiser la session".

**Expected:** `token_expires_at` value updates to approximately now + 24 hours (or + 15 days if signed in with "Se souvenir de moi").

---

### TC-C-03 — Remember-me duration is preserved after refresh

**Steps:**
1. Sign in with "Se souvenir de moi" checked.
2. Check `token_expires_at` in LocalStorage — should be ~15 days from now.
3. Click "Actualiser la session".

**Expected:** New `token_expires_at` is still ~15 days from now (not reduced to 24 hours).

---

### TC-C-04 — Normal login duration is preserved after refresh

**Steps:**
1. Sign in without "Se souvenir de moi".
2. Check `token_expires_at` — should be ~24 hours from now.
3. Click "Actualiser la session".

**Expected:** New `token_expires_at` is ~24 hours from now.

---

### TC-C-05 — PHPUnit: `refresh_returns_new_token_and_200`

Run: `php artisan test --compact --filter=refresh_returns_new_token_and_200`

Expected: PASS

---

### TC-C-06 — PHPUnit: `refresh_preserves_remember_me_duration`

Run: `php artisan test --compact --filter=refresh_preserves_remember_me_duration`

Expected: PASS

---

### TC-C-07 — PHPUnit: `refresh_gives_24h_for_normal_login`

Run: `php artisan test --compact --filter=refresh_gives_24h_for_normal_login`

Expected: PASS

---

### TC-C-08 — PHPUnit: `refresh_with_expired_token_returns_401`

Run: `php artisan test --compact --filter=refresh_with_expired_token_returns_401`

Expected: PASS
