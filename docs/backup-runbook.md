# Backup, Restore, and Disaster Recovery Runbook — Kojaya

## 🛡️ Scope and Safety Status

This document defines the operational procedures for PostgreSQL backup, verification, retention, restore drills, and disaster recovery for Kojaya (KojayaPro & Kojayaku).

**Core Safety Invariants:**
1. **Source Database Read-Only:** Backups execute read-only against the source database. Backups never modify, drop, truncate, migrate, or seed data.
2. **PostgreSQL-Native Logical Tooling:** Production backups use `pg_dump --format=custom` (`-Fc`), enabling verifiable inspection (`pg_restore --list`) and selective table restore.
3. **Empty Recovery Target Required:** Never restore directly over a live, populated production database. Production recovery must restore into a fresh, isolated recovery database target (`kojaya_recovery_<timestamp>`) before controlled cutover.
4. **Never Improvise with Destructive Resets:** Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` as recovery.
5. **No Credentials in Artifacts/Logs:** Manifests, logs, checksums, and deployment receipts must never contain passwords, `APP_KEY`, API tokens, or secrets.
6. **No Public Storage for Backups:** Primary and off-site backup disks must never be public disks, rooted beneath `storage/app/public` or `public/`, or configured with public URLs.

---

## 🎯 Production Backup Layers

| Layer | Type | Mechanism | Schedule / Trigger | Target SLA |
| :--- | :--- | :--- | :--- | :--- |
| **Layer 1** | **Pre-Deploy Logical Backup** | `php artisan backup:database --purpose=pre-deploy` | Mandatory pre-deployment gate in `bin/deploy.sh` | Verified recovery point before deployment mutation |
| **Layer 2** | **Scheduled Logical Backup** | `php artisan backup:database --purpose=scheduled --prune` | Daily at 02:30 UTC / 09:30 WIB via Laravel Scheduler | Max 24h data age (SLA < 26h) |
| **Layer 3** | **Off-Site Copy** | Provider-neutral Laravel Filesystem disk (`s3`, `r2`, `minio`) | Replicated with streaming SHA-256 validation | Geographic redundancy |
| **Layer 4** | **WAL Archiving / PITR** | Continuous WAL streaming (e.g. pgBackRest) *(Follow-up Design)* | Continuous archive | RPO <= 15 min, RTO <= 1 hour |
| **Layer 5** | **Infrastructure Snapshot** | VPS / Disk block storage snapshot | Weekly / Monthly by cloud provider | Disaster recovery of host OS *(Not a DB replacement)* |

> [!NOTE]
> A `pg_dump` custom-format logical dump is a schema- and table-level logical representation, **not** a PostgreSQL physical base backup for WAL replay. Point-in-Time Recovery (PITR) via continuous WAL archiving is designed as follow-up Layer 4.
> VM or block storage snapshots are also **not a replacement** for database-aware logical backups and continuous WAL archiving, as filesystem snapshots can capture in-flight database write buffers in an inconsistent state.

## Backup Policy by Environment

| Environment | Primary backup | Offsite copy | `BACKUP_REQUIRE_OFFSITE` |
| :--- | :--- | :--- | :--- |
| Local / development | Optional | Optional | `false` by default |
| Testing / QA | Required for a backup/restore drill | Optional | `false` by default |
| Staging | Required before deployment | Recommended; set by environment policy | Environment-specific |
| Production | Required before every deployment and before go-live | Mandatory for production go-live readiness | Environment-specific; synchronous fail-closed replication is required only when `true` |

**Pre-deploy backup gate:** every production deployment requires a verified primary backup on private storage. Backup creation verifies the stored artifact, including its manifest and SHA-256 companion. Failure aborts deployment before maintenance mode, code checkout, or database mutation. This gate does not unconditionally require offsite replication.

**Production disaster-recovery gate:** an approved, independent offsite copy is mandatory before production go-live. A directory on the same host or disk is not offsite protection. Production offsite configuration has not been verified by this runbook and must be proven separately before go-live.

**Synchronous deployment behavior:** when `BACKUP_REQUIRE_OFFSITE=true`, missing offsite configuration, replication failure, or integrity failure makes `backup:database` fail and aborts that deployment. With the repository default `false`, offsite replication is not a hard synchronous dependency of each deployment. Do not infer production configuration from repository defaults.

### Production Offsite Acceptance Gate

Before production release, record evidence for each item:

| Acceptance item | Required result |
| :--- | :--- |
| Approved offsite provider | YES |
| `BACKUP_OFFSITE_ENABLED` | `true` |
| `BACKUP_OFFSITE_DISK` | Configured |
| Target private / non-public | PASS |
| Primary backup | PASS |
| Offsite replication | PASS |
| Primary SHA-256 equals offsite SHA-256 | PASS |
| Offsite manifest | PASS |
| Independent retrieval from the application host | PASS |
| `backup:status` | HEALTHY |

If deployment policy also requires replication synchronously before every deploy, set `BACKUP_REQUIRE_OFFSITE=true` in that deployment environment and validate its fail-closed behavior. Provider approval and production configuration are deployment-environment decisions, not repository defaults.

---

## 📋 Operational Commands Reference

### 1. Create a Database Backup (`backup:database`)

```bash
# Standard manual backup (private local disk)
php artisan backup:database --purpose=manual

