# Document 04 — Organization Authorization and Token Cutover

Status: READY FOR INDEPENDENT REVIEW

## Implementation identity

- Repository: johnd-creator/kojaya
- Branch: remediation/document-04-organization-auth-token-cutover
- Starting SHA: 154bf24e8dd00fb331ec42c9287c7584951b2104
- Ending implementation SHA before this evidence commit: f5177486c2c3b528bd612956e206fd3c46000fd1

Document 05 was not started. Payment and reservation state machines were not
changed. PII crypto, rollout, backfill, and PII migrations were not changed.

## Current-state findings

The previous implementation had partial organization query scoping, but
support was inferred in query code and policies could treat an unresolved
organization as equivalent to a valid scope. Account linking was available
through a generic patch route and Google SSO could link an unlinked member by
email. Sanctum token purpose was inferred from abilities and new token
issuance did not persist an application profile. Legacy cooperative abilities
had no explicit cutover phase or expiry guard.

## Organization scope contract

OrganizationScopeService now owns an explicit registry of model class to
organization path. The registry covers:

- CooperativeMember — organization_id
- CooperativeDuesInvoice — member.organization_id
- CooperativePayment — member.organization_id
- CooperativeLedgerEntry — organization_id
- Loan — organization_id
- LoanInstallment — loan.organization_id
- LoanPayment — loan.organization_id
- LoanRestructure — loan.organization_id
- MemberResignationRequest — member.organization_id
- SavingsWithdrawal — member.organization_id
- RewardRedemption — member.organization_id
- PosMemberCreditPayment — member.organization_id
- Employee, User, and registered legacy ERP organization-owned models

The OrganizationScopedModel contract is available for models that need an
explicit path outside the static registry. Unknown models and broken paths
throw a domain exception; they never become globally visible.

OrganizationVisibility distinguishes global, organization, and denied. A
global result is granted only by the explicit view_cooperative_all permission.
A non-global user is scoped to one exact organization. A non-global user
without an organization is denied.

The centralized service is used by list, search, count, statistics, export,
pagination, direct-object policy checks, and account-link operations.

## Authorization parity

Direct-object policy checks now resolve organization ownership through the
central service. The affected parity fixes include cooperative payments, loan
restructures, savings withdrawals, member account actions, member queries,
loan queries, and related nested member paths.

Batch payment paths validate every requested record before writes and reject
mixed-organization payloads as one request. Existing business-state guards
remain in place.

## Explicit member account linking

Dedicated web and API actions are available:

- POST /cooperative/members/{member}/account-link
- DELETE /cooperative/members/{member}/account-link
- equivalent API v1 routes

The link service requires a target user and reason, then performs a
transactional lock and eligibility check:

- member and user have the same organization;
- both relationship sides are currently unlinked;
- target email is verified;
- target is not already linked to another cooperative member;
- target has no privileged, operational, approval, or cooperative-management
  role or permission;
- the actor has member-management permission and visibility to the member;
- Anggota assignment is idempotent;
- the audit record stores a controlled reason code and no free-form reason.

Generic member create/update actions do not accept account-link mutations.
Google SSO no longer creates or links an account for an existing unlinked
member based only on an email collision; it requires the explicit linking
flow. Unlink removes only the member relationship and member-specific role
when no other member relationship remains.

## Token application metadata

The additive personal access token migration adds:

- token_app
- token_version
- device_id
- issued_at

Existing Sanctum created_at and last_used_at fields remain authoritative for
creation and last-use timestamps. New issuance is centralized in
TokenIssuanceService, validates the enum values member, ess, technician, and
admin, and never issues wildcard abilities.

Profiles are isolated:

- member: profile and member abilities;
- ESS: profile, ESS, attendance, and payroll abilities;
- technician: profile and work-order abilities;
- admin: documented cooperative, POS, and report abilities only.

The default application selection is deterministic for existing clients:
member users select member, employee users select ess, and other users select
admin when no profile is supplied. An explicit supplied profile is validated.

Member lifecycle and account unlink revoke tokens with token_app=member.
ESS, technician, and admin tokens are preserved. Account-wide revocation is
a separate service operation and removes all tokens.

## Legacy token classification and cutover

tokens:classify-legacy is dry-run by default, uses bounded batches, emits
aggregate counts only, and never reports token material. Pure member, ESS,
technician, and known admin profiles are classified. Wildcard, combined,
empty, and unknown profiles are unsafe and require explicit rotation
confirmation after a grace deadline. Dry-run performs no mutation.

