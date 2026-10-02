# Phase 5 RC-11 — QA Deployment Readiness Gate

**Current status (2026-10-03): RC-11 TECHNICAL GATES: PASS;
OWNER APPROVAL: PENDING — READY FOR OWNER APPROVAL.** Section 19 supersedes
older candidate-status conclusions; earlier BLOCKED assessments remain history.

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
database listener was found. No Nginx or Docker executable was found on PATH.
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
| PII key readiness | BLOCKED | QA key availability and historical decryption remain unverified |
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
companion targets a disposable local test database; this assessment did not run
that configuration against the workstation PostgreSQL service. The authoritative
CI PostgreSQL evidence used isolated GitHub Actions service databases.
No shared local development database connection, reset, broad seed, production
dataset, or member import was used.

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

## 11. QA host evidence progression — FIX-01A through FIX-01D

This section updates the host findings above without erasing the original
blocked assessment. The owner classified the shared host as development/QA; no
application there is an authoritative production workload. A neighboring
application's production environment label was a configuration classification finding and
was left unchanged. Future Kojaya production uses a separate server.

| Step | Previous status → current evidence |
| --- | --- |
| FIX-01A | Unknown host/workload classification → inspection found multiple applications; a neighboring application's environment label triggered a safe stop pending owner classification. |
| FIX-01B | No isolated QA database/source proof → owner confirmed shared DEV/QA use; an empty dedicated QA database and least-privilege application role were created. The generated credential is stored outside version control, and private QA backup storage was prepared. Fetch made required main commit 007130ee1ecb527a8a7c324ec271c46b984c3881 available. No candidate checkout or migration occurred. |
| FIX-01C | Shared PHP runtime and exposed secrets → dedicated Kojaya runtime identity and PHP-FPM isolation with Kojaya-only site routing were installed. PHP-FPM and web-server config tests passed. Environment/config cache and private storage are protected. Independent queue and scheduler controls were installed stopped/disabled, with a readiness guard preventing premature starts. SSH host identity was verified and pinned. |
| FIX-01D | Unexercised hold, broad database firewall rule and missing HTTP redirect → Kojaya-only hold returned external 503 while a loopback smoke check returned 200; restoration returned HTTPS 200. Other application baselines remained unchanged. Database access was limited to the approved private LAN and broad address-family permits were removed. After the owner's edge redirect was deployed, independent checks returned 308 for HTTP root and /test?x=1 to identical HTTPS path/query, HTTPS /login returned 200 with valid TLS, and the redirect chain reached HTTPS /login without a loop. |

### RC-11 blocker matrix as of FIX-01D

| Area | Current status | Remaining boundary |
| --- | --- | --- |
| QA host classification, SSH identity and source availability | PASS | Shared DEV/QA host approved; SSH host identity verified; required source fetched but not deployed. |
| Runtime, secrets, private storage and domain/TLS | PASS (host) | Dedicated Kojaya PHP pool and protected files; HTTPS and edge redirect verified. Candidate acceptance remains pending. |
| Traffic/writer hold | PASS (host control) | Public 503, loopback smoke and restoration rehearsed; Phase 6 must apply the hold around its deployment. |
| Queue and scheduler control | PASS (prepared) | Kojaya-only units are stopped/disabled and require a deployment-readiness marker; activation awaits acceptance. |
| PostgreSQL target and firewall | PASS (host preparation) | Empty dedicated QA database and private-LAN-only firewall policy; schema and migration acceptance remain untested. |
| Local backup storage | PASS (prerequisite) | Private directory exists; no actual QA backup or restore is credited. |
| Host capacity | PARTIAL | Host capacity evidence remains partial; full backup plus temporary restore capacity is unproved. |
| Migration snapshot and production-like data rehearsal | BLOCKED | No approved snapshot, forward migration ledger or reconciliation; next task is RC-11-FIX-02. |
| Offsite backup, independent retrieval and QA restore | BLOCKED | No offsite receipt, independently retrieved artifact or restore into a fresh QA recovery target. |
| PII keys and provider integrations | BLOCKED | Historical decryption and QA-safe Firebase, payment, SSO, mail and other provider readiness remain unverified. |
| GitHub deployment approvals and Phase 6 acceptance | BLOCKED | Environment/reviewer protection evidence and candidate smoke are absent. |

**FIX-01D / QA HOST HARDENING: PASS. RC-11: BLOCKED.** This is host
infrastructure readiness, not approval to start Phase 6. Neighboring
application configuration remained unchanged; its baseline response does not
establish functional health and remains out of scope. No deploy, migration, seed, import, restore, PAY-006,
provider delivery, or worker/scheduler activation was performed. The next
planned task is **RC-11-FIX-02 — PostgreSQL migration and production-like data
rehearsal**; it was not started here.


