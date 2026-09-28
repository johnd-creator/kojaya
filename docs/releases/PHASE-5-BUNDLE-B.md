# Phase 5 Bundle B — Deployment and Recovery

## Baseline and authority

Baseline `main` / `origin/main`: `d167ae9d2f0be265cab69d1da073970401801710`,
clean, ahead/behind 0/0, verified again before branch creation. Baseline full
[CI 36493519192](https://github.com/johnd-creator/kojaya/actions/runs/36493519192)
passed. Branch: `astra/phase5-bundle-b-rc09-rc10`.

Bundle A is closed: RC-06/07/08 repository gates PASS. Android live acceptance
belongs to Phase 6 QA-06. Production Firebase project/IAM/credentials remain
RC-11 prerequisites. Neither deferral removes the deployment flags
`--strict-production --require-android-push`.

This document is the operator sequence and failure decision contract. The
[backup runbook](../backup-runbook.md) owns backup verification, receipt fields,
and fresh-database restoration. The
[RC-06 plan](PRODUCTION-BOOTSTRAP-MANIFEST.md#rc-06-production-migration-plan)
owns the pending migration review and data/key recovery boundaries.
Commands below describe a future, separately approved production operation;
Bundle B executes only isolated rehearsal/tests.

## RC-09 — Pre-deployment acceptance

The release owner and operator must record all of the following before invoking
the deployment workflow or script. Missing evidence means STOP.

| Gate | Required evidence / implementation boundary |
| --- | --- |
| Release authority | Approved exact 40-character **commit** SHA, successful full CI for that SHA, QA acceptance, release approver, operator identity, environment, UTC maintenance window, incident contact and abort budget. A successful Git lookup is not release approval. |
| Candidate identity | Compare requested SHA to approved receipt and checked-out commit. `main`, `HEAD`, tags (including latest), short SHAs and annotated tag object IDs are not deployment authority. Workflow validates before checkout and compares checkout SHA; server validates again. |
| Repository | Correct approved remote/checkout, readable object database, no in-progress Git operation, no tracked changes or untracked application files. Server rejects dirty status without printing filenames. Ignored runtime/config/artifact directories need separate integrity/ACL review. Preserve unknown files; never use reset/clean as deployment preparation. |
| Host/runtime | Approved OS/PHP/Composer/Node/npm/PostgreSQL client/server versions and extensions, selected from target `composer.json`, lockfiles and successful CI. Record `php --version`, `composer --version`, `node --version`, `npm --version`, `pg_dump --version`, `pg_restore --version`, and `composer check-platform-reqs --no-dev` for the installed target. Do not run setup/install scripts as an environment probe. |
| DB identity and capacity | Operator proves effective DB host/database identity through the intended runtime (including config cache/URL overrides); records redacted identity and `SELECT 1` result. Review complete pending ledger, table/lock budget and clone rehearsal. Check private backup/build/storage free space, inodes, permissions and quota. Never print connection strings. |
| Environment and keys | `APP_ENV=production`, `APP_DEBUG=false`, approved stable `APP_VERSION`, HTTPS APP_URL, Secure/HttpOnly cookies, trusted proxy/host topology, existing APP/PII key versions available, PII schema rollback disabled. Strict preflight enforces configuration checks; it does not prove DNS/TLS/ACL/provider operation. An RC label does not pass stable production preflight. |
| Integrations | Required FCM HTTP v1 project + private service-account path, legacy key absent, approved API/IAM and real delivery evidence; other enabled payment/WhatsApp/SSO/mail/storage integrations accepted according to RC-07/08. Do not store private keys in the repo. Production activation is RC-11. |
| Operations | Fleet ingress hold, workers and scheduler/producers drained/stopped, deployment serialization, monitoring and recovery owner ready. GitHub concurrency serializes this workflow only; manual deployments must share the same operational lock. |
| SSH/workflow | Protected production environment/authorized reviewers and approved `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, `DEPLOY_SSH_KEY` provisioned privately. Current SSH uses `accept-new`; an independently authenticated expected host key and trustworthy known-host provisioning must be verified in RC-11 before first production connection. Do not mistake TOFU for host identity proof. |
| Backup | Verified primary pre-deploy archive, managed manifest and checksum; independent approved offsite readiness mandatory before go-live. Synchronous offsite abort follows `BACKUP_REQUIRE_OFFSITE`. Retain private file/key recovery material separately. |

RC-11 must resolve topology-specific commands, paths, runtime versions,
supervisor identities, worker drain/timeouts, scheduler control, TLS termination,
SSH host trust, storage capacity and actual production connectivity. No guessed
systemd/Supervisor/load-balancer command is part of this contract.

## RC-09 — Execution sequence

1. Open a deployment receipt using the authoritative fields in the backup
   runbook. Record approved target, full previous SHA, environment, operator,
   approver, CI evidence and UTC timestamp. Freeze competing deployments.
2. Establish the **external** ingress/writer hold and drain in-flight requests,
   all workers (including forced workers), scheduled producers and external
   writers across all hosts. This precedes backup so post-backup writes cannot
   silently be lost during recovery. Record last accepted-write boundary.
3. Record the previous runtime's DB identity and `php artisan migrate:status
   --no-interaction`. Compare the target's files with the actual ledger and
   approve every pending migration per RC-06. No migration is executed here.
4. Invoke the approved target's script from the application root:

   ```bash
   bash bin/deploy.sh --ref <EXACT_40_CHARACTER_SHA>
   ```

   The script itself must come from the approved target (the workflow streams
   its checked-out `bin/deploy.sh` to `bash -s` over SSH). An old server script
   must not silently be used as the entrypoint. Never use `git pull`,
   `git checkout main`, `git checkout latest` or a tag as deployment authority.

The actual script order is:

| Stage | Exact operation | Non-zero result |
| --- | --- | --- |
| Input/repository | Validate full SHA, clean `git status --porcelain=v1 --untracked-files=all`, fetch origin heads/tags, resolve target and previous commit; require target equality | No maintenance, checkout or DB migration; stop on previous release. Fetch may update Git refs, never application source. |
| Backup | `php artisan backup:database --purpose=pre-deploy` | Abort before maintenance/checkout/migration. The service verifies stored archive, manifest and checksum; required offsite failure also aborts. No separate verify invocation is hidden in this script. |
| Maintenance | `php artisan down --retry=60` | Keep external hold; inspect whether maintenance was established before reopening. |
| Checkout | `git checkout --detach "$target_commit"` | Keep hold/maintenance and inspect actual HEAD before recovery. |
| PHP dependencies | `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` | No migration issued; dependencies may be partial. Restore previous lockfile dependencies before any reopening. |
| Cache clear | `php artisan optimize:clear` | Stop, repair runtime/cache access under hold. |
| Preflight | `php artisan app:release-preflight --strict-production --require-android-push` | STOP; no migration, no traffic release, no bypass of invalid configuration. |
| Frontend | `npm ci --prefer-offline --no-audit`, then `npm run build` | Stop before DB migration. Build may be partial; rebuild matching previous assets for code recovery. |
| DB boundary | `php artisan migrate --force` | Treat resulting DB as unknown/partial until ledger and schema inspection proves otherwise. Never automatically checkout previous code. |
| Runtime | `php artisan optimize`, then `php artisan queue:restart` | DB may be fully migrated; keep external hold and use RC-10 classification. |
| Application | `php artisan up` | If failed, inspect actual maintenance state; keep hold. If successful, script exits zero but operator acceptance remains pending. |

`queue:restart` writes a restart timestamp to cache; it does not wait for workers,
drain them, stop their supervisor, or prove new processes have started. Laravel
maintenance normally rejects web/API requests with 503 and Retry-After 60, but
`/up` is exempt in Laravel's routing registration. Static files served directly
by the web server and other hosts are outside this middleware. In-flight work,
queue jobs and the scheduler are not an atomic part of `artisan down`.
Therefore retain the external fleet hold even after the script calls `up`.

The script prints full requested/resolved/previous SHAs and, on failure after
maintenance starts, the failing stage and migration state (`not-started`,
`started-inspect-ledger`, `completed`). A failed maintenance command is treated
conservatively; no trap attempts `up`, checkout, reverse migration or restore.
This status message is not a complete deployment receipt or smoke result.

## RC-09 — Operator smoke and reopening

Decision **A: explicit operator acceptance**. The deployment script does not
automatically smoke-test HTTP, authenticated sessions, DB connectivity or workers.
Existing PHPUnit smoke/readiness suites use isolated factories/seeders and must
never be run against production as an operational smoke command.

While the fleet hold is active, permit only approved operator access after
`artisan up`, then record these pass/fail results without response bodies,
member data, cookies or tokens:

| Probe | Required result and limits |
| --- | --- |
| HTTPS `/up` | 200, expected certificate/host and target serving node. This is Laravel boot liveness, not database/queue readiness, and can return during maintenance. |
| HTTPS `/login` | Login page renders, expected redirects and session cookie attributes. Credentials stay in the secure browser/session flow. |
| Existing admin session and `/dashboard` | Authorized existing synthetic/operator account reaches the dashboard; no forced role grants, account creation or business transaction. Login may update normal session/security metadata. |
| DB connection | Correct effective DB identity plus read-only `SELECT 1`, matching approved migration ledger before/after. Never substitute only a successful `/up`. |
| Critical API | Approved existing synthetic principal with `reports:read` calls `GET /api/monitoring/health`; require 200 and expected response shape. This endpoint reads payment counts; it does not prove worker/provider health. Record status/shape only. Also confirm anonymous access is denied. |
| Queue/scheduler | Actual new worker processes on every node, correct queue/cache connection, no old job executing, expected scheduler heartbeat/last-run record and failures within the accepted baseline. No finance/POS job is dispatched merely as a probe. |
| Migration/build | Deployed `git rev-parse HEAD` equals approved SHA; recorded applied set matches the reviewed set; no unexpected pending migration; assets resolve and no server error. |

`/monitoring/health` in the staff UI calls `Health::full()`, which also writes
and removes a temporary storage probe; do not describe it as strictly read-only.
The critical API above does not use that storage-writing method.

If any required probe fails, keep/re-establish maintenance and external hold,
classify using RC-10, and record failure. Only after all checks and release-owner
sign-off should the operator reopen traffic and resume producers/workers in the
approved environment-specific order, monitoring new failures. Unknown smoke
results are PENDING, never PASS. Receipt capture is an operator step.

## RC-09 local evidence

Initial shell/workflow command-contract gate: 21 tests, 175 assertions PASS on
Windows Git Bash with isolated SQLite fixtures. It executes the real deployment
script with replacement git/php/composer/npm commands, not a production host.
The first harness run exposed missing Git Bash PATH and an unavailable fixture
filesystem helper; both harness issues were corrected using installed tooling.
No live deployment, provider call, shared DB or real secrets were used.

RC-10 recovery matrix and integrated evidence follow in this same document.

RC-09 focused local gate: **78 tests / 364 assertions PASS**, including script,
preflight, backup creation/verification and production infrastructure tests;
Bash syntax, Composer strict validation, Pint and diff whitespace checks PASS.
Existing Windows backup-provenance subprocess diagnostics appeared on stderr;
the suites passed and authoritative Linux CI is still required.
**RC-09 local verdict: PASS**, with the listed host/topology checks pending RC-11.
Because this bundle changes the deployment script/workflow, the proposed source
candidate is **v1.0.0-rc.6** (no tag; no deployment/version secret changed).
