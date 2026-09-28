# RC-05 Backup & Restore Preflight

Audit date: 2026-09-28 (Asia/Jakarta). This is a non-production rehearsal using synthetic data and a PostgreSQL 18.6 cluster bound to `127.0.0.1:55439`. No production or shared database was used. No release tag was created or pushed.

## Baseline

- Starting branch: `main`; starting HEAD and `origin/main`: `69c5947f39a631bbaf0a9a8253a9534e6dbca5ba`; starting worktree clean and main synchronized.
- Starting candidate: `v1.0.0-rc.4`; exact-head CI at that baseline was recorded as successful in the supplied release state.
- The backup was created before the RC-05 source fix, against that exact baseline SHA. `APP_GIT_SHA` was explicitly set to the same observed 40-character HEAD because PHP's `shell_exec('git rev-parse HEAD')` resolved to `unknown` in this Windows execution context.
- RC-05 made one runtime hardening change to require managed provenance in `backup:verify`; the candidate therefore becomes `v1.0.0-rc.5` if integrated. No tag was created or pushed.

## Backup Architecture

| Command/service | Purpose | Production safe? | Source read-only? | Destructive target action? | Provenance check | Fail-closed condition |
| --- | --- | --- | --- | --- | --- | --- |
| `backup:database` / backup service | `pg_dump -Fc`, manifest, checksum, stored-byte verification and optional replication | Yes, when configured to private disks and an explicit approved DB | Yes; reads table counts and dumps the configured DB | Writes new artifacts; refuses overwrite; optional `--prune` is destructive | Git SHA, engine/name, purpose, size, row counts, SHA-256 | Primary dump/archive/storage verification or provenance failure returns non-zero; offsite failures do so when offsite is required |
| `backup:verify` / `BackupVerificationService` | Verify managed artifact checksum and archive | Yes | Yes | No | Requires manifest and checksum after this RC-05 fix | Missing either provenance companion, checksum mismatch, empty/invalid archive returns non-zero |
| `backup:status` / `BackupStatusService` | Latest backup integrity and freshness | Yes | Yes | No | Requires strict manifest/checksum and measures age from `created_at` | Missing/corrupt/stale backup reports unhealthy and returns non-zero |
| `backup:prune` / `BackupRetentionService` | Retention preview or deletion | Only on intended private backup namespace | Yes | Only with `--execute`; default is dry-run | Requires valid manifest/checksum and actual streamed SHA; preserves `min_keep` valid artifacts | Invalid/unverified artifacts are retained for manual review |

`PostgresRestoreDrillTest` creates and removes regex-constrained disposable source/target databases and uses real `pg_dump`/`pg_restore`. The runbook prohibits restoring over a live database. The deployment script checks an exact SHA, runs `backup:database --purpose=pre-deploy`, and exits on failure before `php artisan down`, checkout, or migration. Backup creation internally verifies the stored artifact; there is no separate `backup:verify` command in `bin/deploy.sh`.

## Configuration Contract

Observed effective repository defaults (`config/operations.php` / `.env.example`):

| Setting | Default / observed |
| --- | --- |
| `BACKUP_ENABLED` | `true` |
| `BACKUP_DISK` | `local` |
| `BACKUP_DIRECTORY` | `backups/database` |
| `BACKUP_RETENTION_DAYS` | `14` |
| `BACKUP_MIN_KEEP` | `1` |
| `BACKUP_MAX_AGE_HOURS` | `26` |
| `BACKUP_OFFSITE_ENABLED` | `false` |
| `BACKUP_OFFSITE_DISK` | unset |
| `BACKUP_OFFSITE_DIRECTORY` | `backups/database` |
| `BACKUP_REQUIRE_OFFSITE` | `false` |
| `BACKUP_TIMEOUT` | `300` seconds |

### Approved policy: primary deployment gate vs. offsite disaster-recovery gate

- **Every production deployment:** a verified primary private backup is mandatory before maintenance mode or code/database mutation. Failure aborts deployment. Backup creation performs stored-artifact verification internally.
- **Production go-live readiness:** independent offsite disaster-recovery protection is mandatory and must pass the acceptance gate in the runbook. This does not make replication an unconditional synchronous dependency of each deployment.
- **`BACKUP_REQUIRE_OFFSITE=true`:** preserves fail-closed behavior; missing target/configuration, replication failure, or integrity failure returns non-zero and aborts the current deployment.
- **Repository defaults:** `BACKUP_OFFSITE_ENABLED=false`, no `BACKUP_OFFSITE_DISK`, and `BACKUP_REQUIRE_OFFSITE=false` are development/QA defaults only. Production environment settings were not inspected and are not claimed here.

