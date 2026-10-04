# Phase 6 — Bundle B server checkpoint

Assessment: 2026-10-04 UTC. This checkpoint supersedes pending server statuses and preserves the earlier failures. Bundle B remains incomplete pending development-PC acceptance.

```text
REPLACEMENT_DESIGNATION=v1.0.0-rc.14 (untagged)
APPLICATION_CANDIDATE_SHA=043b5b004d66f04e60eef2b0c7a8cdb38fce278a
PROMOTION_STATUS=PROMOTED_AND_DEPLOYED_QA_CANDIDATE
REPAIR_ITERATIONS=3
QA_03=PASS
QA_04=PASS
QA_05_APPLICABLE_AUTH_SMOKE=PASS
QA_06=PASS
QA_07=PASS
QA_08_BACKEND_PREREQUISITE=READY
QA_09_BACKEND_PREREQUISITE=READY
ANDROID_QA_08=NOT_EXECUTED_ON_QA_SERVER
PHYSICAL_DEVICE_QA_09=NOT_EXECUTED_ON_QA_SERVER
BUNDLE_B=INCOMPLETE_PC_HANDOFF
PUBLIC_QA_TRAFFIC=HELD
```

The clean, exact candidate serves QA with 182 applied migrations, none pending, maintenance off, and all 30 compiled assets returning HTTP 200. Only the application version configuration changed; the application key and other configuration were preserved. Final rc.14 validation recorded 192 HTTP requests with zero HTTP 5xx.

QA-06 passed registration, pending-member restrictions, separate verification/final approval, persistent member identity, authenticated web/API access, logout, cross-member isolation, and deactivation/token rejection/reactivation. Two synthetic members remain active, using existing roles.

QA-07 passed savings/payment idempotency, maker-checker approval, dues reconciliation, protected PDF receipts, financial isolation, loan calculation and distinct manager review/final approval. Fourteen independent readback invariants passed. Two payments, two ledger entries and two receipts reconcile to Rp125,000 savings. One approved loan has Rp500,000 principal, Rp515,000 scheduled total and three installments; no disbursement occurred. These are synthetic QA artifacts, not production/reference data.

QA-08 backend readiness includes ten populated responses validated against the served OpenAPI schemas and tested 401/403/422 boundaries. Android source, build, endpoint consumption and application behavior require development-PC execution.

QA-09 backend readiness includes matching FCM configuration/credentials, OAuth success, HTTP v1 validate-only HTTP 200 without delivery, a domain outbox database-worker pending-to-sent transition, and a targeted historical replay without duplicate effects. Both general outbox records are sent; six cooperative outbox records are delivered. The worker and scheduler are active, with repeated successful scheduler completions, zero queued/failed jobs and no worker restart. Device registration rejection boundaries passed; no device token was fabricated. Real token association, reception and notification tap remain untested.

Five fixture accounts remain for audit relationships and PC continuation. All API tokens were revoked, old tokens rejected and web sessions logged out. Staff/peer plaintext test credentials were removed; one member credential remains private for the PC handoff. Known test secrets were absent from application logs and audit records. Financial/account deletion was not improvised. Reference counts remain 16 roles, 129 permissions and 476 role-permission links; no seeder ran. The original reference checksum serializer was unavailable, so final unchanged hashes are not claimed.

The initial member HTTP 500, queue replay failure, two pre-migration rc.13 cutover failures and one pre-migration rc.14 cutover failure remain retained. Final rc.14 cutover succeeded after the durable third repair and bounded runtime metadata recovery. Read-only operator diagnostic SQL errors and expired-client session setup responses are classified separately from runtime acceptance. No fourth source repair or financial policy change occurred.

[Repair PR #104](https://github.com/johnd-creator/kojaya/pull/104) corrected user-guide permissions and pagination schemas. [Repair PR #106](https://github.com/johnd-creator/kojaya/pull/106) scopes the private file-creation mask to QA PHP-FPM, preserving CLI/other environments and deployment ownership guards. [PR CI #549](https://github.com/johnd-creator/kojaya/actions/runs/37196118601) and [UI audit #315](https://github.com/johnd-creator/kojaya/actions/runs/37196118626) passed. Exact-candidate [CI #550](https://github.com/johnd-creator/kojaya/actions/runs/37197521587) passed all 18 mandatory jobs: 3,451 tests, 28,445 assertions, zero errors/failures/skipped tests, 81.41% line coverage against the unchanged 60% gate.

Public QA traffic remains held with HTTPS HTTP 503. Shared PHP-FPM masters were unchanged. Production and legacy data were not accessed or changed.

## Development-PC handoff

Use the authoritative workspace `F:\kojayaapp`. Record its actual Git SHA, working-tree state, QA build variant and Firebase project identity. Verify controlled QA HTTPS access while keeping general traffic held; no Android source was searched, copied, cloned or built on this server.

Run the Android tests/lint and QA build, then exercise member authentication, session/logout, profile, savings, dues, payments/receipts, loans and authorization boundaries against the approved backend. On a physical device verify permission, real token registration, recipient-correct notification delivery and tap behavior through the normal backend/outbox path. Retain delivery evidence and complete supported fixture/credential cleanup afterward. Backend readiness does not substitute for Android or physical-device acceptance; Bundle B must remain incomplete until both pass.
