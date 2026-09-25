# Kojaya RC-01 Release Baseline

Date: 2026-09-25 (WIB)
Repository: `johnd-creator/kojaya`
Branch: `main`
RC Version: `v1.0.0-rc.1` (target; not frozen)
Commit SHA: `9b31b124cd1ed57dd09dc30ced83c534f79a88ee`
Remote origin: `https://github.com/johnd-creator/kojaya.git`

## Repository State

- `main` was synchronized with `git fetch --all --prune` and `git pull --ff-only`.
- `HEAD` equals `origin/main` at the SHA above.
- Phase 4 commits FUNC-01 through FUNC-13 are present in `main`; FUNC-13 is commit `45d2e93f`.
- At audit start, no unmerged files and no untracked files were present; this report was added as the requested evidence deliverable.
- Working tree is **NOT CLEAN**. Pre-existing local modifications remain in:
  - `resources/js/wayfinder/index.ts`
  - `vite.config.ts`
- The local changes were preserved. No reset, checkout, merge, or feature change was performed against them.
- The remote FUNC-12/FUNC-13 work branches contain historical commits already represented by the merged `main` tree; no mandatory Phase 4 change was found only on those branches. Older unrelated fix/release branches remain stale/diverged and were not merged automatically.

## Application Version and Identity

- The established application version source is `config/app.php` → `APP_VERSION`, defaulting to `0.1.0-dev`.
- The API contract version is separate: `API_CONTRACT_VERSION`, defaulting to `1.0.0`.
- Existing published identity is `v0.1.0`; no `v1.0.0-rc.*` tag exists locally.
- Build identity can be mapped externally as `APP_VERSION` + immutable Git SHA + branch/ref + artifact, but the repository does not currently set RC metadata in source.
- `app:release-preflight --strict-production` accepts only stable `x.y.z` values, so the requested prerelease string `v1.0.0-rc.1` is not accepted by the current strict preflight. This must be resolved or explicitly governed before freezing an RC.

## Test Baseline

Authoritative commands were discovered from `composer.json`, `package.json`, `.github/workflows/ci.yml`, and existing release evidence. No production or shared development database was used.

| Suite/check | Result |
| --- | --- |
| `vendor/bin/pint --dirty --test` | PASS |
| `bin/openapi.sh check` | PASS |
| `composer validate --strict` | PASS |
| `composer audit --locked --ignore-severity=medium --ignore-severity=low --abandoned=report` | PASS under repository threshold; 7 medium/low advisories ignored |
| `npm audit --omit=dev --audit-level=high` | PASS under repository threshold; 3 moderate `qs` findings, no fix available |
| `npm run ui:verify-baselines` | PASS; 234 valid, 0 missing/orphan/duplicate/invalid |
| `php artisan app:release-preflight --strict-production` | FAIL on local development configuration (`APP_ENV`, debug, and release version) |
| `npm run build` | FAIL in local worktree; modified `vite.config.ts` invokes a Windows `git checkout` during Wayfinder generation and hit `.git/index.lock` permission failure |
| Focused readiness suite, 84 tests / 697 assertions | FAIL: 1 Windows CRLF-sensitive `Phase4ReadinessGateTest` failure |
| Full parallel PHPUnit baseline | FAIL: 3,282 tests / 27,154 assertions; 12 errors, 7 failures |

The full local failures are recorded as environment/source compatibility findings, not silently waived:

- 6 CI aggregator assertions resolve Windows absolute temp paths as `F:\kojaya/C:\Users\...`.
- 12 errors are concentrated in Windows OpenSSL test-key export and POS worker JSON/process execution.
- `Phase4ReadinessGateTest` and `FrontendFormatterHygieneTest` assume POSIX separators/LF when scanning repository files.
- One POS concurrency test reports malformed worker JSON.

The default `php artisan test --compact` invocation also hit the local PHP 128 MB memory limit and initially lacked the SQLite driver. The suite was rerun with the installed `pdo_sqlite`/`sqlite3` extensions and memory limit override through direct PHPUnit/ParaTest. The configured test database remained SQLite `:memory:` as defined by `phpunit.xml`.

Existing FUNC-12 evidence records exact-head CI success for its reviewed revision: 3,274 SQLite tests, 27,197 assertions, 81.40% coverage, and passing PostgreSQL suites. That evidence is not evidence for the current SHA `9b31b124`.