**Classification:** RC-05 backup/restore mechanism is evaluated independently from production environment readiness. Production offsite configuration and independent retrieval remain **PENDING** as prerequisites for production go-live, carried forward to **RC-07 — Integration Configuration Check** and **RC-11 — QA Deployment Readiness Gate**. They are not RC-05 mechanism blockers.

## Backup Disk Safety

- Effective primary disk `local` resolves under private `storage/app/private` and passed backup creation.
- Public disk, public visibility, public roots, traversal, and root-directory rejection are covered by the focused backup tests (45 tests passed after the fix).
- No public URL or public disk was used. A separate writable-volume/ACL failure was not injected; public-disk rejection is covered by tests.

## Source Dataset

- Created only in the newly initialized disposable PostgreSQL 18.6 cluster on loopback port `55439`.
- Source DB: `kojaya_rc05_qa_source_a93f2`; it was empty (0 public tables) before migration.
- Ran all 182 forward migrations and the QA/production-safe `DatabaseSeeder` path. It seeded eight reference seeders: role/permission catalog, tax, loan type, job grade, leave type, salary component, work shift, and cooperative references. No local/demo fixture branch ran.
- Created only deterministic synthetic entities: anchor organization, `System Admin`, member, dues invoice/payment/ledger entry, loan/installment, POS product/transaction/item, and one audit entry. No real PII or production records were used. The admin password was synthetic and is not recorded here.

## Source Fingerprint

| Entity | Source rows | Deterministic key |
| --- | ---: | --- |
| Organizations | 1 | `KOP-001` / `RC-05 Synthetic Cooperative` |
| Users | 1 | `rc05-admin@example.test` / `RC-05 Synthetic Admin` |
| Roles / permissions | 16 / 129 | Production-safe reference catalog |
| Cooperative members | 1 | `RC5M0001` / `RC-05 Synthetic Member` |
| Dues invoices | 1 | member `RC5M0001`, period `2026-09` |
| Cooperative payments | 1 | `RC05-PAY-0001`, amount `100000.00` |
| Loans / installments | 1 / 1 | `RC05-LOAN-0001`, installment 1 |
| Cooperative ledger entries | 1 | linked to `RC05-PAY-0001` |
| POS products | 1 | `RC05-SKU-0001` |
| POS transactions | 1 | `RC05-TXN-0001` |
| Audit logs | 1 | action `rc05_fixture_created` |
| Migrations | 182 | all applied |

The supported backup row-count evidence also records representative tables. The current schema has no `cooperative_savings_ledgers` table; the cooperative ledger entry table was used instead.

## Backup Creation

- Command: `php artisan backup:database --purpose=restore-drill`
- Primary disk/path: private `local:backups/database/rc05/a93f2/`
- Backup ID: `kojaya-qa-kojaya_rc05_qa_source_a93f2-20260928T011923Z-69c5947`
- Filename: `kojaya-qa-kojaya_rc05_qa_source_a93f2-20260928T011923Z-69c5947.dump`
- Format: PostgreSQL custom archive; 613,484 bytes.
- SHA-256: `5a9adfb9fbb245c0fa94ab78cc036077f332e6ade8c917bb9c297505e047d289`
- Purpose: `restore-drill`; verification status: `verified`.
- An exploratory first dump had `application_git_sha=unknown` because shell-based Git discovery was unavailable. It was not used for restore evidence. The accepted rehearsal dump above uses the observed baseline SHA explicitly.

## Manifest / Provenance

The accepted manifest is parseable and records engine `pgsql`, source DB `kojaya_rc05_qa_source_a93f2`, purpose `restore-drill`, exact baseline SHA `69c5947f39a631bbaf0a9a8253a9534e6dbca5ba`, size, row-count evidence, `created_at`, and SHA-256. Manifest SHA matches `.sha256` and the actual stored dump bytes. A scan found no password, `APP_KEY`, API token, or `secret_key` field/value in the manifest.

## Cryptographic Verification

- `backup:verify` on the accepted dump: PASS; streamed stored-byte SHA and `pg_restore --list`: PASS (TOC listing present; PostgreSQL 18.6 archive).
- Independent `pg_restore --list`: PASS.
- Before the source fix, controlled copies with a modified dump, wrong checksum, zero-byte dump, or invalid custom archive failed. The invalid archive was rejected by `pg_restore --list` even when its manifest/checksum were made internally consistent.
- **Defect found and fixed on the RC-05 branch:** the command previously allowed a valid archive to pass with either `.json` or `.sha256` missing because it did not require provenance. `backup:verify` now calls `verifyStorageBackup(..., requireProvenance: true)`. New regressions cover missing manifest and missing checksum; both pass by requiring non-zero failure. The same suite confirms zero-byte, checksum mismatch, and invalid archive failures.

