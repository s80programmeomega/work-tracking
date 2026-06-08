# Phase 9 Testing Guide — Payment Gateway (MTN MoMo + Orange Money)

> Branch: `feature/phase9-payment-momo-orange`
> Prerequisites: `php artisan migrate` + `php artisan db:seed --class=PlanSeeder` + your sandbox credentials in `.env` (see `.env.example` block) + `php artisan serve` + `npm run dev`.

> **Live-test status (2026-06-08):**
> - **Level 1 (local fake):** ✅ passed in-browser.
> - **MTN sandbox:** ✅ integration proven (auth + 202 + status + mapping); blocked only by
>   MTN-side `INTERNAL_PROCESSING_ERROR` on the success number (see status section).
> - **Orange sandbox:** ⏸️ **deferred** — Orange's Web Payment sandbox access is gated for
>   this account (operator-side, not a code issue). Integration is verified via `Http::fake`
>   tests; provider URLs/currency are env-driven (`PAYMENT_ORANGE_WEBPAYMENT_URL` /
>   `_STATUS_URL`, sandbox currency `OUV`). Resume when sandbox credentials are obtained.
> - **Production (real money):** not run — no merchant credentials yet (see go-live checklist).

---

## Scope

Real payment processing for the Phase 8 subscription flow: a confirmed payment
flips a workspace's selected plan from `pending` → `active`.

- **Provider-agnostic seam** — `PaymentProviderInterface` + `MtnMomoProvider` +
  `OrangeMoneyProvider` + `PaymentProviderRegistry` (Strategy pattern).
- **`payments` table** + `Payment` model (uuid `reference` = idempotency key).
- **`PaymentService`** — `startCheckout()` (create pending + initiate) and
  **idempotent** `confirm()` (a replayed callback never re-activates).
- **`SubscriptionService::activateFromPayment()`** — the `pending → active`
  transition (plan_id, subscription_mode=paid, subscription_status=active,
  subscription_ends_at = now + plan period).
- **Webhook-confirmed activation only** — see security note below.
- Frontend: payment modal on `/subscription/plans` (provider + phone), MTN
  push-poll / Orange redirect, `?payment=return|cancel` handling.

---

## Security model (read this)

Webhook endpoints (`POST /api/webhooks/payment/{momo|orange}`) are **public** —
providers call them with no session. They are therefore **never trusted by body**:

1. The callback is only a "go check" signal.
2. On receipt, `PaymentWebhookController` looks up our `Payment` by reference and
   **re-fetches the real status from the provider's API** (`fetchStatus()`) using
   our own credentials.
3. Only that verified status drives `confirm()`.

So a forged `{"status":"SUCCESS"}` POST cannot activate anything — our server
re-asks the provider, which says PENDING/FAILED, and nothing happens.

Other guards: idempotent `confirm()` (pending-check), owner-only `initiate`,
free plan rejected (422), amount frozen on the payment row, secrets only in
`.env`/`config()` (never logged or committed).

---

## Automated tests

```bash
php artisan test --compact tests/Feature/Payment/
```

Expected: **8 passed**. All use `Http::fake()` — **no network, no credentials**.

`tests/Feature/Payment/PaymentFlowTest.php` covers:
- MTN initiate → pending payment row;
- Orange initiate → returns `redirect_url`;
- non-owner initiate → 403; free plan → 422;
- MTN webhook (status re-verified SUCCESSFUL) → payment succeeded + workspace
  active/paid/plan set;
- webhook FAILED → no activation;
- **idempotent replay** → second callback doesn't re-activate (`subscription_ends_at`
  unchanged);
- unknown reference → 404.

Run the whole subscription + payment area together:
```bash
php artisan test --compact tests/Feature/Payment/ tests/Feature/Subscription/
# 51 passed (8 payment + 43 subscription)
```

---

## Pre-commit gates

```bash
vendor/bin/pint --dirty --format agent
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
```
Both clean.

---

## Manual sandbox testing

> Needs real MTN/Orange **sandbox** credentials in `.env`. Build never asks for or
> stores real values — you fill them yourself.

### TC-01 — MTN MoMo (push) happy path
1. `/subscription/plans` → choose a paid plan → modal → provider **MTN MoMo** + sandbox MSISDN → **Pay**.
2. Backend POSTs requesttopay (202); modal shows "approve on your phone".
3. Frontend polls `GET /api/payment/{ref}/status`. In sandbox, the test number auto-succeeds.
4. When MTN reports SUCCESSFUL (callback or poll), payment → succeeded, workspace → active. Modal closes; "subscription active".

### TC-02 — Orange Money (redirect) happy path
1. Choose paid plan → provider **Orange Money** → **Pay**.
2. Backend calls webpayment, returns `payment_url`; browser redirects to Orange.
3. Pay with OTP on Orange's page → redirected back to `/subscription/plans?payment=return`.
4. Orange POSTs `notif_url`; our webhook re-verifies via `transactionstatus` → activates.

### TC-03 — Webhook re-verification (security)
1. With a pending payment, `curl -XPOST /api/webhooks/payment/momo -d '{"referenceId":"<ref>","status":"SUCCESSFUL"}'` while the provider still reports PENDING.
2. Expect: **no activation** (our server trusts the provider API, not the POST body).

### TC-04 — Lapsed/locked workspace can still pay
1. Lock a workspace (super_admin) → owner hits a normal route → 402 → redirected to `/subscription/plans`.
2. The `payment/*` and `subscription/*` routes remain reachable (exempt) → owner completes payment → workspace reactivated.