# Pre-deployment backup gate (aborts deploy if return code != 0)
php artisan backup:database --purpose=pre-deploy

# Backup with explicit off-site replication to private S3/R2 disk
php artisan backup:database --purpose=scheduled --offsite-disk=s3 --require-offsite --prune
```

**Options & Safety Guards:**
- `--disk=`: Target primary disk (default: `config('operations.backup.disk')` = `local`). Public disks are strictly rejected.
- `--directory=`: Directory inside disk (default: `backups/database`). Path traversal and `public/` paths are rejected.
- `--purpose=`: Purpose label (`manual`, `scheduled`, `pre-deploy`, `restore-drill`).
- `--offsite-disk=`: Optional secondary off-site disk for replication (must be private).
- `--offsite-directory=`: Directory on off-site disk.
- `--require-offsite`: Fail closed with non-zero exit code if off-site replication or streaming SHA-256 verification fails. When omitted, inherits `operations.backup.require_offsite` config.
- `--prune`: Automatically prune expired backups based on verified-backup retention policy after successful backup.

### 2. Verify Backup Artifacts (`backup:verify`)

```bash
# Verify the latest backup in the default directory
php artisan backup:verify

# Verify a specific backup artifact
php artisan backup:verify backups/database/kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump --disk=local
```

**Verification Steps Executed:**
1. Validates filesystem disk safety (rejects public disks).
2. Requires the managed `.json` manifest and `.sha256` companion; a missing provenance artifact fails closed.
3. Validates file existence and non-zero file size.
4. Checks streaming SHA-256 checksum against companion `.json` manifest and `.sha256` file.
5. Performs read-only archive structure inspection:
   - For PostgreSQL: `pg_restore --list <dump_file>`
   - For SQLite: `PRAGMA integrity_check`
6. Returns exit code 0 on success, exit code 1 on failure.

### 3. Check Backup Freshness and Health (`backup:status`)

```bash
# Check status against default SLA (26 hours)
php artisan backup:status

# Check status with custom SLA threshold (e.g. 12 hours)
php artisan backup:status --max-age=12
```

**Freshness & Integrity Rules:**
- Authoritative age is computed from `manifest.created_at` (UTC timestamp), preventing touched or copied files from falsely appearing fresh.
- Strict cryptographic metadata (`.json` and `.sha256`) is required; missing metadata is reported as `corrupt` / failed.
- Exit code `0 (HEALTHY)`: Latest backup exists, manifest and checksum are valid, archive is intact, and age <= max age hours.
- Exit code `1 (FAILURE)`: Backups missing, corrupted, missing metadata, or stale.

### 4. Prune Expired Backups (`backup:prune`)

```bash
# Dry-run preview (DEFAULT - deletes no files)
php artisan backup:prune

