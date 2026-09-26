# Kojaya QA Fresh-Install Runbook

This procedure provisions a disposable QA instance from an approved Kojaya source revision. It is not a production deployment procedure.

## Safety boundary

- Never point this procedure at production or the shared `kojaya_erp` database.
- Never reuse production credentials or run demo/test seeders.
- Use a new, empty PostgreSQL database with a QA-specific name, such as `kojaya_qa_rc02`.
- Do not weaken PostgreSQL authentication globally to make the test work. Use an authorized QA role/credential or a separately provisioned disposable PostgreSQL instance.
- Do not include passwords, application keys, or PII keys in logs, screenshots, or audit reports.
- `composer setup` installs dependencies, prepares `.env`/`APP_KEY`, and builds assets. It deliberately does not provision a database.

## Prerequisites

Install Git, PHP 8.2+ with `pdo_pgsql`, Composer, Node.js 22.x, npm, and PostgreSQL 15+. Node 22 is the project baseline documented in `docs/architecture.md` and used by CI; the Node 24 observation from the RC-02 host is not a supported-version change. The project currently does not pin Node with `.nvmrc` or a `package.json` engine range.

## Fresh checkout and dependencies

From PowerShell:

```powershell
git clone https://github.com/johnd-creator/kojaya.git
Set-Location kojaya
git fetch --all --prune
git switch --detach <approved-candidate-sha>
git rev-parse HEAD
git status --short
composer install --prefer-dist --no-interaction
npm ci
npm run build
```

Confirm the checked-out SHA exactly matches the release/QA approval before proceeding. At this stage there should be no application database writes.

## Environment and PostgreSQL target

```powershell
Copy-Item .env.example .env
```

Review every environment-specific setting before running Artisan commands. Set `APP_ENV=qa`, `APP_DEBUG=false`, the approved candidate in `APP_VERSION`, the correct `APP_URL`, `DB_CONNECTION=pgsql`, PostgreSQL host/port, and a new disposable QA database name such as `kojaya_qa_rc02`. Provide the QA username/password through the approved local secret mechanism; do not place credentials in shell command arguments or commit `.env`.

Have the database owner create the empty database and grant access through the environment's authorized provisioning mechanism. Verify its identity and that it contains no application tables using the approved PostgreSQL client. Record PostgreSQL version, host, port, database name, and `APP_ENV` in the QA evidence; omit credentials. Do not use `kojaya_erp`.

For a PostgreSQL role authorized to create the disposable QA database, the client-side PowerShell form is:

```powershell
createdb --host <qa-host> --port <qa-port> --username <qa-provisioner> --owner <qa-app-role> kojaya_qa_rc02
psql --host <qa-host> --port <qa-port> --username <qa-app-role> --dbname kojaya_qa_rc02 --command "SELECT current_database(), current_user, current_setting('server_version'); SELECT count(*) FROM pg_catalog.pg_tables WHERE schemaname = 'public';"
```

Use the approved password prompt or protected PostgreSQL credential store; never pass passwords on the command line. The initial public-table count must be zero. If the operator cannot create databases, the authorized database owner must provision this exact isolated target and grant the QA application role access; do not change global PostgreSQL authentication to bypass the role boundary.

Generate the app key after the environment file is reviewed:

```powershell
php artisan key:generate
```

The migration safety guard refuses missing/ambiguous environment or target configuration and known template/default database names. QA/staging migrations require a PostgreSQL target with `qa`, `test`, or `staging` in its name and the explicit `--force` flag. Production keeps the existing deployment policy: only the explicit production deploy flow with `--force`; destructive reset commands remain prohibited outside local/testing/playwright.

## Migration and reference bootstrap

```powershell
php artisan migrate --force
php artisan migrate:status
php artisan db:seed --force
```

The default `DatabaseSeeder` in QA creates only the approved reference/bootstrap dataset. Do not invoke local demo, member, payment, POS, ledger, or test seeders. Verify migration status reports no pending migrations. Repeat the default safe seeder once in QA and confirm it creates no duplicate reference/organization rows. Note that current `TaxRuleSeeder` refreshes its canonical tax values and `RolePermissionSeeder` re-synchronizes seeded role mappings; review operator customizations before rerunning it on any persistent environment.

## Storage and administrator

