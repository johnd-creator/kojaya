# Kojaya RC-02 QA Environment Audit

Audit date: 2026-09-25 (Asia/Jakarta). Scope was environment/bootstrap reproducibility only. No frozen source was edited, no production/shared database was migrated or seeded, and no payment, mail, SSO, push, or webhook transaction was sent.

## Release Candidate

| Item | Evidence |
| --- | --- |
| Version/tag | `v1.0.0-rc.1`; local tag was absent, so the exact frozen SHA was checked out detached |
| SHA | `e439b3e8ace035ed6ae9ff032abcccd76685b3c8` |
| Repository | Fresh clone of `https://github.com/johnd-creator/kojaya.git` |
| Initial worktree | Clean; no `vendor`, `node_modules`, `.env`, storage runtime, or build output copied from the main checkout |

## Host Inventory

The host is Windows, whereas the deployment architecture documents Ubuntu 22.04/24.04 with Nginx and PHP-FPM. WMI denied CPU/RAM/disk inventory calls; OS build and disk were obtained through other local APIs.

| Component | Repository requirement/documentation | Installed/result | Status / notes |
| --- | --- | --- | --- |
| OS | Ubuntu 22.04 LTS minimum; Ubuntu 24.04 recommended | Windows NT 10.0, build 26200.9550; product API labels Windows 10 Pro | VERSION MISMATCH; Windows is not the documented production target |
| Architecture | x64 Linux deployment target | AMD64/x64 | PASS for architecture only |
| CPU | 2 cores minimum / 4 recommended | 16 logical processors; exact model and physical core count inaccessible | UNKNOWN against physical-core requirement |
| RAM | 4 GB minimum / 8 GB recommended | Not readable; WMI returned Access denied | UNKNOWN |
| Disk | 40 GB minimum / 80 GB recommended | F: 476.9 GB total, 240.2 GB free | PASS for that volume |
| Git | Not pinned | 2.55.0.windows.5 | Installed |
| PHP | PHP 8.4 in architecture; Composer constraint `^8.2` | 8.4.25 x64; `pdo_pgsql` loaded | PASS; `pdo_sqlite` is not enabled by the host `php.ini` (DLL exists and was enabled only for disposable tests) |
| Composer | Lockfile-driven install | 2.10.3 | PASS |
| Node.js | Node 22 in repository architecture/CI | 24.21.0 | VERSION MISMATCH to documented major; locked install/build nevertheless passed |
| npm | Use lockfile (`npm ci`) | 11.19.0 | Installed; host policy withheld three dependency install scripts, but build passed |
| PostgreSQL | PostgreSQL 15+ | PostgreSQL 18 service running; `psql` 18.6; port 5432 accepts connections | Client/service present; server version and DB access unverified because password authentication rejected passwordless connection |
| Redis | Optional in development; recommended for production cache/session | No Redis executable/service found | OPTIONAL for local boot; production topology not validated |
| Web runtime | Nginx + PHP-FPM recommended in production | No Nginx found; PHP CLI available | MISSING production web runtime; PHP built-in server is development-only |

## Fresh Clone Evidence

The clone was detached at the exact SHA above and `git status --short` was empty before installation. Composer and npm dependencies were installed from the checked-in lockfiles. The C: temp clone initially hit sandbox path restrictions during Vite config discovery; repeating the fresh clone under the workspace produced a successful build. No main-checkout source change was made.

## Dependency Installation

| Check | Result |
| --- | --- |
| `composer install --prefer-dist --no-interaction --no-progress` | PASS; 149 locked packages installed, Laravel package discovery completed |
| `composer validate --strict` | PASS |
| `composer check-platform-reqs` | PASS for all Composer package requirements on PHP 8.4.25 |
| `npm ci --cache <clone-local-cache> --no-audit --no-fund` | PASS; 479 packages. The default profile npm cache first returned `EPERM`; clone-local cache worked |
| npm install scripts | npm 11 reported `esbuild`, `unrs-resolver`, and `vue-demi` postinstall scripts not covered by `allowScripts`; none were approved or run manually |
| `npm run build` | PASS; Vite 7.3.6, 3,904 modules, 25.81 s. One existing CSS parser warning on `article#article-*` |

## Environment Configuration

`.env.example` is a development template, not a ready QA/RC profile. A clone-local `.env` and app key were generated for the disposable smoke environment; `APP_VERSION` was explicitly set to `1.0.0-rc.1` (no `v` prefix, matching RC-01 convention). No secret value is included in this report.

