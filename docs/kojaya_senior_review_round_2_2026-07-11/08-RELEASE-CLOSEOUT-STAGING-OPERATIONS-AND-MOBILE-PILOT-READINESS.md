# Release Closeout, Staging Operations, and Mobile Pilot Readiness

## Document identity

- Document: 08
- Date: 17 July 2026
- Repository: johnd-creator/kojaya
- Companion mobile repository: johnd-creator/KojayaApp
- Baseline release: v0.1.0
- Baseline SHA: ad8bc3afc9b62f549e4f054e181ef9decbecb341
- Release classification: Internal Alpha / Cooperative Backend Baseline
- Target after this document: operationally verified v0.1.x and a scoped v0.2.0 internal beta pilot

## Repository lock

This document governs the Laravel backend repository:

- Target: johnd-creator/kojaya
- Default branch: main
- Android changes belong in johnd-creator/KojayaApp and must use a separate branch and PR.
- Do not mix backend and Android commits in one pull request.
- Do not push implementation directly to main.

## Why this document exists

Documents 01 through 05 closed the principal senior-review remediation areas for
security and correctness, payment and reservation state machines, PII encryption,
organization authorization and token metadata, audit, pagination, and API
contracts. Document 06 defines the CI and PR execution protocol. Document 07 is
the historical junior handoff.

Release v0.1.0 now provides a green internal-alpha backend baseline, but it does
not yet prove production operations or a real member journey across the Android
application and backend. This document defines the next controlled phase:

1. close stale release metadata and repository governance;
2. prove staging deployment, backup, restore, rollback, and observability;
3. validate one member-facing vertical slice end to end;
4. record residual security and Legacy ERP work without expanding scope into an
   ERP big-bang;
5. establish evidence required before v0.2.0 can be called an internal beta.

## Current verified baseline

At the time this document was created:

- pull request #10 was merged into main;
- tag v0.1.0 and main pointed to the same commit:
  ad8bc3afc9b62f549e4f054e181ef9decbecb341;
- the final pull-request CI run completed successfully;
- no open pull request or GitHub issue was found;
- the backend already contains member coffee multi-item checkout, member store
  catalog and checkout, payment intent status, inventory reservation,
  settlement, idempotency, and unified transaction history;
- v0.1.0 remains an internal alpha and not a complete production release.

This document must not reinterpret v0.1.0 as proof that the Legacy ERP, external
payment gateway, WhatsApp, FCM, PII retirement, or production recovery process is
ready.

## Known limitations carried into Document 08

The following remain explicit release risks:

- 42 Legacy ERP test files and approximately 161 test methods are outside the
  default PHPUnit release gate;
- five tests remain skipped;
- PII remains in staged dual-write mode;
- encrypted backfill, production verification, key rotation, and plaintext
  retirement are not complete;
- granular token ability cutover remains in the instrument phase;
- payment gateway production activation and live webhook validation are not
  complete;
- WhatsApp and FCM require credentials and live validation;
- production deployment, backup restore rehearsal, and rollback rehearsal have
  not been accepted as release evidence;
- Composer and NPM still have documented moderate or low residual advisories;
- repository release documentation contains stale statements that merge, tag,
  and release actions are still pending;
- historical remediation and release branches remain visible and require a
  deliberate branch-retention decision.

## Goals

Document 08 is complete only when it produces evidence for all of these goals:

1. Release identity and documentation reflect the actual v0.1.0 state.
2. Repository governance protects main and immutable release tags.
3. A staging deployment of an exact SHA succeeds using the approved deployment
   workflow.
4. Backup, restore, failure handling, and rollback are rehearsed using isolated
   data.
5. Queue, scheduler, audit, outbox, reconciliation, application errors, and
   infrastructure health can be observed.
6. A scoped Android member pilot completes one realistic journey against the
   backend without using admin-only POS permissions.
7. Known limitations are assigned to an owner, version target, and acceptance
   gate.
8. No live collection of member funds is enabled without an approved merchant
   account and required organizational documents.

## Non-goals

The following are outside this document:

- declaring the full Legacy ERP production-ready;
- implementing every ERP, HRM, payroll, procurement, and maintenance feature;
- activating production QRIS before merchant approval and organizational
  requirements are complete;
- retiring plaintext PII before backfill, verification, backup, rollback, and
  operator approval;
- removing legacy token fallback before telemetry and rotation evidence exist;
- redesigning unrelated web or mobile screens;
- changing API contract version 1.0.0 merely because the application version
  changes;
- performing destructive migration or seed commands against shared development,
  staging, or production data.

## Workstream A — Release closeout and repository governance

### A1. Correct release metadata

Review and update these files in a focused documentation PR:

- CHANGELOG.md
- docs/releases/v0.1.0-readiness.md
- docs/project.md
- docs/log.md, only if the project convention requires a chronological entry

Required corrections:

- record the final v0.1.0 SHA;
- distinguish completed merge and tag actions from remaining GitHub Release or
  operational actions;
- preserve the classification Internal Alpha / Not Production Release;
- remove stale statements that v0.1.0 is only pending when the tag already
  exists;
- keep application version 0.1.0 separate from API contract version 1.0.0;
- do not rewrite historical review evidence as if it described the current
  repository state.

### A2. GitHub Release verification

The GitHub Release for v0.1.0 is already published as a pre-release. Do not
create a new release or duplicate the existing one.

Verify that the existing release satisfies:

- the release points to the immutable tag v0.1.0 (peeled commit
  ad8bc3afc9b62f549e4f054e181ef9decbecb341);
- the pre-release classification matches Internal Alpha / Not Production Release;
- the release notes state the scope (Documents 01 through 05), test and CI
  evidence, known exclusions, and residual risks;
- the release is not marked as latest or stable.

Creating, editing, or publishing a GitHub Release is a separate external write
action and requires explicit user authorization at execution time.

### A3. Branch and tag protection

Recommended repository rules:

- require a pull request before changes reach main;
- restrict direct pushes to main;
- require the branch to be current where compatible with the workflow;
- block force pushes and branch deletion;
- protect v-prefixed tags from update or deletion;
- require the existing independent CI jobs:
  - Dependency Audit
  - Pint
  - Frontend Build
  - Generated Drift
  - PHPUnit Parallel
  - Migration and Seed
  - OpenAPI Drift
  - PostgreSQL Concurrency

Ruleset changes are repository administration actions and require explicit
authorization when applied.

### A4. Branch retention review

Classify every non-main branch as one of:

- merged and safe to delete;
- historical evidence to retain temporarily;
- active plan requiring rebase or replacement;
- backup branch requiring an explicit retention expiry;
- unknown and requiring owner confirmation.

At minimum:

- preserve main and the immutable v0.1.0 tag;
- treat already merged PR branches as deletion candidates after verification;
- do not delete plan/member-pos-cart until its useful decisions are moved into a
  current plan;
- do not assume a diverged branch contains missing production fixes merely
  because GitHub reports commits ahead of main;
- never bulk-delete branches without an exact reviewed target list.

## Workstream B — Staging environment and deployment proof

### B1. Environment inventory

Record the staging values or secret ownership for:

- APP_ENV
- APP_DEBUG
- APP_URL
- APP_VERSION
- API_CONTRACT_VERSION
- database host, database name, and restricted application user
- cache and queue drivers
- filesystem and backup disks
- log and audit retention
- Sanctum stateful domains and token expiration
- PII encryption and blind-index key versions
- PII rollout phase
- ability cutover phase and fallback expiry
- Google SSO enablement and redirect URI
- payment provider mode
- WhatsApp and FCM mode
- deployment host, user, path, and SSH key ownership

Do not commit secret values. Store only names, ownership, rotation status, and
validation evidence.

### B2. Exact-SHA staging deployment

Use the existing deployment workflow and an explicit 40-character commit SHA.

Required evidence:

- requested ref;
- resolved SHA;
- previous deployed SHA;
- migration output;
- cache rebuild result;
- application health response;
- maintenance mode exit state;
- deployment start and finish timestamps;
- operator identity.

