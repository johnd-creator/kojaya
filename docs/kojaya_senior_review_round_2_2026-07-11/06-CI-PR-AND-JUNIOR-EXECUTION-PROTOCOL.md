# CI, PR, and Junior Execution Protocol

## Repository lock

```text
Target: johnd-creator/kojaya
Forbidden: johnd-creator/KojayaApp
```

## Release-prep verification and provenance policy

For release-preparation tasks, the implementation model does not run automated
tests, formatters, frontend builds, generators, migrations, seeders, or the full
quality gate by default. The model must provide exact verification commands for
the user to run manually.

The model may run a test or another prohibited verification command only when the
user gives explicit authorization in the active conversation. Every reported
result must identify its provenance:

- executed by user;
- executed by model with explicit authorization; or
- executed by GitHub Actions.

The model must not claim a test passed without corresponding user or CI evidence.
After the user reports manual verification, the model may inspect the final diff,
commit and push the scoped changes when authorized by the task, then stop for
senior review. It must not create a PR, merge, tag, or GitHub Release unless the
senior explicitly requests that separate action.

Sebelum kerja:

```bash
git remote -v
git branch --show-current
git rev-parse HEAD
```

Jika bukan backend, berhenti.

## PR #2 policy

PR #2 tetap integration/reference branch. Jangan merge apa adanya dan jangan menambah seluruh remediasi ke commit raksasa yang sama.

## Required PR split

### PR-1 — CI/generated only

- Wayfinder generated files;
- OpenAPI snapshot bila perlu;
- no business logic;
- second generator run zero diff.

### PR-2 — Member update/lifecycle P0

- request classes;
- member controllers;
- lifecycle service;
- PII-write permission;
- status preflight/backfill;
- focused tests.

### PR-3 — Organization/account linking/token metadata

- explicit scope contract;
- policies/export;
- account linking;
- token app identity.

### PR-4 — Payment/reservation state machine

Only payment-plan files.

### PR-5 — PII rollout

Only crypto/schema/backfill/verification/deployment docs.

### PR-6 — Audit/pagination/contracts

Only audit context/outbox, pagination sweep, contract tests.

### PR-7 — Granular ability final cutover

Only after telemetry and token rotation evidence.

## Per-task prompt contract

Every task states:

```text
Goal
Allowed files
Forbidden files
Required invariants
Required tests
Commands
Expected diff
Decisions not to revisit
```

Junior must not:

- add UI/features;
- refactor unrelated architecture;
- touch Android;
- weaken CI;
- delete/weaken tests;
- bulk format unrelated files;
- mark roadmap complete.

## Execution loop

1. Read one plan.
2. Restate scope in <=10 lines.
3. Inspect relevant files only.
4. Add negative tests with implementation.
5. Run focused tests.
6. Run full quality gate.
7. Inspect generated diff.
8. Commit/push.
9. Report SHA and raw command summary.
10. Stop for senior review.

Do not begin next wave before review.

## Command evidence

Required:

```text
vendor/bin/pint --test
npm run build
php artisan wayfinder:generate
git diff --exit-code resources/js/actions resources/js/routes
php artisan test --compact --parallel
bin/openapi.sh check
php artisan migrate:fresh --seed
git status --short
```

Concurrency PR also needs PostgreSQL/MySQL concurrency CI.

“All tests pass” without command/count is rejected.

## CI job design

Split independently visible required jobs:

```text
style
frontend-build
generated-drift
unit-feature-tests
migration-seed
openapi-drift
postgres-concurrency
```

No `continue-on-error` on required jobs.

## PR template

```markdown
## Scope
## Security invariants
## Files intentionally not changed
## Migrations
## Tests added
## Commands executed
## Known limitations
## Rollback
## Senior checklist
```

Do not use “final/complete” before senior approval.

## Commit rules

- one concern per commit;
- generated artifacts separate;
- no “misc fixes”;
- no unrelated formatting.

Prefixes:

```text
fix(auth):
fix(member):
fix(payment):
security(pii):
test(security):
chore(generated):
docs(deploy):
```

## Stop conditions

Stop/report when:

- business rule ambiguous;
- production data shape unknown;
- key decision missing;
- provider idempotency unknown;
- migration may delete data;
- true concurrency DB unavailable;
- fix exceeds allowed domain;
- generator nondeterministic.

## Handoff template

```markdown
# READY FOR SENIOR REVIEW

Repository:
Branch:
Base SHA:
Head SHA:
PR:

## Plan item completed
## Invariants proven
## Tests
- focused:
- full:
- concurrency:
- migration:

## Generated artifacts
## Migrations and rollback
## Known gaps
## Files changed
```