# Execute actual deletion of expired backups
php artisan backup:prune --execute --days=14 --keep=1
```

**Safety Guarantees:**
- **Dry-run by default:** Does not delete any file unless `--execute` is supplied.
- **Verified Backup Protection:** Identifies verified valid backups and guarantees at least `--keep` (default 1) **verified valid** backups remain, preventing corrupt newest backups from causing deletion of the only valid older backup.
- **Companion File Pruning:** Automatically deletes companion `.json` manifest and `.sha256` checksum files alongside the dump.
- **Path Traversal Protection:** Validates directory path to prevent escaping the backup namespace or accessing `public/`.

---

## 📦 Backup Artifacts & Manifest Schema

For every backup, three deterministic artifacts are generated:
1. `kojaya-{environment}-{database}-{timestamp}-{git_sha}.dump` (Binary Custom Archive)
2. `kojaya-{environment}-{database}-{timestamp}-{git_sha}.dump.json` (Cryptographic Manifest)
3. `kojaya-{environment}-{database}-{timestamp}-{git_sha}.dump.sha256` (Standard SHA-256 Checksum)

### Manifest JSON Structure (`.json`)

```json
{
  "schema_version": 1,
  "backup_id": "kojaya-production-kojaya_erp-20260829T132000Z-138963f",
  "created_at": "2026-08-29T13:20:00Z",
  "application_environment": "production",
  "application_git_sha": "138963f69c045546170c1beedee5f5d555c63d14",
  "database_engine": "pgsql",
  "database_name": "kojaya_erp",
  "database_host": "127.0.0.1",
  "database_port": 5432,
  "database_server_version": "PostgreSQL 16.2",
  "backup_filename": "kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump",
  "backup_format": "custom",
  "backup_size_bytes": 14258900,
  "sha256": "4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82",
  "purpose": "pre-deploy",
  "verification_status": "verified",
  "verified_at": "2026-08-29T13:20:04Z",
  "row_counts": {
    "users": 4027,
    "organizations": 3,
    "cooperative_members": 1200,
    "roles": 15,
    "permissions": 74,
    "cooperative_payments": 850,
    "cooperative_dues_invoices": 3200,
    "pos_products": 450,
    "pos_transactions": 2300,
    "audit_logs": 14500,
    "migrations": 112
  },
  "offsite_copy": {
    "enabled": true,
    "disk": "s3",
    "directory": "backups/database",
    "copied": true,
    "copied_at": "2026-08-29T13:20:08Z",
    "sha256_verified": true
  }
}
```

---

## 🚀 Pre-Deployment Backup Gate & Deployment Contract

The deployment script `bin/deploy.sh` enforces this ordering. Backup creation includes primary stored-artifact verification; the script does not make a separate `backup:verify` invocation. The detailed operator sequence and traffic hold are defined in [RC-09](releases/PHASE-5-BUNDLE-B.md#rc-09--execution-sequence).

```text
1. Validate exact commit input and clean worktree; fetch refs; resolve and compare the exact target SHA
   ↓
2. Verified primary pre-deploy backup: php artisan backup:database --purpose=pre-deploy
   ↓ (If primary backup, checksum, archive, manifest, or stored-artifact verification fails -> ABORT before maintenance/code/database mutation)
3. Maintenance Mode: php artisan down --retry=60
   ↓
4. Checkout exact target; install Composer dependencies; clear optimization cache; strict release preflight with required Android push
   ↓
5. Install frontend dependencies and build assets (npm ci, npm run build)
   ↓
6. Forward-only Migrations: php artisan migrate --force
   ↓
7. Application optimization, queue restart, and exit maintenance