## CI Baseline

| Workflow | Purpose | Trigger | RC status |
| --- | --- | --- | --- |
| `.github/workflows/ci.yml` | Change classification, dependency audit, Pint, frontend build, generated drift, four PHPUnit shards, seed/migration, OpenAPI, PostgreSQL, and Phase 4 readiness | push/PR to `main`, manual | Mandatory; exact-head status for `9b31b124` was not verified |
| `.github/workflows/ui-audit.yml` | Deterministic Playwright visual/accessibility audit | PR path filter, manual | Required when applicable; no current exact-head result verified |
| `.github/workflows/deploy.yml` | Production deployment by explicit ref and production secrets | manual only | Not run; not part of RC-01 |

The CI source is compatible with the Laravel 12/PHP 8.4/Node 22/PostgreSQL project constraints and has a fail-closed `phase4-readiness` job. Live GitHub Actions status could not be read from this Windows environment because the GitHub CLI is unavailable; therefore mandatory CI is **UNVERIFIED**, not PASS.

## Migration Inventory

- Total migration files: **182**.
- Latest migration: `2026_09_11_193000_cutover_bank_batch_global_permission.php`.
- No production migration was executed.
- No shared database was reset, migrated, seeded, truncated, or rewritten.

Potentially destructive or operationally sensitive migrations:

- Schema drops/changes: removal of `employees.basic_salary`, removal of `cooperative_members.no_urut`, foreign-key/index changes, and POS uniqueness changes.
- Data/backfill migrations: savings ledger classification, payment contribution-type propagation, member validation-status backfill, notification JSON conversion, POS transaction/category organization backfills, including category duplication/remapping.
- Environment/driver-dependent migrations: PostgreSQL `jsonb`, MySQL/MariaDB JSON, and SQLite/MySQL/PostgreSQL-specific schema paths.
- Permission/data cutovers: attendance and bank-batch role permission mutations execute in transactions but change existing authorization rows.
- Long-running/data-sensitive work: migrations that iterate or chunk existing ledger, payment, member, transaction, category, and permission data.
- PAY-006 remains a separate operational process: `php artisan payments:migrate-proofs-to-private`, with dry-run, execute, second dry-run, and legacy-public-fallback disablement required before production deployment.

## Seeder / Bootstrap Inventory

The registry classifies 19 seeders; `DatabaseSeeder` is the orchestrator. Idempotence below is based on the implementation pattern and is not a production execution claim.

| Seeder/profile | Classification | Production-safe | Idempotent | Environment guarded | Notes |
| --- | --- | --- | --- | --- | --- |
| `TaxRuleSeeder`, `RolePermissionSeeder`, `LoanTypeSeeder`, `JobGradeSeeder`, `LeaveTypeSeeder`, `SalaryComponentTypeSeeder`, `WorkShiftSeeder` | Production-safe/reference | Yes | Yes / reference upsert | Registry profile | Reference roles, permissions, HR, loan, and tax data |
| `CooperativeReferenceSeeder` | Production-safe + organization/bootstrap candidate | Yes | Yes (`firstOrCreate`) | Registry profile | Creates canonical KOP-001 and cooperative reference data; does not create an admin |
| `CooperativeFixtureReferenceSeeder` | Organization/test bootstrap | No | Yes | Yes; local/testing/playwright | Adds KBU-001 and ISO-999 synthetic topology |
| `CooperativePersonaSeeder` | Development/test only | No | Yes-ish (`updateOrCreate`) | Yes; local/testing/playwright | Synthetic users/members with test credentials |
| `CooperativeMemberLifecycleSeeder` | Development/test only | No | Yes-ish | Yes; local/testing/playwright | Synthetic lifecycle states |
| `CooperativeFinancialFixtureSeeder` | Development/test only | No | Yes-ish | Yes; local/testing/playwright | Synthetic financial/POS/store-credit fixtures |
| `CooperativeSeeder` | Development/demo fixture | No | Partial | Yes; local/testing/playwright | Demo organizations, users, members, POS, dues, and SHU |
| `AnggotaSeeder` | Development/test fixture | No | Partial | Yes; local/testing/playwright | Synthetic member/payment data |
| `DemoDataSeeder` | Development/demo fixture | No | Partial | Yes; local/testing/playwright | Broad ERP demo data, users, projects, and transactions |
| `InvoiceSeeder` | Development/test fixture | No | No (factory inserts) | Yes; local/testing/playwright | Creates five invoices per organization/unit |
| `CooperativeManagerRoleSeeder` | Development/test admin fixture | No | Yes-ish | Yes; local/testing/playwright | Creates a synthetic manager account; never production bootstrap |
| `UiAuditSeeder` | Test-only fixture | No | Yes-ish | Yes; testing/playwright only | Deterministic Playwright/UI users and data |
| `CooperativeEdgeCaseFixtureSeeder` | Test-only invalid fixture | No | Yes-ish | Yes; testing/playwright only | Negative/edge lifecycle and financial fixtures |

