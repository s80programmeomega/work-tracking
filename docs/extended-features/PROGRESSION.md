# Extended Features — Progression Tracker

> Living status tracker for the 10-feature extended-features roadmap (see `README.md` for the full
> plan, `SECURITY_PERF_BASELINE.md` for Phase 0 findings). Update this file at the end of every
> phase/task. One row per phase + a deliverables checklist per phase as it is worked.

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⏸️ | Paused / awaiting decision |
| ⚠️ | Blocked |

---

## Preparatory work (before Phase 0)

| Item | Status | Notes |
|---|---|---|
| Translate `/documents` subtabs (FR/EN i18n) | ✅ | Committed `65c8a7f`, pushed `origin` + `client` |
| Scaffold `docs/extended-features/` (roadmap README, `testing/`, relocate `HELP_CENTER_PLAN.md`) | ✅ | Committed `ce06485`, pushed both remotes |
| Merge `feature/frontend-alignment-phase-1` → `jonas` (`--no-ff`) | ✅ | Merge `1f6cf59`; build green; **654 tests pass**; pushed both remotes |

---

## Phase Progress

| # | Phase | Branch | Status | Started | Completed | Notes |
|---|---|---|---|---|---|---|
| 0 | Security + perf baseline (audit) | `chore/security-perf-baseline` | ✅ | 2026-06-01 | 2026-06-01 | Docs-only, zero code change. Finding F1 (token expiry) folded into Phase 1. Merged into `jonas` `4493754`, pushed both remotes. |
| 1 | MFA — TOTP/QR + recovery codes + email-OTP fallback | `feature/mfa-2fa` | ✅ | 2026-06-01 | 2026-06-01 | F1 token-expiry fixed; prune scheduled; 12 feature tests; 666 total passing. Awaiting commit + merge. |
| 2 | Social auth (Google) | `feature/social-auth-google` | ⬜ | — | — | Socialite; `provider`/`provider_id` on users; link by verified email |
| 3 | Technical contact + Help & Support | `feature/support-contact` | ⬜ | — | — | `SupportTicket` + notifications to super-admins/requester |
| 4 | Super-admin app log & activity viewer | `feature/admin-activity-viewer` | ⬜ | — | — | Cross-app feed over `activity_log`; super_admin only |
| 5 | Chat — real-time + @mentions + polish | `feature/chat-realtime` | ⬜ | — | — | Reverb broadcast events; implement @mention notif (TODO at `TeamMessageService.php:68`) |
| 6 | Global search (Typesense + Scout, manager+) | `feature/global-search-typesense` | ⬜ | — | — | Typesense day one; `search.global` perm; workspace-scoped + gated |
| 7 | Help Center (reader + author, no AI) | `feature/help-center` | ⬜ | — | — | Follow `HELP_CENTER_PLAN.md` Phases 1–3 + 6; skip AI (Phase 4) |
| 8 | Subscription Plans + payment gate (manual) | `feature/subscription-plans` | ⬜ | — | — | `Plan` entity; gate = lapse→free→hard-lock(402); no processing yet |
| 9 | Payment gateway — MTN MoMo + Orange Money | `feature/payment-momo-orange` | ⬜ | — | — | Provider-agnostic seam; async webhook flow; security review pre-merge |
| 10 | Perf + security hardening (deep) | `chore/hardening-pass` | ⬜ | — | — | Deferred items from `SECURITY_PERF_BASELINE.md` |

---

## Deliverables checklist per phase

### Phase 0 — Security + perf baseline ✅
- [x] `docs/extended-features/` folder + `testing/` subfolder + `README.md` roadmap
- [x] Relocate `HELP_CENTER_PLAN.md` into the folder + fix stale path references
- [x] `SECURITY_PERF_BASELINE.md` written (F1–F4 + per-phase action items + Phase 10 deferrals)
- [x] No behavioral code change (docs-only)
- [x] Existing suite confirmed green (654 on merged `jonas`)
- [x] Merge into `jonas` `4493754` — pushed `origin` + `client`