Offsite replication failure also aborts at step 2 only when `BACKUP_REQUIRE_OFFSITE=true`. Production offsite readiness is independently mandatory before production go-live; it is not an unconditional synchronous dependency for every deployment.
```

### Target / Required Deployment Receipt Format

This is the single authoritative receipt schema. The current script prints
SHA/stage messages but does **not** generate this receipt automatically. The
operator completes it from redacted evidence, including failures and PENDING
checks; a zero script exit alone must not become a PASS smoke/final verdict.
All SHA fields contain complete commit IDs; approved CI must identify that exact
target. The following is an illustrative schema, not an executed deployment:

```json
{
  "deployment_timestamp": "2026-08-29T13:30:00Z",
  "environment": "production",
  "requested_git_sha": "138963f69c045546170c1beedee5f5d555c63d14",
  "resolved_git_sha": "138963f69c045546170c1beedee5f5d555c63d14",
  "deployed_git_sha": "138963f69c045546170c1beedee5f5d555c63d14",
  "previous_git_sha": "f4fb6aa87c8913aae1eee86e84778bd8c1056a55",
  "database_name": "kojaya_erp",
  "backup_id": "kojaya-production-kojaya_erp-20260829T132000Z-138963f",
  "backup_sha256": "4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82",
  "backup_verification": "PASS",
  "offsite_replication": "PASS",
  "production_offsite_readiness": "independent-retrieval-evidence-reference",
  "migrations_applied": ["2026_08_29_000001_example.php"],
  "migration_status_before": "private-evidence-reference",
  "migration_status_after": "private-evidence-reference",
  "approved_ci_run": "exact-target-full-CI-reference",
  "release_approval": "approval-reference",
  "maintenance_window": "approved-UTC-window",
  "preflight_status": "PENDING",
  "build_status": "PENDING",
  "smoke_test_status": "PENDING",
  "operator": "operator-identity",
  "approver": "release-authority-identity",
  "operator_role": "Release Manager",
  "failed_stage": null,
  "database_migration_state": "not-started",
  "final_verdict": "PENDING"
}
```

`offsite_replication` is PASS/FAIL/NOT_REQUIRED_BY_SYNC_POLICY, independently
from the required production offsite readiness evidence. Record the latter's
approved independent-retrieval reference separately; an optional synchronous
copy does not waive that production gate. Protect receipts and omit password,
APP_KEY, DB password/URL, API/OAuth tokens, Firebase private key, SSH key,
session cookie, raw environment dumps and business response bodies.

---

## 🔄 Disaster Recovery & Production Restore Runbook

Restoring a production database is an exceptional emergency procedure requiring explicit incident declaration and approvals.

### ⚠️ Prohibited Actions
- 🚫 **NEVER** run `migrate:fresh` or `db:wipe` as a recovery mechanism.
- 🚫 **NEVER** restore directly over a populated live production database.
- 🚫 **NEVER** restore while queues or workers are consuming jobs.

### Step-by-Step Production Recovery Procedure (Empty Recovery Database Model)

```text
Preserve Current Broken DB State
              ↓
Create Fresh Empty Recovery DB (createdb kojaya_recovery_<timestamp>)
              ↓
Restore Archive into Recovery DB (pg_restore --exit-on-error)
              ↓
Verify Checksum, Data Integrity, and Representative Row Counts
              ↓
Align Application Code to Manifest Git SHA (git checkout <git_sha>)
              ↓
Inspect and Apply Deliberate Forward Migrations (php artisan migrate:status / migrate --force)
              ↓
Execute Preflight & Smoke Test against Recovery DB
              ↓
Controlled Cutover to Recovery DB (approved connection switch on all runtimes)
              ↓
Retain Prior Broken DB for Forensic Review Until Final Acceptance
```

#### 1. Incident Declaration & Approvals
- Declare Severity 1 / Disaster Recovery incident.
- Designate Incident Commander and Database Recovery Lead.
- Record incident timeline, target recovery time, and affected systems.

#### 2. Enter Maintenance Mode & Drain Queues
Retain/re-establish the external traffic and writer hold on every node first.
Drain in-flight work and stop scheduler/producers/workers through the approved
environment procedure; concrete commands are PENDING RC-11 ENVIRONMENT
VERIFICATION. `queue:restart` is only a cached restart signal, not a drain or
stop acknowledgment. The repository defines no `kojaya-worker` systemd unit.

```bash
php artisan down --retry=60
php artisan queue:restart
```

Do not put a maintenance bypass secret in command arguments or evidence.
Use the approved private operator ingress path during smoke verification.

#### 3. Select & Verify Recovery Source Artifact
```bash
# Check status and locate intended backup artifact
php artisan backup:status

