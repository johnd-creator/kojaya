# Backup, Restore, and Disaster Recovery Runbook — Kojaya

## 🛡️ Scope and Safety Status

This document defines the operational procedures for PostgreSQL backup, verification, retention, restore drills, and disaster recovery for Kojaya (KojayaPro & Kojayaku).

**Core Safety Invariants:**
1. **Source Database Read-Only:** Backups execute read-only against the source database. Backups never modify, drop, truncate, migrate, or seed data.
2. **PostgreSQL-Native Logical Tooling:** Production backups use `pg_dump --format=custom` (`-Fc`), enabling verifiable inspection (`pg_restore --list`) and selective restore.
3. **No Overwrites over Live Database:** Never restore directly over the live production or shared staging database with single-line scripts. Production restore is an approved, controlled runbook executed through maintenance mode.
4. **Never Improvise with Destructive Resets:** Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` as recovery.
5. **No Credentials in Artifacts/Logs:** Manifests, logs, checksums, and deployment receipts must never contain passwords, `APP_KEY`, API tokens, or secrets.

---

## 🎯 Production Backup Layers

| Layer | Type | Mechanism | Schedule / Trigger | Target SLA |
| :--- | :--- | :--- | :--- | :--- |
| **Layer 1** | **Pre-Deploy Logical Backup** | `php artisan backup:database --purpose=pre-deploy` | Mandatory pre-deployment gate in `bin/deploy.sh` | Zero data loss across deployments |
| **Layer 2** | **Scheduled Logical Backup** | `php artisan backup:database --purpose=scheduled --prune` | Daily at 02:30 UTC+7 via Laravel Scheduler | Max 24h data age (SLA < 26h) |
| **Layer 3** | **Off-Site Copy** | Provider-neutral Laravel Filesystem disk (`s3`, `r2`, `minio`) | Replicated upon successful local verification | Geographic redundancy |
| **Layer 4** | **WAL Archiving / PITR** | Continuous WAL streaming (e.g. pgBackRest) *(Follow-up Design)* | Continuous archive | RPO <= 15 min, RTO <= 1 hour |
| **Layer 5** | **Infrastructure Snapshot** | VPS / Disk block storage snapshot | Weekly / Monthly by cloud provider | Disaster recovery of host OS *(Not a DB replacement)* |

> [!IMPORTANT]
> VM or disk snapshots are **not a replacement** for database-aware logical dumps and continuous WAL streams, as snapshots may capture in-memory write buffers in an inconsistent crash state.

---

## 📋 Operational Commands Reference

### 1. Create a Database Backup (`backup:database`)

```bash
# Standard manual backup (local disk)
php artisan backup:database --purpose=manual

# Pre-deployment backup gate (aborts deploy if return code != 0)
php artisan backup:database --purpose=pre-deploy

# Backup with explicit off-site replication to S3/R2
php artisan backup:database --purpose=scheduled --offsite-disk=s3 --require-offsite --prune
```

**Options:**
- `--disk=`: Target primary disk (default: `config('operations.backup.disk')` = `local`).
- `--directory=`: Directory inside disk (default: `backups/database`).
- `--purpose=`: Purpose label (`manual`, `scheduled`, `pre-deploy`, `restore-drill`).
- `--offsite-disk=`: Optional secondary off-site disk for replication.
- `--offsite-directory=`: Directory on off-site disk.
- `--require-offsite`: Fail closed with non-zero exit code if off-site replication fails.
- `--prune`: Automatically prune expired backups based on retention policy after successful backup.

### 2. Verify Backup Artifacts (`backup:verify`)

```bash
# Verify the latest backup in the default directory
php artisan backup:verify

# Verify a specific backup artifact
php artisan backup:verify backups/database/kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump --disk=local
```

**Verification Steps Executed:**
1. Validates file existence and non-zero file size.
2. Checks SHA-256 checksum against companion `.json` manifest or `.sha256` file.
3. Performs read-only archive inspection:
   - For PostgreSQL: `pg_restore --list <dump_file>`
   - For SQLite: `PRAGMA integrity_check`
4. Returns exit code 0 on success, exit code 1 on failure.

### 3. Check Backup Freshness and Health (`backup:status`)

```bash
# Check status against default SLA (26 hours)
php artisan backup:status

# Check status with custom SLA threshold (e.g. 12 hours)
php artisan backup:status --max-age=12
```

**Outputs & Exit Codes:**
- `0 (SUCCESS)`: Latest backup exists, manifest and checksum are valid, and age <= max age hours.
- `1 (FAILURE)`: Backups missing, corrupted, or stale (older than SLA threshold).

### 4. Prune Expired Backups (`backup:prune`)

```bash
# Dry-run preview (DEFAULT - deletes no files)
php artisan backup:prune