## Corruption Testing

| Injection | Result |
| --- | --- |
| Modified dump bytes | `backup:verify` failed on actual stored SHA mismatch |
| Wrong `.sha256` | Failed on checksum mismatch |
| Missing manifest | Pre-fix false PASS reproduced; strict branch regression now requires failure |
| Missing `.sha256` | Pre-fix false PASS reproduced; strict branch regression now requires failure |
| Zero-byte dump | Failed |
| Invalid archive with matching manifest/checksum | Failed `pg_restore --list` |
| Missing-metadata `backup:status` | Both cases reported `corrupt`, exit 1 |
| Restore of invalid archive into new empty DB | `pg_restore` failed; target remained at 0 public tables |

The valid recovery artifact was never modified; all injections used separate copies under the RC-05 test directory.

## Backup Freshness

- Actual `backup:status`: `healthy`, age `0.44` hours, max age 26 hours, manifest timestamp authoritative.
- Controlled manifest timestamp 40 hours old: `stale`, exit 1, despite a newly written fixture file.
- `BACKUP_MAX_AGE_HOURS` was not changed.

## Retention / Prune

Ran the default dry-run behavior explicitly on an isolated set with a recent valid, 30-day-old valid, and old corrupt backup; `--days=14 --keep=1`.

- Recent valid backup retained.
- The old valid backup was the only prune candidate; dry-run listed its `.dump`, `.json`, and `.sha256` companions (3 artifacts).
- Corrupt backup retained; minimum valid backup protection remained in effect.
- All nine fixture files remained after the dry run. No prune execution was performed.

## Restore Drill

- Recovery DB: `kojaya_rc05_qa_restore_a93f2`, independently confirmed empty (0 public tables) immediately before restore.
- Restored with real PostgreSQL `pg_restore --no-owner --no-acl --exit-on-error --dbname=...` from the accepted custom dump.
- Confirmed `current_database()` was exactly `kojaya_rc05_qa_restore_a93f2` after restore.
- Source and restored semantic fingerprints matched for every listed representative entity and key, including organization name/code, synthetic admin email/name, member key, payment reference/amount, loan reference/status, POS SKU/name and transaction/status, audit entry, and all 182 migration rows.

## Restored Data Verification

| Entity | Source | Restored | Result |
| --- | ---: | ---: | --- |
| Organizations | 1 | 1 | PASS |
| Users | 1 | 1 | PASS |
| Members | 1 | 1 | PASS |
| Dues invoices | 1 | 1 | PASS |
| Payments | 1 | 1 | PASS |
| Loans / installments | 1 / 1 | 1 / 1 | PASS |
| Ledger entries | 1 | 1 | PASS |
| POS products | 1 | 1 | PASS |
| POS transactions | 1 | 1 | PASS |
| Audit logs | 1 | 1 | PASS |
| Migrations | 182 | 182 | PASS |

## Application Smoke Test

QA runtime was explicitly pointed to the restored DB; `SELECT current_database()` returned `kojaya_rc05_qa_restore_a93f2`. `optimize:clear` completed. `migrate:status` showed 182 ran and 0 pending. On a local HTTP server bound to `127.0.0.1:8055`:

| Check | Result |
| --- | --- |
| `GET /up` | 200 |
| `GET /login` | 200 |
| Synthetic admin login | PASS; redirected to `/dashboard` |
| `GET /dashboard` after login | 200 |
| `POST /logout` | PASS; redirected to `/login` |
| `GET /dashboard` after logout | redirected to login; final page `/login` |

No production account or credential was used.

## Provenance

Accepted rehearsal manifest SHA is exactly `69c5947f39a631bbaf0a9a8253a9534e6dbca5ba`, the source revision under test before RC-05 changes. The RC-05 code fix has its own candidate commit SHA and requires new exact-head CI before integration; the older dump is not presented as evidence for that new candidate's deployment.

## Off-Site Replication

- Repository default: offsite disabled, no disk configured, and `BACKUP_REQUIRE_OFFSITE=false`.
- Fail-closed injection with `require_offsite=true` and no target: non-zero failure before backup.
- Replication rehearsal to a separate directory on the private `local` disk: primary and replica each produced the expected three files; SHA-256 matched, manifest showed `copied=true` and `sha256_verified=true`, and replica verification passed.
- This is a same-host/same-volume mechanism test, **not geographic or independent offsite protection**. No approved non-production object-storage target or production bucket credentials were available/used. Production offsite configuration and independent retrieval remain **PENDING for RC-07 / RC-11**; no production environment or target was accessed.

## Deployment Backup Gate

