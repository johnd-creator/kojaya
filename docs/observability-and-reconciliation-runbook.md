# Observability and Reconciliation Runbook

Status: implementation and operational runbook prepared; provider alerting and
live staging validation remain pending.

Scope: Document 08-E for the cooperative backend internal alpha. This runbook
does not enable production integrations, send notifications, perform payment,
or change external monitoring settings.

## Current evidence

The repository already exposes the following instrumentation and operational
surfaces:

| Signal | Repository evidence | Local verification |
| --- | --- | --- |
| Application health | `App\Monitoring\Health`, `/monitoring/health`, `/api/monitoring/health` | `tests/Feature/PhaseD1ObservabilityTest.php` |
| Correlation and response timing | `CorrelationIdMiddleware`, `CorrelationIdProcessor`, `X-Correlation-ID`, `X-Response-Time-Ms` | `tests/Feature/PhaseD1ObservabilityTest.php` |
| Queue failures | `FailedJobListener`, `failed_jobs`, monitoring metrics | `tests/Feature/PhaseD1ObservabilityTest.php` |
| Notification outbox | `notification_outboxes`, retry command, failed-outbox health count | `tests/Feature/PaymentNotificationOutboxTest.php` |
| Payment recovery | `RecoverStaleChargeCreating`, reconciliation incidents, recovery scheduler | `tests/Feature/PaymentChargeRecoveryTest.php` |
| Payment settlement | member payment settlement and cooperative payment reconciliation services | `tests/Feature/MemberCoffeeOrderApiTest.php`, `tests/Feature/MemberStoreOrderApiTest.php`, `tests/Feature/PaymentCanonicalItemTest.php` |
| Scheduler registration | Laravel scheduler in `routes/console.php` | `php artisan schedule:list` |
| Audit trail | `AuditContext` and lifecycle/domain audit events | Document 05 audit contract tests |

The health implementation returns component status and safe error codes. It
does not return exception messages, credentials, webhook bodies, tokens, or
PII when a health component fails. Provider availability is represented as a
status; it is not proof that a live provider transaction succeeded.

## Signal and alert contract

The following is the proposed minimum alert contract. Thresholds and owners
are recorded here for approval; they are not silently applied to a monitoring
provider by this change.

| Signal | Condition / threshold | Severity | First response | Escalation owner |
| --- | --- | --- | --- | --- |
| API/application errors | HTTP 5xx rate above 2% for 5 minutes, or a repeated unhandled exception | High | On-call application operator correlates the request ID and checks the latest deployment | Backend maintainer |
| Authentication/SSO failures | More than 5 consecutive provider or session-bootstrap failures in 10 minutes | High | Check auth logs using correlation IDs; disable only the affected integration if approved | Security owner |
| Queue failures | Any failed job for a critical queue, or more than 3 failures in 10 minutes | High | Inspect the failed-job class and retry policy without exposing payloads | Backend maintainer |
| Scheduler heartbeat | No expected scheduler heartbeat for 2 consecutive intervals | High | Check scheduler process, host clock, and `schedule:list` registration | Operations owner |
| Notification outbox | Failed outbox rows exceed 0 for 10 minutes, or oldest pending row exceeds the approved retry window | Medium | Inspect retry command and provider-disabled state; do not resend manually without idempotency review | Operations owner |
| Payment charge recovery | Any `UNKNOWN` recovery or reconciliation incident | Critical | Freeze a second charge attempt and open a finance reconciliation incident | Finance owner |
| Stale reservations | Expired reservation remains active after the expiry schedule interval | High | Run the approved expiry/reconciliation procedure and inspect the reservation state transition | Payment owner |
| Paid but unsettled | Any paid gateway/member intent without settlement after the approved provider latency window | Critical | Verify provider reference and settlement incident; do not create a second charge | Finance owner |
| Idempotency conflict | A repeated client reference produces a new order or payment rather than a replay/conflict | High | Preserve the original reference and investigate the request correlation chain | Backend maintainer |
| Reconciliation mismatch | Amount, reference, or lifecycle state differs between local record and provider evidence | Critical | Stop automated retry and reconcile against the provider using the approved finance runbook | Finance owner |
| Database/storage health | Health component is degraded, database check fails, or storage is not writable | Critical | Keep the service protected, inspect infrastructure, and do not run destructive recovery commands | Operations owner |
| Backup integrity | Checksum mismatch or backup older than the approved RPO | Critical | Mark the backup unusable and notify the recovery owner; do not delete other copies | Operations owner |
| Deployment | Preflight failure, failed migration, or deployment remains in maintenance mode | High | Preserve maintenance mode and inspect the exact deployment SHA | Release engineer |

