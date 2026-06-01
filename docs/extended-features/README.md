# Extended Features — Roadmap (10 features, phased)

> Home for the "extended features" initiative: 10 new capabilities sequenced into shippable phases.
> This folder holds the roadmap (this file), per-phase specs, the security/perf baseline, and every
> testing guide under `testing/`. Kept separate from the v2 docs in `docs/` so the initiative is
> self-contained.

## Context

Introduces 10 new capabilities into the existing Laravel 10 + Vue 3 SPA (multi-workspace task
tracker: Projet → Activité → Tâche; Fortify + Sanctum auth; Spatie permissions; Laravel Reverb
websockets; spatie/activitylog; mature notification system).

These 10 items are **not one task** — several are full subsystems. They are sequenced
**quick-wins-first**, each phase = its own feature branch (from `jonas`) + tests + doc trail +
merge into `jonas` (never `main`, never push directly to `jonas`). Perf and security work are woven
across phases, not saved for the end.

### Key decisions

1. **Payments are CEMAC/Cameroon-context, NOT Stripe.** Stripe does not operate in Cameroon/CEMAC.
   A provider-agnostic `PaymentProvider` interface is built first; the gateway phase ships **direct
   MTN Mobile Money + Orange Money** drivers (mobile-money is async/webhook-confirmed, not
   synchronous card charges). The seam keeps an aggregator (CinetPay/Notch Pay/Fapshi) as a future
   fallback option.
2. **Payment gate behavior:** no grace period. On lapse → workspace falls back to **free tier**;
   only if free-tier resources are *already exhausted* does it **hard-lock** (402, billing+support
   only). Otherwise it becomes a normal free-tier workspace (reads open, writes gated by free-tier
   limits via existing `CheckSubscriptionLimits`).
3. **Search:** Typesense + Laravel Scout **from day one**, reserved for **manager-and-above**.
4. **Help Center:** build reader + author UI + FULLTEXT search; **defer AI Q&A** (Phase 4 of
   `HELP_CENTER_PLAN.md`, in this folder) to a later, separate effort.
5. **Ordering:** quick wins first, then big subsystems, perf/security throughout.
6. **MFA factors = TOTP/authenticator-app (QR, primary) + recovery codes + email OTP (secondary
   fallback).** SMS/phone OTP is **dropped** — no genuinely free SMS gateway exists for production.
   TOTP stays preferred; email OTP is never the only factor. Fortify's two-factor is TOTP-only, so
   email OTP is a modest custom addition built *alongside* it.
7. **All docs for this initiative live under `docs/extended-features/`.**

### What already exists (reuse, do not rebuild)

- **Notifications:** `app/Services/NotificationService.php` (`channelsFor`, `wantsEmail`,
  `wantsWebPush`, `notifyHierarchy`, `sendUnlessSelf`, dedup). New type = extend the `match()` arms;
  never invent a new dispatch path. (See Guide 12.)
- **Permissions:** the enforced flow is Guide 4 / Guide 15 — `PermissionService` →
  `RolePermissionSeeder` → `Role::permissions()` (Enum) → frontend composable → API Resource →
  `PERMISSIONS_MATRIX.md` (hard gate, same commit). `app/Permissions/Permission.php` holds the
  string constants mirrored in `resources/js/permissions/Permission.js`.
- **Subscription scaffold:** `SubscriptionService`, `config/subscription.php`, `Workspace`
  (`subscription_mode` trial|paid|free, `trial_started_at`, `trial_duration_days`),
  `CheckSubscriptionLimits` middleware (gates `add_member` + `upload_file`). **No Plan entity, no
  payment processing exists.**
- **Super-admin area:** `AdminController`, `super_admin` middleware, Vue `pages/admin/*`, router
  `requiresSuperAdmin` guard.
- **2FA:** Fortify installed; `two_factor_*` columns on `users`; feature commented out in
  `config/fortify.php`; no UI.
- **Social auth:** ABSENT. Socialite not installed; Google buttons are non-functional placeholders.
- **Chat:** `TeamMessage`/`TeamMessageReaction`, `TeamMessageService`, `TeamMessageController`,
  `useTeamMessages.js`, `Teams/Show.vue`. **No Reverb broadcast (no real-time); @mention
  notification is a stub (TODO at `TeamMessageService.php:68`).**
- **Activity logs:** spatie/activitylog (`activity_log`) + `TeamActivity` + immutable
  `ValidationAuditLog` + `ActivityController`. **No super-admin cross-app log viewer UI.**
