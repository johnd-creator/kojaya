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