## 12. RC-11-FIX-02 PostgreSQL rehearsal — 2026-09-29

**Verdict: RC-11-FIX-02 BLOCKED. RC-11 remains BLOCKED.** The exact runtime
candidate `007130ee1ecb527a8a7c324ec271c46b984c3881` was checked out in the
isolated worktree; the serving checkout remained unchanged. Composer and npm
installed from unchanged lockfiles, and only the isolated worktree was built. No source
file or lockfile changed.

The private rehearsal environment used QA configuration, a dedicated
PostgreSQL database, and a dedicated application role. Direct PostgreSQL
identity and Laravel resolved configuration matched before every mutating Artisan command. The
initial QA database had zero public objects. All **182** candidate migrations
applied in about **15.5 seconds**; `migrate:status` showed none pending and a
repeat `migrate --force` reported nothing to migrate. The RC-03-approved default
production-safe seeder ran twice. Counts were stable at 16 roles, 129
permissions, one synthetic QA organization, zero members/users, and unchanged
reference totals. The synthetic organization label is rehearsal-only and is
not an approved production identity. The QA release preflight passed, and
`admin:create` availability plus invalid-input rejection were verified without
creating an administrator.

The owner approved the existing development database as sanitized
rehearsal input. It was read only. A private dump was restored into a new
dedicated rehearsal database and deleted after the restore. The source had
**176** applied migrations and a small synthetic dataset; the six pending
September migrations applied in about **0.24 seconds**. All 182
migrations then showed as applied. Source and upgraded-copy counts matched:
one member, one organization, one user, two permissions, zero roles, and zero
payments, ledger entries, POS transactions/products/categories, store
accounts, receipts and payment intents. PostgreSQL reported zero unvalidated
constraints. This source is too sparse to exercise the POS ownership backfills,
payment/ledger reconciliation, or realistic volume/lock behavior. No approved
member import CSV was supplied, so import preview was validated through tests
only; no bulk import ran. PAY-006 dry-run is not applicable to this dataset
because it has no payment records or legacy proof references.

Static review classifies September POS transaction/category ownership
backfills, tenant-scoped unique indexes, and daily-closing type/uniqueness
changes as **HIGH** upgrade risk for populated data. Their `down()` paths can
also reject duplicates created after migration. A separate **HIGH rollback
defect** was reproduced on disposable PostgreSQL test database:
`2026_06_23_010000_add_metadata_to_cooperative_ledger_entries_table.php`
line 57 executes `DROP INDEX IF EXISTS coop_ledger_source_entry_unique` in
`down()`, but PostgreSQL reports SQLSTATE `2BP01` because that index backs the
same-named table constraint. DatabaseMigrations teardown therefore failed in
nine POS and six authentication test cases. This is a source defect in the
exact candidate, not a failed forward migration. The source was not patched;
a corrected candidate and repeat rollback rehearsal are required. The
disposable test database was removed after evidence capture.

Targeted PostgreSQL test results: migration safety **11 tests/14 assertions
PASS**, member-import preview **23/241 PASS**, payment-proof private storage
**14/63 PASS**, and finance ledger **15/510 PASS**. POS **9 cases/87
assertions with 9 teardown errors** and authentication **6 cases/13
assertions with 6 teardown errors** are not credited as passing. The first
attempt using repository-default SQLite could not run database-backed tests
because the host PHP CLI has no SQLite driver; the guarded PostgreSQL test
profile supplied valid database-backed evidence. No QA worker, scheduler,
Phase 6 deployment, PAY-006 execution, real-data import, or source switch
occurred.

### Current RC-11 blocker matrix after FIX-02

| Area | Status | Evidence / next requirement |
| --- | --- | --- |
| QA host isolation, identity, TLS, traffic hold, firewall | PASS | FIX-01A–01D evidence retained above; serving application remains unchanged. |
| Exact source and dependency installation | PASS | Exact SHA and clean isolated worktree; lockfiles unchanged. |
| Empty QA PostgreSQL migration and bootstrap | PASS (rehearsal) | 182 migrations; production-safe seed twice without count drift; preflight passed. |
| Production-like upgrade on available sanitized source | PARTIAL | Six migrations applied and structural counts matched, but business data are too sparse for payment, ledger and POS backfills or realistic volume. |
| PostgreSQL rollback/retry readiness | BLOCKED | Candidate `down()` fails with SQLSTATE 2BP01 on a constraint-backed index; corrected RC source and repeat proof required. |
| Member import / PAY-006 evidence | BLOCKED / NOT APPLICABLE | No approved import CSV; no legacy payment-proof rows in available dataset. |
| Actual QA backup, independent offsite retrieval and restore | BLOCKED | Rehearsal dump is not a QA backup or recovery drill. |
| PII historical keys and QA integrations | BLOCKED | Historical decryption and provider readiness remain unverified. |
| GitHub deployment approvals and candidate acceptance | BLOCKED | Reviewer/environment protections and Phase 6 smoke remain pending. |
| Host backup plus restore capacity | PARTIAL | Disk free-space observation only; complete backup/restore capacity not proven. |