Ability cutover phases are configured with ABILITY_CUTOVER_PHASE:

1. instrument — legacy issuance/fallback remains temporarily available and
   usage is counted and audited without token secrets.
2. rotate — new tokens use granular profiles and legacy usage is bounded by
   the configured grace deadline.
3. deprecate — legacy requests receive Deprecation and optional Sunset
   headers while usage remains observable.
4. remove — legacy abilities are rejected.

Emergency fallback is disabled by default. It requires the explicit feature
flag and a valid future expiry timestamp. It does not grant wildcard access
or bypass organization authorization.

## Global permission matrix

The current role-permission contract assigns view_cooperative_all to:

- System Admin;
- Admin Pusat;
- Pengurus Koperasi, which is the current central cooperative role in the
  repository role model.

Manajer Koperasi, Admin Koperasi, Kasir Koperasi, branch operational roles,
finance, payroll, and HR roles do not receive global cooperative visibility by
default. Runtime scope logic checks the permission, not a role name.

The repository does not currently distinguish central and branch variants of
the Pengurus Koperasi role. That role-model limitation remains an operator
and independent-review consideration.

## Migration and rollback

Migration 2026_07_15_000001_add_application_metadata_to_personal_access_tokens
is additive, nullable for existing rows, indexed for tokenable/application
and token-version queries, and rollback-safe on a disposable SQLite database.
Its rollback removes only the four columns introduced by that migration and
preserves existing Sanctum columns.

No migration, seeder, or token classification was run against a persistent
development or production database.

## Focused verification

Formatting:

    ./vendor/bin/pint --dirty
    PASS — 39 files

New Document 04 tests:

    php artisan test --compact \
      tests/Feature/Authorization/OrganizationScopeContractTest.php \
      tests/Feature/Cooperative/MemberAccountLinkAuthorizationTest.php \
      tests/Feature/Cooperative/MemberP0SecurityClosureTest.php \
      tests/Feature/Security/GranularAbilityCutoverTest.php \
      tests/Feature/Security/LegacyTokenClassificationCommandTest.php \
      tests/Feature/Security/TokenAppMetadataTest.php \
      tests/Feature/Security/TokenMetadataMigrationTest.php \
      tests/Unit/Security/LegacyTokenClassifierTest.php

Result: 32 passed, 114 assertions.

Directly affected regression tests:

    php artisan test --compact \
      tests/Feature/Cooperative/OrganizationIsolationTest.php \
      tests/Feature/Cooperative/MemberUpdateCommandSeparationTest.php \
      tests/Feature/Cooperative/MemberP0SecurityClosureTest.php \
      tests/Feature/Auth/MemberTokenAbilityTest.php \
      tests/Feature/PhaseBContractApiTest.php \
      tests/Feature/LegacyErp/EmployeeScopeTest.php \
      tests/Feature/LegacyErp/InvoiceFlowTest.php \
      tests/Feature/Auth/Sso/GoogleSsoFlowTest.php \
      tests/Feature/Phase0MobileApiTest.php \
      tests/Feature/Member/MemberLifecycleTokenRevocationTest.php

Result: 128 passed, 831 assertions.

Wayfinder and frontend verification:

    php artisan wayfinder:generate
    PASS — generated output remains ignored

    npm run build
    PASS — Vite production build completed

## Intentionally skipped

- full PHPUnit suite;
- parallel coverage suite;
- PostgreSQL concurrency suite;
- production/shared-database migration;
- production token classification or revocation;
- shared database seeders;
- GitHub Actions monitoring;
- pull request creation and merge;
- Document 05.

## Known residual risks

- Full CI and PostgreSQL verification remains the responsibility of the
  independent GLM review.
- The current role model does not encode a separate central versus branch
  Pengurus Koperasi role.
- Legacy token inventory must be classified and rotated operationally before
  moving from instrument to later cutover phases.
- Direct operations outside the covered registry still require independent
  review when new organization-owned models are introduced.

## Operator rollout steps

1. Keep ABILITY_CUTOVER_PHASE=instrument while inventorying legacy tokens.
2. Run tokens:classify-legacy --dry-run --batch=<bounded-size> and retain
   only aggregate output.
3. Rotate safe profiles through the token rotation endpoint and explicitly
   review unsafe profiles.
4. Set a future grace deadline, then move to rotate and later deprecate.
5. Verify no supported client requires legacy abilities, configure emergency
   fallback only with a future expiry if necessary, and then move to remove.
6. Keep old token keys and metadata available until all legacy records have
   been rotated or revoked.