Do not reuse a mutable branch name as the only deployment identity.

### B3. Staging smoke tests

Run smoke checks with synthetic accounts and isolated data:

1. web authentication and logout;
2. API token issue and profile retrieval;
3. member account linkage and active-member gate;
4. member dashboard;
5. savings and dues read endpoints;
6. loan list and calculator;
7. coffee menu;
8. coffee multi-item checkout using a non-production payment provider;
9. member store catalog and checkout;
10. payment intent status;
11. simulated paid webhook;
12. settlement to POS transaction;
13. stock reservation consumption or release;
14. unified transaction history;
15. notification and outbox delivery;
16. audit-log visibility and redaction;
17. cross-organization access rejection;
18. expired or inactive member rejection.

All identifiers in evidence must be synthetic or redacted.

### B4. Failure and maintenance-mode rehearsal

In staging, intentionally trigger a safe post-maintenance deployment failure.

Acceptance criteria:

- the application does not falsely report a successful deployment;
- maintenance state is known and visible;
- previous and target SHAs are recorded;
- the operator has an explicit recovery command;
- the application is not automatically brought online with an uncertain schema
  or partial artifact state;
- the incident is documented without using production member data.

## Workstream C — Backup, restore, and rollback

### C1. Backup proof

Produce a backup from staging or a sanitized production-like database.

Record:

- backup timestamp;
- database engine and version;
- source environment;
- storage destination;
- encryption status;
- checksum;
- retention policy;
- operator;
- restore command reference.

A backup file existing is not sufficient evidence. Restore must be tested.

### C2. Restore rehearsal

Restore into a separate disposable database and prove:

- schema and migration history are present;
- critical row counts are plausible;
- encrypted PII can be read using the expected key version;
- audit logs remain queryable;
- payment intent and POS relationships remain consistent;
- the application can boot against the restored database;
- the restored environment cannot call live payment or notification providers.

### C3. Application rollback rehearsal

Rehearse rollback from a staging deployment to a known previous SHA.

Required decisions:

- whether schema rollback is required or forbidden;
- whether the previous application version is forward-compatible with the
  migrated schema;
- how maintenance mode is handled;
- how queues and scheduled jobs are paused and resumed;
- how target and previous SHAs are recorded;
- who approves returning the application to service.

Do not use migration down commands for PII retirement or any migration that may
destroy the only valid data representation.

## Workstream D — Observability and incident readiness

At minimum, staging and production plans must cover:

- HTTP health and uptime;
- application exception rate;
- authentication and SSO failures;
- queue depth and failed jobs;
- scheduler heartbeat;
- notification outbox backlog;
- payment charge-creation recovery;
- expired reservation processing;
- paid-but-unsettled reconciliation incidents;
- database connection and storage capacity;
- audit log write failures;
- backup success and backup age;
- disk usage and log growth;
- deployment success or failure.

Define for every signal:

- source;
- threshold;
- notification recipient;
- severity;
- first response;
- escalation path;
- evidence retention.

Do not send PII, tokens, secrets, raw webhook payloads, or payment credentials in
alerts.

## Workstream E — Security and secret validation

### E1. Secret rotation checklist

Verify and record rotation status for:

- Google client secret previously identified as exposed;
- application key ownership and backup;
- PII encryption keys;
- PII blind-index keys;
- payment gateway credentials;
- WhatsApp access token;
- FCM credential;
- deployment SSH key;
- database credentials.

Never rotate the active APP_KEY or PII key without an approved migration and
recovery plan.

### E2. PII rollout gate

Document 08 may prepare evidence but must not retire plaintext.

Required before a later retirement decision:

- dry-run inventory;
- backfill checkpoint and resume proof;
- verification report;
- decrypt-failure count;
- blind-index coverage;
- backup and restore success;
- rollback rehearsal;
- key custody and rotation procedure;
- operator approval;
- a separately reviewed production change window.

### E3. Token ability cutover gate

Do not advance directly from instrument to remove.

Required sequence:

1. instrument;
2. classify legacy tokens;
3. measure active legacy usage;
4. rotate unsafe or unknown tokens;
5. set and communicate a bounded grace period;
6. deprecate fallback;
7. verify no required client still depends on legacy abilities;
8. remove fallback in a separately reviewed release.

## Workstream F — Android mobile pilot

### F1. Pilot objective

The first pilot must prove a narrow, useful member journey rather than the entire
Kojaya product:

1. member signs in;
2. member account is linked and active;
3. member sees profile and financial summary;
4. member views coffee or store catalog;
5. member adds multiple items to a local cart;
6. Android sends a stable client_reference;
7. backend recalculates price and reserves stock;
8. Android displays a pending payment state;
9. a non-production provider or controlled webhook marks the intent paid;
10. backend settles the intent;
11. Android resolves the settled resource;
12. member sees the transaction and notification.

### F2. Backend contract already available

Do not recreate admin POS endpoints for the member app.

The backend baseline already contains member-facing contracts for:

- coffee menu and multi-item order;
- store catalog and multi-item order;
- payment intent status;
- idempotent checkout;
- inventory reservation;
- settlement to POS transaction;
- unified member transaction history.

The pilot must use member abilities and active-member enforcement. It must not
grant pos:read or pos:write to ordinary members merely to reuse cashier routes.

### F3. Android scope

Android work belongs in a separate KojayaApp branch and PR.

Recommended first implementation:

- coffee cart state and customization-aware merge key;
- store cart state;
- stable client_reference per checkout attempt;
- pending payment screen;
- payment intent polling with bounded retry and cancellation;
- settled-resource navigation;
- stock, price-change, inactive-member, expired-intent, and offline error states;
- unit tests for payload mapping and cart merge behavior;
- API contract fixtures sourced from the backend response shape.

### F4. Payment gateway constraint

Production QRIS must not block development of the member journey.

Until merchant activation and organizational requirements such as NIB are
complete:

- use an internal, fake, sandbox, or controlled non-production provider;
- display a clear non-production indicator;
- prevent production fund collection;
- do not place real provider credentials in Android;
- keep provider secrets on the backend;
- retain webhook idempotency and settlement tests.

Live payment activation requires a separate approved checklist and change window.

## Workstream G — Legacy ERP and skipped-test recovery

Do not enable all quarantined Legacy ERP tests in one change.

Create an inventory with these columns:

- test file;
- module;
- reason excluded;
- current failure type;
- production risk;
- owner;
- target version;
- required fixture or architecture change;
- disposition: restore, rewrite, replace, or retire with approval.

Recovery order:

1. authorization and organization isolation;
2. payment, POS, inventory, and accounting;
3. payroll and employee money movement;
4. savings, dues, loans, and approvals;
5. exports, reports, procurement, and lower-risk administration.

Every restored test must execute the production surface named by the test and
must not be weakened merely to enter the default suite.

Resolve the five skipped tests individually. Each skip must have:

- a documented reason;
- an owner;
- an expiry or target version;
- an issue or task reference;
- evidence that it is not hiding a critical release path.

## Recommended PR sequence

### PR 08-A — Release metadata closeout

Allowed scope:

- CHANGELOG.md
- docs/releases/v0.1.0-readiness.md
- docs/project.md
- docs/log.md, if required

No production code.

### PR 08-B — Repository governance record

Documentation of rulesets, required checks, branch-retention decisions, and
release ownership. Applying GitHub administrative changes remains a separately
authorized action.

### PR 08-C — Staging preflight and smoke automation

Allowed scope should be declared before implementation. Prefer additive,
non-destructive checks and synthetic data.

### PR 08-D — Backup, restore, rollback runbook

Documentation and safe validation helpers only. No destructive production
execution in the PR.

### PR 08-E — Observability and reconciliation

Add only the monitoring or operator surfaces required by this document. Do not
bundle unrelated dashboards.

### PR 08-F — Android pilot

Separate repository: johnd-creator/KojayaApp.

The PR must point to the backend contract version and backend baseline SHA used
for validation.

### PR 08-G — Legacy test recovery wave 1

Restore only a bounded critical-module test group with focused fixes and
independent evidence.