The next FIX-02 action is to correct the PostgreSQL rollback migration in a new
reviewed RC candidate, obtain a richer approved sanitized dataset (including
payments, ledger, POS and import cases), and repeat the isolated rehearsal.
This task does not authorize Phase 6 or RC-11-FIX-03.


## 13. RC-11-FIX-02A PostgreSQL rollback remediation — 2026-09-29

**RC-11-FIX-02A PASS; QA rollback defect remediated. RC-11 remains BLOCKED.**
Section 12 preserves the rc.6 failure at
`007130ee1ecb527a8a7c324ec271c46b984c3881`; it has not been recast as a
passing rehearsal. FIX-02A is on dedicated fix PR #93, with candidate source
commit `d241e2af0f532e9cef17f37cb4afb44411133063`. No tag was created and
the serving QA checkout was not changed.

On a newly created disposable PostgreSQL test database, the unmodified
rc.6 migration set applied all 182 migrations. Direct catalog inspection showed
`coop_ledger_source_entry_unique` is a UNIQUE constraint with a same-named
backing index on `cooperative_ledger_entries`; the earlier June 13 POS ledger
migration created that constraint. Rolling back the June 23 metadata migration
reproduced SQLSTATE `2BP01` from `DROP INDEX IF EXISTS`. The minimal fix leaves
the earlier constraint/index intact and drops the index only when PostgreSQL
reports it is standalone. Forward migration behavior was unchanged.

The repaired migration rolled back successfully: the `metadata` column and its
migration row were removed, while the earlier constraint and index each
remained present once. Re-applying the target migration restored the column
without duplicates. A full rollback of the disposable database then completed
with the migration's explicit test-only PII rollback flag enabled; it left zero
migration rows, no application tables and no orphan ledger index. All 182
migrations reapplied successfully. The disposable database was removed after
testing. The new real-PostgreSQL regression covers both preservation of the
pre-existing constraint and deletion/re-application of a standalone index:
**2 tests, 23 assertions PASS**.

Previously failing PostgreSQL suites now pass: POS **9/87**, authentication
**6/13**, finance ledger **15/510**, and private payment proof **14/63**
(tests/assertions). Pint, strict Composer validation, OpenAPI drift, UI baseline
integrity and PHPUnit shard verification passed locally. The new regression is
registered in the existing PostgreSQL CI suite and excluded from the
SQLite-only default suite; no test was disabled to hide a failure. The host PHP
CLI lacks SQLite support, so the complete backend suite and UI audit were
validated through PR CI. All four PHPUnit shards passed: **3,374 tests, 27,751
assertions**. CI run 36568087976 passed every job, including PostgreSQL
Concurrency, migration/seed, OpenAPI, frontend build, Pint, dependency audit,
PHPUnit aggregation and the Phase 4 readiness gate. UI Audit run 36568087932
also passed. PR #93's merge ref `d444b7c1a69d57097642f3db50787a248343a01e`
has the same Git tree (`452c8b4f15579954d09993d14ae287566ac74a03`)
as candidate commit `d241e2af0f532e9cef17f37cb4afb44411133063`, so
CI validated the exact candidate source tree. The candidate is designated
`v1.0.0-rc.7` for review; no tag was created.

FIX-02A does not clear the separate RC-11 blockers from section 12: the
approved sanitized dataset lacks representative payment, ledger, POS and
member-import cases; actual QA backup/offsite retrieval/restore, historical PII
key and provider readiness, deployment approvals, and Phase 6 candidate smoke
remain outstanding. FIX-02B and Phase 6 have not started.


## 14. RC-11-FIX-02B representative populated-data rehearsal — 2026-09-29

**RC-11-FIX-02B PASS for synthetic populated-data rehearsal; RC-11 remains BLOCKED.**
The exact `v1.0.0-rc.7` main commit is
`0b02ad2441c1e4e8ca5f41933e33597246c07b9b`; exact-main CI run
`36575645418` passed all jobs. The rehearsal used a clean detached worktree at
that SHA and a new dedicated QA database with an isolated application role.
The serving checkout remained unchanged. No serving deployment,
queue/scheduler activation, or unrelated application change was made.

The source was migrated from an empty database to exactly **176 migrations**;
the next migration was the September 5 POS transaction tenant migration.
Only deterministic QA-only synthetic rows were then inserted, with no copied
production data or real member identities. The previous FIX-02 dataset's one
member and zero payments, ledger and POS rows was insufficient; this new
baseline contained three members, three payments, four cooperative ledger
entries, two store accounts, two store ledger entries, six POS transactions,
six POS items, four products, three categories, two closings and two sync
requests across two fake organizations.

