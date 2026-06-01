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
