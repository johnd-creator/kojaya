# Document 05 — Audit, Pagination, and Contract Remediation Evidence

Status: READY FOR INDEPENDENT REVIEW

## Scope and SHA provenance

- Repository: johnd-creator/kojaya
- Branch: remediation/document-05-audit-pagination-contract-tests
- Remote branch SHA before this remediation: `892b5058a3ddcb1a58f4d1ef792d01bcd73c2442`
- Local implementation baseline before this remediation: `7e01aee722fb9a86a3fef9d300bcab4644790b3b`
- Ending implementation SHA before this evidence commit: `9d5a18b4`
- Evidence/documentation commit SHA: recorded by the final documentation commit and reported separately

The implementation baseline includes the earlier Document 05 fixes for
transactional lifecycle revocation, OpenAPI pagination parity, and runtime
pagination boundaries. No Document 06 work was started.

## Findings resolved

### D05-AUD-ROLE — Transactional privileged role mutation

User creation and role/profile updates continue through
`UserRoleManagementService`. The authoritative role mutation audit is written
inside the same transaction as user creation or update, organization and
password changes, and role replacement. Audit metadata contains actor and
organization context, affected user ID, previous roles, resulting roles, and a
controlled operation code. Passwords, hashes, tokens, secrets, and unrestricted
request payloads are excluded. Audit failure propagates and rolls back the
mutation; it is not retried through the same sink.

### D05-AUD-WRITEOFF — Truthful atomic loan write-off

`LoanService::writeOff()` locks and validates the loan, updates status and
notes, creates the approval record, and writes `loan.writeoff.completed` in one
transaction. A mandatory audit failure rolls back the loan and approval record
and does not dispatch a notification. Invalid source states throw and may emit
only a truthful failed event; they never emit completed for an unchanged loan.

### D05-CONTRACT-PII and D05-UI-MEMBER — Mask-safe member updates

Sensitive update requests reject mask-shaped identity, tax, and bank-account
values. Omitted values preserve the encrypted data; explicit authorized null is
the dedicated clear contract; generic member update remains unable to mutate
PII. Web and API tests inspect persisted encrypted columns and cover
cross-organization denial. The Members index quick-edit now submits only the
generic profile fields allowed by its request contract. Lifecycle, account
linking, opening balance, password, and sensitive data remain dedicated flows.

### D05-AUD-CONTEXT — Explicit machine-operation audit context

`AuditContext` now validates controlled sources and provides explicit HTTP,
webhook, queue, CLI, scheduler, and system factories. Correlation IDs are
accepted only when valid UUIDs and otherwise generated. Production paths now
thread one context through payment webhook/state/settlement operations,
reservation expiry, PII commands, account linking, lifecycle revocation, and a
real POS report queue job. Machine actors remain null and organization context
is derived from the affected subject when available.

Flat metadata from affected callers is normalized into redacted `new_values`
so gateway status, reservation status, settlement status, incident type, and
manual-resolution flags are not silently discarded. Sensitive keys are
redacted recursively, including case and nesting variants of identity number,
NIK, NPWP, bank account data, token, authorization, secret, password, QR data,
gateway payload, ciphertext, and blind index. Safe operational sibling fields
remain available.

### D05-PAGINATION-ARCH — Repository-wide pagination guard

The architecture test now scans PHP application code under `app/`, not only
controllers. It rejects request-derived `per_page`, `page_size`, and
pagination-style `limit` values passed directly to `paginate`,
`simplePaginate`, `cursorPaginate`, `limit`, or `take`, including common request
access methods and intermediate variables. Centralized resolver usage remains
allowed. Runtime boundary tests remain in place for normal, malformed,
negative, zero, oversized, array, notification, and project-finance inputs.

### D05-TEST-HARDENING — Persisted rollback evidence

Lifecycle failure tests now use a partial revocation double so the real
lifecycle audit is inserted inside the outer transaction before the mandatory
revocation audit fails. Assertions prove that the member mutation, role/token
effects, lifecycle audit row, and revocation audit all roll back. Equivalent
coverage exists for transition/deactivation and `deleteAccess()`.