## Evidence requirements

Every completed work item must record provenance as one of:

- executed by user;
- executed by model with explicit authorization;
- executed by GitHub Actions;
- executed by an identified operator in staging.

A claim such as deployment passed, backup succeeded, or mobile flow works is
rejected without:

- environment;
- exact SHA;
- command or workflow;
- timestamp;
- result;
- redacted artifact or log reference;
- operator or CI identity.

## Minimum acceptance checklist

Document 08 can be marked complete only when all required items are checked.

### Release

- [ ] v0.1.0 SHA is recorded consistently.
- [ ] Stale release-pending statements are corrected.
- [ ] GitHub Release state is verified.
- [ ] main and v-prefixed tag protection are reviewed.
- [ ] Branch-retention decisions are recorded.

### Staging operations

- [ ] Exact-SHA staging deployment succeeds.
- [ ] Staging smoke checklist passes.
- [ ] Safe failure rehearsal is completed.
- [ ] Backup is created and checksummed.
- [ ] Backup restore succeeds in isolation.
- [ ] Application rollback is rehearsed.
- [ ] Queue and scheduler operation are verified.
- [ ] Monitoring and escalation ownership are recorded.

### Security

- [ ] Exposed-secret rotation status is verified.
- [ ] PII key ownership and recovery are documented.
- [ ] PII remains dual-write until the separate retirement gate.
- [ ] Legacy token usage is measured before cutover.
- [ ] No secret or PII is stored in evidence.

### Mobile pilot

- [ ] Backend contract baseline SHA is recorded.
- [ ] Android uses member-facing routes.
- [ ] Coffee or store multi-item cart works.
- [ ] Stable client_reference prevents duplicate checkout.
- [ ] Pending payment state is handled correctly.
- [ ] Settlement resolves to a transaction or order.
- [ ] Inactive member and cross-account access are rejected.
- [ ] No real payment is collected without merchant approval.

### Technical debt

- [ ] Legacy ERP test inventory exists.
- [ ] Critical recovery wave is prioritized.
- [ ] Five skipped tests have owners and targets.
- [ ] Residual dependency advisories are rechecked.

## Versioning decision

Use patch releases for compatible stabilization:

- v0.1.1: release metadata, staging-discovered fixes, operational hardening, and
  compatible bug fixes;
- later v0.1.x: additional compatible reliability fixes.

Use v0.2.0 only when the scoped Android member journey is accepted as an
internal beta. API contract version remains 1.0.0 unless the public API contract
itself changes incompatibly.

Do not use v1.0.0 until:

- payment production validation is approved where payment is in scope;
- PII production rollout is accepted;
- backup and restore are proven;
- rollback and incident response are proven;
- critical quarantined test paths are in the release gate;
- a limited member pilot has completed with accepted results;
- owners accept the remaining operational and business risk.

## Stop conditions

Stop and request senior or business direction when:

- production data shape is unknown;
- a command may delete or overwrite shared data;
- an active encryption key is unavailable;
- rollback compatibility is unproven;
- payment provider legal or merchant status is incomplete;
- a test requires live member funds;
- Android needs an undocumented backend contract change;
- the pilot would require ordinary members to receive admin POS abilities;
- a branch deletion target has not been verified;
- a ruleset change could lock out the repository owner;
- evidence contains secrets or personal data;
- scope expands into unrelated ERP or HRM implementation.

## Completion handoff template

```markdown
# READY FOR SENIOR REVIEW — DOCUMENT 08

Repository:
Branch:
Base SHA:
Head SHA:
Pull request:
Environment:

## Workstream completed

## Exact scope

## Evidence

- user executed:
- model executed with authorization:
- GitHub Actions:
- staging operator:

## Deployment and rollback

## Backup and restore

## Security and secret status

## Mobile contract baseline

## Known limitations

## Files changed

## Decisions required

## Final recommendation

Choose exactly one:

- keep internal alpha;
- accept v0.1.x operational patch;
- begin limited v0.2.0 internal beta pilot;
- stop rollout and remediate listed blockers.
```