### Migration impact matrix (177–182)

| Migration | Tables / columns | Backfill and populated-row expectation | Constraint / index change | Risk |
|---|---|---|---|---|
| Sep 5 POS transactions | `pos_transactions.organization_id` | Resolve only transactions whose item products share one organization and whose member, when present, agrees; ambiguous rows remain null. | Nullable organization FK, organization/sold-at index; replace global client-reference unique with organization/client-reference unique. | HIGH |
| Sep 6 daily closings | `pos_daily_closings.organization_id`, `closed_at`, `is_locked` | Existing closings retain null organization; `closed_at` becomes nullable and `is_locked` defaults false for new rows. | Organization FK; replace global date unique with organization/date unique. | MEDIUM |
| Sep 7 categories | `pos_categories.organization_id`, `duplicated_from_id`; `pos_products.pos_category_id` | One-organization categories resolve; multi-organization categories duplicate deterministically and remap products; productless categories stay null. | Organization and self-reference FKs, organization/active index; replace global slug unique with organization/slug unique. | HIGH |
| Sep 8 sync requests | `pos_sync_requests.organization_id` | Historical requests stay null because current user tenancy is not historical proof. | Organization FK and organization/status index. | MEDIUM |
| Sep 11 attendance permission cutover | `roles`, `permissions`, `role_has_permissions` | Grant HR Pusat/HR Unit/Admin Unit required attendance edges; revoke insecure Employee/Admin Unit edges. | No schema/index change; scoped permission rows and edges. | MEDIUM |
| Sep 11 bank-batch permission cutover | `roles`, `permissions`, `role_has_permissions` | Grant global-view permission to designated global roles and revoke it from Finance Unit. | No schema/index change; scoped permission rows and edges. | MEDIUM |

Before upgrade, all four relevant pre-existing unique constraints were present
and PostgreSQL reported zero unvalidated constraints. A private local
pre-upgrade backup was created and verified, and a second private backup was
captured before the populated rollback. These establish local rollback
readiness only, **not** the RC-11 offsite backup/retrieval/restore proof.

All six rc.7 migrations completed successfully, without migration errors or
lock/constraint failures. The six durations in order were 92.20, 33.97,
159.09, 33.72, 61.78 and 39.02 ms. At 182 migrations, exactly three of six
POS transactions resolved to their expected organization; three ambiguous or
itemless transactions remained null. One shared category was duplicated and
one product remapped. One productless category remained null. Both historical
closings and both historical sync requests remained null as designed. The
attendance and bank-batch cutovers removed all three planted insecure
permission edges and installed eight expected edges. All tracked business-table
counts matched their pre-upgrade counts except the intentional one-row category
duplication. The database had zero dangling checked POS/ledger relationships, zero
product/category or resolved transaction/member/item tenant mismatches, zero
store balance/ledger mismatches, zero tenant uniqueness duplicates, and zero
unvalidated PostgreSQL constraints; the three expected tenant-scoped
unique constraints and the original ledger unique constraint were present.

A populated rollback of only the repaired June 23 ledger metadata migration
removed its column and migration row while preserving all four ledger rows and
the earlier UNIQUE constraint and backing index exactly once. Re-apply restored
the column and 182-migration state. The separate PostgreSQL regression covers
the standalone-index branch. The first rollback attempt was refused by the
application's `qa` environment gate before mutation; the successful bounded
rollback/re-apply ran under `testing` after direct PostgreSQL and Laravel
identity checks still resolved exclusively to the disposable database.
Afterward, final business-row counts again matched the pre-upgrade snapshot,
with zero orphan checks and zero unvalidated constraints. Repeating standard
`migrate --force` reported “Nothing to migrate.”

A private, fake six-row canonical member CSV was previewed without import:
header valid, two valid rows, four invalid rows, one member number requiring
generation, two duplicate-number findings, one existing-email finding and one
invalid-company-code finding; member count remained three. Authorization
boundary and canonical preview tests are included in the targeted test run.
A **SYNTHETIC REHEARSAL** of PAY-006 used isolated fake files: one public-only,
one matching public/private and one missing proof. Dry-run reported
`would_migrate=1`, `would_remove_public=2`, `already_private=1`, `missing=1`,
`failed=0`; its nonzero exit was the expected fail-closed response to the
missing reference. All three existing file hashes were unchanged. No
`--execute` command was used. This does not replace an approved real-snapshot
dry run if later policy requires it.