# Verify the specific managed artifact and both provenance companions
php artisan backup:verify "$BACKUP_PATH" --disk="$BACKUP_DISK"
```

The operator sets these non-secret artifact identifiers from the approved
incident record. Check manifest `application_git_sha`, environment, database
identity, creation time, format/size and SHA-256 against the incident and RC-05
policy. `backup:status`/latest alone is not backup selection. Verify independent
retrieval when recovering from offsite. Preserve the manifest and checksum with
the dump. A missing/mismatched companion or archive failure means STOP.

#### 4. Preserve Existing State & Create Empty Recovery Target Database
```bash
# Only after exact source/target and private paths are approved:
set -euo pipefail
umask 077
: "${FAILED_DB:?Approved failed database required}"
: "${FAILED_STATE_DUMP:?Approved private preservation path required}"
: "${RECOVERY_DB:?Approved fresh recovery database required}"
[[ "$RECOVERY_DB" =~ ^kojaya_recovery_[a-zA-Z0-9_]+$ ]] || exit 1
[[ "$RECOVERY_DB" != "$FAILED_DB" ]] || exit 1
test ! -e "$FAILED_STATE_DUMP"
pg_dump --format=custom --file="$FAILED_STATE_DUMP" "$FAILED_DB"
createdb "$RECOVERY_DB"
```

Keep the failed DB intact. The recovery target must be a new, independently
approved `kojaya_recovery_<incident>` name on the approved server, distinct from
every live/shared DB. Confirm the actual server identity, role and TLS privately;
credentials come from the protected connection mechanism, never shell arguments.
If preservation fails, stop and escalate rather than overwriting the only copy.

#### 5. Execute Restore into Empty Recovery Database
```bash
# Check runtime identity and emptiness on the approved recovery server.
set -euo pipefail
: "${RECOVERY_DB:?Approved fresh recovery database required}"
: "${VERIFIED_LOCAL_DUMP:?Approved verified private archive required}"
[[ "$RECOVERY_DB" =~ ^kojaya_recovery_[a-zA-Z0-9_]+$ ]] || exit 1
test "$(psql -X -v ON_ERROR_STOP=1 --dbname="$RECOVERY_DB" -Atc 'SELECT current_database()')" = "$RECOVERY_DB" || exit 1
test "$(psql -X -v ON_ERROR_STOP=1 --dbname="$RECOVERY_DB" -Atc "SELECT count(*) FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname NOT IN ('pg_catalog','information_schema') AND n.nspname NOT LIKE 'pg_toast%' AND c.relkind IN ('r','p','S','v','m','f')")" = '0' || exit 1
pg_restore --no-owner --no-acl --exit-on-error --dbname="$RECOVERY_DB" "$VERIFIED_LOCAL_DUMP"
```

`VERIFIED_LOCAL_DUMP` is the privately retrieved, verified artifact above, not
an arbitrary export. Do not add `--clean` or `--create`. A restore failure can
leave a partial recovery DB: retain it for inspection and keep traffic held;
never cut over merely because some tables exist. Reconcile schema/migration
ledger, representative counts, referential/financial invariants and required
private files/key versions before application use. Reconciliation of post-backup
writes, outboxes and provider-side payments needs explicit business approval;
blindly replaying jobs can duplicate external effects.

#### 6. Application Code Alignment, Config Cache Clear, and Migration After Restore
1. **Checkout Application Revision:** Check out the exact Git commit SHA recorded in the manifest (`application_git_sha`).
   ```bash
   git checkout --detach <EXACT_40_CHARACTER_MANIFEST_COMMIT_SHA>
   ```
   Use an isolated approved recovery checkout and preserve the matching existing
   APP/PII keys. Stage source/lockfiles first; prepare step 2 before running any
   dependency hooks or Artisan command. Do not call `bin/deploy.sh` as a restore shortcut: it would
   create another backup and run migrations automatically.
2. **Prepare Isolated Runtime:** Do not copy cached production configuration.
   Configure the recovery DB and isolated cache/session/queue/storage context
   privately before booting the app. Disable producers/workers/provider sends.
   Account for `DB_URL`, which can override `DB_DATABASE`; changing only one
   environment variable is not connection proof. Do not clear a shared live
   cache from the recovery runtime.
   Restore the manifest SHA's Composer and frontend lockfile dependencies/assets
   only in this prepared isolated runtime; review hooks before execution.
3. **Establish Recovery DB Context & Verify Runtime DB Identity:**
   Execute an explicit runtime PostgreSQL verification query to prove that the application runtime is actively connected to the intended recovery database:
   ```bash
   php artisan tinker --execute="echo 'Connected DB: ' . DB::selectOne('SELECT current_database() as db')->db . PHP_EOL;"
   ```
   > [!CRITICAL]
   > The output MUST strictly equal the approved `RECOVERY_DB`; server identity
   > must also match the approved recovery server. Otherwise STOP. Prove identity
   > again after any configuration/cache/connection change before migration.
4. **Inspect Migration Status:**
   ```bash
   php artisan migrate:status
   ```
5. **Review Migration Plan:** Confirm the list of unapplied migrations and verify that no destructive operations are pending.
6. **Preflight Before Deliberate Forward Migrations:**
   Run `php artisan app:release-preflight --strict-production --require-android-push`
   with the approved production configuration. Failure means STOP. Use the
   separate QA candidate procedure for QA; never relax production preflight.
   Preserve the proven runtime identity while clearing/rebuilding only isolated
   recovery caches. Apply only the reviewed forward set, if any is needed.
   Only after runtime DB identity is proven and reviewed:
   ```bash
   php artisan migrate --force
   ```
7. **Execute Release Preflight:**
   ```bash
   php artisan app:release-preflight --strict-production --require-android-push
   ```

#### 7. Cutover, Post-Recovery Smoke Test, and Exit Maintenance
1. Obtain recovery approval after proving the isolated app uses the fresh recovery
   DB and the manifest-aligned source. Verify essential business entities (Users,
   Members, Accounting Ledgers, POS Products) through non-mutating checks.
2. Switch approved connection configuration on **all** app/worker/scheduler
   runtimes; rebuild caches and verify effective DB identity on each. No ad hoc
   database rename/drop is part of this runbook. Keep external ingress/producers
   held throughout; topology-specific cutover is pending RC-11 verification.
3. Optimize the aligned runtime, signal queue restart, and verify new processes
   through the approved supervisor procedure while preventing job consumption.
4. Exit application maintenance only for restricted operator smoke acceptance:
   ```bash
   php artisan up
   ```
5. Complete the [RC-09 smoke matrix](releases/PHASE-5-BUNDLE-B.md#rc-09--operator-smoke-and-reopening), obtain sign-off, then reopen traffic/resume producers in the approved order. On failure, re-establish maintenance and retain the hold.
6. Retain the prior broken DB and safety dump until recovery sign-off. Record
   incident timestamp, failed/previous/backup/final recovered SHA, observed DB
   migration state, strategy, backup ID, operator, approver, smoke outcome and
   final verdict in the incident receipt. No automatic production rollback exists.

---

## 📈 PITR & Continuous WAL Archiving Strategy (Follow-up Design)

For Kojaya Production V2 disaster recovery, continuous Point-in-Time Recovery (PITR) will be introduced to achieve:
- **Target RPO (Recovery Point Objective):** $\le 15 \text{ minutes}$
- **Target RTO (Recovery Time Objective):** $\le 1 \text{ hour}$

### Architectural Design

```text
┌─────────────────────────────────────────────────────────────┐
│                    PostgreSQL Server                        │
│  ┌───────────────────────┐       ┌───────────────────────┐  │
│  │   Daily Base Backup   │       │   Continuous WAL Seg   │  │
│  │     (pgBackRest)      │       │   (16MB WAL files)    │  │
│  └───────────┬───────────┘       └───────────┬───────────┘  │
└──────────────┼───────────────────────────────┼──────────────┘
               │                               │
               ▼                               ▼