- **Search:** only `DocumentController::search` + `UserController::search` (SQL LIKE). No unified
  search, no Scout/Typesense.
- **Support/contact:** ABSENT.

---

## Phase ordering (quick-wins-first)

| # | Phase | Type | Risk | Depends on |
|---|---|---|---|---|
| 0 | Security + perf baseline (audit, no behavior change) | Cross-cutting | Low | — |
| 1 | MFA — TOTP/QR + recovery codes + email-OTP fallback | Quick win | Low | 0 |
| 2 | Social auth (Google) | Quick win | Low | 0 |
| 3 | Technical contact + Help & Support | Quick win | Low | notifications |
| 4 | Super-admin app log & activity viewer | Quick win | Low | activitylog |
| 5 | Chat fixes (real-time + @mentions + polish) | Medium | Medium | Reverb, notifications |
| 6 | Global search (Typesense + Scout, manager+) | Subsystem | Medium | Typesense infra |
| 7 | Help Center (reader + author, no AI) | Subsystem | Medium | — |
| 8 | Subscription Plans + payment-gate (manual activation) | Subsystem | High | subscription scaffold |
| 9 | Payment gateway — MTN MoMo + Orange Money | Subsystem | High | 8 |
| 10 | Perf + security hardening pass (deep) | Cross-cutting | Medium | all above |

Detailed per-phase scope lives in the sections below. Line-level sub-task breakdown is produced at
the start of each phase. Branch names follow Guide 1 (`feature/v2-task-…` style, from `jonas`).

---

## Phase 0 — Security + performance baseline (audit only)

**Goal:** establish a measured starting point and fix only zero-risk issues; no feature behavior change.

- Create this `docs/extended-features/` folder (done): roadmap (this README) + `testing/` subfolder;
  relocate `HELP_CENTER_PLAN.md` here.
- Run a security review on the current tree; triage findings into "fix now (trivial)" vs "scheduled
  per phase".
- N+1 sweep on hot endpoints (dashboard, taches list, workspace show); add `with()` where missing.
  Confirm `throttle:60,1` (already in `RouteServiceProvider`).
- Reconcile Sanctum token expiry (AuthService returns 7-day expiry but `config/sanctum.php`
  `expiration=null`).
- Add DB indexes flagged by the query review (migration only).
- Output: `SECURITY_PERF_BASELINE.md` (findings + per-phase action items). No app code beyond trivial
  fixes + indexes.

**Tests:** existing suite stays green; regression tests for any trivial fix made.

---

## Phase 1 — MFA (TOTP + recovery codes + email-OTP fallback)

**Scope:** authenticator-app TOTP (QR flow, primary) + recovery codes via Fortify, plus a custom
email-OTP secondary factor/fallback. No SMS. TOTP stays preferred; email OTP is never the only factor.

**Part A — Fortify TOTP + recovery codes:** enable `Features::twoFactorAuthentication(...)` in
`config/fortify.php`; wire the Fortify actions in `FortifyServiceProvider`; expose enable/confirm/
disable, QR SVG, recovery codes (fetch + regenerate), and the login challenge to the SPA; require a
challenge before issuing the Sanctum token in `AuthService::login` when a factor is confirmed.

**Part B — Email OTP (custom):** opt-in email OTP as a second factor/fallback; short-lived hashed code
(table vs cache decided in-phase); `POST /api/two-factor/email/send` (rate-limited + cooldown) mails a
6-digit code valid ~10 min; `POST /api/two-factor/email/verify` checks it at the challenge. New
Mailable + FR/EN Blade templates. Guardrails: short expiry, single-use, constant-time compare, attempt
cap, no email-existence disclosure. Cannot be the sole factor.

**Frontend:** 2FA section in profile/settings (QR + manual key + recovery codes + enable/disable +
email-OTP toggle); login challenge screen with "use a recovery code" and "email me a code" options;
i18n in `lang/{fr,en}/auth.php` + locale JSON.

**Tests:** see `testing/MFA_TESTING.md`. Feature coverage for TOTP enable/confirm/disable, QR/secret,
login-requires-factor, recovery-code login, wrong-OTP rejected, regenerate; email-OTP rate-limit,
single-use, expiry, cannot-be-sole-factor.

---

## Phase 2 — Social auth (Google)

