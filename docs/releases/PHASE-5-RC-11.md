# Phase 5 RC-11 — QA Deployment Readiness Gate

**Assessment date:** 2026-09-29

**Verdict:** **RC-11 BLOCKED**

**Scope:** readiness to start Phase 6 QA. No QA or production deployment,
database connection, migration, member import, restore, provider delivery, or
secret access was performed for this assessment.

## 1. Baseline and candidate identity

| Evidence | Result |
| --- | --- |
| Requested baseline `main` | `007130ee1ecb527a8a7c324ec271c46b984c3881` |
| Local `main` / `origin/main` after fetch | Both equal the requested SHA; ahead/behind `0/0` |
| Candidate source | `v1.0.0-rc.6`, same source tree as baseline |
| Exact-head CI | [Run 36523950378](https://github.com/johnd-creator/kojaya/actions/runs/36523950378), run #487, `push` on `main`, exact SHA, completed `success` |
| Newer/unreviewed change | None at assessment start; release label has no new source commit |
| Release tag | No tag created or pushed by this assessment |

**Candidate identity: PASS.** The main tree is Bundle B's reviewed tree. CI
success proves repository checks only; it does not establish QA host readiness.

## 2. Workstation inventory and QA target

The configured QA host/domain, application path, database identity, host owner,
and provisioning record were not present in the repository or available
connections. No QA server was identified. The Windows developer workstation is
not the required Linux QA target.

Safe workstation inventory (not QA evidence): Windows PowerShell host,
AMD64; PHP 8.4.25; Composer 2.10.3; Node 24.21.0; npm 11.19.0; PostgreSQL
client 18.6. A local PostgreSQL 18 Windows service reports Running, but no local
listener was found on TCP 5432. No Nginx or Docker executable was found on PATH.
The OS caption query was denied, so no Windows edition is asserted. The local
workspace `.env` is not the QA configuration; only key-presence checks were
made, never values. Its FCM project/service-account entries are absent and its
offsite disk entry is empty.

| RC-11 area | Status | Evidence / missing proof |
| --- | --- | --- |
| Candidate identity | PASS | SHA, tree, and exact-main CI above |
| QA environment | BLOCKED | No Linux QA host inventory, URL/TLS topology, application path, or approved QA mode |
| PostgreSQL target | BLOCKED | No QA connection, identity, version, permissions, extension/capacity or TLS evidence; no QA migration ledger |
| Migration snapshot rehearsal | BLOCKED | Required QA-like PostgreSQL snapshot/upgrade rehearsal and before/after ledger counts are absent |
| Production-like data rehearsal | BLOCKED | No approved sanitized snapshot or reconciliation counts/totals; no real data was read |
| Member import readiness | PASS (repository) | Role/route rules and synthetic import tests pass; QA dataset/rehearsal is still pending |
| Backup system | PASS (repository) | Backup create/verify/status tests and exact-main PostgreSQL restore drill pass in CI |
| Independent offsite backup | BLOCKED | No QA/production target, replication/hash/manifest evidence, or independent retrieval receipt |
| QA restore drill | BLOCKED | CI drill is isolated test evidence, not a restore from the actual QA backup into its recovery target |
| Traffic/writer hold | BLOCKED | No actual ingress, API, external-writer or multi-host control identified |
| Queue/workers | BLOCKED | Code defaults and restart signal are known; no QA driver, supervisor, processes, ownership or observed restart evidence |
| Scheduler | BLOCKED | Laravel scheduled tasks are defined; no QA cron/timer/container scheduler, owner, or stop/resume proof |
| TLS/reverse proxy | BLOCKED | No QA hostname, certificate, forwarding, HTTP-to-HTTPS, or trusted-proxy evidence |
| Private storage/permissions | BLOCKED | Repository defines private disks; QA mount layout, ACLs and web isolation are unverified |
| PII/encryption keys | BLOCKED | Test keys/guards pass; QA APP_KEY and historical PII key availability/decryption have not been verified |
| FCM backend/provider | BLOCKED | HTTP v1 implementation and preflight are in source; QA project, IAM, mounted account file and safe auth/config proof unavailable |
| Other integrations | BLOCKED | No QA inventory/classification of Midtrans, WhatsApp, SSO, mail, storage or other providers |
| SSH/host identity | BLOCKED | Workflow uses `StrictHostKeyChecking=accept-new` (TOFU); approved host fingerprint and QA deployment secrets cannot be verified |
| GitHub deployment approval | BLOCKED | Public repository metadata reports `main` unprotected and no environments configured; reviewer/secret scoping evidence is absent |
| Capacity | BLOCKED | No QA filesystem/database capacity or isolated restore-space measurements |
| Phase 6 smoke plan | PASS (plan only) | Checklist below is prepared; none of its probes has been run against QA |

Statuses are fail-closed: absence of evidence is BLOCKED for mandatory
environment prerequisites, not PASS. The local `.env` check is recorded only to
prevent it being mistaken for QA configuration.

## 3. Deployment authority and code-level readiness

Repository review of `.github/workflows/deploy.yml` and `bin/deploy.sh` confirms:

- dispatch input must be an exact 40-character SHA; checked-out commit must match;
- server refuses dirty worktrees and resolves the target commit explicitly;
- verified pre-deploy backup precedes maintenance, checkout, and migration;
- strict production and required Android push preflight remain mandatory;
- migrations are forward-only at deployment; the script reports stage and
  migration boundary on failure, with no automatic restore or rollback.

**Repository deployment contract: PASS.** The workflow targets the GitHub
`production` environment and contains the required secret names, but source
references do not prove a configured QA/production target or protected approval.
The GitHub API branch response reports `protected: false` and
`protection.enabled: false`; the readable environment list was empty. The
protection detail endpoint requires authentication not available in this
assessment. Host key pinning is not configured. These are production deployment
blocks and the current QA host/protection evidence is also absent.

## 4. Database, data and migration evidence

The exact-main CI run passed its Migration and Seed, PostgreSQL Concurrency, and
PostgreSQL Backup/Restore Drill jobs. The Bundle B candidate's identical source
tree previously completed the full CI. The application test suite verifies
migration rules and synthetic onboarding/import behavior. This does not provide
RC-11's mandatory pre-RC QA PostgreSQL snapshot, representative sanitized data,
or a migration run with recorded ledger count, duration, before/after totals,
or reconciliation of members, balances, dues, payment references and POS.

The test configuration `phpunit.xml` forces SQLite `:memory:`. Its PostgreSQL
companion targets `kojaya_test` on localhost; this assessment did not run that
configuration against the workstation PostgreSQL service. The authoritative
CI PostgreSQL evidence used isolated GitHub Actions service databases.
No shared `kojaya_erp` connection, reset, broad seed, production dataset, or
member import was used.

## 5. Backup, recovery, integrations and operations

Repository support exists for `backup:database`, `backup:verify`, and
`backup:status`; exact-main CI's PostgreSQL `BackupRestoreDrill` passed. Actual
QA backup artifacts, offsite replication, matching SHA-256/manifest, retrieval,
and a fresh recovery-target receipt are unavailable. Hence target backup,
offsite, and restore readiness remain BLOCKED.

`routes/console.php` defines the application's scheduled tasks and
`config/queue.php` defines queue drivers, failed-job table and retry defaults.
Neither file identifies an installed QA supervisor, cron/timer, deployment
traffic rule, or process owner. `queue:restart` alone does not prove worker
drain or restart. No service names or commands are invented here.

FCM HTTP v1 repository readiness is inherited from RC-07, and deployment
preflight still requires Android push configuration. QA Firebase project/IAM
and mounted credential file are unverified. Midtrans, WhatsApp, Google SSO,
mail and storage are likewise unclassified for an actual QA environment.
Android registration, receipt, foreground/background handling, token refresh,
logout revoke, account-switch isolation, and denied-permission behavior remain
**Phase 6 — QA-06**; no real provider notification was sent.

## 6. Member import and PII

Source routes and role seeder show `manage_cooperative_member` permits the
Pengurus Koperasi and Admin Koperasi page/template/preview; only
`import_cooperative_member_batch` authorizes execution, assigned to Admin
Koperasi. Validator, preview, execution, and permission-matrix tests pass with
synthetic data. Actual QA-account assignment and representative data review are
pending. No real member records were imported.

The release preflight checks PII key maps/versions, key distinction, service
resolution, and requires PII schema rollback disabled in strict production
mode. CI and migration-guard tests pass. Historical QA key availability and
ability to decrypt existing QA data have not been established.

## 7. Phase 6 QA smoke plan (not executed)

Once QA is provisioned and RC-11 blockers are cleared, QA-06 should record the
approved SHA and DB identity, then test HTTPS `/up`, `/login`, a synthetic admin
session and dashboard, member page, import template/preview with synthetic CSV,
read-only DB identity check, critical API, observed queue worker and scheduler
health, private storage access controls, TLS/cookie behavior and each enabled
integration's QA-safe readiness. Keep traffic held during migration and smoke;
resume only after owner sign-off. These are planned checks, not PASS results.
Android device/emulator and FCM delivery scenarios remain QA-06 acceptance.

## 8. Exact-main CI evidence

Run [36523950378](https://github.com/johnd-creator/kojaya/actions/runs/36523950378)
is `push` on `main`, SHA `007130ee1ecb527a8a7c324ec271c46b984c3881`,
completed `success`. It ran the full CI path, including Dependency Audit,
Pint, Frontend Build, Generated Drift, PHPUnit shards 1–4 and aggregate, SEED-09,
Migration and Seed, OpenAPI Drift, PostgreSQL Concurrency/BackupRestoreDrill,
and Phase 4 Readiness Gate. All 15 jobs passed. PHPUnit aggregate: **3,374
tests / 27,751 assertions / 0 failures / 0 errors / 0 skips**, combined line
coverage **81.41%**. PostgreSQL Concurrency: 26 tests / 375 assertions;
Document05PostgreSQL: 71 / 393; BackupRestoreDrill: 1 / 20. SEED-09:
41 / 507. Phase 4 Readiness/preflight suites: 55 / 247; desktop accessibility
audit: 104 passed. Separate job counts are not added to the PHPUnit aggregate.
This is repository evidence only; it cannot clear the environment blocks
above. No Phase 6 job has been executed.

## 9. Local validation and history

The focused, isolated SQLite selection completed **228 tests / 1,356
assertions**, zero failures/errors/skips. It covered database migration safety,
preflight, backup create/verify/status/retention, member import validation,
preview/execution, cooperative permissions, PII migration guards, and release
smoke/readiness. The deployment harness separately completed **28 tests / 242
assertions**, zero failures/errors/skips, with permission to manage its own
loopback fixture. An earlier attempt without the PDO SQLite extension failed
before DB setup; a combined attempt without permission to stop that fixture
timed out and was stopped. Neither attempt is acceptance evidence.

The isolated PHPUnit run produced Windows path diagnostics on stderr in some
backup subprocess tests; all assertions passed. PostgreSQL behavior is credited
only from the exact-main GitHub CI service database, not from local SQLite.
`composer validate --strict` and `git diff --check` pass. Local whole-repository
`vendor/bin/pint --test` reports 1,409 style issues across 1,425 files, primarily
line-ending differences: this checkout has global `core.autocrlf=true`; no PHP
file was changed in RC-11. The authoritative Linux exact-main CI Pint job passed.
The local Pint report is retained as a Windows checkout limitation, not waived
as an unverified source change.

## 10. Final decision and required follow-up

**RC-11 BLOCKED.** Candidate identity, deployment source contract, member-import
authorization, repository backup tooling, CI, and smoke-plan preparation pass.
QA target provisioning/identity, PostgreSQL snapshot migration and data
reconciliation, actual offsite replication/retrieval, QA restore, traffic and
worker/scheduler controls, TLS/storage/PII keys, FCM and other provider setup,
trusted SSH host identity, GitHub approvals, and capacity remain mandatory
blocks.

Phase 5 is **not closed**. It still requires clearing the RC-11 blocks, a
reviewed/merged RC-11 PR, and full exact-head `main` CI. Do not start Phase 6
until the gate is cleared. No `v1.0.0-rc.7` is proposed because no runtime
source changed; no tag was created. The next action is to provision and identify
the QA target and supply safe, non-secret evidence for the blocked prerequisites.
