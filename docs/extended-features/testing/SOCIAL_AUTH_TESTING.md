# Phase 2 — Social Auth (Google) Testing Guide

> Manual test guide. Requires a real Google OAuth app configured in `.env`.
> Automated tests mock Socialite and pass without credentials.

---

## Prerequisites

1. Google OAuth 2.0 credentials set in `.env`:
   ```
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
   ```
2. In Google Cloud Console, add `http://localhost:8000/api/auth/google/callback` as an authorised redirect URI.
3. `php artisan serve` + `npm run dev` running.

---

## 1 — New user via Google (sign-up flow)

| # | Action | Expected |
|---|---|---|
| 1.1 | Go to `/signin`, click "Sign in with Google" | Redirected to Google's consent screen |
| 1.2 | Choose a Google account that does NOT exist in the app | Authorise the app |
| 1.3 | After authorisation | Redirected to `/auth/callback`, brief spinner, then to `/` |
| 1.4 | Check profile | User created with `provider=google`, avatar from Google, `nom`/`prenom` from Google name |
| 1.5 | Check that password login for this email is not possible (optional) | Login with email/password fails (no password set) |

---

## 2 — Existing account linking

| # | Action | Expected |
|---|---|---|
| 2.1 | Register normally with email `test@gmail.com` | Account exists without `provider` |
| 2.2 | Click "Sign in with Google" and choose the same `test@gmail.com` account | After authorisation, redirected to `/` and logged in |
| 2.3 | Check DB | `provider=google` and `provider_id` are now set on the existing user — no duplicate created |

---

## 3 — Sign-up page Google button

| # | Action | Expected |
|---|---|---|
| 3.1 | Go to `/signup`, click "Sign up with Google" | Redirected to Google's consent screen (same flow as sign-in) |

---

## 4 — Inactive account

| # | Action | Expected |
|---|---|---|
| 4.1 | Set `is_active=false` on a user that has a Google account | Attempt Google sign-in with that account |
| 4.2 | After Google authorises | Redirected to `/signin?error=account_inactive` |

---

## Negative cases

| Scenario | Expected |
|---|---|
| User denies Google consent | Redirected to `/signin?error=social_auth_failed` |
| `GOOGLE_CLIENT_ID` not set / invalid | 500 or redirect to error page |
| Same Google account signs in twice | Same user returned (no second account created) |

---

## Notes

- Automated tests mock Socialite — they pass without any `.env` credentials.
- The `SocialCallback.vue` page handles the token hand-off; it reads `?token=` and `?expires_at=` from the redirect URL.
