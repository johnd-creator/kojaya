# Document 05 — Final Remediation Evidence

## Implementation commit

764ad3a2 — fix(audit,pagination): atomic lifecycle revocation and OpenAPI page-size parity

## Finding 1 — Lifecycle and token revocation not atomic

### Root cause

`MemberStatusTransitionService::applyTransition()` wrapped status change, role mutation,
and lifecycle audit in `DB::transaction()`, but token revocation was deferred via
`MemberAccessRevocationService::revokeAfterCommit()` which calls `DB::afterCommit()`.
If the mandatory revocation audit failed after the outer transaction committed, the result
was: member status changed, role removed, but tokens still active and exception thrown
post-commit.

### Solution

Replaced both `revokeAfterCommit()` calls (in `applyTransition` and `deleteAccess`)
with `revokeFor()`. Since `revokeFor()` uses `DB::transaction()` internally, it becomes
a nested transaction (savepoint) within the lifecycle transaction. Any failure in token
deletion or revocation audit rolls back the savepoint, and the propagated exception
causes the outer transaction to roll back status, role, lifecycle audit, token deletion,
and revocation audit atomically.

### Verification commands and results

```
php artisan test --compact tests/Feature/Member/MemberLifecycleTokenRevocationTest.php
Tests: 13 passed (60 assertions)
```

New tests added:
- `test_mandatory_revocation_audit_failure_during_lifecycle_rolls_back_everything`
  — mocks revocation audit to fail during deactivate; asserts exception propagates,
    status/validation_status unchanged, Anggota role preserved, token survives, no
    partial revocation audit.
- `test_lifecycle_happy_path_revokes_only_member_tokens_preserving_ess_and_technician`
  — happy path through MemberStatusTransitionService::deactivate; asserts member token
    revoked, ESS and technician tokens preserved, status changed.

## Finding 2 — OpenAPI pagination contract mismatch

### Root cause

`OpenApiGenerator::paginationParameter()` used `str_starts_with($uri, 'api/v1/dues/') ? 100 : 50`
for the per_page maximum, and `ApiPaginationMeta` schema hardcoded `maximum: 100`. The
controller uses `apiPageSize($request)` with default maximum 50 (from
`PaginationLimitResolver::MAXIMUM`). Since the endpoint is not admin-only (accessible
to members with `view_cooperative_member` permission), the documented maximum 100 was
incorrect.

### Solution

- Changed `OpenApiGenerator::paginationParameter()` to always return `maximum: 50`.
- Changed `ApiPaginationMeta.per_page.maximum` from 100 to 50.
- Updated `PhaseBContractApiTest` assertion from 100 to 50.
- Added runtime tests proving per_page=51/999999 clamp to 50, non-numeric uses default
  15, and 0/negative clamp to minimum 1.

### Verification commands and results

```
php artisan test --compact tests/Feature/PhaseBContractApiTest.php
Tests: 32 passed (476 assertions)
```

## Full test suite

```
vendor/bin/pint --test
PASS — 1182 files

php artisan test --compact
Tests: 5 skipped, 1163 passed (6539 assertions)
```

## Changed files

- app/Services/Cooperative/MemberStatusTransitionService.php
- app/Services/OpenApi/OpenApiGenerator.php
- docs/openapi.json (generated output)
- tests/Feature/Member/MemberLifecycleTokenRevocationTest.php
- tests/Feature/PhaseBContractApiTest.php

## Residual risk

None identified. Both findings are fully resolved with runtime tests proving the
correct behavior. The `revokeAfterCommit()` method is retained in
`MemberAccessRevocationService` for potential future use cases that genuinely need
post-commit deferral, but it is no longer used for mandatory lifecycle revocation.
