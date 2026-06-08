# Extended Features — Index

> Quick reference to every document in this folder.
> Full 10-phase roadmap: [README.md](README.md)
> Live status tracker: [PROGRESSION.md](PROGRESSION.md)

---

## Phase plans

| Phase | Plan doc | Status | Branch |
|---|---|---|---|
| 0 — Security + perf baseline | [SECURITY_PERF_BASELINE.md](SECURITY_PERF_BASELINE.md) | ✅ Merged | `chore/security-perf-baseline` |
| 1 — MFA (TOTP + email-OTP) | *(within README.md)* | ✅ Merged | `feature/mfa-2fa` |
| 2 — Social auth (Google) | *(within README.md)* | ✅ Merged | `feature/social-auth-google` |
| 3 — Technical contact + Help & Support | *(within README.md)* | ✅ Merged | `feature/support-contact` |
| 4 — Super-admin logs (activity + app + audit) | [PHASE4_PLAN.md](PHASE4_PLAN.md) | ✅ Merged | `feature/support-contact` |
| 5 — Chat real-time + @mentions + unread | [PHASE5_PLAN.md](PHASE5_PLAN.md) | ✅ Merged | `feature/phase5-chat` |
| 6 — Global search (Typesense + Scout + search page) | [PHASE6_SEARCH_PLAN.md](PHASE6_SEARCH_PLAN.md) | ✅ Merged | `feature/phase6-search` |
| 7 — Help Center (reader + author, no AI) | [HELP_CENTER_PLAN.md](HELP_CENTER_PLAN.md) | ✅ Merged | `feature/phase7-help-center` |
| 8 — Subscription plans + payment gate | *(within README.md)* | 🚧 In progress | `feature/phase8-subscription-plans` |
| 9 — Payment gateway (MTN MoMo + Orange Money) | *(within README.md)* | 🚧 In progress | `feature/phase9-payment-momo-orange` |
| 10 — Perf + security hardening | [SECURITY_PERF_BASELINE.md](SECURITY_PERF_BASELINE.md) | 🚧 In progress | `chore/hardening-pass` |

---

## Testing guides

| Guide | Phase | Location |
|---|---|---|
| MFA testing | Phase 1 | [testing/MFA_TESTING.md](testing/MFA_TESTING.md) |
| Social auth testing | Phase 2 | [testing/SOCIAL_AUTH_TESTING.md](testing/SOCIAL_AUTH_TESTING.md) |
| Support tickets testing | Phase 3 | [testing/SUPPORT_TESTING.md](testing/SUPPORT_TESTING.md) |
| Admin logs testing | Phase 4 | [testing/TASK_PHASE4_TESTING.md](testing/TASK_PHASE4_TESTING.md) |
| Chat real-time testing | Phase 5 | [testing/TASK_PHASE5_TESTING.md](testing/TASK_PHASE5_TESTING.md) |
| Global search testing | Phase 6 (Part A) | [testing/TASK_PHASE6_TESTING.md](testing/TASK_PHASE6_TESTING.md) |
| Enhanced search page testing | Phase 6 (Part B + tiers/notifications/security) | [testing/TASK_PHASE6_ENHANCED_TESTING.md](testing/TASK_PHASE6_ENHANCED_TESTING.md) |
| Help Center testing | Phase 7 | [testing/TASK_PHASE7_TESTING.md](testing/TASK_PHASE7_TESTING.md) |
| Subscription plans + gate testing | Phase 8 | [testing/TASK_PHASE8_TESTING.md](testing/TASK_PHASE8_TESTING.md) |
| Payment gateway testing | Phase 9 | [testing/TASK_PHASE9_TESTING.md](testing/TASK_PHASE9_TESTING.md) |

---

## Other docs in this folder

| File | Purpose |
|---|---|
| [README.md](README.md) | Full 10-phase roadmap with spec for each phase |
| [PROGRESSION.md](PROGRESSION.md) | Live phase status tracker with deliverables checklists |
| [SECURITY_PERF_BASELINE.md](SECURITY_PERF_BASELINE.md) | Phase 0 findings + per-phase action items + Phase 10 hardening results |
| [MAJOR_UPGRADES_PLAN.md](MAJOR_UPGRADES_PLAN.md) | Deferred breaking upgrades (Laravel 11, vite 8, admin-lte 4) |
| [HELP_CENTER_PLAN.md](HELP_CENTER_PLAN.md) | Detailed spec for Phase 7 (Help Center) |