Static inspection confirms the actual order: exact target SHA validation/resolution; `backup:database --purpose=pre-deploy`; maintenance mode; checkout and Composer install; cache clear and strict `app:release-preflight`; npm install/build; migration; optimization/queue restart; exit maintenance. Primary backup failure exits before maintenance, checkout, and migration. No deployment was executed. The script does not call `backup:verify` separately because backup creation verifies the stored artifact internally. Offsite failure aborts at the backup step only when `BACKUP_REQUIRE_OFFSITE=true`.

## Failure Injection

| Failure | Result |
| --- | --- |
| `pg_dump` unavailable (PATH isolated) | Non-zero failure; no false success |
| Database unreachable (loopback port 55438) | Connection refused, non-zero failure |
| Required offsite target missing with `require_offsite=true` | Non-zero fail-closed response |
| Modified bytes / mismatched checksum / missing metadata | Rejected as described above |
| `pg_restore --list` failure | Rejected |
| Restore failure | Non-zero; empty target remained empty |
| Unwritable filesystem target | Not directly injected; public target/visibility/path safety covered by tests |
| Backup failure → deployment continuation | Not executed end-to-end; static branch exits before `artisan down` and migration |

## RPO / RTO Boundary

- Logical PostgreSQL backup: **implemented and exercised**.
- Restore drill: **implemented and exercised**.
- Scheduled backup freshness SLA: configured default 26 hours; this is not a guarantee of achieved RPO.
- Offsite copy: **configuration-dependent at deployment time; mandatory before production go-live**. The same-host test is not offsite resilience. Production configuration remains pending RC-07 / RC-11.
- PITR/WAL archiving: **follow-up / not verified as implemented**. No RPO/RTO claim is made.

## Cleanup

Cleanup completed after evidence capture. Dropped only the five exact databases created in the disposable cluster (`kojaya_rc05_qa_source_a93f2`, `kojaya_rc05_qa_restore_a93f2`, `kojaya_rc05_qa_restorefail_a93f2`, `kojaya_rc05_qa_tests_a93f2`, and `kojaya_rc05_test_migrationdiag_a93f2`); the restore-drill test's own `kojaya_restore_source_*` / `kojaya_restore_target_*` databases were already cleaned by its teardown. Verified no RC-05 databases remained, stopped the dedicated port-55439 server, removed only its exact temporary cluster directory, and removed only the generated `storage/app/private/backups/database/rc05/a93f2` directory (40 files). The existing PostgreSQL service on port 5432 and shared `kojaya_erp` were never connected to or modified. The temporary HTTP server was stopped.

## Test Results

- Focused SQLite backup command, verification, status, retention, and related infrastructure suites on the RC-05 branch: **45 tests, 149 assertions, PASS**, using the PHP CLI's installed SQLite extension DLLs explicitly; no database reset or shared DB.
- PostgreSQL `PostgresRestoreDrillTest` on the original checkout and disposable cluster: **1 test, 20 assertions, PASS**, using real `pg_dump` and `pg_restore`.
- Re-running that same test from the secondary Windows managed worktree failed during its fresh migrations (`permissions` relation missing), while an independent full migration on a new `APP_ENV=testing` database in the same cluster passed all 182. This indicates a worktree/test-runner setup discrepancy that is not yet explained; authoritative Linux CI is still required.
- Default `phpunit.xml` initially failed before assertions because this machine's active PHP CLI does not load `pdo_sqlite`; explicitly loading the already-installed SQLite DLLs produced the passing focused run.

## Exact-Head CI

The original RC-05 runtime candidate `095f12b15811d3bf974e60b8814bd335cf741548` passed PR CI run **#481 / 36368047006**: 3,314 tests, 27,375 assertions, 0 errors, 0 failures, 0 skipped, 81.33% coverage; PostgreSQL restore drill and Phase 4 Readiness Gate passed. The policy reconciliation changes this PR head, so fresh exact-head PR CI is required before integration. Final exact-head CI on `main` is also required after integration. No dummy commit or release tag was created.

## RC-05 Verdict

**MECHANISM: PASS.** The rehearsal proved real backup, SHA verification, empty-target restore, semantic equality, and app boot/login/logout. The strict provenance defect has been fixed with focused regressions. CI run #481 cleared the previously noted Windows secondary-worktree test discrepancy through authoritative Linux CI.

**Production readiness: PENDING.** Independent approved offsite configuration/retrieval is mandatory before production go-live and is carried to RC-07 / RC-11. No production target was inspected or configured. This pending production prerequisite does not block the RC-05 backup/restore mechanism verdict.

**Integration gate: PENDING** fresh exact-head CI for the reconciled PR head, safe PR integration, and final exact-head `main` CI. Until those are complete, the integrated RC-05 release gate is not final. No release tag was created or pushed.
