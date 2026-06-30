# Kojaya — Native Midtrans Core API (QRIS Sandbox) Implementation Plan

## Status

**Planning document only. Do not start coding until the implementation plan from Codex is reviewed and explicitly approved.**

## Product Decision

Kojaya will use **Midtrans Core API / Custom Interface**, not Snap.

The Kojayaku Flutter app must provide a native Kojaya payment experience:
- no Snap checkout page;
- no Snap token;
- no embedded Snap WebView;
- no direct Flutter-to-Midtrans request using a Server Key;
- no payment success based only on a Flutter callback or locally displayed QR code.

Laravel is the payment authority. Flutter talks only to Kojaya APIs. Laravel talks to Midtrans Core API using the Server Key stored only in server-side environment configuration.

## Initial Scope

### Phase 1 — QRIS Sandbox for member payments

Implement and validate one end-to-end native QRIS payment flow in Sandbox for:
1. member dues / savings invoice payment; and
2. loan installment payment **only if the current domain model already represents the installment as a payable `CooperativePayment` or a compatible payment intent**.

Do not implement POS settlement in the first coding pass. However, the payment architecture must stay reusable so POS can use the same Core API payment engine later.

### Phase 2 — POS QRIS

Implement POS QRIS only after Phase 1 is proven with webhook settlement and accounting reconciliation.

A pending QRIS payment must not be treated as a completed sale. Before writing POS code, decide and document the stock-reservation and transaction-state behaviour:
- create a pending POS payment/transaction;
- reserve stock if the current POS design supports it;
- deduct stock / complete sale only after verified settlement;
- release the reservation on expiry, denial, cancellation, or timeout.

### Phase 3 — Additional channels

Add VA first, then e-wallet only when each channel is activated for the Midtrans account and its UX/testing path is understood. QRIS is the first channel.

## Existing Kojaya Foundation — Reuse, Do Not Replace

The repository already contains payment foundations. Codex must inspect and extend them instead of creating a parallel payment system.

Known starting points:
- `config/services.php` contains `services.midtrans` with server key, client key, and production flag.
- `routes/api.php` contains:
  - `POST /api/payments/webhook`
  - `POST /api/payments/charge`
  - member payment-intent and payment-status routes.
- `app/Http/Controllers/Api/ProductionIntegrationController.php`
- `app/Services/Integrations/PaymentGatewayService.php`
- `app/Services/Integrations/MidtransPaymentProvider.php`
- `app/Contracts/Integrations/PaymentGatewayProvider.php`
- `app/Services/Cooperative/CooperativePaymentService.php`
- related payment models, resources, requests, migrations, and tests.

Before editing, inspect the current payment-intent creation path for:
- dues/savings invoice;
- member bills;
- loan installment, if available;
- the `CooperativePayment` model and relationships;
- POS transaction/payment behaviour;
- the current `CooperativePaymentService::reconcile()` side effects.

Do not invent a relationship between payment, invoice, loan installment, ledger, POS transaction, inventory, or journal entry. Derive it from existing models and documented business rules.

## Required Architecture

```text
Kojayaku Flutter
    |
    | 1. create / obtain Kojaya payment intent
    | 2. request QRIS charge
    v
Laravel Kojaya API
    |
    | 3. validate ownership, payable state, amount, idempotency
    | 4. call Midtrans Core API with Server Key
    v
Midtrans Core API (Sandbox)
    |
    | 5. returns pending QRIS transaction + QR generation action
    v
Laravel stores gateway reference/status/payload
    |
    | 6. returns safe payment presentation data to Flutter
    v
Kojayaku Flutter native QR payment screen
    |
    | 7. Sandbox: test via Midtrans QRIS web simulator
    v
Midtrans HTTPS webhook
    |
    | 8. verify signature / optionally verify Status API
    | 9. update gateway status idempotently
    | 10. reconcile accounting/business records exactly once
    v
Laravel payment status endpoint
    |
    | 11. Flutter polls/refreshes its own Kojaya payment status
    v
Native success / pending / expired / failed screen
```

## Non-Negotiable Security Rules