The `public/storage` runtime link is intentionally not tracked in Git. On Windows, Laravel creates a directory junction when the process has sufficient filesystem permission; Developer Mode or elevated symlink privilege is not required for the junction path. If the link cannot be created, inspect the exact `public/storage` object and remove only an invalid pointer created by a prior checkout; never remove private storage. On Linux, Laravel creates a symbolic link. Both target only `storage/app/public`; private employee/member/project documents and payment proofs remain outside that public target.

Initialize public storage:

```powershell
php artisan storage:link
```

Create the first administrator only after the safe reference seed has created the approved role. Prefer the hidden interactive password prompts:

```powershell
php artisan admin:create --email=<qa-admin-email> --name="QA Administrator"
```

For a controlled non-interactive process, supply the password through standard input (never an argument):

```powershell
Get-Content -Raw -LiteralPath <protected-password-file> | php artisan admin:create --email=<qa-admin-email> --name="QA Administrator" --password-stdin
```

Protect and remove the temporary password file according to the QA secret-handling policy. The command validates email format and uniqueness, enforces the password policy, assigns only an allowed administrative role, and never prints the password. `--password` exists only for automated tests and is rejected outside `APP_ENV=testing`.

## Boot and smoke checks

```powershell
php artisan about
php artisan route:list --path=login
php artisan serve --host=127.0.0.1 --port=8000
```

From a browser, verify login-page GET, static assets, CSRF/session behavior, administrator POST login, authenticated dashboard redirect, and logout. Also verify `/up` and the approved API health/OpenAPI endpoints. Confirm no runtime error in Laravel logs, without copying secrets or personal data into the report. Shut down the local server after the smoke test.

A non-GUI HTTP client is also acceptable for this smoke test when it preserves one cookie jar across the complete flow. Start it only after the reviewed `.env` and generated `APP_KEY` are in place; use the exact origin/port printed by the server. GET `/login`, read the form action, field names, CSRF token, and session cookie from that response, then POST `application/x-www-form-urlencoded` credentials with the same cookie jar (including the Inertia request headers used by the page). Keep redirects and subsequent dashboard/logout requests on that same client and cookie jar. Verify the QA target from the loaded application configuration and report only whether the expected fields are present/exact/non-empty; never print password values or cookie contents. A CLI provider/hash check alone is not evidence of HTTP authentication.

If the selected QA queue driver is not `sync`, run the configured queue worker. Run the Laravel scheduler through the approved process supervisor; for a local Windows QA host, Task Scheduler may invoke `php artisan schedule:run` each minute. Neither a local built-in PHP server nor `sync` queue is evidence of production web/worker topology.

## Bootstrap manifest

| Item | Source | Required at first boot? | Automated/manual | Idempotency / production safety |
| --- | --- | --- | --- | --- |
| Schema | Versioned Laravel migrations | Yes | Automated | Forward migrations; target guard; no production reset |
| Roles and permissions | `RolePermissionSeeder` via `DatabaseSeeder` | Yes for authorized admin pages | Automated | Re-syncs canonical mappings; safe only after reviewing local policy customizations |
| Head-office organization | `CooperativeReferenceSeeder` | Required for current admin organization assignment/real operations; not framework boot | Automated then manual identity review | `firstOrCreate`; production-safe mechanics, but placeholder identity/contact details need owner confirmation |
| Reference masters (tax, loan, HR, shifts, contribution types, POS categories) | The eight canonical reference seeders via `DatabaseSeeder` | Feature-dependent | Automated then manual policy review | Mostly first-create; tax uses `updateOrCreate`, so can overwrite canonical tax values |
| Initial QA administrator | `admin:create` | Yes for authenticated admin smoke | Manual, explicit | Email unique; secret hidden/stdin; no seeded/default user |
| Departments and operational units | Approved cooperative structure | No for first boot/admin smoke; needed for realistic operational QA | Manual | Real organization data; do not invent in seeders |
| Members, employees, transactions, ledgers, payment artifacts | Approved migrated business data | No for first boot | Controlled data migration only | Not seeds; outside this fresh-install procedure |
| APP_KEY, PII keys, DB credentials, integrations | Environment secret/configuration owner | Keys/DB required for corresponding feature; integrations optional unless tested | Manual/secret manager | Environment-specific; never report values |
| Public storage link | Laravel filesystem configuration | Only for public uploaded assets | Automated command | Runtime OS link to public disk only; no private data |

## Completion evidence

Record source SHA, Git status, PostgreSQL server version and non-secret target identity, migration counts/status, safe-seed row counts and repeat result, build/test results, live GET/POST login/dashboard/logout result, storage link type, and any limitations. Keep production data, secrets, and payment integration activity out of the QA evidence.