### D05-REDACTION-TESTS — Complete redaction matrix

`AuditContextSourceTest` exercises nested and case-variant sensitive keys for
identity number, NIK, NPWP, bank account/holder, token, authorization, secret,
password, QR data, gateway payload, ciphertext, and blind index. It verifies
sentinels are absent from serialized audit values while safe operational
metadata remains present.

## Runtime test coverage

Runtime tests exercise actual routes, services, commands, queue handling, and
generated artifacts rather than relying on source-string assertions. The
remaining static guard is intentionally limited to prohibiting raw
request-derived pagination.

Latest focused remediation run:

    php artisan test --compact tests/Feature/Security/AuditContextSourceTest.php tests/Feature/Member/MemberLifecycleTokenRevocationTest.php tests/Feature/Security/LoanWriteOffAuditLifecycleTest.php tests/Feature/Cooperative/SensitiveDataMaskPreventionTest.php tests/Feature/Document05AuditPaginationContractTest.php

Result: 51 passed, 191 assertions.

Additional focused regression run:

    php artisan test --compact tests/Feature/Member/MemberLifecycleTokenRevocationTest.php tests/Feature/Cooperative/MemberUpdateCommandSeparationTest.php tests/Feature/Document05AuditPaginationContractTest.php tests/Feature/PhaseBContractApiTest.php tests/Feature/Security/AuditContextSourceTest.php tests/Feature/Security/LoanWriteOffAuditLifecycleTest.php tests/Feature/Security/PrivilegedRoleMutationAuditTest.php tests/Feature/Cooperative/SensitiveDataMaskPreventionTest.php tests/Feature/ApiPaginationHardeningTest.php tests/Feature/LegacyErp/ProjectFinanceTest.php

Result: 106 passed, 759 assertions.

Queue compatibility regression run:

    php artisan test --compact tests/Feature/Cooperative/Plan07BackgroundJobExportTest.php tests/Feature/Security/AuditContextSourceTest.php

Result: 28 passed, 105 assertions.

The complete compact suite was executed after the queue compatibility fix. The
verified result supplied for this handoff was:

    php artisan test --compact

Result: 1,204 passed, 5 skipped, 6,701 assertions.

No failures were reported in that final run.

## Other verification

- `./vendor/bin/pint --dirty --format agent`: passed.
- `php artisan openapi:snapshot --check`: passed; snapshot is up to date.
- `php artisan wayfinder:generate`: passed; generated output remained ignored.
- `npm run build`: passed; 3,847 modules transformed.
- `git diff --check`: passed.
- PHP syntax checks on every modified PHP file: passed.

Migration safety evidence uses PHPUnit's disposable SQLite configuration
(`APP_ENV=testing`, `DB_DATABASE=:memory:`). No migration was added or changed
by this remediation. No shared or production database was migrated, reset, or
seeded.

## Residual risks and handoff

- Independent PostgreSQL and GitHub Actions verification remains required.
- The compatibility `member.pii.exported` event remains best effort and is not
  the authoritative export completion record.
- Unrelated legacy ERP endpoints remain outside this remediation's response
  contract scope.
- The pre-existing untracked Document 04 authority plan was preserved and was
  not included in this branch's commits.

Document 05 is READY FOR INDEPENDENT REVIEW. No PR or merge was created, and CI
was not monitored by this task.

## Document 05 remediation continuation — July 16, 2026