1. `MIDTRANS_SERVER_KEY` stays only in Laravel `.env`, deployment secret manager, and server-side configuration.
2. Flutter must never receive a Server Key, Basic Authorization header, raw Midtrans secret, or a request capable of charging Midtrans directly.
3. Flutter must call Kojaya payment endpoints, never Midtrans charge/status endpoints directly.
4. A QR code being displayed is **not** a successful payment.
5. Only a verified Midtrans notification or verified Midtrans status response may authorize financial reconciliation.
6. All payment-creation requests must use the existing idempotency pattern.
7. All payment access must be scoped to the authenticated member and their own payment/invoice/loan data.
8. Never log secrets, full authorization headers, or sensitive customer data.
9. Never use `migrate:fresh`, broad seeders, or destructive database commands against `kojaya_erp`.

## Core API Rules

### Use QRIS Core API

For QRIS, Laravel must create a Core API charge with a payload shaped around:
- `payment_type: qris`
- `transaction_details.order_id`
- `transaction_details.gross_amount`
- `item_details`
- `customer_details`
- QRIS configuration only when it is confirmed against the current Midtrans account/documentation.

The application order ID must be:
- unique;
- traceable to exactly one `CooperativePayment`;
- stable after initial charge creation;
- safe to expose in logs/support tooling;
- not based only on a random value without a database relationship.

### QR image handling

The current provider reads `qr_string` from the Midtrans response. This must be verified against the actual current QRIS response. Midtrans QRIS documentation commonly returns QR generation URLs inside the `actions` list (`generate-qr-code` / `generate-qr-code-v2`).

Before implementation, perform a short Sandbox spike to determine:
1. whether the Core API response for this account provides `qr_string`, action URLs, or both;
2. whether the action URL can be safely rendered by Flutter without server credentials;
3. whether Laravel should proxy/stream the QR image through an authenticated Kojaya endpoint;
4. whether the QR action URL needs to be retained only in protected server-side `gateway_payload`.

Default-safe decision:
- return a **Kojaya authenticated QR image endpoint** or a safe, time-limited presentation URL to Flutter;
- do not expose server credentials;
- do not make Flutter construct or authenticate a Midtrans QR image request;
- do not add a production-visible debug URL solely for the Sandbox simulator.

### Safe Flutter response contract

The Flutter app should receive a provider-neutral payment presentation response. Exact field names must follow existing API conventions, but the intended information is:

```json
{
  "data": {
    "payment_id": "uuid-or-id",
    "reference": "KOJ-...",
    "status": "PENDING",
    "channel": "QRIS",
    "amount": 100000,
    "expires_at": "ISO-8601 timestamp or null",
    "instructions": {
      "title": "Scan QRIS untuk membayar",
      "description": "Pembayaran akan diperbarui otomatis setelah Midtrans mengonfirmasi."
    },
    "qr_image_url": "Kojaya authenticated/temporary URL or null",
    "poll_after_seconds": 5
  }
}
```

Do not return raw gateway secrets, raw Basic auth data, or a public API contract that locks Flutter to Midtrans internal field names.

## Gateway Status Mapping

Use an explicit, tested mapping between Midtrans and Kojaya payment status.

| Midtrans status | Kojaya gateway status | Reconcile accounting? | Flutter message |
|---|---|---:|---|
| `pending` | `PENDING` | No | Menunggu pembayaran |
| `settlement` | `PAID` | Yes, exactly once | Pembayaran berhasil |
| `capture` (only where applicable and accepted) | `PAID` | Yes, exactly once | Pembayaran berhasil |
| `expire` | `EXPIRED` | No | Pembayaran kedaluwarsa |
| `deny` | `FAILED` | No | Pembayaran ditolak |
| `cancel` | `CANCELLED` | No | Pembayaran dibatalkan |
| `refund` / `partial_refund` | explicit refund state | No automatic reverse without approved refund workflow | Pengembalian dana diproses |

The current transition map and webhook parser must be reviewed together. In particular:
- do not map `expire` to a generic failure if the product needs a distinct retry/expiry experience;
- make refund states valid transitions only when refund workflows are implemented;
- preserve idempotency when Midtrans repeats a webhook;
- do not reconcile twice when a webhook is replayed or the member refreshes the app.

