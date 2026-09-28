# Phase 5 Bundle A - RC-06 to RC-08

## Baseline and scope

- Audit date: 2026-09-28 (Asia/Jakarta).
- Clean starting main: `7acc96bd706127e6945e8beff730fd1ae564e902`, after PR #89.
- Branch: `astra/phase5-bundle-a-rc06-rc08`; one integrated push/PR after local gates.
- Existing release candidate label: `v1.0.0-rc.5` (application SemVer omits `v`).
  No version promotion, production approval, merge, or tag is authorized here.
- No production database, credentials, provider calls, or shared `kojaya_erp`
  mutation. Local tests use forced SQLite `:memory:` in `phpunit.xml` and
  `Tests\TestCase`; cached application config is absent.
- Graph/Boost tools unavailable; evidence is source inspection and focused tests.

## RC-06 - Production Migration Plan

**Local verdict: PASS (plan).** Execution is not claimed.

The [bootstrap manifest](PRODUCTION-BOOTSTRAP-MANIFEST.md#rc-06-production-migration-plan)
now separates fresh installation from upgrades, defines operator/backup/drain
gates, pending-ledger and snapshot rehearsal, mutation hotspots, post-migration
checks, and recovery boundaries. Source inventory: 182 migration files; no new
schema change. Existing migration safety and rollback safeguards are preserved.

Findings: the deployment script's maintenance/restart sequence is not proof of
fleet-wide write quiescence; historical migrations can backfill/remap tenant
data and permissions; empty-schema success is not upgrade evidence; generic
schema rollback can be destructive or explicitly rejected. These are addressed
by a controlled plan, not by editing released migrations or reseeding production.

Local focused command (installed SQLite DLLs loaded explicitly on Windows):

```text
php -d extension=php_sqlite3.dll -d extension=php_pdo_sqlite.dll vendor/bin/phpunit --configuration phpunit.xml tests/Unit/DatabaseMigrationSafetyTest.php tests/Feature/Security/PiiMigrationGuardTest.php tests/Feature/Security/PiiDatabaseMigrationsCompatibilityTest.php tests/Feature/Security/AttendanceRolePermissionCutoverTest.php tests/Feature/Security/BankBatchPermissionCutoverTest.php
```

Result: **30 tests, 174 assertions, PASS**. No production migration status or
live PostgreSQL upgrade rehearsal was run. Production pending-ledger review,
snapshot rehearsal, downtime/lock budget, recovery approval, and fleet drain
evidence are carried to RC-11; they do not prevent RC-07/08 repository checks.

## RC-07 - Integration Configuration Check

**Initial finding:** push activation was blocked by the legacy FCM implementation.
The release owner explicitly requires Android push and authorized HTTP v1
migration before Bundle A can pass. See remediation and final local gate below.

`app:release-preflight` checks credential completeness for Midtrans, WhatsApp,
and FCM, not provider connectivity, entitlement, delivery, or production
readiness. `CONFIGURED` must not be recorded as an integration smoke PASS.
Production settings were not read. The matrix describes source/template state.

| Integration / owner | Repository configuration and safe state | Required production evidence / RC-11 gate |
| --- | --- | --- |
| Midtrans / payment owner | `services.midtrans`; three credentials blank in template, sandbox default, simulation false. Runtime forbids simulation in production even if requested. Partial credentials fail preflight. | Approved merchant and enabled channels, mode/key match, HTTPS callback, valid/invalid signature and retry/reconciliation evidence under separately approved provider testing. Leave disabled until approved; no live charge here. |
| WhatsApp / messaging owner | `services.whatsapp`; token and phone ID blank; Graph endpoint v20.0, country code 62. Runtime requires opt-in and both credential fields. | Confirm currently supported Graph version/account entitlement, opted-in synthetic recipient, applicable messaging/session/template rules, failed-delivery handling, and redacted delivery proof. No messages sent here. |
| Android FCM / mobile owner | `services.fcm`; migrated to explicit project + mounted service account, HTTP v1 payload and OAuth Bearer auth; legacy key rejected by preflight. | Android push is mandatory. IAM/API activation, credential provisioning, and actual synthetic device receipt are required at RC-11; no Kotlin/device compatibility claim from local tests. |
| iOS push / mobile owner | APNs path is a logging placeholder, not delivery. | Do not advertise iOS push as ready; explicit scope decision or separate implementation required. |
| Google SSO / identity owner | `services.google` / `auth_sso`; disabled by default, blank client credentials; redirect derives from APP_URL. | Approved client/audience/redirect/domain policy, no unintended account linking, TLS callback, and synthetic login/denial proof. Disabled is acceptable only when sign-in scope explicitly allows it. |
| Mail / operations owner | `config/mail.php`; `log` template transport, not delivery. SMTP and named transports need their own credentials; not covered by release-preflight integration completeness. | Choose approved transport/sender/TLS and test verification/reset mail to a controlled account without retaining token-bearing mail in general logs. |
| Private storage / operations owner | `filesystems`: local private root, private employee/project documents; payment-proof legacy public fallback true by default. S3 credentials blank. | Verify private remote ACL/root and access denial; approved legacy proof/document inventory and migration/cleanup under RC-04 conditions. Never disable fallback blindly or copy real files here. |
| Backup offsite / recovery owner | `operations.backup`: primary enabled, offsite false/no disk, require_offsite false by default. | Approved independent private target; copy + checksum + manifest + independent retrieval + healthy status. Mandatory before go-live, synchronous only per approved policy. Same-host replication is insufficient. |
| Queue/cache/session/scheduler / operations owner | Template uses sync queue and file cache/session. Command availability is not supervisor readiness. | Approved persistent topology, multi-host consistency, worker/scheduler heartbeat and synthetic execution, failure alert, drain/restart plan. |

FCM incompatibility is source-backed and independently corroborated by the
[AWS FCM authentication migration guidance](https://docs.aws.amazon.com/sns/latest/dg/sns-fcm-authentication-methods.html)
describing retirement of the legacy key API and HTTP v1 token authentication.
The old Firebase `migrate-v1` URL returned 404 during this audit; the current
[HTTP v1 contract](https://firebase.google.com/docs/cloud-messaging/send/v1-api)
was used for remediation. No live provider credential or delivery was used.
Empty HTTP v1 configuration prevents outbound calls but does not satisfy this
release: deploy now requires `--require-android-push`, and all-active-device
delivery failure remains retryable, not falsely reported as sent.

Focused SQLite tests: `ReleasePreflightTest`, `PaymentWebhookFailClosedTest`,
`Sprint6WhatsAppNotificationTest`, `GoogleSsoFlowTest`, and
`BackupStatusCommandTest`: **78 tests, 366 assertions, PASS** using the same
explicit SQLite DLL invocation as RC-06. Provider flows use synthetic fixtures
and mocked responses; these are configuration/behavior regressions, not live
provider smoke tests. This was the initial audit gate, before HTTP v1 changes.

### RC-07 remediation

- Added service-account RS256 OAuth exchange using existing JWT/OpenSSL and
  Laravel HTTP facilities, fixed HTTPS Google destinations, redirect refusal,
  bounded request timeouts, credential validation and process-local token expiry.
- Converted push payload/acknowledgment/error handling to HTTP v1. Raw device
  tokens, notification content and provider bodies are no longer logged by push.
  Only typed FCM UNREGISTERED revokes a token; auth/payload/quota failures do not.
- Preserved the device registration API and notification/string-data semantics.
  Partial failures keep push outboxes retryable, honor Retry-After, and use
  exponential minimum-one-minute delay. Other channel retry delays are unchanged.
  Outbox delivery remains at-least-once; successful devices can see duplicates.
- Release preflight rejects legacy/partial/unreadable credentials without
  network calls. Mandatory Android configuration is enforced by deploy.
- Reconciled the payment go-live checklist: changing production credentials or
  sandbox mode does not activate internal simulation. No security bypass added.
- No dependency, schema, frontend or mobile repository change. The referenced
  Kotlin repository is unavailable here; real client compatibility stays pending.

Final focused HTTP v1 gate: `FcmHttpV1Test`, `ReleasePreflightTest`,
`PhaseBContractApiTest`, `Sprint4ReliabilityDxTest`, and
`Sprint6WhatsAppNotificationTest`: **86 tests, 729 assertions, PASS**. Pint dirty
check, deployment shell syntax, and Composer strict validation also PASS.
Two development-test issues (JWT test key lookup and subsecond timestamp
comparison) were corrected before this final passing run; no failure suppressed.

**Final RC-07 local verdict: PASS WITH CARRIED-FORWARD PREREQUISITES.** The
repository compatibility blocker is remediated. Production IAM/credentials,
actual Android receipt, provider activation and independent offsite recovery
are still mandatory RC-11 evidence; push may not be silently deferred.