### Phase 1 — MFA ⬜
- [ ] Enable Fortify `twoFactorAuthentication` + wire actions in `FortifyServiceProvider`
- [ ] Expose enable/confirm/disable, QR SVG, recovery codes, challenge to SPA
- [ ] Require factor at login in `AuthService::login` before issuing token
- [ ] **F1 fix:** login token 7-day expiry consistent with refresh + advertised `expires_at`
- [ ] **F1 fix:** schedule `sanctum:prune-expired` in `app/Console/Kernel.php`
- [ ] Email-OTP: store (table/cache), send (rate-limited) + verify endpoints, Mailable + FR/EN Blade
- [ ] Email-OTP guardrails: single-use, ~10-min expiry, constant-time compare, attempt cap, no email-existence disclosure, cannot be sole factor
- [ ] Frontend: 2FA settings section (QR + manual key + recovery codes + toggles) + login challenge screen
- [ ] i18n `lang/{fr,en}/auth.php` + locale JSON
- [ ] Feature tests (TOTP + email-OTP paths) + Dusk (Guide 18) + `testing/MFA_TESTING.md`
- [ ] `PERMISSIONS_MATRIX.md` only if a permission is added (likely none)

### Phase 1 — MFA ✅

- [x] `TwoFactorAuthenticatable` trait added to `User` model
- [x] Fortify `twoFactorAuthentication` feature enabled in `config/fortify.php` (fixed typo `?`)
- [x] `FortifyServiceProvider::boot` wires `two-factor` rate limiter
- [x] **F1 fix:** `AuthService::issueToken()` extracted; login token now issued with 7-day expiry (matches `expires_at` + refresh path)
- [x] `sanctum:prune-expired --hours=24` scheduled daily in `Kernel.php`
- [x] `two_factor_email_codes` table migration
- [x] `email_otp_enabled` column added to `users` migration
- [x] `TwoFactorEmailCode` model
- [x] `MfaService` (challenge token, TOTP verify, email-OTP send/verify, cooldown, attempt cap)
- [x] `TwoFactorEmailCodeMail` Mailable (ShouldQueue) + FR/EN Blade templates
- [x] `AuthController`: `twoFactorChallenge`, `twoFactorEmailSend`, `toggleEmailOtp` methods
- [x] Routes: `POST /api/auth/two-factor-challenge` (throttle:10,1) + `POST /api/auth/two-factor-email-send` (throttle:3,1) + `POST /api/auth/email-otp-toggle` (auth:sanctum)
- [x] `authStore.js`: `mfaChallengeToken` + `mfaEmailOtpAvailable` state; `completeMfaLogin()`; `fetchUser()`; `login()` handles `two_factor:true` response
- [x] `Signin.vue`: MFA challenge screen (TOTP/recovery/email-OTP with type switcher, rate-limit feedback)
- [x] `TwoFactorSettings.vue` component (QR setup, confirm, recovery codes, regenerate, email-OTP toggle)
- [x] `UserProfile.vue` Security tab wired with `<TwoFactorSettings />`
- [x] i18n: `auth.mfa.*` keys in `lang/{fr,en}/auth.php` + `resources/js/locales/{en,fr}.json`
- [x] 12 feature tests in `tests/Feature/Mfa/MfaTest.php` — 666 total, all passing
- [x] Build green (`npm run build`)
- [x] `docs/extended-features/testing/MFA_TESTING.md` written
- [ ] Commit + push feature branch
- [ ] Merge into `jonas` (awaiting per-push approval)

_(Checklists for Phases 2–10 added as each phase starts.)_

---

## Session Log

| Date | Worked on | Outcome |
|---|---|---|
| 2026-06-01 | Roadmap planning; prep; Phase 0 | Approved 10-phase roadmap; committed/pushed `/documents` i18n; scaffolded `docs/extended-features/`; merged frontend-alignment into `jonas` (654 tests green); completed Phase 0 baseline (docs-only). Phase 1 next. |
| 2026-06-01 | Phase 1 — MFA | TOTP + recovery codes + email-OTP implemented. F1 token-expiry fixed. `sanctum:prune-expired` scheduled. 12 feature tests + 666 total passing. Build green. |