## Webhook Requirements

Use the existing `POST /api/payments/webhook` route and improve it only where required.

Before accepting a state change:
1. validate request structure;
2. verify `signature_key` using the Midtrans formula:
   `SHA512(order_id + status_code + gross_amount + ServerKey)`;
3. optionally query Midtrans Status API server-to-server for high-risk troubleshooting/reconciliation;
4. locate exactly one Kojaya payment using the gateway reference/order ID;
5. enforce valid state transition;
6. persist the raw provider payload safely;
7. reconcile financial side effects only if status becomes paid and `reconciled_at` is still empty;
8. return HTTP 200 after successful handling, including safe idempotent replays.

Webhook testing requires a public HTTPS callback URL. Localhost alone cannot receive a Midtrans callback. Use a controlled temporary tunnel for development, such as Cloudflare Tunnel or ngrok, and configure the Sandbox notification URL to the tunnel endpoint.

## Sandbox Testing Plan

### Environment

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=<sandbox server key only in local .env>
MIDTRANS_CLIENT_KEY=<sandbox client key if a separate client-facing feature needs it>
```

Do not commit real values.

### Manual end-to-end test

1. Start Laravel and Flutter locally.
2. Start a temporary public HTTPS tunnel that forwards to Laravel.
3. Configure Midtrans Sandbox HTTP notification URL to:
   `https://<temporary-tunnel-host>/api/payments/webhook`
4. Sign in as a member who owns a real local test invoice/payment.
5. Create a QRIS payment intent through the member flow.
6. Confirm Laravel creates exactly one `CooperativePayment`, one Midtrans order ID, and a `PENDING` gateway status.
7. Confirm Flutter displays a native Kojaya QRIS payment screen.
8. Use the Midtrans Sandbox QRIS simulator with the generated QR image URL according to current Midtrans Sandbox documentation.
9. Confirm Midtrans invokes the webhook.
10. Confirm signature verification succeeds.
11. Confirm the payment changes to `PAID`.
12. Confirm reconciliation runs exactly once.
13. Confirm Flutter status refresh shows success from the Kojaya payment-status endpoint.
14. Repeat the webhook/reload status to prove no duplicate ledger, invoice settlement, POS sale, or journal side effect is created.
15. Test an expired/denied path and confirm no settlement/reconciliation occurs.

Sandbox uses a web-based simulator. It does not prove real banking-app QR scans or real app deeplinks. That validation belongs to Production activation/readiness testing.

## Implementation Phases

### Phase A — Discovery and Gap Report (no code)

Codex must:
1. read `AGENTS.md`, `docs/project.md`, `docs/architecture.md`, `docs/api.md`, `docs/plan.md`, and `docs/decisions.md`;
2. inspect current payment classes/routes/models/requests/tests;
3. inspect the Flutter repo contract for payment screens;
4. output:
   - current payment flow;
   - exact entities used for dues, loan installment, and POS;
   - existing QRIS gaps;
   - exact files that need changes;
   - migration need, if any;
   - API contract change proposal;
   - test plan;
   - unresolved business decisions;
5. stop and wait for explicit approval.

### Phase B — QRIS Sandbox Member Payment

Implement only the smallest verified vertical slice:
- one member invoice/payment intent;
- Core API QRIS charge;
- native Flutter QR payment screen;
- webhook verification;
- payment status refresh;
- exactly-once reconciliation;
- focused automated tests;
- manual Sandbox simulator checklist.

Do not implement VA, e-wallet, POS, refunds, or a redesigned general payment engine in this phase unless the current code requires a small compatible adjustment.

### Phase C — Loan installment mapping

After Phase B passes:
- inspect the actual loan installment model and payment linkage;
- create/extend payment intent only if it uses the same `CooperativePayment` lifecycle;
- confirm the settlement side effect marks only the intended installment paid and updates the loan/ledger exactly once;
- add feature tests and Sandbox manual verification.

### Phase D — POS QRIS

After member payments and loan installments are stable:
- design pending POS transaction/reservation state;
- create charge before final POS completion;
- settle inventory, sales, member points, accounting, and receipt only after verified settlement;
- define expiry/cancel release rules;
- test duplicate webhook, stock reservation, and cashier retry scenarios.