┌─────────────────────────────────────────────────────────────┐
│             pgBackRest / Dedicated WAL Archiver             │
│  - Compression (zstd/lz4)                                   │
│  - Client-side AES-256 Encryption                           │
│  - Multi-repository sync                                    │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│           Off-Site Storage (Cloudflare R2 / S3)             │
│  - Primary: /kojaya-backups/base/                           │
│  - WAL Stream: /kojaya-backups/wal/                         │
│  - Retention: 7 days WAL, 30 days full base                 │
└─────────────────────────────────────────────────────────────┘
```

### Tooling Evaluation

1. **pgBackRest (Recommended for Production):**
   - **Pros:** Native multi-threaded backup/restore, delta restore, async WAL pushing, built-in S3/R2 support, encryption, integrity verify.
   - **Operational Complexity:** Medium. Requires daemon on DB host.
2. **WAL-G:**
   - **Pros:** Fast Go-based tool, good S3 support.
   - **Operational Complexity:** Low-Medium.
3. **Native PostgreSQL `archive_command` with AWS CLI / rclone:**
   - **Pros:** Simple script.
   - **Cons:** Slower, lacks delta restore and deduplication.

### Proposed PostgreSQL Configuration (P0 Follow-Up)

```ini
# postgresql.conf (Production Target)
wal_level = replica
archive_mode = on
archive_command = 'pgbackrest --stanza=kojaya archive-push %p'
archive_timeout = 300 # Forces WAL segment switch every 5 min for <= 5 min RPO
```