`laravel/socialite` + Google block in `config/services.php` (env-driven; `.env.example`). Migration
adds `provider` + `provider_id` (nullable, indexed) to `users`; `password` handling for social-only
accounts decided in-phase. Routes `GET /api/auth/google/redirect|callback`; callback resolves-or-
creates the user, links by verified email (no duplicates), issues a Sanctum token (reuse
`AuthService`), hands the token to the SPA. Wire the existing Google buttons. Activity-logged.

**Tests:** Socialite mocked — new-user create, existing-email link, token issued.

---

## Phase 3 — Technical contact + Help & Support

`SupportTicket` model (+ migration/factory/seeder): user_id, workspace_id nullable, subject, category,
message, status [open/in_progress/resolved], optional attachment. `SupportTicketController` (store for
any authed user; index/show/updateStatus for super_admin) + Form Request. Notifications via
`NotificationService` — new ticket → super-admins (in-app + mail to `teams@cerdafrica.org`); status
change → requester; add event keys to `wantsEmail`/`wantsWebPush`. Frontend contact page + super-admin
tickets list + sidebar; i18n `lang/{fr,en}/support.php`.

---

## Phase 4 — Super-admin app log & activity viewer

Backend: cross-app, paginated, filterable feed over `activity_log` (causer, subject_type, log_name,
event, date range, free-text) — extend `ActivityController` or new `AdminActivityController` under
`super_admin`; reuse `ActivityResource`; optionally surface `ValidationAuditLog` + `TeamActivity`.
Frontend `pages/admin/AdminActivityLog.vue` (filter bar + paginated table + detail drawer);
`requiresSuperAdmin`; sidebar under Administration; i18n.

---

## Phase 5 — Chat: real-time + @mentions + polish

The "check, fix and update" item. Broadcast events `MessageSent/Updated/Deleted/ReactionChanged`
(`ShouldBroadcast`) on private `team.{teamId}`, authorized in `routes/channels.php` against
membership; dispatched from `TeamMessageService`. Frontend subscribes via `useEcho()` in
`useTeamMessages.js` / `Teams/Show.vue` (live append/update/remove). Implement the @mention
notification (replace TODO at `TeamMessageService.php:68`) via `NotificationService`; add `chat_mention`
event key. Polish: typing indicator (Reverb whisper), unread-per-team badge, attachment UI, optimistic
send. Keep `TeamActivity` logging.

---

## Phase 6 — Global search (Typesense + Scout, manager+)

Stand up Typesense (dev docker + documented prod provisioning); `laravel/scout` +
`typesense/typesense-php`; configure `config/scout.php` (env-driven; `.env.example`). Make `Tache`,
`Projet`, `Activite`, `Document`, `User` Searchable (safe fields + `workspace_id`); index command +
queued sync. **Authorization is critical:** reserved for manager-and-above via a new `search.global`
permission (Guide 4/15 flow); every query **workspace-scoped** to the caller's accessible workspaces
AND permission-gated — never leak cross-workspace or below-manager. `SearchController`
(`GET /api/search?q=`) multi-model, grouped, scoped + gated. Frontend command-palette search bar
gated by `can_search_global`; i18n.

**Tests:** manager 200 + scoped; cadre/collaborateur 403; cross-workspace leak test empty;
index/sync unit test.

---

## Phase 7 — Help Center (reader + author, AI deferred)

Follow `HELP_CENTER_PLAN.md` (this folder) — implement its Phases 1–3 + 6, **SKIP Phase 4 (AI)** and
Phase 5 (auto-screenshots, optional later). Resolve §16: bilingual FR/EN, `mews/purifier`, manage
permission to super_admin + owner/directeur.

Backend: migrations `help_categories`, `help_articles` (+ FULLTEXT raw SQL), `help_article_images`;
models with `published`/`searchable` scopes + boot-hook sanitize + `body_plain_*` extraction; reader
endpoints (any authed) + admin endpoints (`help_articles.read` / `help_articles.manage` via Guide 4/15);
Form Requests; `mews/purifier` config. (Skip `help_ai_queries`.) Frontend: reader pages `/help`,
`/help/c/:slug`, `/help/a/:slug`; admin list + Tiptap editor (FR/EN tabs, drag-drop image upload,
publish toggle); sidebar "Aide"; i18n `lang/{fr,en}/help.php`. Search via MySQL FULLTEXT (independent
of Phase 6 — help articles are global, not workspace-scoped).

**Tests:** see `testing/HELP_CENTER_TESTING.md`.

---