First administrator bootstrap is the explicit `php artisan admin:create` command (`CreateAdminUserCommand`), not a production seeder. No production admin account was created during this task. Development/test seeders contain synthetic credentials and must not be used in production.

## Configuration Inventory

`.env.example` covers the main application, database, security, storage, payment, mail, SSO, notification, queue, cache, backup, and operations settings without real secret values. Required or conditionally required categories are:

| Category | Required/conditional keys | Notes |
| --- | --- | --- |
| Application | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_VERSION`, `API_CONTRACT_VERSION` | `APP_KEY` secret; production requires debug off and valid release version |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Required for the selected driver; no values recorded here |
| Cache/session/queue | `CACHE_STORE`, `SESSION_DRIVER`, session settings, `QUEUE_CONNECTION` | Must match deployed services and persistence expectations |
| PII/security | `PII_*` key maps/versions, `TOKEN_VERSION`, `SANCTUM_*`, ability cutover and legacy fallback settings | Required for release preflight; values are secret or security-sensitive |
| Storage/backup | `FILESYSTEM_DISK`, `PAYMENT_PROOF_DISK`, `PAYMENT_PROOF_LEGACY_PUBLIC_FALLBACK`, `BACKUP_*`, and conditional `AWS_*` | PAY-006 requires private payment-proof storage and fallback disabled after migration |
| Mail/notifications | `MAIL_*`, conditional `FCM_*`, `WHATSAPP_*` | Integrations may be disabled only when all credentials in that integration are absent |
| Payment | conditional `MIDTRANS_*`, `PAYMENT_GATEWAY_ALLOW_SIMULATION` | Production Midtrans requires the complete credential set and production policy |
| SSO | conditional `GOOGLE_SSO_*` | Required only when Google SSO is enabled |
| Network/operations | `TRUSTED_PROXIES`, `LOG_*`, `AUDIT_LOG_RETENTION_DAYS` | Must be reviewed for deployed topology |

The example is not fully exhaustive: several framework defaults and optional driver keys referenced by `config/*.php` are omitted, including some Redis/Mail/AWS/queue options and payment-proof fallback keys. This is an RC follow-up for fresh-environment documentation; no secret was added or exposed.

## Known Findings

### RC blockers

1. Working tree is not clean because two local files were already modified before RC-01. An immutable release baseline cannot be declared from this state.
2. Mandatory exact-head CI evidence for `9b31b124cd1ed57dd09dc30ced83c534f79a88ee` is unavailable. Existing FUNC-12/FUNC-13 evidence refers to earlier reviewed revisions.
3. The local Windows baseline does not pass: 3,282 tests produced 12 errors and 7 failures. Several are environment portability failures, but the release candidate is not reproducibly green in the stated fresh Windows environment.
4. The requested RC prerelease identity `v1.0.0-rc.1` is rejected by the current strict production preflight version validator, which accepts only stable `x.y.z`.
5. The local build is blocked by the pre-existing `vite.config.ts` modification. No source fix was made in RC-01.

### RC follow-up / deployment blocker

- PAY-006 payment-proof migration remains a production deployment blocker, as documented by FUNC-13, even though it is not itself a code-readiness blocker.
- Run the exact-head CI gate, including PostgreSQL, coverage, migration/seed, OpenAPI, generated drift, accessibility, and UI baseline checks, after the worktree and Windows compatibility findings are resolved.
- Make the release version policy explicit for RC prerelease values and ensure `.env.example` documents all conditionally required keys.
- Review the existing FUNC-13 PARTIAL evidence gaps EDGE-001, EDGE-002, EDGE-004, EDGE-005, and EDGE-006 with their recorded owners and follow-ups.

### Non-blocking dependency/debt findings

- Composer reports seven ignored medium/low advisories under the established CI threshold.
- NPM reports three moderate `qs` advisories with no available fix under the current dependency graph.
- Existing documentation contains older historical version/status statements; this report does not rewrite unrelated historical records.

## RC-01 Remediation

Original baseline:
9b31b124cd1ed57dd09dc30ced83c534f79a88ee

Remediated baseline:
f8e0800f7c234f5b1dc6d2471163b0e1a8461f8e (fix: close RC-01 baseline blockers)

### Blocker Resolution

- B1 Working Tree: **RESOLVED**. The stale Vite command was reverted, the generated Wayfinder runtime remained unchanged, and intended remediation changes are committed.
- B2 Exact-head CI: **RESOLVED**. CI run #462 executed ci.yml with head_sha exactly f8e0800f7c234f5b1dc6d2471163b0e1a8461f8e and concluded success.
- B3 Windows Test Baseline: **RESOLVED**. Full ParaTest passes with 3,286 tests and 27,287 assertions. Shared fixes cover Windows absolute paths, child SQLite CLI configuration, deterministic RSA test keys, CRLF workflow scanning, and path-separator normalization.
- B4 Frontend Build: **RESOLVED**. Canonical npm run build passes after moving the manual audit-log API module out of the Wayfinder-generated resources/js/Actions tree; no OS-specific Vite checkout command remains.
- B5 RC Version Policy: **RESOLVED**. --strict-release-candidate accepts valid SemVer stable/prerelease values, while --strict-production continues to require a stable SemVer without a prerelease identifier. APP_VERSION uses no v prefix; Git tags retain the v prefix.

### Remediation Verification

| Check | Result |
| --- | --- |
| Focused readiness suite | PASS; 88 tests / 729 assertions |
| Full Windows ParaTest | PASS; 3,286 tests / 27,287 assertions |
| Pint | PASS |
| OpenAPI snapshot | PASS; up to date |
| Composer validation | PASS |
| UI baseline integrity | PASS; 234 valid, 0 missing/orphan/duplicate/invalid |
| Frontend production build | PASS; canonical npm run build |
| Strict RC preflight | PASS with APP_VERSION=1.0.0-rc.1 |
| Composer/NPM audit thresholds | PASS under repository thresholds; existing ignored advisories remain |
| Exact-head CI run #462 | PASS; [ci.yml](https://github.com/johnd-creator/kojaya/actions/runs/36130328122) |
| Phase 4 Readiness Gate | PASS; includes desktop accessibility audit |
| ui-audit.yml | Not triggered by push; workflow trigger is PR/manual, with equivalent UI/accessibility checks PASS in ci.yml Phase 4 |

## Release Blockers

No RC-01 baseline blockers remain. PAY-006 remains a known pre-deployment requirement outside this remediation scope and is not a reason to alter production data in RC-01-FIX-01.

## Freeze Decision

**PASS**

No `v1.0.0-rc.1` tag was created or moved. After all blockers are resolved, the exact command for approval is:

```bash
git tag -a v1.0.0-rc.1 f8e0800f7c234f5b1dc6d2471163b0e1a8461f8e -m "Kojaya v1.0.0-rc.1"
```

The tag must be created only after the final clean-tree and exact-head CI review. It must not be pushed or moved by RC-01 without the required review/approval.

## RC-01 Result

```text
KOJAYA RELEASE CANDIDATE BASELINE
================================

Version        : v1.0.0-rc.1 (candidate, not tagged)
Branch         : main
Commit SHA     : f8e0800f7c234f5b1dc6d2471163b0e1a8461f8e

Working Tree   : CLEAN after final evidence commit
Phase 4        : PASS; exact-head run #462
Tests          : PASS / 3,286 tests / 27,287 assertions locally
Mandatory CI   : PASS / exact-head verified
Migration Audit: COMPLETE
Seeder Audit   : COMPLETE
Config Audit   : COMPLETE

Release Blocker: NONE

RC-01 RESULT
============
PASS
RC-01 baseline gate passed. Do not create or push the tag without the required approval.
```
