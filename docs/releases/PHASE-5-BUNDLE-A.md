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
