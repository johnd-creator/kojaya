# Backup, Restore, and Rollback Runbook — Kojaya

## Scope and safety status

This runbook is for an approved isolated staging or sanitized production-like
environment. It is not evidence that a backup, restore, or rollback rehearsal
has succeeded. No backup, restore, deletion, or rollback was executed for
Document 08.

Never restore over production or a shared development database. Never use
`migrate:fresh`, `db:wipe`, `pg_restore --clean`, broad backup deletion, or a
migration-down command as an improvised rollback. Database rollback requires an
explicit owner decision after the application and schema compatibility are
known.

## Ownership and recovery objectives

| Decision | Current status | Required owner |
| --- | --- | --- |
| Backup operator | Not assigned in this repository | Operations/DBA role |
| Restore approver | Not assigned | Repository owner or DBA role |
| Rollback approver | Not assigned | Incident/release approver role |
| Target RPO | Not agreed | Business and operations owner |
| Target RTO | Not agreed | Business and operations owner |
| Encrypted backup storage | Name/ownership pending environment inventory | Operations/DBA role |
| Retention policy | Proposed below; owner approval pending | Repository owner/admin |

## Pre-deploy backup contract

Before an approved staging deployment:

1. Record the source environment, database engine/version, exact deployed SHA,
   operator role, and timestamps.
2. Pause or drain write-producing workers according to the environment runbook.
3. Create a consistent PostgreSQL custom-format dump using the environment
   secret manager or a protected `.pgpass`; never put a password in a command
   argument or commit it to evidence.
4. Write the dump to an encrypted backup destination with restricted access.
5. Generate a SHA-256 checksum beside the backup and verify it immediately.
6. Record only the backup object name, checksum, size, retention class, and
   storage-owner reference. Do not attach raw data to a PR or issue.

Example shape for an approved isolated host (not executed here):

```bash
# DO NOT RUN IN THIS TASK. Run only in an approved isolated staging environment.
set -euo pipefail
backup_path="${BACKUP_PATH:?secret-manager-provided path required}"
pg_dump --format=custom --file="$backup_path" "$STAGING_DATABASE_URL"
sha256sum "$backup_path" > "$backup_path.sha256"
sha256sum --check "$backup_path.sha256"
pg_restore --list "$backup_path" > "$backup_path.contents.txt"
```

The database URL and backup path are environment inputs owned by the operator;
their values must never be printed. The checksum and `pg_restore --list`
output are safe evidence only after removing hostnames, usernames, and PII-like
object names where necessary.

## Storage and retention expectations

The environment owner must confirm:

- encryption at rest and in transit;
- least-privilege read/write/delete roles;
- immutable or write-once retention where supported;
- off-host or off-site replication;
- backup age alert and checksum verification schedule;
- deletion approval and retention expiry.

Proposed defaults, pending owner approval:

| Artifact | Proposed retention | Verification |
| --- | ---: | --- |
| Full staging database dump | 30 days | Daily checksum and monthly restore rehearsal |
| WAL/incremental stream | 7 days | Restore-point listing and age check |
| Sanitized file storage | 30 days | Archive listing and checksum |
| Deployment/config metadata | 90 days | Access audit and versioned manifest |
| Audit evidence | 1 year | Redacted export and access review |

Do not run broad `find ... -delete` commands. A retention job must receive an
exact approved artifact class and emit a deletion manifest for review.

## Isolated restore rehearsal

Restore only into a disposable database and isolated application environment:

1. Create a new database and restricted user using the environment owner’s
   database procedure; do not reuse the source database.
2. Verify the checksum before opening the archive.
3. Restore with `pg_restore --no-owner --exit-on-error` and without `--clean`.
4. Apply only forward migrations if the restore procedure requires them; record
   the migration state before and after. Never use a destructive reset.
5. Configure payment, WhatsApp, FCM, email, and webhook integrations disabled or
   pointed to non-production fakes.
6. Boot the application against the restored database and run the read-only
   staging preflight.
7. Verify plausible counts for users, members, payment intents, POS
   transactions, notification outboxes, and audit logs without exporting row
   contents or PII.
8. Verify that encrypted PII can be read with the expected key version without
   recording any decrypted value.
9. Verify payment-intent/POS relationships and audit-log queryability.
10. Destroy the disposable environment only through its approved owner process
    after the evidence record is finalized.

Example shape (not executed here):

```bash
# DO NOT RUN IN THIS TASK. The target must be a newly created disposable DB.
set -euo pipefail
sha256sum --check "$BACKUP_PATH.sha256"
pg_restore --no-owner --exit-on-error --dbname="$ISOLATED_DATABASE_URL" "$BACKUP_PATH"
```

Acceptance requires a redacted restore log, migration status, validation
summary, isolated environment identifier, exact source SHA, target database
identifier, timestamps, and operator role. A backup file alone is insufficient.

## Application rollback decision tree

Rollback is an application release decision, not an automatic database reset:

1. Keep the application in maintenance mode if code, dependencies, assets, or
   caches are incomplete.
2. Record target SHA and previous SHA from the deployment log.
3. Determine whether the previous application is forward-compatible with the
   current schema. If unknown, do not bring the previous application online.
4. If schema is compatible, deploy the recorded previous exact SHA with the
   tag-safe deployment procedure, reinstall matching dependencies/assets, clear
   stale caches, run preflight, and only then exit maintenance mode.
5. If schema is not compatible, keep maintenance mode and request the database
   owner’s restore/forward-fix decision. Do not run `migrate:rollback` merely to
   make the old code boot.
6. Pause or drain queues and scheduled jobs during the decision. Resume them
   only after queue idempotency and application health are verified.
7. The rollback approver records whether the incident was resolved by roll
   forward, application rollback, data restore, or a combination.

The existing `bin/deploy.sh` intentionally leaves the application in
maintenance mode after a post-maintenance failure and does not attempt an
incomplete automatic rollback. That behavior is a safety invariant.

## Evidence record

```text
Environment: staging/<identifier>
Source SHA: <40-character SHA>
Previous SHA: <40-character SHA or none>
Database engine/version: <non-secret metadata>
Backup object reference: <name only>
Backup timestamp: <Asia/Jakarta timestamp>
Checksum: <sha256>
Encryption/storage policy: <approved reference>
Operator role: <role>
Restore target: <disposable database/environment identifier>
Restore result: PASS/FAIL/BLOCKED
Validation result: PASS/FAIL/BLOCKED
Rollback rehearsal result: PASS/FAIL/BLOCKED
RTO/RPO: <approved values or NOT AGREED>
Artifact reference: <redacted log/CI artifact>
```

## Current acceptance status

- Backup creation/checksum: implementation contract documented; rehearsal
  pending isolated environment and operator approval.
- Restore: blocked pending disposable database and safe credentials.
- Application rollback: procedure documented; rehearsal pending a known staging
  previous SHA and approval.
- RTO/RPO and retention: proposed only; owner decision pending.