Targeted local PostgreSQL-compatible files passed **88 tests, 965 assertions,
zero failures, zero errors and zero skips**: ledger metadata rollback 2/23,
POS cashier 9/87, store-credit ledger 19/28, finance ledger 15/510,
private payment proof 14/63, authentication 6/13 and member import preview
23/241 (tests/assertions). A separate attempt to run the entire POS category
file on PostgreSQL completed 52 tests/257 assertions with one failure: its
explicitly SQLite-only test asserts that the driver is `sqlite`. This is a
test-environment mismatch, not a migration or application failure; that
unmodified file is included in the default SQLite suite, which passed in
exact-main CI run `36575645418`. No test was disabled or changed. The local PHP
CLI has no SQLite driver. The populated migration and category backfill were
verified directly on PostgreSQL as recorded above.

The rehearsal database and local private backups are retained for RC-11
review, not connected to Nginx or the serving application. FIX-02B addresses
the synthetic populated-data branch only. RC-11 still requires actual QA
backup/offsite retrieval/restore, PII key and provider readiness, deployment
approvals and candidate smoke, plus any policy-required real-snapshot proof.
FIX-03's local primary backup was verified, but no approved independent offsite
target was configured; the drill stopped before transfer or restore. Phase 6
has not started.


## 15. RC-11-FIX-03 independent offsite backup and restore drill — 2026-09-30

**RC-11-FIX-03 PASS for the isolated rc.7 recovery rehearsal; RC-11 remains
BLOCKED.** The exact candidate `0b02ad2441c1e4e8ca5f41933e33597246c07b9b`
was used with the retained sanitized rehearsal source. Its primary backup
passed managed verification. A dedicated Google Drive location and separate
client-side encryption remote were configured locally; only the encrypted
backup was uploaded. The encrypted remote object was verified, then independently
retrieved and decrypted into a new local artifact. The retrieved artifact's
SHA-256 and size matched the verified primary backup.

A new empty recovery database, separate from the source and serving QA
database, was verified before restore. Restore used only the independently
retrieved artifact and completed successfully in one transaction. Representative
table counts matched the rehearsal source. Constraint and index reconciliation
passed with no unvalidated constraints, invalid indexes, or orphan relationships.
The source and recovery databases had the same 182 migration records. Migration
status showed 182 ran and none pending; a repeat `php artisan migrate --force`
returned `Nothing to migrate`.

Read-only Eloquent smoke checks passed across seven representative model and
relationship groups against the recovery database. No FCM, email, SMS, payment
provider, queue, or other external integration was invoked. The encrypted remote
backup is retained for RC-11 review. Credentials, encryption material, remote
identifiers, local paths, database identities, and raw checksums remain private.

This proves backup, independent retrieval, and recovery for the isolated
sanitized rehearsal dataset; the serving QA database was deliberately untouched.
The serving checkout remained unchanged, and the Kojaya queue and scheduler
remained stopped and disabled. No deployment, seed, import, provider delivery,
PR merge, FIX-04, or Phase 6 activity occurred.

### Current RC-11 blocker matrix after FIX-03

| Area | Status | Evidence / next requirement |
| --- | --- | --- |
| QA host isolation and controls | PASS | FIX-01A–01D evidence remains valid; serving application was not changed. |
| Exact rc.7 source and synthetic populated-data rehearsal | PASS | Exact candidate verified; 182-migration rehearsal source retained. |
| Encrypted offsite backup, independent retrieval, and recovery restore | PASS (isolated rehearsal) | Encrypted remote object retained; retrieved checksum matched; restore and structural reconciliation passed. |
| Serving QA database backup or deployment | BLOCKED / NOT RUN | The serving database and checkout were intentionally outside this recovery drill. |
| Full production-like dataset and policy-required real-snapshot proof | PARTIAL | This rehearsal used the approved sanitized synthetic dataset; any separate real-snapshot requirement remains outstanding. |
| PII historical keys and QA integrations | BLOCKED | Historical decryption and provider readiness remain unverified. |
| Deployment approvals and candidate acceptance | BLOCKED | Required approvals and deployment acceptance evidence remain outstanding. |
| Host backup plus restore capacity | PARTIAL | Complete capacity proof remains outstanding. |

RC-11 remains BLOCKED on the items above. PR #92 remains open and unmerged.

## 16. RC-11-FIX-04A PII classification and FCM QA readiness — 2026-09-30

**RC-11-FIX-04A PASS; RC-11 remains BLOCKED.** Verification used exact rc.7
candidate `0b02ad2441c1e4e8ca5f41933e33597246c07b9b` in the isolated QA
workspace.

Historical production PII is **NOT APPLICABLE** for this first production
release: the approved rc.7 populated-data rehearsal used synthetic records and
copied no production data; the owner-classified shared host has no authoritative
production workload, and future Kojaya production uses a separate server. No
historical production ciphertext or keys were represented as present. Current
PII configuration, key-map/version validation, key distinction, service
resolution, and rollback guard passed the application release preflight.

