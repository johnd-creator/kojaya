# Organization, Authorization, Provisioning, and Token Cutover Plan

## Scope

- organization query scope;
- policy object authorization;
- member-user linking;
- token app identity;
- granular ability migration.

Do not change payment state machine or PII crypto here.

---

## AUTH-1 — Explicit scope contract

Do not detect scope using `$fillable` or `method_exists()`.

Create explicit interface/registry:

```php
interface OrganizationScopedModel
{
    public function organizationScopePath(): string;
}
```

Examples:

```text
CooperativeMember -> organization_id
Loan -> organization_id
CooperativePayment -> organization_id/member.organization_id
MemberResignationRequest -> member.organization_id
SavingsWithdrawal -> member.organization_id
RewardRedemption -> member.organization_id
```

Service API:

```php
scopeVisibleTo(Builder $query, User $user)
assertUserHasOrganizationOrGlobal(User $user)
visibilityFor(User $user): OrganizationVisibility
```

Rules:

- global permission -> intentional global;
- non-global + org -> exact scope;
- non-global + null org -> deny;
- unsupported model -> throw;
- never silently unscoped.

Table-driven tests for every registered model.

---

## AUTH-2 — Layer parity

Every sensitive action must align:

| Layer | Rule |
|---|---|
| Route | granular ability/permission |
| Request | input authorization |
| Policy | permission + organization |
| Query | same scope |
| Service | state/business invariant |
| Response | explicit allowlist |

Cover:

- member actions;
- resignation process;
- loan actions;
- payment approval/batch;
- ledger mutation;
- withdrawal;
- POS credit;
- reward redemption.

Add direct-ID cross-org negative tests for every mutation.

---

## AUTH-3 — Explicit account linking

Replace two-role denylist with allowed-state policy.

Default target user:

```text
same organization
not linked to another member
identity/email verified
no privileged or operational role
```

Deny by default:

- System Admin;
- Admin Pusat;
- Pengurus;
- Manajer;
- Admin Koperasi;
- Kasir;
- finance/payroll/HR roles;
- any user with cooperative manage/approve permissions.

Prefer permission-based privileged detection.

Dedicated routes:

```text
POST /cooperative/members/{member}/account-link
DELETE /cooperative/members/{member}/account-link
```

Payload requires `user_id` and `reason`.

Generic update cannot link/unlink.

Tests:

- operational roles denied;
- cross-org denied;
- existing link denied;
- email collision does not auto-link;
- same-org ordinary user succeeds;
- unlink role behavior correct;
- audit includes actor/old/new/reason.

---

## AUTH-4 — Explicit token app metadata

Ability-based classification cannot safely distinguish wildcard/combined tokens.

Store:

```text
token_app = member|ess|technician|admin
token_version
device_id
issued_at
last_used_at
```

Each login issues one app profile. Avoid combined tokens unless formally supported.

Member lifecycle revokes:

```text
where token_app = member
```

Account-wide security revoke is separate and audited.

### Legacy migration

Classify:

- member-only -> member;
- ESS-only -> ess;
- technician-only -> technician;
- wildcard/combined/unknown -> force rotation and revoke after grace period.

Tests include pure, combined, wildcard, unknown, and account-wide revoke.

---

## AUTH-5 — Granular ability cutover

Current granular+legacy fallback is migration only.

### Phase 1 — Instrument

Metrics by route/token app/version; no token secrets.

### Phase 2 — Rotate

New tokens granular only. Rotate old admin/mobile tokens.

### Phase 3 — Deprecate

Return deprecation header and dashboard remaining legacy count.

### Phase 4 — Remove

Remove legacy issuance and route fallback.

Architecture test fails if routes contain:

```text
cooperative:read
cooperative:write
```

Emergency feature flag may temporarily re-enable fallback, but requires expiry timestamp and audit.

---

## AUTH-6 — Global role semantics

Document roles permitted `view_cooperative_all`.

Recommended:

- System Admin;
- central Pengurus only if business requires;
- no branch Admin/Manager/Kasir by default.

Seeder matrix tests must assert exact distribution.

## Append-only closure note — release preparation

The historical authorization and token-cutover plan remains unchanged. Its
focused implementation was merged through PR #8, and final main CI evidence is
successful run #101 at `21a45fc17f073b6b10f2a10c13798108110f2433`. Granular
token cutover remains instrument-phase work and is not represented as final
production completion.