- Starting SHA: `fd01714f8a3b1b1883536c3a3841c33f4f73565a`.
- Ending implementation SHA: `6d4b0688` (`test(document-05): harden audit rollback and pagination contracts`).
- Implementation commits: `01e7efc1` and `6d4b0688`.
- Main areas changed: `AuditLogService`, privileged user mutation services/controllers, member account link/unlink lifecycle services, sensitive member update request, audit/pagination/security regression tests, and compliance pagination portability.
- Findings addressed: fail-closed audit change keys and expanded recursive redaction; truthful actor and organization audit context; transactional user deletion and account link/unlink completion events; real rollback-path lifecycle tests; pagination architecture and runtime boundary coverage; and explicit sensitive-data clearing semantics.
- Focused verification: 166 passed, 1,058 assertions.
- Full verification: 1,237 passed, 5 skipped, 6,796 assertions.
- Commands run: focused Document 05 test matrix, `php artisan test --compact`, `./vendor/bin/pint --dirty --format agent`, `php artisan openapi:snapshot --check`, `php artisan wayfinder:generate`, `npm run build`, `git diff --check`, and PHP syntax checks for modified files.
- Residual risk: the five pre-existing skipped tests and independent PostgreSQL/CI verification remain outside this local run. GitHub Actions status was not observed.
- The unrelated untracked Document 04 authority plan was preserved and excluded from all commits.

## Document 05 senior review round 2 closure — July 16, 2026 (round 3)

- Starting SHA: `4bb2de37` (`docs(audit): record Document 05 remediation continuation`).
- Scope: close six remaining senior review gaps — PostgreSQL compliance query portability, single AuditContext for member lifecycle, deleteAccess rollback proof, atomic cooperative member user provisioning, truthful actor identity in `logAuth()`, and strengthened role mutation contract tests.
- Key changes:
  - `ComplianceReportController`: HAVING now uses real aggregate expressions (`COUNT(DISTINCT ec.id)`, `MAX(mc.next_checkup_date)`) instead of SELECT aliases; removed the unreachable `next_mcu_date < now()` condition that was impossible given the future-only LEFT JOIN.
  - `MemberStatusTransitionService` / `MemberAccessRevocationService`: one `AuditContext` is created at the operation boundary and threaded through lifecycle audit, `revokeFor()`, and token deletion, so a single operation shares one correlation ID, actor, and organization even without `X-Correlation-ID`.
  - `CooperativeMemberUserProvisioningService`: user creation, Anggota role assignment, member link, and the mandatory completed audit are now wrapped in a single transaction with one shared context.
  - `AuditLogService::logAuth()`: actor identity is now truthful — anonymous context rebuilds from the real affected user; a pre-existing differing actor is preserved with the affected user recorded as subject; unknown users produce a null actor with no fabricated roles/organization (foreign-key safe).
  - Role mutation and lifecycle rollback tests rewritten to integration-style with narrow partial mocks and explicit post-exception assertions.

### Test evidence (executed manually by user; model did not execute these commands)

The model did not run any automated test, formatter, or validation command. The following commands were executed manually by the user and the results reported back:

    php artisan test --compact tests/Feature/ComplianceReportQueryTest.php
    Result: 4 passed, 19 assertions.

    php artisan test --compact tests/Feature/Cooperative/CooperativeMemberUserProvisioningTest.php
    Result: 2 passed, 8 assertions.

    php artisan test --compact tests/Feature/Member/MemberLifecycleTokenRevocationTest.php
    Result: 17 passed, 65 assertions.

    php artisan test --compact tests/Feature/Security/PrivilegedRoleMutationAuditTest.php
    Result: 9 passed, 45 assertions.

An initial run surfaced eight failures (foreign-key constraint on unknown user_id, missing RolePermissionSeeder seed, and invalid third-argument messages passed to `assertDatabaseHas`/`assertDatabaseMissing`). These were corrected and the user confirmed all four focused suites above pass after the fixes.

The full compact suite (`php artisan test --compact`) was not re-run by the user for this round; the focused suites above are the verified evidence. Independent PostgreSQL and GitHub Actions verification remains required.

## Document 05 senior review round 2 closure — July 16, 2026 (round 4)