## Required Automated Tests

Add focused PHPUnit tests. Tests must use the test database, factories, HTTP fakes, and controlled webhook fixtures—not the shared local `kojaya_erp` database.

Minimum tests:
1. member cannot charge another member's payment;
2. member can charge an owned payable invoice using QRIS;
3. request uses existing idempotency behavior;
4. Midtrans QRIS payload contains correct order ID, gross amount, item, and customer data;
5. QR presentation response is safe and does not expose server credentials;
6. invalid Midtrans signature is rejected and does not reconcile;
7. settlement webhook updates status and reconciles exactly once;
8. replayed settlement webhook does not duplicate financial side effects;
9. expire/deny does not reconcile;
10. payment status endpoint returns only the authenticated member's payment;
11. existing dues/savings business rules remain intact;
12. if loan installment is included, only the intended installment is settled.

Run:
- `vendor/bin/pint --dirty --format agent`
- the focused PHPUnit files with `php artisan test --compact ...`

## Definition of Done for Phase B

- [ ] Uses Midtrans Core API only; no Snap dependency/redirect/WebView.
- [ ] `MIDTRANS_IS_PRODUCTION=false` is used for Sandbox.
- [ ] QRIS charge is created server-to-server.
- [ ] Flutter renders a native Kojaya payment screen.
- [ ] Server Key never reaches Flutter.
- [ ] Sandbox QRIS simulator can drive a real webhook settlement.
- [ ] Webhook signature is validated.
- [ ] Settlement reconciles exactly once.
- [ ] Payment status refresh works after app reopen/reload.
- [ ] Expired/failed state is understandable and retry-safe.
- [ ] Focused tests, Pint, and relevant static checks pass.
- [ ] No destructive database operation, broad seeding, or unrelated refactor occurred.
- [ ] Documentation is updated with the final API contract and operational tunnel/Sandbox instructions.

## Production Readiness Gate — Not Part of Sandbox Coding

Do not switch `MIDTRANS_IS_PRODUCTION=true` merely because Sandbox works.

Before production:
- obtain Midtrans Core API Production activation;
- activate and confirm required channels;
- configure production HTTPS notification endpoint;
- configure production secrets via deployment secret management;
- verify production webhooks, logging, alerting, retries, and reconciliation monitoring;
- test a controlled real transaction and refund/reversal process;
- define finance reconciliation SOP and customer support handling;
- obtain business/legal/finance approval.

---

# Codex Kickoff Prompt

Paste this into Codex from the Laravel `kojaya` repository:

```text
Read AGENTS.md and these documents before doing anything:
- docs/project.md
- docs/architecture.md
- docs/api.md
- docs/plan.md
- docs/decisions.md
- docs/plan_midtrans_native_core_api_qris_sandbox.md

Goal:
Implement native Kojaya QRIS payments using Midtrans Core API only. Do NOT use Snap, Snap token, Snap checkout, Snap WebView, or direct Flutter-to-Midtrans calls.

Current target:
Phase A only — discovery and gap report. Do not edit code, create migrations, install dependencies, change configuration, run destructive commands, or commit anything.

Inspect:
- config/services.php
- routes/api.php
- ProductionIntegrationController
- PaymentGatewayService
- MidtransPaymentProvider
- PaymentGatewayProvider
- CooperativePaymentService
- CooperativePayment model and relationships
- payment intent routes/controllers/resources/requests
- current PHPUnit payment tests
- the Kojayaku Flutter payment contract/screens if present

Return:
1. Current end-to-end payment flow and existing Midtrans Core API behaviour.
2. Exact gaps for QRIS Sandbox native rendering, webhook handling, status mapping, and safe Flutter API response.
3. Whether dues/savings, loan installment, and POS can all use the existing CooperativePayment model today. Do not guess.
4. Exact files to change in Phase B.
5. Any migration/API contract changes needed.
6. Focused PHPUnit test plan and manual Sandbox + public HTTPS webhook test plan.
7. Risks, unknowns, and decisions that need user approval.

Stop after the report and wait for explicit approval.
```
