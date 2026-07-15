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

## Independent Review Remediation

Independent review verdict: REQUEST CHANGES. This remediation was limited to
Document 04. No pull request was created, no merge was performed, and
Document 05 was not started.

### Remediation identity

- Actual remediation starting SHA: d8692fb843137f7a1811730a1f0102271f684e66
- Ending implementation SHA before this evidence commit:
  70c4e3bec0ca6b9c2353bbe13d3b12446d7269b4
- Branch: remediation/document-04-organization-auth-token-cutover

### Gaps addressed

1. `OrganizationScopeService` now validates the explicit model contract and
   the complete relationship path before checking global visibility. Unknown
   models, broken paths, and null model relationships fail closed for scoped
   and global actors.
2. Global visibility is mapped explicitly per model. Ordinary `manage_*`
   permissions no longer create global visibility, and unrelated models do
   not inherit `view_cooperative_all` implicitly.
3. `HasOrganizationScope`, legacy session selection, and resignation list
   scoping delegate to the central service. Guests, null-organization actors,
   invalid organization IDs, and cross-organization session selections are
   denied; only an explicit global permission may select another organization.
4. Web and API member creation use the actor's exact organization. A
   non-global actor without an organization is rejected before the head-office
   resolver can run. Client `organization_id` remains prohibited.
5. Member lifecycle revocation selects the union of explicit `member` tokens
   and legacy tokens classified exactly as the member profile, independent of
   cutover phase. ESS, technician, admin, and unsafe legacy profiles remain
   outside member-only revocation.
6. Ability cutover phases are validated through `AbilityCutoverPhase` and
   `AbilityCutoverPolicy`. Invalid phases, missing or invalid deadlines, and
   expired emergency fallback configuration fail closed. Deprecation headers
   are emitted only for accepted legacy requests; wildcard abilities are not
   accepted as an emergency shortcut.
7. Focused cross-organization tests cover member reads and mutations,
   validation actions, loan reads/approval, payment approval, mixed payment
   batches, and legacy fallback organization safety. Batch rejection is
   atomic and leaves target records unchanged.
8. Account-link requests use controlled reason codes. The denial matrix
   covers privileged, finance, HR, payroll, operational, manage, and approve
   permissions, as well as the corresponding role matrix. Linking remains
   transactional and unlinking revokes member-profile tokens only.
9. Generic member create/edit responses no longer query or expose a broad
   user list. Account linking remains an explicit dedicated flow.
10. `LoanApiController@index` now uses `LoanResource::collection`, preserving
    pagination metadata while removing raw Eloquent serialization from the
    list response.
11. The exact cooperative global permission matrix is tested: System Admin,
    Admin Pusat, and Pengurus Koperasi receive `view_cooperative_all` under
    the current repository contract; branch and operational roles do not.
    Permission removal immediately removes global visibility, and a role name
    alone never grants it.

### Focused verification

Final targeted regression command:

    php artisan test --compact \
      tests/Feature/Authorization/CrossOrganizationMutationTest.php \
      tests/Feature/LegacyErp/EmployeeScopeTest.php \
      tests/Feature/OrganizationManagementTest.php \
      tests/Feature/Cooperative/MemberAccountLinkAuthorizationTest.php \
      tests/Feature/Security/GranularAbilityCutoverTest.php \
      tests/Feature/Security/TokenAppMetadataTest.php

Result: 41 passed, 173 assertions.

Additional token classification and migration verification:

    php artisan test --compact \
      tests/Unit/Security/LegacyTokenClassifierTest.php \
      tests/Feature/Security/LegacyTokenClassificationCommandTest.php \
      tests/Feature/Security/TokenMetadataMigrationTest.php

Result: 5 passed, 30 assertions.

The broader Document 04 focused set passed with 74 tests and 264 assertions;
the directly affected regression set passed with 124 tests and 832 assertions
before the final session and validation additions. `vendor/bin/pint --dirty
--format agent` passed, `php artisan wayfinder:generate --no-interaction`
passed with generated output remaining ignored, and `npm run build` passed.

No new migration was required. The existing additive token metadata migration
verification remains the disposable-database migration check; no persistent
development or production database was modified.

### Intentionally skipped and residual risks

- Full PHPUnit and parallel coverage suites were not run.
- PostgreSQL concurrency, production token classification/revocation, shared
  database migrations, and shared database seeders were not run.
- GitHub Actions were not monitored, and no pull request or merge was made.
- The repository still does not distinguish central and branch Pengurus
  Koperasi roles; the documented current matrix is preserved.
- Independent GLM full verification remains pending, including any routes or
  organization-owned models outside the focused matrix.

## Senior remediation plan update

The follow-up plan was applied without changing payment/reservation state
machines, PII encryption behavior, PII migrations/backfill, or Document 05.

### Remediation identity

- Actual remediation update starting SHA: ff5b80ca57a949495f644371aa3e4200050cad23
- Ending implementation SHA before this evidence commit: 1f9aec0a76f196432ba00bab62f91a6898db997f
- Branch: remediation/document-04-organization-auth-token-cutover

### Account-link resolution

- Added exact-email candidate lookup for web and API under the dedicated
  account-link path.
- Candidate results are restricted to the member organization, verified users,
  users without an existing cooperative-member link, and non-privileged
  accounts. The endpoint is not a broad user directory and does not mutate
  either record.
- Existing transactional link/unlink actions remain the only mutation path;
  controlled reason codes, organization checks, role eligibility, and
  member-only token revocation remain enforced there.
- Google SSO continues to refuse email-only auto-linking for an existing member
  without an account. Operators now have an exact, scoped candidate route to
  resolve that state safely.
- Generic member create/edit forms no longer carry `user_id`.

### Unsafe legacy token rotation

- `POST /api/token/rotate` accepts an optional `app` enum: `member`, `ess`,
  `technician`, or `admin`.
- `app` is required only when the current token is wildcard, combined, empty,
  unknown, or otherwise unsafe. Safe legacy profiles and tokens with explicit
  metadata must preserve their current app profile; a safe profile cannot be
  switched to another app during rotation.
- New abilities are resolved from current user permissions through the existing
  app-specific issuer. Rotation creates the replacement before deleting the
  old token, and the response exposes only safe metadata (`token_app` and
  `token_version`) alongside the bearer token response contract.
- Wildcard abilities are not issued by the new profile resolver and app choice
  cannot grant permissions beyond the current user authorization.

### Contract and documentation updates

- OpenAPI generator and snapshot now describe the rotation `app` contract and
  the exact account-link candidate route.
- API and architecture docs no longer claim that the admin app receives a
  wildcard token. Legacy cooperative abilities are documented as a staged
  compatibility concern only.
- ADR-026 records the app-specific token and account-link decisions; the
  operator boundary remains cooperative-only while ERP role/workflow scope is
  deferred.

### Verification prepared

- PHP syntax lint passed for every changed PHP implementation and test file.
- `vendor/bin/pint --dirty --format agent` passed.
- `php artisan wayfinder:generate --no-interaction` passed; generated output
  remains ignored.
- Focused regression coverage was added to `P0SecurityTest`,
  `MemberAccountLinkAuthorizationTest`, and `GoogleSsoFlowTest` for unsafe
  rotation, app-profile preservation, exact scoped candidates, duplicate-link
  protection, and the no-auto-link SSO path.
- The repository AGENTS test policy requires focused PHPUnit commands to be
  provided for operator execution rather than run by Codex in this task.

Status remains: READY FOR INDEPENDENT REVIEW.
