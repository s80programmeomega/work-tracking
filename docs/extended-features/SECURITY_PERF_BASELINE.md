# Security & Performance Baseline (Phase 0)

> **Scope:** lightweight baseline for the extended-features roadmap. Records the starting state and
> the findings worth tracking. Per the user's decision, deep N+1/query profiling and the full
> security sweep are **deferred to Phase 10** (hardening pass). This doc is the running ledger;
> Phase 10 updates it with before/after.
>
> **Branch:** `chore/security-perf-baseline` · **Date:** 2026-06-01

---

## Method

- Reviewed auth flow (`AuthService`, `config/sanctum.php`), the hot controllers
  (`DashboardController`, `TacheController`, `WorkspaceController`, `ProjetController`,
  `ActiviteController`), eager-loading density, and rate limiting.
- No behavioral code changed in Phase 0 beyond items explicitly approved from the triage below.

---

## Findings

### F1 — Sanctum login token has no server-side expiry, but the API advertises 7 days (MEDIUM)

**Where:** `app/Services/AuthService.php`

- `login()` (line 66) issues the token **without** an expiry — the `now()->addDays(7)` third arg is
  commented out (line 65): `$user->createToken('auth_token', ['*'])`.
- …yet `login()` returns `'expires_at' => now()->addDays(7)` (line 81), so the SPA believes the
  token expires in 7 days and proactively refreshes ~1 min before (per `authStore`/`axios.js`).
- `refreshToken()` (line 115) **does** pass `now()->addDays(7)`.
- `config/sanctum.php` `expiration => null` (no global expiry), and **`sanctum:prune-expired` is not
  scheduled** in `app/Console/Kernel.php`.

**Impact:**
- Real posture (login tokens never expire server-side) ≠ advertised posture (7-day). A stolen login
  token is valid indefinitely until manually revoked.
- Login-path and refresh-path tokens have different lifetimes — inconsistent.
- Expired/dead tokens accumulate in `personal_access_tokens` (no pruning).

**Recommended fix (low risk):**
1. Uncomment the 7-day expiry on the `login()` token so it matches the advertised `expires_at` and
   the refresh path: `$user->createToken('auth_token', ['*'], now()->addDays(7))`.
   - *Alternative:* set `config/sanctum.php` `expiration` globally (e.g. `60*24*7`) and drop the
     per-call arg — but the per-call form is already used in `refreshToken()`, so matching it in
     `login()` is the smaller, more consistent change.
2. Schedule `sanctum:prune-expired --hours=24` in `app/Console/Kernel.php` to clear dead tokens.
3. (Optional, defer) consider a shorter access-token TTL once the SPA refresh loop is confirmed
   robust — out of scope for Phase 0.

**Triage:** _fix candidate for Phase 1_ (it sits right next to the MFA login-flow work, which also
touches `AuthService::login` token issuance — natural to fix together rather than as a standalone
auth change now).

---

### F2 — Eager-loading density is healthy; no urgent N+1 (INFO)

**Where:** hot controllers.

Eager-load usage (counted): `TacheController` with=16/load=2/withCount=8; `WorkspaceController`
5/4/6; `ProjetController` 6/1/1; `ActiviteController` 1/5/6; `DashboardController` 1/0/2.

`DashboardController::index` looks light on `with()` but is doing **scoped aggregate/count** queries
(`accessibleBy(...)->pluck('id')`, `whereIn`, `whereHas`), not loading collections for row-by-row
rendering — so the low count is correct, not an N+1 smell.

**Triage:** _no action in Phase 0._ Deep per-endpoint query profiling (Telescope/clockwork or
`DB::listen` counts on the new endpoints too) is **deferred to Phase 10**.

---

### F3 — Rate limiting present but not yet applied to sensitive auth endpoints (LOW, forward-looking)

**Where:** `RouteServiceProvider` (global `throttle:60,1` confirmed present per CLAUDE.md / prior CDC
review).

