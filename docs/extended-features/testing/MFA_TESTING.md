# Phase 1 — MFA Testing Guide

> Manual test guide for Phase 1: TOTP authenticator-app + recovery codes + email-OTP fallback.
> Run after `php artisan serve` + `npm run dev` + queue worker (`php artisan queue:work`).

---

## Prerequisites

- A user account exists in the app (or create one via the signup flow).
- An authenticator app is installed on a mobile device (Google Authenticator, Authy, etc.).
- Mail is configured (or `MAIL_MAILER=log` in `.env` — check `storage/logs/laravel.log` for sent emails).
- Queue worker running: `php artisan queue:work` (email-OTP is queued).

---

## 1 — Enable TOTP (QR code flow)

| # | Action | Expected result |
|---|---|---|
| 1.1 | Sign in and open **Profile → Security** tab | "Two-Factor Authentication" panel is visible with status "Not configured" |
| 1.2 | Click **Enable** next to "Authenticator app" | A QR code appears along with a manual setup key |
| 1.3 | Scan the QR code with the authenticator app | App shows a 6-digit code rotating every 30s |
| 1.4 | Enter the 6-digit code in the "Confirmation code" field and click **Confirm** | Panel status changes to "Active"; recovery codes panel appears |
| 1.5 | Copy all recovery codes and store them safely | Codes are displayed once — regeneration is available |

---

## 2 — Login with TOTP active

| # | Action | Expected result |
|---|---|---|
| 2.1 | Sign out, then go to the sign-in page | Standard login form shown |
| 2.2 | Enter correct email + password and submit | MFA challenge screen appears ("Enter the code from your authenticator app") |
| 2.3 | Enter the current 6-digit TOTP code and click **Verify** | Successful login, redirected to `/` |
| 2.4 | Repeat login but enter an incorrect 6-digit code | Error: "Invalid or expired code" |
| 2.5 | Wait for the TOTP code to cycle (30s) and use the new code | Login succeeds |

---

## 3 — Login with a recovery code

| # | Action | Expected result |
|---|---|---|
| 3.1 | Sign out, start login, reach the MFA challenge screen | Challenge screen shown |
| 3.2 | Click **Use a recovery code** | Input type switches; placeholder changes |
| 3.3 | Enter one of the saved recovery codes and click **Verify** | Login succeeds; that code is now consumed |
| 3.4 | Try to use the same recovery code again | Error: "Invalid or expired code" (single-use enforced) |

---

## 4 — Email OTP

| # | Action | Expected result |
|---|---|---|
| 4.1 | On Profile → Security, with TOTP active, find "Email verification code" section | Toggle shows "Not configured" |
| 4.2 | Click **Enable** (requires TOTP to be active first) | Status changes to "Active" |
| 4.3 | Sign out, start login, reach the MFA challenge screen | "Send code to my email" link is visible |
| 4.4 | Click "Send code to my email" | Button shows "Sending..."; subtitle changes to email instructions |
| 4.5 | Check email (or `storage/logs/laravel.log` with MAIL_MAILER=log) for a 6-digit code | Email contains large, clearly formatted code and 10-minute expiry notice |
| 4.6 | Enter the code and click **Verify** | Login succeeds |
| 4.7 | Try to use the same code a second time | Error: "Invalid or expired code" (single-use) |
| 4.8 | Request a new code within 60 seconds of the last one | 429 response; UI shows error (cooldown) |

---

## 5 — Disable TOTP

| # | Action | Expected result |
|---|---|---|
| 5.1 | On Profile → Security, click **Disable** next to "Authenticator app" | Confirmation prompt appears |
| 5.2 | Confirm the disable action | Status reverts to "Not configured"; recovery codes panel disappears |
| 5.3 | Sign out and log back in | No MFA challenge screen; token issued directly |

---

## 6 — Email OTP cannot be sole factor

| # | Action | Expected result |
|---|---|---|
| 6.1 | Disable TOTP (if active), then try to enable Email OTP via `POST /api/auth/email-otp-toggle {enabled:true}` | 422 response: "Authenticator app must be enabled before activating email OTP" |

---

## Negative cases

| Scenario | Expected |
|---|---|
| Expired challenge token (wait >5 min between login and challenge) | 422 "Session expired, please sign in again" |
| Invalid TOTP code (wrong digits) | 422 "Invalid or expired code" |
| Email OTP with more than 5 attempts | Code locked, marked as used |
| `POST /api/auth/two-factor-challenge` without `challenge_token` | 422 validation error |
| `POST /api/auth/two-factor-email-send` with a challenge_token belonging to a user without email_otp_enabled | 403 |

---

## Cleanup

No cleanup needed — all 2FA state is per-user and resets when TOTP is disabled.
