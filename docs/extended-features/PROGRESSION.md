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
| 1 | MFA — TOTP/QR + recovery codes + email-OTP fallback | `feature/mfa-2fa` | ✅ | 2026-06-01 | 2026-06-01 | Merged `7525a81`, pushed both remotes. 666 tests passing. |
| 2 | Social auth (Google) | `feature/social-auth-google` | ✅ | 2026-06-02 | 2026-06-02 | Merged `19047fd`, pushed both remotes. 672 tests passing. |
| 3 | Technical contact + Help & Support | `feature/support-contact` | ✅ | 2026-06-02 | 2026-06-04 | SupportTicket system, MFA fixes, single-session, log infra. 682 tests. Merged into `jonas` `c986bae`. |
| 4 | Super-admin app log & activity viewer | `feature/support-contact` | ✅ | 2026-06-03 | 2026-06-04 | Unified AdminLogs.vue (3 tabs): activity log + native app logs (replaces iframe) + validation audit log. 688 tests. Merged with Phase 3 into `jonas`. Plan: `PHASE4_PLAN.md`. |
| 5 | Chat — real-time + @mentions + polish | `feature/phase5-chat` | ✅ | 2026-06-04 | 2026-06-04 | 4 broadcast events (ShouldBroadcastNow), team channel auth, ChatMentionNotification, TeamMessageResource, +5 controller methods, useTeamMessages.js rewrite, Teams/Show.vue overhaul, sidebar unread badge. 12 PHPUnit + 3 Dusk tests. Merged into `jonas` `cae2a7e`. Plan: `PHASE5_PLAN.md`. |
| 6 | Global search (Typesense + Scout + search page) | `feature/phase6-search` | 🔄 | 2026-06-04 | — | Part A (command palette) + Part B (search page, super-admin, highlights, export, file extraction, TeamMessage) both complete. Awaiting commit + jonas merge. Plan: `PHASE6_SEARCH_PLAN.md`. |
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
- [x] Commit + push feature branch (`779fef5`)
- [x] Merge into `jonas` (`7525a81`) — pushed `origin` + `client`

### Phase 2 — Social auth (Google) ✅
- [x] `laravel/socialite` installed
- [x] `provider` + `provider_id` nullable columns on `users` (migration + `down()` safe)
- [x] `config/services.php` Google block (env-driven `GOOGLE_CLIENT_ID/SECRET/REDIRECT`)
- [x] `.env.example` Google placeholders
- [x] `SocialAuthController` — `redirectToGoogle()` (stateless) + `handleGoogleCallback()` (resolves-or-creates user, links by verified email, no duplicates, issues Sanctum token via `AuthService::issueToken()`, redirects SPA with `?token=`)
- [x] Routes: `GET /api/auth/google/redirect` + `GET /api/auth/google/callback`
- [x] `SocialCallback.vue` page — reads `?token=` from URL, hydrates `authStore`, redirects to `/`
- [x] Vue router entry `/auth/callback` (guest)
- [x] Google buttons wired in `Signin.vue` + `Signup.vue` (was inert `<button>`, now `<a :href="googleRedirectUrl">`)
- [x] Activity-logged on create + link
- [x] 6 feature tests (`SocialAuthTest`) — 672 total passing
- [x] Commit + push `feature/social-auth-google`
- [x] Merged into `jonas` `19047fd` — pushed `origin` + `client`

### Phase 3 — Technical contact + Help & Support ✅

**Core feature:**
- [x] `SupportTicket` model (category, subject, message, status, reproducibility, steps_to_reproduce, SLA columns) + migration + factory + seeder
- [x] `SupportTicketReply` model + migration (`support_ticket_replies` — mandatory on every admin status change)
- [x] `SupportTicketAttachment` model + migration (`support_ticket_attachments` — replaces single attachment column)
- [x] `StoreSupportTicketRequest` — validates category, subject, message, reproducibility, steps, multi-file attachments (max 5 × 5 MB)
- [x] `SupportTicketController`: `store` (any auth user, SLA auto-calculated), `index` (own tickets + replies + attachments), `adminIndex` (super-admin, filterable), `reply` (mandatory reply + status, sets `first_responded_at`/`resolved_at`), `addAttachments`, `downloadAttachment`
- [x] `NewSupportTicketNotification` (ShouldQueue → all super-admins, in-app + email)
- [x] `SupportTicketReplyNotification` (ShouldQueue → requester, **always** on admin reply regardless of status change — includes reply text in email)
- [x] `NotificationService`: `support_ticket_new`, `support_ticket_reply`, `support_ticket_status_changed` registered as high-signal in `wantsEmail` + `wantsWebPush`
- [x] All three notification `toArray()` responses include `url` field for modal clickable redirect
- [x] Routes: `POST/GET /api/support`, `POST /api/support/{ticket}/attachments`, `GET /api/support/attachments/{id}/download`, `GET /api/admin/support`, `POST /api/admin/support/{ticket}/reply`
- [x] `lang/{fr,en}/support.php` + `resources/js/locales/{en,fr}.json` support.* keys
- [x] `Support.vue` — role-based: super-admin sees `AdminSupport` component; user sees contact form (reproducibility, steps, multi-file) + my-tickets list with reply thread + attachments
- [x] `AdminSupport.vue` — mandatory reply panel (body + status required), SLA badge, reply thread with stagger, SLA-breached filter
- [x] Single sidebar "Help & Support" entry (removed duplicate admin entry)
- [x] 10 feature tests (`SupportTicketTest`) — 682 total passing, build green, Larastan clean
- [x] `docs/extended-features/testing/SUPPORT_TESTING.md` written