The global API throttle exists. But login, token refresh, and (incoming) 2FA challenge + email-OTP
send + payment initiate are sensitive endpoints that warrant **tighter, dedicated** limits than the
generic 60/min.

**Triage:** _addressed per-phase as those endpoints are built_ — Phase 1 adds tight limits on the
2FA challenge + email-OTP send; Phase 9 on payment initiate; Phase 10 re-verifies the whole set.

---

### F4 — `personal_access_tokens` pruning + token-table growth (LOW)

Covered by F1 item 2 (schedule `sanctum:prune-expired`). No separate action.

---

## Deferred to Phase 10 (deep hardening pass)

- Full `/security-review` across the tree + every new endpoint's authz re-verification.
- Per-endpoint N+1/query profiling (existing + new endpoints), caching opportunities, unbounded-list
  pagination audit, Vue route lazy-loading, Scout/Typesense index-sync cost, bundle-size check.
- `composer audit` + `npm audit`; secret-leakage scan; CSRF/CORS review for the new public payment
  webhook routes; verify the payment hard-lock can't be bypassed.

## Per-phase security action items (carried forward)

| Phase | Security item |
|---|---|
| 1 (MFA) | Fix F1 (token expiry + prune schedule); tight rate-limit on 2FA challenge + email-OTP send; email-OTP guardrails (single-use, short expiry, constant-time compare, no email-existence disclosure) |
| 2 (Social auth) | Validate OAuth state/CSRF; link only on verified email; don't create duplicate accounts |
| 6 (Search) | Workspace-scope every query + manager+ gate; no cross-workspace/below-manager leakage |
| 9 (Payments) | Webhook signature/IP verification + idempotency + replay protection; no secrets in repo; dedicated `/security-review` pre-merge |
| 10 | Everything under "Deferred" above |

---

# Phase 10 — Hardening Pass Results (2026-06-08, branch `chore/hardening-pass`)

Deep security + performance sweep. Findings below with **before → after**.

## Security — fixed

### S1 — Dependency CVEs (composer) — FIXED (7 of 8)
- **Before:** `composer audit` = 8 advisories / 5 packages — **1 HIGH** (`symfony/mime`
  CVE-2026-45067, email-header/SMTP CRLF injection), 3 medium, 4 low.
- **Fix:** `composer update` within Laravel 10 constraints —
  `symfony/mime` 6.4.37→6.4.41 (the HIGH), `symfony/http-foundation` →6.4.41,
  `symfony/mailer` →6.4.40, `symfony/routing` →6.4.41, `symfony/yaml` 7.4.10→7.4.13,
  `symfony/polyfill-intl-idn` 1.37→1.38.1.
- **After:** **1 advisory left** — `laravel/framework` CVE-2026-48019 (CRLF in the default
  `email` validation rule). Patch is **not reachable within `^10`** → requires a Laravel
  10→11 major upgrade. **Deferred** (out of the approved no-major-bump scope). Low practical
  risk here (exploitable only if untrusted input flows through that rule into a mail header).
- Verified: full suite **771/772** (the 1 failure is a pre-existing SocialAuth ordering
  flake — passes 3/3 in isolation; unrelated to the bumps).

### S2 — Dependency CVEs (npm) — FIXED (high-sev), majors deferred
- **Before:** `npm audit` = 11 vulns (9 moderate, **2 high**); high-sev in the
  `ws`/`engine.io-client`/`socket.io-client` chain (via `laravel-echo`).
- **Fix:** `npm audit fix` (non-breaking) — `ws` 8.18.3→8.20.1; both **highs cleared**.
- **After:** 5 moderate left, fixable only via `npm audit fix --force` (breaking majors:
  `vite@8` from esbuild, `admin-lte@4` from summernote). **Deferred** — separate migration.
- Verified: `npm run build` green.