| Category | Audit |
| --- | --- |
| Application | Template defaults `APP_ENV=local`, `APP_DEBUG=true`, and `APP_VERSION=0.1.0-dev`; QA must explicitly set its URL, environment, debug policy, and RC version. `APP_KEY` was generated only in the disposable clone |
| Database | Template selects PostgreSQL but defaults `DB_DATABASE=kojaya_erp`. Do not run setup/migrations before replacing it with a unique QA database and credentials |
| PII/security | PII encryption and blind-index key slots are blank in the template. Release/QA owners must set and validate environment-specific keys/versions through the secret manager/preflight; values were not displayed |
| Cache/session/queue | Template uses file sessions/cache and `sync` queue: sufficient for basic local boot, not evidence of production worker/cache topology |
| Storage | Default local disk is private-root storage; `public` is a separate disk. Payment-proof disk/fallback keys are absent from the template and rely on config defaults (including legacy public fallback enabled) |
| Mail/payment/SSO/push | Mail defaults to `log`; Midtrans credentials are blank and production mode false; Google SSO is disabled; FCM/WhatsApp credentials are blank |
| Optional cloud services | AWS/S3 and Redis settings are conditional and require explicit QA/production values if selected |

No complete RC-specific fresh-install runbook was found. `docs/staging-preflight-and-smoke.md` is for an already provisioned staging environment and explicitly leaves execution pending.

## Database Fresh Migration

PostgreSQL was not used: the server accepted TCP connections, but `psql -U postgres -d postgres -w` failed with `fe_sendauth: no password supplied`. No password was guessed or read from the existing checkout, and no database was created or changed on that server.

To validate migration mechanics safely, a new, empty, file-backed SQLite database inside the fresh clone was used with the available SQLite DLLs enabled for that process:

| Item | Result |
| --- | --- |
| Initial state | New zero-byte disposable SQLite file; 0 application tables |
| `php artisan migrate` | PASS; 182 migrations applied, no failure; approximately 7.3 s on the first run |
| `php artisan migrate:status` | PASS; 182 recorded as applied, 0 pending |
| Engine identity | SQLite scratch database; not equivalent to the required PostgreSQL migration proof |

## Seeder Audit

The canonical `DatabaseSeeder` path was run after migration on a second empty disposable SQLite file with `APP_ENV=qa`. It completed successfully and called exactly these eight production-safe/reference seeders. Development/demo/test seeders were not run.

| Seeder | Purpose / observed behavior | Fresh bootstrap need | Repeat behavior / caveat |
| --- | --- | --- | --- |
| `TaxRuleSeeder` | Default PPh 21 TER rule | Reference data for payroll tax | Uses `updateOrCreate`; repeat succeeds but overwrites operator edits to that code |
| `RolePermissionSeeder` | Roles and permission catalog | Required by authorization and `admin:create` | Creates 16 roles / 129 permissions; `syncPermissions` resets role mappings to seeded policy |
| `LoanTypeSeeder` | Three canonical loan types | Loan operations | `firstOrCreate`; preserves existing values |
| `JobGradeSeeder` | HR/job-grade reference | HR/payroll operations | `firstOrCreate` |
| `LeaveTypeSeeder` | Leave reference types | Leave operations | `firstOrCreate` |
| `SalaryComponentTypeSeeder` | Payroll component reference | Payroll operations | `firstOrCreate` |
| `WorkShiftSeeder` | Four work shifts | Attendance/roster operations | `firstOrCreate` |
| `CooperativeReferenceSeeder` | Head-office organization, savings contribution types, POS categories | Cooperative bootstrap candidate | `firstOrCreate`; seeds KOP-001 with hard-coded organization contact/address defaults that must be confirmed before real operational use |

All eight classes were also run individually twice on the disposable database without errors. After the canonical QA seed path: roles 16, permissions 129, role-permission mappings 476, organizations 1, departments 0, users 0, members 0, loan types 3, tax rules 1, work shifts 4, contribution types 4, and POS categories 6. This demonstrates no development-account/member fixture dependency, but it does not establish that the seeded cooperative identity or tax defaults are appropriate for a real cooperative.

## Bootstrap Gap Analysis

