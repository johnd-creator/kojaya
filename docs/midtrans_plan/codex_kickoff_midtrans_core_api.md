# Codex Kickoff — Midtrans Core API QRIS Sandbox

Paste this from the Laravel `kojaya` repository:

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