The QA FCM HTTP v1 configuration now resolves the QA project and a protected
runtime credential. The credential JSON parsed successfully, its project
identity matched the configured QA project, and the application provider
validated it. The credential is outside the repository and public webroot,
with directory mode `0750` and file mode `0640`; access is limited to the
credential owner and the dedicated Kojaya PHP-FPM runtime group. Credential
identities, filenames, locations, project identifier, and key material are
intentionally omitted. The legacy FCM server key is absent.

Both `php artisan app:release-preflight --no-interaction` and
`php artisan app:release-preflight --strict-release-candidate
--require-android-push --no-interaction` passed. A non-send OAuth readiness
check using the Firebase messaging scope succeeded; the access token remained
process-local and was discarded. No FCM notification or device test was sent.

Runtime environment hygiene passed: the isolated `.env` is ignored and
untracked, mode `0600`, outside the public webroot, and an HTTP probe to the
`.env` resource returned `403`. A streaming scan covered all 663 reachable Git
commits and the current tracked tree. It found no high-confidence credential
patterns; six generic assignment matches were reviewed as test-only
placeholders. No credential value was printed or added to repository history.

The serving checkout and database were unchanged; no deployment, migration,
seed/import, queue or scheduler start, provider delivery, PR merge, or Phase 6
work occurred. PR #92 remains open and unmerged. RC-11 remains BLOCKED on the
other outstanding capacity, serving-QA backup/deployment, approval, and
candidate-acceptance evidence.


## 17. RC-11-FIX-05A — QA cutover target and safety backup — 2026-09-30

**FIX-05A technical gates PASS. RC-11 is technically ready; explicit owner approval for Phase-6 QA entry remains required.** The current authoritative main and isolated candidate resolve to rc.7 SHA `0b02ad2441c1e4e8ca5f41933e33597246c07b9b`. The serving checkout remains at its historical pre-cutover revision `878b3678d4d29bec635d918ebbd98d9367878b2a`.

### Database classification and read-only evidence

The active QA site currently connects to legacy/pre-cutover database `kojaya`. The intended Phase-6 QA database is `kojaya_qa`; PostgreSQL confirms these are distinct databases. Read-only structural checks found:

| Database classification | Size | Migration rows | Application tables |
|---|---:|---:|---:|
| Legacy/pre-cutover QA (current serving target) | 29.01 MiB | 176 | 155 |
| Phase-6 QA target (not serving) | 15.95 MiB | 182 | 155 |

No schema or business data was changed. The QA target's 182 migration rows match the rc.7 rehearsal state.

### Legacy safety snapshot

A one-time, read-only host-tool snapshot of the legacy QA database was created in PostgreSQL custom format with no-owner/no-ACL options. The source was rechecked through Laravel and an independent PostgreSQL client immediately before the dump. `pg_restore --list` passed; a private manifest records UTC time, source classification, serving SHA, purpose, size, and SHA-256. The dump and manifest are restricted to mode `0600` in a mode `0700` directory; the snapshot is 0.57 MiB. Its checksum and storage location remain private. This legacy snapshot is not the rc.7 managed pre-deploy backup for `kojaya_qa` and was not restored.

### Managed backup diagnosis and proof

The earlier rc.7 managed-backup failure was caused by `BACKUP_ENABLED=false` in the isolated candidate runtime configuration, not a source defect, PostgreSQL access failure, or storage safety rejection. The flag was enabled only in the private, mode-`0600` isolated candidate environment. The exact rc.7 managed backup command and `backup:verify` then passed against non-serving Phase-6 target `kojaya_qa`; its manifest, SHA-256 companions, custom archive, and post-permission-change re-verification all passed. Artifacts are mode `0600` in a mode `0700` private directory. This readiness test does not replace the fresh managed pre-deploy backup that must pass during Phase 6 before any deployment mutation.

### Phase-6 cutover contract — prepared, not activated

Phase-6 QA database target is `kojaya_qa`. Before any Phase-6 deployment, the operator must:

1. Establish the external Kojaya-only traffic hold and keep the Kojaya queue and scheduler stopped/disabled.
2. Configure the serving runtime to use `kojaya_qa`; never run rc.7 migrations against legacy `kojaya`.
3. Clear and rebuild Laravel configuration cache as required, then verify the resolved runtime database with a Laravel database probe.
4. Independently query PostgreSQL with `psql -XAt -v ON_ERROR_STOP=1` using the protected QA PostgreSQL runtime environment. Require both checks to return exactly `kojaya_qa`; otherwise stop and retain the hold.
5. Verify the owner-approved SHA, deployment `--ref`, and resolved commit are all exactly `0b02ad2441c1e4e8ca5f41933e33597246c07b9b`, with a clean worktree.
6. Only after explicit Phase-6 QA approval, invoke the rc.7 deployment procedure. Its managed pre-deploy backup must pass against `kojaya_qa` before maintenance, checkout, or migration; a failure aborts before mutation. Keep traffic held and workers stopped through the defined smoke acceptance.