| Bootstrap item | After migrate | After safe seed | Manual/owner action | Blocks basic boot? |
| --- | --- | --- | --- | --- |
| Roles and permissions | Absent | Present | None for canonical roles; approve any local policy customization | Yes for admin creation/authorized pages |
| Organization | Absent | One KOP-001 head-office row | Confirm official identity/contact fields; create approved operational units/branches | Not for framework boot; required for real operations |
| Departments/operational units | Empty | Still empty | Configure actual organization topology and departments | Not for initial System Admin login; blocks realistic operational QA |
| Reference master data | Absent | Tax, loans, HR, shifts, contribution types, POS categories present | Review defaults against cooperative policy | Feature-dependent |
| Initial administrator | Absent | Still absent | Run `php artisan admin:create` after role seeding | Yes for authenticated admin smoke |
| Numbering/workflow state | No separate row is created by the eight safe seeders; workflow states are code-level | No additional bootstrap row | Verify any deployment-specific numbering/sequence needs in RC-03 | Not shown to block app boot |
| Demo/test fixtures | Absent | Absent (`users=0`, `members=0`) | Do not add for production bootstrap | No |

`composer.json`'s `setup` script does not seed roles or reference data. Thus `admin:create` cannot run immediately after that script; the safe `DatabaseSeeder` step must be explicit and documented.

## Admin Bootstrap

`php artisan admin:create --help` passed. On the disposable seeded SQLite database, `admin:create` created temporary synthetic QA administrators using its generated 24-character password; output was retained only in process memory for the smoke attempt and was not printed or persisted. A duplicate email without `--update-existing` was rejected. Source inspection confirms a role allowlist, password hashing, no application-log write of the generated password, and assignment of the selected role; `RolePermissionSeeder` supplies its permissions.

Limitations/findings: email input is only checked for non-empty, not validated as an email address. The generated password is printed once to stdout (so a shell/CI transcript can capture it), and supplying `--password` can expose it in shell history/process listings; the command itself warns about that risk. The command has no environment restriction. A valid temporary admin was created only in the disposable database; no production identity was used.

## Application Boot

| Smoke | Result |
| --- | --- |
| Composer post-autoload Laravel package discovery / `php artisan about` | PASS |
| `php artisan route:list --path=api/openapi.json` | PASS; route registered |
| Login page route and password-authentication feature suite | PASS; `AuthenticationTest`: 6 tests / 13 assertions |
| Authenticated admin dashboard/sidebar and API smoke suite | PASS; 3 targeted tests / 20 assertions (dashboard/sidebar, API health, OpenAPI endpoint) |
| Static production assets | PASS via successful production Vite build |
| Live PostgreSQL-backed login/dashboard | NOT VERIFIED; no authenticated QA PostgreSQL database was available. The attempted live HTTP login POST returned 500 in the local server smoke and was not resolved; the host PHP does not enable `pdo_sqlite` by default. In-process Laravel authentication tests passed with the SQLite DLL explicitly enabled |

The test configuration was inspected before test execution: `phpunit.xml` forces SQLite `:memory:` and `Tests\TestCase` resets its configured connection to in-memory SQLite. No test used `kojaya_erp`.
The dashboard/sidebar smoke used the existing `Admin Pusat` fixture; it does not substitute for a successful live login with the command-created `System Admin` account, which remains unverified.

## Storage

`storage/app/private` and `storage/app/public` exist in the clean checkout; employee/project documents use separate private disks, while the public disk targets only `storage/app/public`. The configured `public/storage` link does not target the private directory.

Windows-specific checkout blocker: the repository tracks `public/storage` as a Git symlink (mode `120000`), but this host has `core.symlinks=false`. A fresh clone therefore materializes the 21-byte target text (`../storage/app/public`) as a regular file. Both `php artisan storage:link` and `php artisan storage:link --force` report that the link already exists and leave the invalid file in place. After removing only that verified pointer file in the disposable clone, `storage:link` succeeded and created a Windows Junction. The fresh-clone procedure does not document this required workaround. This is a real public-file serving gap on this Windows checkout, not a private-document exposure observed in this audit.

## Queue / Cache / Scheduler

- Basic web boot uses file session/cache and `sync` queue from `.env.example`; no Redis is required for this smoke path.
- The application contains `ShouldQueue` jobs/listeners/notifications (including notification outbox, auth audit, and report PDF work). `sync` executes such work inline and is not evidence of an operational background worker. QA/production must choose a persistent queue backend and run a worker.
- `routes/console.php` registers periodic maintenance/expiry, monthly dues, token pruning, notification/outbox delivery, order reservation/charge recovery, retention, and database backup tasks. The scheduler must run; on Windows the equivalent can be Task Scheduler invoking `php artisan schedule:run` each minute or a supervised `schedule:work` process. No worker/scheduler service was installed or tested here.
- Redis is recommended for production cache/session in architecture docs but absent on this host; configure file/database drivers deliberately for QA if Redis is not part of that environment.

## Integration Readiness

