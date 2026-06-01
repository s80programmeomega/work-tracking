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
| 0 | Security + perf baseline (audit) | `chore/security-perf-baseline` | ✅ | 2026-06-01 | 2026-06-01 | Docs-only, zero code change. Finding F1 (token expiry) folded into Phase 1. Commit `ecf2e25`. Merge into `jonas` pending decision. |
| 1 | MFA — TOTP/QR + recovery codes + email-OTP fallback | `feature/mfa-2fa` | ⬜ | — | — | Includes F1 token-expiry fix + `sanctum:prune-expired` schedule |
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
- [ ] Merge into `jonas` (awaiting per-push approval)

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

_(Checklists for Phases 2–10 added as each phase starts.)_

---

## Session Log

| Date | Worked on | Outcome |
|---|---|---|
| 2026-06-01 | Roadmap planning; prep; Phase 0 | Approved 10-phase roadmap; committed/pushed `/documents` i18n; scaffolded `docs/extended-features/`; merged frontend-alignment into `jonas` (654 tests green); completed Phase 0 baseline (docs-only). Phase 1 next. |
