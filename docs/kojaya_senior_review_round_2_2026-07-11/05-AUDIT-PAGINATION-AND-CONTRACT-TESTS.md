# Audit, Pagination, and Data Contract Closure Plan

## AUDIT-1 — Explicit context

Audit must support HTTP, queue, CLI, webhook, and scheduler.

Create:

```php
AuditContext(
    actorId,
    actorRoles,
    organizationId,
    correlationId,
    ip,
    userAgent,
    source,
)
```

HTTP factory may read Auth/request. Queue/CLI/webhook pass explicit context.

Do not accept actor in a domain method and then ignore it during audit.

---

## AUDIT-2 — Truthful event lifecycle

Export must use:

```text
member.export.requested
member.export.completed
member.export.failed
```

Completion includes:

- export/job ID;
- explicit scope;
- filters;
- row count;
- masked/full mode;
- completion time;
- checksum if safe;
- reason.

Do not log “exported” before file creation succeeds.

Apply requested/completed/failed semantics to backfill, PII rotation, batch payment, reservation expiry, account linking, and write-off.

---

## AUDIT-3 — Mandatory vs best-effort

Mandatory, guaranteed via transaction/outbox:

- full PII export;
- privileged account-link override;
- write-off;
- privileged role mutation.

Best-effort with alert:

- denied-access telemetry;
- legacy ability use;
- low-risk read metrics.

Do not catch audit failure and retry through the same failing path without isolation.

---

## AUDIT-4 — Redaction

Recursive tests for:

```text
identity_number
nik
npwp
no_rekening
bank_account_number
account_holder
token
authorization
secret
password
qr_string
gateway_payload
```

Also redact ciphertext and blind index unless an explicit security decision allows them.

Test nested arrays and case variants.

---

## PAGINATION-1 — Repository-wide bounded limits

Standard:

```text
default 15
minimum 1
maximum 50
maximum 100 only for documented admin endpoint
```

Search all PHP code for:

```text
paginate(
simplePaginate(
cursorPaginate(
limit($request
take($request
per_page
page_size
```

Every request-derived limit uses centralized resolver.

Surfaces:

- member;
- cooperative API;
- ESS;
- technician;
- notifications;
- loans/payments;
- audit/compliance;
- POS/procurement;
- certificates/MCU;
- support tickets.

Add architecture test that fails raw patterns outside explicit allowlist.

Behavior per API group:

| Input | Expected |
|---|---:|
| omitted | default |
| -1/0 | 1 |
| 1 | 1 |
| 50 | 50 |
| 51/999999 | 50 |
| non-numeric | default |

---

## CONTRACT-1 — Exact allowlists

No sensitive controller returns Eloquent model directly.

Use JSON Resources and Inertia DTO/page-data.

Contract tests for:

- member list/detail/edit;
- loan list/detail;
- resignation;
- payment detail.

Assert exact top-level and nested keys, PII by permission, organization denial, and absence of future model fields.

---

## CONTRACT-2 — Remove false-confidence tests

A test named for `LoanResource` must execute the actual LoanResource or actual loan endpoint.

Required:

1. actual API route;
2. actual Inertia route props;
3. deactivate/resign endpoint with same browser session;
4. actual export file;
5. actual token issuance/revocation;
6. actual backfill command/raw DB.

Test names must identify surface, actor, condition, outcome.

---

## CONTRACT-3 — Partial update preservation

Tests:

- omitted sensitive field unchanged;
- masked UI value never persisted as PII;
- empty string clear only through authorized explicit action;
- absent means preserve;
- web/API parity;
- cross-org update denied.

## Done

- audit semantics truthful;
- mandatory audit guaranteed;
- all limits bounded;
- exact API/Inertia contracts;
- no misleading tests;
- full CI runs everything.

## Append-only closure note — release preparation

The historical audit, pagination, and contract-test plan remains preserved as
evidence. Its focused implementation was merged through PR #9, with final main
CI evidence in successful run #101 at
`21a45fc17f073b6b10f2a10c13798108110f2433`. This note does not alter historical
test counts or claim that the internal-alpha baseline is a production release.