# Execute actual deletion of expired backups
php artisan backup:prune --execute --days=14 --keep=1
```

**Safety Guarantees:**
- **Dry-run by default:** Does not delete any file unless `--execute` is supplied.
- **Minimum Keep Guarantee:** Never deletes the only remaining valid backup (keeps at least `--keep` backups, default 1).
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

The deployment script `bin/deploy.sh` enforces the mandatory deployment ordering:

```text
1. Release Preflight & Exact SHA verification
   ↓
2. Pre-deploy Backup: php artisan backup:database --purpose=pre-deploy
   ↓ (If backup, checksum, or archive verification fails -> ABORT DEPLOYMENT)
3. Maintenance Mode: php artisan down --retry=60
   ↓
4. Deploy Code & Install Dependencies (composer install, npm ci, npm run build)
   ↓
5. Forward-only Migrations: php artisan migrate --force
   ↓
6. Application Optimization & Queue Restart
   ↓
7. Exit Maintenance: php artisan up
```

### Deployment Receipt Specification

Deployments generate a structured receipt recording execution metadata:

```json
{
  "deployment_timestamp": "2026-08-29T13:30:00Z",
  "environment": "production",
  "deployed_git_sha": "138963f69c045546170c1beedee5f5d555c63d14",
  "previous_git_sha": "f4fb6aa87c8913aae1eee86e84778bd8c1056a55",
  "database_name": "kojaya_erp",
  "backup_id": "kojaya-production-kojaya_erp-20260829T132000Z-138963f",
  "backup_sha256": "4b68e9f2913e61c5c47864f7831d683a3089d8713028cf56d353b34b6f199e82",
  "backup_verification": "PASS",
  "offsite_replication": "PASS",
  "migrations_applied": ["2026_08_29_000001_example.php"],
  "smoke_test_status": "PASS",
  "operator_role": "Release Manager"
}
```

---

## 🔄 Disaster Recovery & Production Restore Runbook

Restoring a production database is an exceptional emergency procedure requiring explicit incident declaration and approvals.

### ⚠️ Prohibited Actions
- 🚫 **NEVER** run `migrate:fresh` or `db:wipe` as a recovery mechanism.
- 🚫 **NEVER** execute `pg_restore --clean` without prior approval and off-line backup verification.
- 🚫 **NEVER** restore over an active application while queues or workers are consuming jobs.

### Step-by-Step Production Recovery Procedure

#### 1. Incident Declaration & Approvals
- Declare Severity 1 / Disaster Recovery incident.
- Designate Incident Commander and Database Recovery Lead.
- Record incident timeline, target recovery time, and affected systems.

#### 2. Enter Maintenance Mode & Drain Queues
```bash
php artisan down --retry=60 --secret="emergency-recovery-access"
php artisan queue:restart
# Stop queue workers on the server (systemctl stop kojaya-worker)
```

#### 3. Select & Verify Recovery Source Artifact
```bash
# Check status and locate intended backup artifact
php artisan backup:status

# Verify checksum and archive listing before touching database
sha256sum --check kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump.sha256
pg_restore --list kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump > /tmp/restore_table_manifest.txt
```

#### 4. Rehearsal in Isolated Disposable Database
Before restoring into production, verify the backup against a disposable database:
```bash
# In PostgreSQL CLI (isolated server/instance):
createdb kojaya_restore_drill_temp
pg_restore --no-owner --no-acl --exit-on-error --dbname=kojaya_restore_drill_temp kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump
# Validate row counts and table integrity
psql -d kojaya_restore_drill_temp -c "SELECT count(*) FROM users;"
dropdb kojaya_restore_drill_temp
```

#### 5. Execute Production Database Restore
```bash
# Create a safety snapshot of the broken/current DB before restoring
pg_dump --format=custom --file=/var/backups/kojaya/pre_restore_state_$(date +%s).dump kojaya_erp

# Restore into target database
pg_restore --no-owner --no-acl --exit-on-error --dbname=kojaya_erp kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump
```

#### 6. Application Code Alignment & Migration After Restore
When restoring an older dump:
1. **Checkout Application Revision:** Check out the exact Git commit SHA recorded in the manifest (`application_git_sha`).
2. **Inspect Migration Status:**
   ```bash
   php artisan migrate:status
   ```
3. **Apply Forward-Only Migrations Deliberately:**
   If rolling forward to a newer application version, apply only forward migrations deliberately:
   ```bash
   php artisan migrate --force
   ```
4. **Clear Caches & Run Preflight:**
   ```bash
   php artisan optimize:clear
   php artisan app:release-preflight --strict-production
   ```

#### 7. Post-Recovery Smoke Test & Exit Maintenance
- Verify essential business entities (Users, Members, Accounting Ledgers, POS Products).
- Verify read/write functionality on non-financial endpoints.
- Start queue workers.
- Exit maintenance mode:
  ```bash
  php artisan up
  ```

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
│  │   Daily Full Backup   │       │   Continuous WAL Seg   │  │
│  │   (pg_dump / base)    │       │   (16MB WAL files)    │  │
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
