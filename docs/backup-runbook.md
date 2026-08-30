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
| **Layer 1** | **Pre-Deploy Logical Backup** | `php artisan backup:database --purpose=pre-deploy` | Mandatory pre-deployment gate in `bin/deploy.sh` | Zero data loss across deployments |
| **Layer 2** | **Scheduled Logical Backup** | `php artisan backup:database --purpose=scheduled --prune` | Daily at 02:30 UTC / 09:30 WIB via Laravel Scheduler | Max 24h data age (SLA < 26h) |
| **Layer 3** | **Off-Site Copy** | Provider-neutral Laravel Filesystem disk (`s3`, `r2`, `minio`) | Replicated with streaming SHA-256 validation | Geographic redundancy |
| **Layer 4** | **WAL Archiving / PITR** | Continuous WAL streaming (e.g. pgBackRest) *(Follow-up Design)* | Continuous archive | RPO <= 15 min, RTO <= 1 hour |
| **Layer 5** | **Infrastructure Snapshot** | VPS / Disk block storage snapshot | Weekly / Monthly by cloud provider | Disaster recovery of host OS *(Not a DB replacement)* |

> [!NOTE]
> A `pg_dump` custom-format logical dump is a schema- and table-level logical representation, **not** a PostgreSQL physical base backup for WAL replay. Point-in-Time Recovery (PITR) via continuous WAL archiving is designed as follow-up Layer 4.
> VM or block storage snapshots are also **not a replacement** for database-aware logical backups and continuous WAL archiving, as filesystem snapshots can capture in-flight database write buffers in an inconsistent state.

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
2. Validates file existence and non-zero file size.
3. Checks streaming SHA-256 checksum against companion `.json` manifest and `.sha256` file.
4. Performs read-only archive structure inspection:
   - For PostgreSQL: `pg_restore --list <dump_file>`
   - For SQLite: `PRAGMA integrity_check`
5. Returns exit code 0 on success, exit code 1 on failure.

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

The deployment script `bin/deploy.sh` enforces the mandatory deployment ordering:

```text
1. Release Preflight & Exact SHA verification
   ↓
2. Pre-deploy Backup: php artisan backup:database --purpose=pre-deploy
   ↓ (If backup, checksum, primary stored verification, or offsite copy fails -> ABORT DEPLOYMENT)
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

### Target / Required Deployment Receipt Format

For auditing and release verification, deployment pipelines must record deployment receipts adhering to this specification:

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
Controlled Cutover to Recovery DB (update connection / rename database)
              ↓
Retain Prior Broken DB for Forensic Review Until Final Acceptance
```

#### 1. Incident Declaration & Approvals
- Declare Severity 1 / Disaster Recovery incident.
- Designate Incident Commander and Database Recovery Lead.
- Record incident timeline, target recovery time, and affected systems.

#### 2. Enter Maintenance Mode & Drain Queues
```bash
php artisan down --retry=60 --secret="<APPROVED_RECOVERY_TOKEN>"
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

#### 4. Preserve Existing State & Create Empty Recovery Target Database
```bash
# Take a safety snapshot of the broken/current state before any changes
pg_dump --format=custom --file=/var/backups/kojaya/pre_recovery_broken_state_$(date +%s).dump kojaya_erp

# Create new empty recovery database
createdb kojaya_recovery_20260829
```

#### 5. Execute Restore into Empty Recovery Database
```bash
# Restore into the empty recovery target database
pg_restore --no-owner --no-acl --exit-on-error --dbname=kojaya_recovery_20260829 kojaya-production-kojaya_erp-20260829T132000Z-138963f.dump
```

#### 6. Application Code Alignment, Config Cache Clear, and Migration After Restore
1. **Checkout Application Revision:** Check out the exact Git commit SHA recorded in the manifest (`application_git_sha`).
   ```bash
   git checkout <manifest_git_sha>
   ```
2. **Clear Caching Layers Immediately:** Clear all cached configuration, routes, and views before establishing recovery database context to prevent stale configuration pollution.
   ```bash
   php artisan optimize:clear
   ```
3. **Establish Recovery DB Context & Verify Runtime DB Identity:**
   Configure the recovery database target context:
   ```bash
   export DB_DATABASE=kojaya_recovery_20260829
   ```
   Execute an explicit runtime PostgreSQL verification query to prove that the application runtime is actively connected to the intended recovery database:
   ```bash
   php artisan tinker --execute="echo 'Connected DB: ' . DB::selectOne('SELECT current_database() as db')->db . PHP_EOL;"
   ```
   > [!CRITICAL]
   > The output MUST strictly equal `kojaya_recovery_20260829`. If the output does not match or indicates the live production database, **STOP IMMEDIATELY**. Never execute migrations or commands before runtime database identity is proven.
4. **Inspect Migration Status:**
   ```bash
   php artisan migrate:status
   ```
5. **Review Migration Plan:** Confirm the list of unapplied migrations and verify that no destructive operations are pending.
6. **Apply Forward-Only Migrations Deliberately:**
   Only after runtime DB identity is proven and reviewed:
   ```bash
   php artisan migrate --force
   ```
7. **Execute Release Preflight:**
   ```bash
   php artisan app:release-preflight --strict-production
   ```

#### 7. Cutover, Post-Recovery Smoke Test, and Exit Maintenance
1. Update production configuration `DB_DATABASE=kojaya_recovery_20260829` (or rename database after disconnecting sessions).
2. Verify essential business entities (Users, Members, Accounting Ledgers, POS Products).
3. Start queue workers (`systemctl start kojaya-worker`).
4. Exit maintenance mode:
   ```bash
   php artisan up
   ```
5. Retain prior broken database (`kojaya_erp` or safety dump) for forensic and auditing purposes until sign-off.

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
