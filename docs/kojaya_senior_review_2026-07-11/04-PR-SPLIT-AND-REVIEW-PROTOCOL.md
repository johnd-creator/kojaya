# PR Split and Review Protocol

Current PR is too large for reliable security review. Keep it as an integration/reference branch and extract focused PRs.

## Proposed split

### PR-A — CI and generated artifacts

Scope:

- Wayfinder generated files;
- OpenAPI generated snapshot;
- no business logic.

Gate:

- CI reaches and executes PHPUnit.

### PR-B — Member permission and lifecycle P0

Scope:

- role Anggota permission split;
- token ability filtering;
- active-member middleware;
- state transition service;
- member-token scoped revocation;
- web/API lifecycle tests.

Must include fixes for:

- points/rewards/payment charge bypass;
- resign/deactivate validation status;
- web session gate;
- activation parity.

### PR-C — Organization isolation P0/P1

Scope:

- organization query service;
- policy organization checks;
- scoped bindings or explicit resolver;
- web/API list/detail/action/batch isolation;
- negative cross-org tests.

Do not mix PII encryption or reservation changes here.

### PR-D — Member data contract and audit

Scope:

- API resources;
- Inertia DTO/allowlists;
- PII permission;
- full-view/export audit;
- audit contract centralization and redaction.

Masking may be merged here. Encryption remains separate.

### PR-E — Member-user provisioning safety

Scope:

- explicit linking flow;
- privileged-user guard;
- organization guard;
- audit;
- transactional rollback tests.

This deserves separate review because it affects account identity.

### PR-F — Store/coffee idempotency and reservation

Scope:

- client reference migration;
- reservation service;
- webhook consume/release;
- expiry worker;
- provider charge reuse rules;
- true concurrency tests.

### PR-G — PII encryption and blind index

Scope:

- schema;
- dual-write;
- backfill command;
- reads/search migration;
- key rotation docs;
- rollback plan.

Do not combine with unrelated authorization changes.

### PR-H — Pagination hardening

Scope:

- centralized page-size resolver;
- all APIs;
- architecture tests.

## Review checklist per PR

### Scope

- one security concern;
- no unrelated refactor;
- generated artifacts separated when practical;
- migration documented.

### Authorization

- route middleware;
- FormRequest authorization;
- policy;
- domain service invariant;
- query scope;
- negative tests.

### Data contract

- no raw model serialization for sensitive domain;
- explicit resource/DTO;
- organization scope;
- PII rules.

### Transaction/concurrency

- transaction boundary explicit;
- locks in correct order;
- idempotency key unique;
- retries safe;
- catch only expected DB exception.

### Audit

- successful and denied action where appropriate;
- no plaintext PII/secrets;
- correlation ID;
- actor/organization.

### CI

- full workflow green;
- no skipped test due earlier step;
- Wayfinder/OpenAPI drift zero.

## Senior sign-off states

Use these labels in review notes:

- `BLOCKED-P0`: exploitable access/lifecycle/data isolation issue.
- `CHANGES-P1`: important correctness/security issue before merge.
- `P2-PARTIAL`: improvement valid, roadmap not closed.
- `READY-FOR-SENIOR-REVIEW`: author asserts all acceptance criteria met.
- `APPROVED-SCOPE`: current PR may merge; does not imply entire roadmap complete.
- `ROADMAP-CLOSED`: only after all P0–P2 completion criteria pass.

## Recommended current status

For PR #2:

```text
BLOCKED-P0
P2-PARTIAL
KEEP-DRAFT
DO-NOT-MERGE-AS-ONE
```
