# Phase 5 Bundle A - RC-06 to RC-08

## Baseline and scope

**Closure update (2026-09-29): Bundle A backend CLOSED.** PR #90 merged normally
as `d167ae9d2f0be265cab69d1da073970401801710`; exact-main
[CI 36493519192](https://github.com/johnd-creator/kojaya/actions/runs/36493519192)
passed (3,346 tests / 27,509 assertions, zero failures/errors/skips, 81.41%).
The release owner carried Android live runtime acceptance to Phase 6 QA-06 and
production Firebase/IAM to RC-11. Earlier pre-push BLOCKED statements below are
historical, superseded by this closure. Required production push preflight remains.

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
- Preserved the device registration API. Companion integration subsequently
  changes delivery to recipient-checked data-only messages (see below).
  Partial failures keep push outboxes retryable, honor Retry-After, and use
  exponential minimum-one-minute delay. Other channel retry delays are unchanged.
  Outbox delivery remains at-least-once; successful devices can see duplicates.
- Release preflight rejects legacy/partial/unreadable credentials without
  network calls. Mandatory Android configuration is enforced by deploy.
- Reconciled the payment go-live checklist: changing production credentials or
  sandbox mode does not activate internal simulation. No security bypass added.
- No backend dependency, schema, or frontend change. The user subsequently
  supplied `F:\kojayaapp` and explicitly authorized a separate Android companion
  plus Firebase Messaging dependency. The initial unavailable-Kotlin limitation
  is superseded; real provider/device receipt is still unverified.

Initial post-migration HTTP v1 gate: `FcmHttpV1Test`, `ReleasePreflightTest`,
`PhaseBContractApiTest`, `Sprint4ReliabilityDxTest`, and
`Sprint6WhatsAppNotificationTest`: **86 tests, 729 assertions, PASS**. Pint dirty
check, deployment shell syntax, and Composer strict validation also PASS.
Two development-test issues (JWT test key lookup and subsecond timestamp
comparison) were corrected before this final passing run; no failure suppressed.

**RC-07 repository verdict: PASS WITH CARRIED-FORWARD PREREQUISITES.** Backend
legacy transport is remediated; see companion verification below. Actual push
activation is NOT PASS: Android Firebase options, backend IAM/credentials and
device receipt are absent. QA configuration/device acceptance must be resolved
before claiming push readiness; production activation and independent offsite
recovery remain mandatory RC-11 evidence. Push may not be silently deferred.

### Authorized Android companion and coordinated contract

Android baseline: `b58facb5aa6825bec7f5bcaa0869efca4f67b332`, `F:\kojayaapp`.
Its working tree was clean before companion edits. Changes remain local and
separate from this backend PR; no Android commit/push/PR is claimed.

- Added Firebase Messaging, private receiver, notification channel/icon and
  Android 13+ permission request. Public Firebase options are per build type;
  no server account/key is embedded. No `google-services.json` found in that
  project, including ignored/hidden paths outside generated/cache directories.
- Independent registration loop handles login, SSO, restore, token refresh,
  retries and session cancellation without changing auth business behavior.
  It sends a captured bearer token over a non-redirecting client, binds the
  returned user ID to a session hash in encrypted backup-excluded storage,
  and never logs raw tokens/provider failures.
- Backend Android payload is data-only, high priority/one-hour TTL, with
  reserved `recipient_user_id`, `kojaya_push_version=1`, `title`, `body` and
  existing event data. Receiver verifies current local session/recipient before
  displaying; local logout/forced logout/account switch invalidate display.
  Tapping uses normal authenticated navigation, not arbitrary payload deep links.
- This is a coordinated sender/client rollout. Other/older client compatibility,
  remote account revocation behavior, actual background delivery, permission
  denial UX and process-killed receipt require device acceptance, not inference
  from mocks. The authenticated inbox remains authoritative. SDK token methods
  are deprecated but supported; a future FID migration is distinct from HTTP v1.
- Android gate: `testDebugUnitTest`, `assembleDebug`, `lintDebug` **PASS**;
  **732 tests, zero failures/errors/skips**, lint **0 errors / 70 warnings**.
  Ten new push policy/coordinator/HTTP-contract tests are included. Debug build
  without Firebase options is intentionally not a working-push artifact.
- QA/release configuration gates were exercised with absent settings: both
  **rejected as expected**. Actual configured QA/release builds remain pending.
  Initial manifest duplication, ActivityResult/transitive Fragment lint issue,
  and composition-root allowlist mismatch were fixed; no baseline refreshed or
  lint/test disabled. The new Application is explicitly documented as wiring.

## RC-08 - Security / Secret / Env Verification

**Local verdict: PASS WITH CARRIED-FORWARD PREREQUISITES.**

Strict production preflight now rejects non-HTTPS/credential-bearing APP_URL,
non-secure cookies and disabled HTTP-only cookies without printing values.
WhatsApp logging no longer retains phone/content/raw provider bodies; transport
exceptions become generic delivery failures before outbox error persistence.
Redirects are refused and provider calls have bounded timeouts. Existing auth,
authorization, PII rollback/key and tenant controls remain unchanged.

The tracked-file check found only approved `.env.example`/`.env.playwright.example`
among the targeted env/key/dump patterns. Bounded non-test/non-doc tracked source
signature search found no GitHub token, AWS access-key or private-key header
matches. This is not a comprehensive secret-history scan. Actual `.env`, secret
mounts and production configuration were not printed or inspected.

Codex Security scan `81b28208-1a70-4941-b563-a0de73796e08` completed against
`config/` at `66def7f7b9cf2ae197ccf9cf8cb1bfdce62c01b6`: 19 files reviewed,
zero reportable findings. The parent completed the static review after both
independent workers hit usage limits; independent review is unavailable.
No repository-wide, Android, runtime or production security assurance is claimed.
The workbench recorded a working-tree-change warning: its result belongs to the
original scan snapshot, not the final bundle. Canonical artifacts are retained
locally in the Security workbench, not copied into source control. Tool-reported
usage: 7,300,811 total tokens (including 6,874,496 cached input tokens), four
threads; this is the tool's rollout accounting, not a separate billing estimate.

Initial focused RC-08 regression gate: **64 tests / 274 assertions PASS**.
Final integrated focused gate: **188 tests / 1,281 assertions PASS**, SQLite
`:memory:`; includes all RC-06 and changed RC-07/08 tests plus webhook fail-closed,
SSO, backup status, public-root protection, granular ability cutover and member
serialization. A new assertion initially matched the OAuth request instead of
the FCM request; its URL predicate was corrected and both the 24-test FCM suite
and the complete 188-test gate were rerun successfully.

Operator checks still required: actual HTTPS/cookie/proxy topology, least-privilege
credentials and OS ACLs, PII key/version recovery, database TLS/network controls,
private object ACLs, approved mail delivery without log fallback, queue/scheduler
supervision and offsite retrieval. None are replaced by static configuration PASS.

## Cross-RC self-review and pre-push gate

- Reviewed complete diff from the recorded baseline: no migration, seed, financial
  side-effect change, new backend dependency, auth bypass, production credential,
  generated artifact or unrelated refactor. Android files are not in this PR.
- Migration plan and deploy agree on required Android preflight; stable-production
  version gate is not bypassed for `v1.0.0-rc.5`. Payment simulation claim corrected.
- Client compatibility became a real companion implementation after authorization,
  not merely an operator-config label. Sender rollout must wait for that client.
- Full Windows working-copy Pint initially failed on existing CRLF files; the
  changed-file gate passes. Git archive also applied the Windows conversion on
  the first attempt. Recreating only the temporary snapshot with command-local
  `core.autocrlf=false` produced an LF tree: **full Pint PASS**. All **1,428 tracked
  PHP files** matched working-copy content after newline normalization (zero
  mismatches). No user files/global Git setting were rewritten. Authoritative
  Linux CI remains required.
- Composer strict validation, OpenAPI snapshot check, deployment shell syntax,
  and `git diff --check` pass. Frontend/schema code unchanged; no production or
  shared PostgreSQL database was used. Full CI must still cover frontend/PG and
  the default suite's exclusions/alternate gates.

**BUNDLE_LOCAL_VERDICT: PASS for the integrated repository candidate.** All
focused tests, format, metadata, API snapshot and diff checks listed above pass.
This authorizes the single integrated push/PR for CI, not provider activation.
**Bundle A overall: BLOCKED pending exact-head full CI and required push activation
evidence; not approved for production.** No merge or tag is authorized.

Local logical commits before the final evidence commit:

| Commit | Scope |
| --- | --- |
| `f87e5d91` | RC-06 controlled migration plan |
| `05bab4ee` | RC-07 integration inventory and initial blocker |
| `66def7f7` | RC-07 HTTP v1 server migration |
| `4a361ad7` | RC-07 recipient-checked Android companion contract |
| `050cb9eb` | RC-08 secure configuration and sanitized provider failures |

PR metadata is the authoritative post-push location for exact candidate SHA,
CI run IDs/results and any follow-up failures; this pre-push evidence does not
claim an unexecuted CI result. RC candidate remains `v1.0.0-rc.5`; tag created NO,
tag pushed NO. Next planned bundle is RC-09 + RC-10, after the release owner
reviews outstanding gates; this task does not execute that bundle.