### S3 — CORS: wildcard origin with credentials — FIXED (MEDIUM/HIGH)
- **Before:** `config/cors.php` had `'allowed_origins' => ['*']` **with**
  `'supports_credentials' => true`. This combo lets **any** website make credentialed
  (cookie/auth) requests to the API — weakens CSRF posture for the SPA + Sanctum setup.
- **Fix:** `allowed_origins` is now **env-driven** — `CORS_ALLOWED_ORIGINS` (comma-separated),
  fallback to `APP_URL`. No more `*`.
- **Deploy note:** production `.env` MUST set `CORS_ALLOWED_ORIGINS` to the real frontend
  domain(s); otherwise CORS blocks the app (intended).

### S4 — F3: sensitive auth/payment endpoints lacked dedicated rate limits — FIXED (LOW→MED)
- **Before:** `/auth/login`, `/auth/register`, `/auth/refresh`, `/payment/initiate` relied on
  the global `throttle:60,1` only (login = prime brute-force target). (2FA endpoints were
  already tight.)
- **Fix:** dedicated throttles — `login` & `register` `5,1`; `refresh` `10,1`;
  `payment/initiate` `6,1`.

## Security — verified already-sound (no change needed)

- **F1 (token expiry):** ✅ already fixed in Phase 1 — `login()` + `refreshToken()` both issue
  7-day tokens (consistent with advertised `expires_at`); `sanctum:prune-expired --hours=24`
  scheduled.
- **Payment webhooks CSRF:** ✅ correct — public webhooks are on the stateless `api` group
  (no `VerifyCsrfToken`, which is `web`-only), so provider POSTs work without tokens by design.
- **Payment hard-lock (402) bypass:** ✅ sound — exempt prefixes (`auth`, `subscription`,
  `payment`, `webhooks`, `user`) are exactly those a locked workspace must reach to pay/log
  out; everything else is gated. No over-exemption.
- **Webhook authenticity:** ✅ (Phase 9) — webhooks re-verify status via the provider API,
  never trust the POST body; `confirm()` is idempotent (replay-safe).

## Performance — fixed

### P1 — N+1: `Plan::free()` queried repeatedly — FIXED (MEDIUM)
- **Before:** `SubscriptionService::summary()` issued **5 queries**, of which **4 were the
  identical** `select * from plans where is_free=1 limit 1` (one in `effectivePlan` + 3 in
  `planLimit`). `AdminController::workspaces` calls `summary()` **per row** → a 20-row page
  did **~80 redundant free-plan queries**.
- **Fix:** request-scoped memoization of `Plan::free()` (static cache + `forgetFreeCache()`,
  busted on `Plan` save/delete via `booted()`).
- **After:** `summary()` = **2 queries** (cold) / **1** (warm). Admin workspaces list:
  ~80 free-plan queries → **~1**. Verified via `DB::listen`.

## Performance — audited, clean (no action)

- **Unbounded-list / pagination:** real list endpoints use `->paginate()` (19 call sites).
  The `index() → ->get()` cases (AdminPlan, PushSubscription, SousTache, ProjetInvitation)
  are naturally bounded or single-parent-scoped. **No `Model::all()` in any controller.**
- **Dashboard `->get()` sets** are workspace/user-scoped (`accessibleBy`, `assignedTo`,
  `whereIn($ids)`) — aggregates, not whole-table loads (confirms F2). Watch-item only for
  very large single workspaces.

## Deferred from Phase 10 (require separate, approved work)

> Migration plan for the breaking bumps: **`MAJOR_UPGRADES_PLAN.md`**.

- `laravel/framework` CVE-2026-48019 — needs Laravel 10→11 major upgrade.
- npm `vite@8` + `admin-lte@4` major upgrades (breaking) — separate migration.
- Phase 8 limit-enforcement gaps (addMember route unguarded, storage quota unenforced,
  middleware-only enforcement) — see `testing/TASK_PHASE9_TESTING.md` "Known gaps".
- Deeper perf (caching layer, Vue route lazy-loading, bundle-size reduction) — not pursued
  this round; no blocking issue found.