| Integration | Configuration evidence | Sandbox/network/credential test | Status |
| --- | --- | --- | --- |
| Midtrans | Template keys blank; production flag false | No sandbox credential; no request or transaction | NOT TESTED; RC-06 input |
| Google SSO | Disabled by default; no credentials supplied | No OAuth round-trip | NOT TESTED; RC-06 input |
| Mail | `log` mailer default | No SMTP send | Local-only; QA mail transport required if tested |
| FCM / WhatsApp | Credential fields empty | No delivery or network call | NOT TESTED; RC-06 input |
| S3/object storage | Conditional AWS values | No bucket/credential | NOT TESTED; configure only if selected |
| Webhooks/external API | Routes/config exist | No external webhook/API call | NOT TESTED; no production transaction performed |

## Findings and Blockers

### Blockers

1. **Setup command can migrate the wrong database.** `composer.json`'s `setup` script runs `php artisan migrate --force` after copying `.env.example`, whose default database name is `kojaya_erp`. It also uses `npm install` rather than the authoritative `npm ci`. The unsafe script was not run. Do not use it until the target DB is explicitly isolated; resolve in RC-02-FIX-01 and document a safe fresh install.
2. **Primary-engine gate remains unproven.** The repository requires PostgreSQL, and a local PostgreSQL 18 service is listening, but password authentication prevented verifying its server version, creating a uniquely named empty QA database, or running the 182 migrations there. SQLite migration success is supplemental only. Provide an authorized isolated QA DB/credential or a separately provisioned disposable PostgreSQL instance, then rerun migration and application smoke.
3. **Windows Git symlink checkout breaks the documented storage command.** See Storage: clean clone produces a regular pointer file; the standard command and `--force` both fail. The required deletion/workaround is undocumented. Resolve clone/link handling and add a tested Windows bootstrap instruction in RC-02-FIX-01/RC-08.
4. **Fresh provisioning procedure is incomplete.** The current staging runbook assumes an existing staging host and synthetic accounts; there is no safe first-install sequence that configures an isolated DB, applies migrations, runs only safe seeders, creates an admin, and prepares storage/worker/scheduler. The existing `composer setup` is not a safe substitute.

### Follow-up inputs

- **RC-03 — Production Bootstrap & Seed Readiness:** approve canonical cooperative organization details and branch/department topology; decide how operators change tax defaults and whether reseeding should reset custom role permissions. The current classification “production-safe” does not mean all operator-edited values are preserved.
- **RC-06 — Integrations:** provide sandbox endpoint/credentials and explicit non-production smoke procedure for payment, SSO, mail, push, and webhooks.
- **RC-07 — Environment/security:** define and validate PII key provisioning/version policy; make QA/production values mandatory without displaying them.
- **RC-08 — Runbook:** document Node 22 or expand supported-version policy; safe Composer/npm commands; Windows symlink behavior; OS-specific queue/scheduler/web-runtime operating model.
- **Admin command:** add email-format validation and a secure generated-password delivery path that cannot be silently captured in CI/shell logs; these were audited but not changed on the frozen RC.
- **Host inventory limitation:** exact CPU model/physical cores, RAM, and definitive Windows product release could not be read under current WMI permissions.

## Reproducibility Verdict

**REPRODUCIBLE: NO — RC-02 BLOCKED.** The exact clone, lockfile dependency installs, frontend build, SQLite migrations, eight-seeder QA bootstrap, CLI app boot, auth feature tests, dashboard/API tests, and temporary-admin command passed. However, the documented setup path is unsafe/incomplete, PostgreSQL fresh migration and live PostgreSQL login remain unverified, and a fresh Windows clone's public-storage symlink is not usable through the normal `storage:link` command. Do not claim QA-environment acceptance until the blockers above are resolved and the full gate is rerun against an isolated PostgreSQL QA database.

No frozen application source was edited during RC-02. The intended tracked deliverable is this audit report only.

## RC-02-FIX-01 Remediation

Original RC: `v1.0.0-rc.1`

Original SHA: `e439b3e8ace035ed6ae9ff032abcccd76685b3c8`

Starting branch: `main`
Starting local and fetched `origin/main`: both `e439b3e8ace035ed6ae9ff032abcccd76685b3c8`; `git pull --ff-only` reported already up to date. The audit report from the preceding RC-02 task was already untracked and has been preserved/updated. No RC-1 tag was edited.

The original blocked findings above are retained as the historical RC-02 record. Current remediation evidence is:

| Gate | Current result | Evidence / remaining work |
| --- | --- | --- |
| B1 dependency setup / implicit database write | RESOLVED LOCALLY | Composer `setup` and `post-create-project-cmd` no longer run migrations; setup uses `npm ci`. `ComposerSetupSafetyTest` and database-target policy tests pass. Unsafe environment/target defaults are rejected by the new migration guard. |
| B2 fresh PostgreSQL migration | VERIFIED LOCALLY | Separate disposable PostgreSQL 18.6 cluster, loopback-only port 55432, QA database `kojaya_qa_rc02`, initially 0 application tables. All 182 migrations applied and status showed no pending migrations. The existing PostgreSQL Windows service/authentication configuration was not changed. The temporary cluster used local trust authentication and is not evidence for production credential/ACL policy. |
| Safe QA reference seed / repeat | VERIFIED LOCALLY | Default `DatabaseSeeder` was run twice before admin creation and once more afterward. The final counts remained stable: 16 roles, 129 permissions, 476 role-permission mappings, 1 organization, 3 loan types, 1 tax rule, 4 work shifts, 4 contribution types, 6 POS categories; 1 explicit QA administrator, 0 members, and 0 departments. No demo seed was run. |
| B3 Windows storage initialization | RESOLVED LOCALLY | `public/storage` was removed as a tracked Git symlink (mode 120000). `php artisan storage:link` succeeded on Windows and created a Junction to `storage/app/public`. Private employee/member/project documents and payment proofs were not linked. |
| B4 safe first-install procedure | IMPLEMENTED; REPLAY PENDING | Added `docs/releases/QA-FRESH-INSTALL-RUNBOOK.md`, including the bootstrap manifest, QA-only database setup, Windows storage, admin password, and runtime smoke sequence. Exact clean-room replay remains pending on a committed candidate SHA. |
| B5 live PostgreSQL application/login | VERIFIED LOCALLY | On the disposable PostgreSQL database, live HTTP `/up` and `/api/openapi.json` GET=200; `/login` GET=200 with session/CSRF cookie; admin POST `/login`=302; dashboard GET=200; logout POST=302; login GET after logout=200. A built Vite JavaScript asset was independently served over HTTP with status 200. Synthetic admin password was supplied via stdin and was not printed. |
| Admin email/password hardening | RESOLVED LOCALLY | Email uses required RFC-format validation and duplicate checks. New account password is hidden interactive input or `--password-stdin`; `--password` is rejected outside automated tests; generated password output has been removed. Focused tests cover creation, role/permission assignment, invalid/duplicate email, password strength, and output secrecy. |
| `.env.example` | RESOLVED LOCALLY | `APP_ENV=qa`, `APP_DEBUG=false`, sentinel `DB_DATABASE=kojaya_qa_unconfigured`, and `APP_VERSION=0.0.0-unconfigured`. The migration guard rejects the sentinel target; strict RC preflight rejects the template version. Real credentials remain absent. |
| Node policy | NO POLICY CHANGE | Node 22 remains the documented architecture and CI baseline. The RC-02 host's Node 24 success is only a compatibility observation; no `.nvmrc` or `engines` range currently exists. |
| Regression / exact-head CI | IN PROGRESS; CONCURRENCY CI GATE REQUIRED | Latest focused SQLite regression: PASS (45 tests, 135 assertions), including migration safety, Composer setup safety, admin credentials, and strict release preflight. `Document05PostgreSQL`: PASS (71 tests, 393 assertions) against the isolated PostgreSQL 18.6 test database after adding an explicit, name-locked PostgreSQL suite profile; ordinary tests still force SQLite `:memory:`. `PostgreSQLConcurrency`: 26 tests / 267 assertions, 10 failures on Windows in worker synchronization (nine lock-wait assertions and one concurrent Google SSO worker result); this is not counted as passing and must be adjudicated by exact-head Linux CI. Full default PHPUnit, run with a 1 GB memory limit after the default 128 MB process exhausted memory, was manually stopped at 1,464/3,301 tests (44%) because repeated Laravel/SQLite setup made the local run excessively long; it produced no reported failure before stopping, but is incomplete and not a pass. Earlier Composer strict validation/platform requirements, Pint, OpenAPI drift, frontend build, UI-baseline integrity (234/234), and strict RC preflight passed. Candidate commit, clean-room replay, push, and exact-head CI have not yet completed. |

The temporary QA administrator and PostgreSQL cluster are disposable test state, not production/bootstrap identities. No external integrations were contacted, no shared/production DB was used, no PAY-006 operation was performed, and no RC-2 tag was created. Final remediation SHA, CI run ID/result, clean-room replay, and final re-gate will be recorded only after those steps complete.