The proposed thresholds require approval of the maintainer model, notification
channel, maintenance window, and RTO/RPO before they become external alerts.

## Safe logging and correlation rules

1. Every API incident should retain the response `X-Correlation-ID` and the
   deployment short SHA. A client-supplied correlation ID may be used only as
   a reference identifier; it is not a secret.
2. Logs may contain event type, safe resource type, non-sensitive internal
   reference IDs, status, and timing. They must not contain access tokens,
   payment credentials, webhook payloads, encrypted keys, raw PII, or full
   exception messages in health responses.
3. When triaging a payment incident, use the local payment/intent identifier
   and provider reference only in the restricted finance workflow. Do not copy
   customer payloads into issues or chat.
4. A provider timeout or unknown result is a reconciliation incident, not a
   permission to create another charge.
5. Evidence should record command, timestamp, environment class, deployment
   SHA, exit code, and safe summary. Redact all values that could authenticate
   or identify a customer.

## Triage procedures

### API or application error

1. Capture the UTC/WIB timestamp, endpoint class, response status, correlation
   ID, and deployment short SHA.
2. Check `/api/monitoring/health` through an authenticated, read-only staging
   request or the approved host health check.
3. Compare the error start time with the deployment and queue/scheduler
   timeline.
4. If the error is related to payment, reservation, or settlement, stop retry
   attempts and hand off to the finance reconciliation owner.

### Queue or scheduler failure

1. Inspect failed-job metadata and scheduler registration without printing job
   payloads.
2. Verify that the worker and scheduler processes are alive on the staging
   host.
3. Retry only through the documented, idempotent command after the failure
   class is understood.
4. Escalate repeated failures to the backend maintainer and preserve the first
   correlation/reference IDs.

### Payment reconciliation mismatch

1. Keep the local payment or intent in its safe unresolved state.
2. Record the local reference, provider reference, amount comparison result,
   and lifecycle states in the restricted reconciliation record.
3. Do not issue a refund, second charge, manual settlement, or database update
   without finance-owner approval and provider evidence.
4. Close the incident only after the local ledger, settlement state, and
   provider evidence agree.

## Verification commands

The following commands are read-only or test-environment checks. Run them only
against an isolated test/staging environment with the required operator
authorization:

```bash
php artisan test --compact tests/Feature/PhaseD1ObservabilityTest.php
php artisan test --compact tests/Feature/PaymentChargeRecoveryTest.php
php artisan test --compact tests/Feature/PaymentNotificationOutboxTest.php
php artisan test --compact tests/Feature/MemberCoffeeOrderApiTest.php
php artisan test --compact tests/Feature/MemberStoreOrderApiTest.php
php artisan test --compact tests/Feature/PaymentCanonicalItemTest.php
php artisan schedule:list
```

For staging health and process checks, use the bounded checker and runbook:

```bash
bin/staging-preflight.sh --sha "$DEPLOYMENT_SHA" --health-url "$STAGING_HEALTH_URL" --strict-production
```

Do not place credentials in command arguments. Do not call live provider APIs,
send WhatsApp/FCM notifications, create real payments, or use production data
as part of this verification.

## Operational ownership and open evidence

The following items require owner approval or an environment that is not
available in this repository-only task:

| Item | Status | Required evidence |
| --- | --- | --- |
| Local instrumentation and safe failure output | Implemented; focused verification pending on this branch | Passing focused observability tests and CI job |
| Alert thresholds and escalation recipients | Pending owner decision | Approved alert policy and provider configuration record |
| Staging alert delivery | Blocked | Staging alert event with safe artifact and acknowledgement |
| Provider-specific monitoring | Pending | Sandbox/provider credential approval and redacted validation record |
| Payment reconciliation rehearsal | Pending | Isolated sandbox incident and finance-owner closure |
| Production monitoring enablement | Out of scope | Separate production change approval |

Document 08-E is therefore not eligible for a passed closeout checkbox until
the pending live alert and reconciliation evidence is supplied.