The rc.7 deployment script validates a full 40-character SHA, exact resolution, and clean worktree, and orders the managed backup before maintenance/checkout/migration. The governance binding is external: owner-approved SHA = `--ref` SHA = resolved SHA. The repository's `main` branch is unprotected; no GitHub reviewer/environment approval gate is claimed. This task supplies no explicit Phase-6 entry approval.

No runtime cutover, deployment, migration, seed/import, restore, queue/scheduler start, or external provider traffic occurred. Phase 6 remains **NOT STARTED**. FIX-01A–01D, FIX-02/02A/02B, FIX-03, and FIX-04A evidence remains as recorded above; capacity and FIX-05A backup/cutover preparation now pass. The only remaining RC-11 gate is explicit owner/release-authority approval for QA-only Phase-6 entry. PR #92 remains open and unmerged.

## 18. RC-11 — RC.8 Reconciliation — 2026-09-30

This section updates the current candidate and gate state. Earlier rc.7 evidence remains historical evidence where its scope was unaffected; it is not evidence for the rc.8 candidate or its changed QA cutover code.

### Authoritative source and exact-main validation

PR #94 is merged. Its merge commit is current origin/main and the exact v1.0.0-rc.8 candidate:

4f3afd8a5940e0ad7735f7e2ffb143a3d8ab9dd5

A clean, isolated worktree resolved to this exact SHA. The rc.7 candidate 0b02ad2441c1e4e8ca5f41933e33597246c07b9b is superseded. The exact-SHA comparison covers the QA deployment orchestrator, Laravel/PostgreSQL identity guard, managed backup verification, serving environment metadata preservation and recovery, contract tests, and runbook/ADR/log changes. It contains no migration-file change. bin/deploy.sh is unchanged.

No CI or Kojaya UI Audit run was found for the exact rc.8 main SHA. This session had no authenticated workflow-dispatch capability; both required exact-main workflow results are therefore BLOCKED / NOT RUN. PR-head CI results are not substituted for exact-main results.

### Candidate deployment and readiness evidence

The exact rc.8 QaDeploymentScriptTest.php suite passed: 18 tests and 127 assertions. It covers exact-SHA and worktree gates, QA database identity, backup ordering/verification, hold and worker controls, candidate env mode, serving env metadata preservation, and pre-migration recovery. Local related legacy deployment and SQLite feature suites could not run because the host PHP CLI lacks pdo_sqlite; they were not used to claim an exact-main CI pass.

The rc.8 qa:deployment-identity --expect=kojaya_qa --no-interaction gate passed. It verified Laravel configuration and an independent PostgreSQL connection resolve to the designated QA database.

The strict release-candidate preflight failed. The sanitized failing checks were PII key-map validity, distinct PII keys, PII service resolution, and required FCM readiness. No secret values or provider requests were emitted.

The rc.8 managed backup of non-serving kojaya_qa and independent backup:verify both passed. The verifier required the expected source database, provenance, archive/checksum integrity, and private artifact and directory permissions. The backup was not restored; no migrations or data changes were performed.

### Serving and approval state

The serving checkout remained at its historical pre-cutover SHA 878b3678d4d29bec635d918ebbd98d9367878b2a; its runtime still identifies the legacy kojaya database. Queue state was inactive. The scheduler timer was inactive, but systemd reported its enablement state as not found, so the required disabled state could not be proven. This control is BLOCKED.

No deployment, migration, seed/import, restore, queue/scheduler start, traffic release, or external provider request occurred. Legacy kojaya was not migrated. kojaya_qa remains the intended Phase-6 target. Phase 6 has NOT STARTED.

The owner approval previously given for rc.7 does not authorize rc.8. New explicit owner approval for exact SHA 4f3afd8a5940e0ad7735f7e2ffb143a3d8ab9dd5 remains required after the technical blockers are cleared. RC-11 remains BLOCKED on exact-main CI/UI Audit, release-preflight readiness, scheduler disabled-state proof, and owner approval.

RC-11-RC8-RECONCILIATION: BLOCKED

## 19. Final rc.11 dossier reconciliation — 2026-10-03

### Candidate and evidence authority