**MFA bug fix (Phase 1 regression — 405 error):**
- [x] `TwoFactorManagementController` — API endpoints wrapping Fortify 2FA actions under `auth:sanctum`, bypassing Fortify's session-based routes
- [x] Routes: `POST|DELETE /api/user/two-factor-authentication`, `POST /api/user/confirmed-two-factor-authentication`, `GET /api/user/two-factor-qr-code|secret-key|recovery-codes`, `POST /api/user/two-factor-recovery-codes`

**Cross-cutting fixes:**
- [x] Single-session enforcement: `AuthService::issueToken()` revokes existing `auth_token` tokens before issuing new one
- [x] Notification modal: support ticket types wired into all lookup maps (TITLES, HEADER_COLORS, TYPE_LABEL_DISPLAY, actionLabel, modalTitle)
- [x] Notification modal + `NotificationItem`: hardcoded French strings → `$t()` (mark_read, delete, confirm_delete, read, unread_label)
- [x] Email links use `config('app.frontend_url')` + `APP_FRONTEND_URL` env var (fixes localhost vs 127.0.0.1 origin mismatch causing redirect to login)
- [x] Laravel logging switched to `daily` channel (was `single`), 14-day retention
- [x] `opcodesio/log-viewer` published + `LogViewer::auth()` callback (production only: super-admin Bearer token; local: open freely)
- [x] Log viewer sidebar entry (super-admin, opens `/log-viewer?token=xxx` in new tab)
- [x] `logs:rotate-browser` artisan command + scheduled daily at 00:05 (rotates Boost's `browser.log` which bypasses Laravel channels)
- [x] `vite.config.js`: `cssMinify: 'lightningcss'` — eliminates the recurring Tailwind v4 `:is()` CSS warning
- [x] Guide 23 (stagger animations mandatory) + Guide 24 (full integration checklist) added to `WORKING_GUIDELINES.md` + `CLAUDE.md` + auto-memory
- [x] Commit + push `feature/support-contact` (`121dc75`)
- [ ] Merge into `jonas` (awaiting per-push approval)

_(Checklists for Phases 4–10 added as each phase starts.)_

---

## Session Log

| Date | Worked on | Outcome |
|---|---|---|
| 2026-06-01 | Roadmap planning; prep; Phase 0 | Approved 10-phase roadmap; committed/pushed `/documents` i18n; scaffolded `docs/extended-features/`; merged frontend-alignment into `jonas` (654 tests green); completed Phase 0 baseline (docs-only). Phase 1 next. |
| 2026-06-01 | Phase 1 — MFA | TOTP + recovery codes + email-OTP implemented. F1 token-expiry fixed. `sanctum:prune-expired` scheduled. 12 feature tests + 666 total passing. Build green. |
| 2026-06-02 | Phase 2 — Social auth | Socialite installed, `provider`/`provider_id` migration, `SocialAuthController` (redirect + stateless callback), `SocialCallback.vue`, Google buttons wired in Signin/Signup. 6 feature tests + 672 total passing. Merged into `jonas` `19047fd`. |
| 2026-06-02–03 | Phase 3 — Support + bug fixes | Full support ticket system (SLA, mandatory admin reply, multi-attachments, reproducibility). MFA 405 fixed (`TwoFactorManagementController`). Single-session enforcement. Notification modal support types + translations. Email link origin fix (`APP_FRONTEND_URL`). Daily log rotation (Laravel + browser.log). Log viewer gated + sidebar link. Stagger + full-integration guidelines. 682 tests passing. |