- Starting SHA: `bfbad9eaaaebc07d8cb2b03532abb2f0bfaa559a` (`fix(document-05): close remaining audit and pagination review gaps`).
- Scope: close four remaining senior review gaps — PostgreSQL-safe auth user lookup in `logAuth()`, truthful `delete_access` reason code, audited role reconciliation in member user provisioning, and correction of transaction documentation on the revocation service.
- Key changes:
  - `AuditLogService::logAuth()` / `buildContextFromAffectedUser()` / `resolveAuthActorAndSubject()`: added `isValidUserIdentifier()` guard so nonnumeric, empty, negative, or decimal identifiers never reach the bigint primary-key lookup (PostgreSQL-safe). Invalid identifiers produce a null actor with preserved correlation ID and request metadata. Extracted `anonymousContext()` helper to avoid duplication.
  - `MemberAccessRevocationService`: added `delete_access` to `CONTROLLED_REASONS` so the revocation audit preserves the truthful reason code instead of normalizing it to `member_lifecycle`.
  - `CooperativeMemberUserProvisioningService::provision()`: added state-change tracking (`userCreated`, `roleAssigned`, `linkChanged`). Link change writes `member.account.link.completed`; role-only reconciliation writes `member.role.reconciled`; no-op writes no audit. Metadata allowlisted: `affected_user_id`, `role_assigned`, `link_changed`, `user_created`, `operation`.
  - `MemberAccessRevocationService` docblocks rewritten to document the two invocation modes (atomic nested transaction vs explicit post-commit) and clarify that `revokeAfterCommit()` is not for atomic lifecycle operations.

### Test evidence (executed manually by user; model did not execute these commands)

The model did not run any automated test, formatter, or validation command. The following commands were executed manually by the user and the results reported back:

    php artisan test --compact tests/Feature/Auth/AuthAuditActorIdentityTest.php
    Result: 12 passed, 40 assertions.

    php artisan test --compact tests/Feature/Member/MemberLifecycleTokenRevocationTest.php
    Result: 18 passed, 66 assertions.

    php artisan test --compact tests/Feature/ComplianceReportQueryTest.php
    Result: 4 passed, 19 assertions.

    php artisan test --compact tests/Feature/Security/PrivilegedRoleMutationAuditTest.php
    Result: 9 passed, 45 assertions.

    php artisan test --compact tests/Feature/Cooperative/CooperativeMemberUserProvisioningTest.php
    Result: 6 passed, 35 assertions.

    php artisan test --compact
    Result: 5 skipped, 1264 passed, 6917 assertions.

The full compact suite was run by the user and reported 1264 passed, 5 skipped (pre-existing skips), 6917 assertions, with no failures. PostgreSQL and GitHub Actions verification remains required and was not run.

## Document 05 final remediation verification — July 16, 2026 (round 5)

- Starting SHA: `27c70674396453376c5cd7bedd8cc93337989ad5`.
- Ending implementation SHA: `db96cb142a3ee65483e52efbe61d27f8c40af11a` (`fix(document-05): bound auth identifiers and canonicalize account linking`).
- Account-linking decision: `CooperativeMemberUserProvisioningService` and its dedicated test were removed. Exhaustive repository search found no production caller, container binding, queued job, listener, observer, command, seeder, or factory using the service. Cooperative web/API callers and the contract tests use canonical `MemberAccountLinkService`. The separate SSO `MemberAccountLinkingService` remains the OAuth `social_accounts` path and is not a cooperative-member account-linking implementation.
- PostgreSQL BIGINT remediation: `AuditLogService::logAuth()` now accepts only positive integers or decimal strings that normalize to a positive value within `9223372036854775807`, rejects whitespace and all-zero/oversized forms without casting, and preserves truthful anonymous audit context. Invalid identifiers are proven not to issue a `SELECT` against `users`; valid identifiers are proven to perform the lookup.
- Test commands executed manually by the user:

      php artisan test --compact tests/Feature/Auth/AuthAuditActorIdentityTest.php
      Result: 27 passed, 175 assertions.

      php artisan test --compact tests/Feature/Cooperative/MemberAccountLinkAuthorizationTest.php
      Result: 13 passed, 88 assertions.

      php artisan test --compact tests/Feature/Document05AuditPaginationContractTest.php
      Result: 13 passed, 22 assertions.

      php artisan test --compact
      Result: 5 skipped, 1274 passed, 7021 assertions.