Authoritative untagged candidate: **v1.0.0-rc.11**, exact main SHA
`1c257b3e5ad76d9d453222213dd055d9ab18c9a9`. PR #97 is MERGED;
FIX-06A is PASS. Main was fetched and remained at that SHA during reconciliation.
PR #92 is a documentation dossier, not a replacement application candidate.
Earlier rc.7/rc.8 assessments, including BLOCKED results, are historical and
are not retroactively changed. rc.9 `f52610326e4ff43bff05b9afe2efcdc9646eefb7`
and rc.10 `5c2e76ca7010dbd87174850f17ea48615f200dfe` are superseded candidates.
The remote dossier previously ended at rc.8; no missing rc.9/rc.10 assessment
is invented. The supplied rc.10 backup history is distinguished below.

### Exact-main application and UI gates

[CI #523 / 37029602498](https://github.com/johnd-creator/kojaya/actions/runs/37029602498):
SUCCESS on branch main, exact SHA above. All 15 mandatory jobs PASS:
Change Classification, Frontend Build, Generated Drift, Pint, Migration and Seed,
PostgreSQL Concurrency, OpenAPI Drift, Dependency Audit, SEED-09, PHPUnit Shards
1/4 through 4/4, PHPUnit Parallel, and Phase 4 Readiness Gate.
Existing exact-main evidence is authoritative; CI was not rerun for this dossier.

[Kojaya UI Audit #310 / 37071233636](https://github.com/johnd-creator/kojaya/actions/runs/37071233636):
workflow_dispatch, mode=full, viewport=all, scope=all; SUCCESS on the same exact
rc.11 tested SHA. Playwright: 577 pass, 0 fail, 213 policy skips, 0 flaky,
0 expected-screen skips. Baseline inventory 234/234 valid; missing, orphan,
duplicate and invalid dimensions all 0. Artifact contract PASS. Accessibility:
critical 0, serious 0, no new waiver. Logout regression 12/12 PASS covers header
logout, user-menu logout, Enter and Space across desktop, tablet and mobile.
No additional full UI audit is dispatched solely for documentation changes.

### FIX-06A backup closure and fresh runtime proof

Managed primary local and local offsite backups enforce directory 0700 and
dump/checksum/manifest 0600, including the final manifest rewrite, independently
of shell umask. The shared independent permission verifier is fail-closed.
Required offsite failure fails the backup; optional offsite failure cannot report
unsafe copied=true. Non-local adapters are not subjected to POSIX chmod.
Failed-operation cleanup is bounded to newly owned files, preserving history.

The completed QA-02 handoff supplies fresh rc.11 automatic backup proof:
source `kojaya_qa`, purpose pre-deploy readiness proof, directory 0700,
dump/manifest/checksum 0600, **MANUAL CHMOD = NO**. Provenance, checksum,
PostgreSQL archive integrity, source DB identity and independent permission
verification all PASS. The private backup identifier is intentionally omitted.
The historical rc.10 backup required manual permission correction; that defect
is now superseded and closed by FIX-06A in rc.11, not erased from history.

### Final QA runtime revalidation and network reconciliation

**RC-11-RC11-QA-02: PASS**, recorded from the owner's completed runtime handoff,
not from new SSH/probes in this documentation task. Candidate is the exact rc.11
SHA above; serving checkout remained historical and unchanged.

- QA database identity PASS; intended target `kojaya_qa`. Legacy `kojaya` and
  `kojaya_qa` exist and are distinct.
- Strict release-candidate preflight PASS; Android push readiness PASS.
- FCM OAuth non-send PASS / HTTP 2xx; process-local OAuth token discarded;
  no FCM notification or external provider delivery.
- Queue loaded and inactive; scheduler loaded, inactive and disabled.
- Deployment contract PASS; no migration, restore, serving cutover or legacy
  database mutation. Phase 6 NOT STARTED.

Earlier Google OAuth transport failures were traced to upstream network
security/reputation policy rather than application JWT/FCM code. A narrowly
scoped approved network exception restored Google OAuth HTTPS connectivity;
final rc.11 non-send OAuth verification PASSed. No token/JWT, project identifier,
service-account email, credential path/key, database credential, internal address,
private threat-feed URI or firewall topology is published.

### Current decision and explicit approval boundary

**RC-11 TECHNICAL GATES: PASS**

**OWNER APPROVAL: PENDING**

**READY FOR OWNER APPROVAL** — not RC-11 CLOSED, not Phase 5 CLOSED, and not
Phase 6 STARTED. Earlier approval for another candidate does not authorize rc.11.
Required fresh approval must explicitly bind:

- Exact SHA `1c257b3e5ad76d9d453222213dd055d9ab18c9a9`.
- Environment QA ONLY; target database `kojaya_qa`.
- Legacy `kojaya` MUST NOT be the migration target.
- Production NOT AUTHORIZED; Phase 6 may start only after explicit approval.

PR #92 remains OPEN/UNMERGED pending that approval. This task changes only
`docs/log.md` and this dossier, preserves current-main source, and performs no
deployment, migration, restore, seed/import, QA server change, provider send,
credential rotation, release tag or new candidate creation.