## Phase 8 — Subscription Plans + payment gate (manual activation)

Net-new domain; no payment processing yet (super-admin activates manually; gateway is Phase 9).

- **Plan entity** (+ migration/factory/seeder): nom, slug, price_xaf, billing_period, `limits` JSON,
  `features` JSON, is_active, position. Seed Free/Standard/Pro reflecting current env limits.
  `Workspace` gains nullable `plan_id`.
- **SubscriptionService refactor:** read limits from the workspace's `Plan` when set, falling back to
  `config/subscription.php`. Keep all existing method signatures so callers/middleware are unchanged.
- **Super-admin Plan management:** CRUD plans + assign to workspace + mark paid/unpaid + paid-until
  (`AdminController` or `AdminPlanController`); `subscription.manage` exists (super_admin implicit);
  frontend `pages/admin/AdminPlans.vue` + workspace plan picker.
- **Payment-gate middleware (`subscription.gate`):** no grace period — paid & current → allow; lapsed
  → downgrade to free tier (enforce free limits via `CheckSubscriptionLimits`); free tier with free
  resources already exhausted → hard-lock (402) except auth + billing + support. Reads otherwise open.
- **Notifications:** reuse trial/subscription notifications; add `plan_changed` / `payment_required`
  event keys.
- **Billing page:** replace mailto-only `Subscription.vue` with plan comparison + current plan/usage/
  status (pay button arrives Phase 9).

---

## Phase 9 — Payment gateway: MTN MoMo + Orange Money (CEMAC)

Highest risk → its own phase + a dedicated security review before merge.

- **Provider-agnostic seam:** `App\Payments\PaymentProvider` interface (`initiateCollection`,
  `verify`, `handleWebhook`); concrete `MtnMomoProvider` + `OrangeMoneyProvider`; driver chosen by
  operator/phone prefix; `PaymentService` orchestrates. Aggregator remains a future option behind the
  same interface.
- **Async model:** `payment_transactions` table (workspace_id, plan_id, provider, operator, phone,
  amount_xaf, currency=XAF, status, provider_reference, payload JSON, timestamps). Flow: initiate →
  `pending` → operator USSD/push → webhook updates status → on `paid`, activate plan + notify +
  activitylog.
- **Config + secrets:** `config/payments.php` (env-driven, sandbox vs prod, callback URLs);
  `.env.example` placeholders; no secrets in repo; sandbox for all tests.
- **Routes:** `POST /api/payments/initiate` (authed, gated to billing); `POST /api/payments/webhook/mtn`
  + `/orange` (public, signature/IP-verified, idempotent on provider_reference).
- **Frontend:** real pay flow (pick plan → enter MoMo/OM number → initiate → await async confirmation →
  success); i18n.

**Tests:** drivers `Http::fake()` (initiate + verify); webhook marks paid + activates + idempotent;
invalid signature rejected; hard-lock lifts after `paid`. **Security review required pre-merge.**

---

## Phase 10 — Performance + security hardening (deep pass)

After features land. Perf: re-profile new endpoints; cache where safe; paginate unbounded lists;
lazy-load heavy Vue routes; review Scout sync cost; bundle-size check. Security: full security review;
authz matrix re-verification per new endpoint (search scoping, payment webhooks, super-admin routes);
rate-limit sensitive endpoints; CSRF/CORS for public webhooks; `composer audit` + `npm audit`; confirm
no secret leakage; verify the payment hard-lock can't be bypassed. Update `SECURITY_PERF_BASELINE.md`
with before/after.

---

## Cross-cutting conventions (every phase — see WORKING_GUIDELINES.md)

- New permission → Guide 4 + Guide 15 (the matrix is a hard gate, same commit).
- New notification type → add event key to `NotificationService::wantsEmail` + `wantsWebPush`; never
  bypass `channelsFor` (Guide 12). All notifications `ShouldQueue`.
- New model → factory + seeder + policy (if access-controlled) + Form Requests + API Resource.
- Code comments + log messages in French (Guides 17/19); docs in English.
- Per task: focused PHPUnit run → `pint --dirty --format agent` → Larastan → a `testing/*.md` guide →
  update `PROGRESSION.md` + `SESSION_STATE.md`. At least one Dusk test per UI-touching task (Guide 18).
- Commit only on instruction; no AI authorship in messages (Guide 0); push to `origin` **and**
  `client`; branch from `jonas`, merge back via PR (never push `main`/`jonas` directly).