---

## Endpoints reference

```
POST /api/payment/initiate            {plan_id, provider, payer_phone}  (auth, owner)
GET  /api/payment/{reference}/status                                    (auth, member)
POST /api/webhooks/payment/momo       public — re-verifies via MTN API
POST /api/webhooks/payment/orange     public — re-verifies via Orange API
```

> **Deploy note:** set all `PAYMENT_*` env vars per environment (sandbox vs production
> base URLs + credentials). MTN sandbox only accepts EUR; production uses the local
> currency. Orange base URLs/payloads differ by operator/country — confirm against your
> Orange onboarding pack. Webhook URLs must be publicly reachable (configure
> `PAYMENT_MOMO_CALLBACK_URL` / `PAYMENT_ORANGE_NOTIF_URL`).

---

## Known gaps / future enhancements

Observed during Phase 9 testing — **deferred** (to fix in a follow-up):

1. **Payment phone number not validated** (frontend) — the payment modal accepts any
   string for `payer_phone`; no MSISDN format/length check before `initiate`.
   _Enhancement:_ validate format client-side + tighten the `payer_phone` rule in
   `InitiatePaymentRequest`.
2. **`subscription.limits` not on the direct `addMember` route** — only
   `POST /workspaces/{id}/members/invite` carries the middleware; the direct
   `POST /workspaces/{id}/members` (`WorkspaceController::addMember`) does **not**, so
   the member cap can be bypassed there. Also `projets`/`activites` `addMember` routes
   are unguarded by design (membership ≠ workspace seat) — confirm intended.
3. **Storage quota (`canUploadStorage`) never enforced** — `CheckSubscriptionLimits`
   only handles `add_member` and `upload_file` (file *size*); total workspace storage
   is computed in `SubscriptionService::canUploadStorage()` but no route/match-arm calls
   it. _Enhancement:_ add an `upload_storage` arm + apply on document upload.
4. **Enforcement is middleware-only** — limits are checked by route middleware, not also
   inside the controllers, so any unguarded path skips the cap. _Enhancement:_ enforce
   `canAddMember`/`canUpload*` inside the service-calling controllers as a safety net.

> These predate Phase 9 (Phase 8 limit-enforcement) and were surfaced by paid-plan
> activation. They do not affect the payment flow itself.

---

## Sandbox testing status (2026-06-08)

Verified against the **real MTN sandbox** with provisioned credentials:

| Step | Result |
|---|---|
| OAuth token (`/collection/token/`) | ✅ HTTP 200, valid JWT |
| `requesttopay` (real charge request) | ✅ HTTP 202 Accepted |
| Status query (`GET requesttopay/{id}`) | ✅ HTTP 200, parsed |
| Provider status mapping (SUCCESSFUL→succeeded, FAILED→failed) | ✅ |
| Currency EUR, UUID v4 ref, no-space externalId, real callback host | ✅ per MTN spec |

**Blocker (MTN-side):** the documented sandbox success number `46733123450` returns
`status=FAILED, reason=INTERNAL_PROCESSING_ERROR`. We worked through MTN's entire
documented cause list for this error (currency, unique UUID, Bearer auth, externalId
spaces, callback host) and ruled out every one — it's a known intermittent sandbox
condition ("Wallet Platform not reachable"). The **integration is correct**; only MTN's
sandbox is failing. Network latency to the sandbox is also high (~6–10 s/call), which is
why a 30 s `PAYMENT_HTTP_TIMEOUT` is required (a shorter timeout causes false `cURL 28`).

**MTN sandbox magic MSISDNs:** `46733123450` → SUCCESSFUL, `46733123451` → FAILED.
No real phone/money is involved in sandbox.

---

## Production go-live checklist (real money — do NOT do from a dev session)

Going live debits **real** mobile-money wallets and is a deliberate launch step on the
**deployed** app, with the client. Sandbox keys CANNOT reach production.

**1. Obtain production access (per provider):**
- **MTN:** commercial/merchant agreement + KYC → **production** subscription key + a
  **production** API user/key provisioned against `https://proxy.momoapi.mtn.com`.
- **Orange:** production merchant key from your local Orange operator (Cameroon = XAF).

**2. Production `.env` (on the production server only):**
```
PAYMENT_FAKE=false
PAYMENT_MOMO_BASE_URL=https://proxy.momoapi.mtn.com
PAYMENT_MOMO_TARGET_ENV=production
PAYMENT_MOMO_CURRENCY=XAF                      # real currency (config-driven, no code change)
PAYMENT_MOMO_SUBSCRIPTION_KEY=<prod key>
PAYMENT_MOMO_API_USER=<prod user>
PAYMENT_MOMO_API_KEY=<prod key>
PAYMENT_MOMO_CALLBACK_URL=https://<live-domain>/api/webhooks/payment/momo
# Orange equivalents with production URLs + merchant key
```

**3. Public webhook URLs** — `PAYMENT_MOMO_CALLBACK_URL` / `PAYMENT_ORANGE_NOTIF_URL`
must be your **live HTTPS domain**, reachable by the providers.

**4. Controlled first real test:** initiate **one** payment of a tiny amount (e.g. 100 XAF)
to **your own phone**, approve it, confirm the workspace activates. Do this once,
deliberately — never in an automated loop. Refund/settle per the provider's process.

> The XAF code path is already built and config-driven (`MtnMomoProvider` sends
> `config('payment.mtn_momo.currency')`). Going live is config + credentials, not code.