- The model did not run automated tests, formatter, build, generator, or other validation; all results above were reported by the user after manual execution.
- PostgreSQL status: not independently executed for this remediation. The repository PHPUnit configuration uses SQLite `:memory:`; no dedicated PostgreSQL test configuration or credentials were supplied for this run.
- GitHub Actions status: not observed or executed.
- Residual risk: the five pre-existing skipped tests and independent PostgreSQL/GitHub Actions verification remain outstanding. No double-role or role-policy expansion was introduced.

## Document 05 CI closure — July 16, 2026 (round 6)

- Starting SHA: `b93fd109973a27e578c1e3a760d32c70fdf75fd8` (`docs(document-05): record final remediation verification`).
- Scope: enforce PostgreSQL contract coverage in CI so Document 05 fixes can no longer silently fall back to SQLite.

### Root cause

The `postgres-concurrency` CI job ran its second command via `php artisan test --compact`, which reads the default `phpunit.xml`. That file forces `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` with `force="true"`, silently overriding the PostgreSQL environment provided by the workflow. As a result, `PosTransactionConcurrencyTest` and `MemberLifecycleConcurrencyTest` actually ran on SQLite, not PostgreSQL. Additionally, the Document 05 PostgreSQL-specific fixes (`AuthAuditActorIdentityTest`, `ComplianceReportQueryTest`, etc.) were never executed against PostgreSQL by CI.

### Changes

- `phpunit.pgsql.xml`:
  - Renamed `Concurrency` suite to `PostgreSQLConcurrency` containing `PaymentConcurrencyTest` and `MemberLifecycleConcurrencyTest` (both architecturally PostgreSQL-only). `PosTransactionConcurrencyTest` was excluded because it is hardcoded to SQLite subprocess workers.
  - Added new `Document05PostgreSQL` suite: `AuthAuditActorIdentityTest`, `ComplianceReportQueryTest`, `MemberAccountLinkAuthorizationTest`, `MemberLifecycleTokenRevocationTest`, `PrivilegedRoleMutationAuditTest`.
  - Added 7 PII encryption env keys that were previously missing (required for tests that create users/members/employees with PII columns).
- `.github/workflows/ci.yml`:
  - Both PostgreSQL commands now use `vendor/bin/phpunit --configuration phpunit.pgsql.xml --testsuite <name>` explicitly — no `php artisan test` that can fall back to SQLite.
  - Both suites are mandatory (no `continue-on-error`, no `|| true`).
  - Triggers, PostgreSQL service, and credentials unchanged.

### Test evidence

The implementation model executed the `Document05PostgreSQL` suite against a disposable local PostgreSQL database using environment-specific credentials (credential values are intentionally omitted) at the explicit request of the user:

    vendor/bin/phpunit --configuration phpunit.pgsql.xml --testsuite Document05PostgreSQL
    Result: 71 passed, 393 assertions.

The model also attempted the `PostgreSQLConcurrency` suite, but it failed because `phpunit.pgsql.xml` forces a CI-specific database role with `force="true"` that is not available on the local PostgreSQL instance. This is a local environment mismatch, not a code defect; the suite is designed for the CI PostgreSQL service. The `PostgreSQLConcurrency` suite was not validated locally.

The user executed the default SQLite regression suite:

    php artisan test --compact
    Result: 5 skipped, 1274 passed, 7021 assertions.

GitHub Actions has not been run yet; it will execute both PostgreSQL suites when a PR is created against `main`. GitHub Actions through the Draft PR is the authoritative verification for the `PostgreSQLConcurrency` suite.

### Residual risk

- GitHub Actions verification of both PostgreSQL suites is pending until a PR is created.
- The `PostgreSQLConcurrency` suite requires a CI-specific PostgreSQL role; it could not be verified locally and awaits GitHub Actions execution.
