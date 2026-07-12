# Security Regression Test Matrix

## 1. Member status gates

| Surface | Pending | Revision | Rejected | Inactive | Resigned | Active |
|---|---:|---:|---:|---:|---:|---:|
| Web dashboard/onboarding | allow | allow | limited | limited | limited | allow |
| Web savings | deny | deny | deny | deny | deny | allow |
| Web loans | deny | deny | deny | deny | deny | allow |
| Web points/rewards | deny | deny | deny | deny | deny | allow |
| API profile/onboarding | allow | allow | allow-limited | allow-limited | allow-limited | allow |
| API savings/loans/bills | deny | deny | deny | deny | deny | allow |
| API payment charge | deny | deny | deny | deny | deny | allow |
| API points history | deny | deny | deny | deny | deny | allow |
| API reward redeem | deny | deny | deny | deny | deny | allow |
| API store/coffee order | deny | deny | deny | deny | deny | allow |

Test both:

- token created before transition;
- token created after transition;
- existing browser session before transition;
- fresh browser login after transition.

## 2. Token revocation scope

Create one user with:

- `member-mobile` token;
- `ess-mobile` token;
- `technician-mobile` token.

For revision/reject/deactivate/resign:

| Token | Expected |
|---|---|
| member-mobile | revoked |
| ESS | preserved |
| technician | preserved |
| all tokens during explicit security incident | revoked |

## 3. Organization isolation

Set up organization A and B.

### Cooperative member

For Admin Koperasi A:

- list contains A only;
- stats count A only;
- search cannot discover B;
- show B by UUID → 403/404;
- edit/update B → 403/404;
- activate/deactivate/resign/delete B → 403/404;
- export contains A only;
- opening-balance wizard B → 403/404.

### Resignation

- list A only;
- stats A only;
- direct process request B → 403/404;
- API list A only;
- API direct process B → 403/404.

### Loan

- list/stats/member selector A only;
- show loan B → 403/404;
- review/approve/reject/disburse/pay B → 403/404;
- API equivalents → 403/404;
- calculator may be global only if loan type is global by design.

### Batch operations

For payload mixing A and B:

- reject entire request;
- no partial writes;
- audit attempted cross-org access.

## 4. Provisioning/linking

- new member + new email creates ordinary member user;
- existing ordinary verified user can be linked through approved flow;
- privileged System Admin cannot be linked as member by Admin Koperasi;
- user in org B cannot be linked by admin org A;
- existing member-linked user cannot be linked again;
- email-only collision does not silently link;
- transaction rollback leaves neither half-created member nor role drift;
- role assignment is idempotent;
- account linking writes audit event.

## 5. PII contracts

### API

Without PII permission:

- NIK masked;
- NPWP masked;
- account masked;
- address absent/masked;
- account holder absent;
- no hidden Eloquent attributes.

With PII permission and same organization:

- permitted full values;
- audit event exists.

With PII permission but cross organization:

- 403/404.

### Inertia

Assert props for list/show/edit:

- explicit allowed keys only;
- no model internals;
- no full PII on list;
- no global user/employee options;
- no nested token/password fields;
- no unrestricted documents/payments/ledger.

### Export

- dedicated export permission required;
- organization scope;
- masked columns by default;
- explicit full export permission if business requires full PII;
- audit includes filters, result count, organization, actor, reason;
- exported PII never appears in application logs.

## 6. Reservation concurrency

Tests must use a DB engine and process model capable of true parallel transactions; SQLite single connection is insufficient.

### Scenarios

1. Stock 1, two parallel orders quantity 1:
   - one succeeds;
   - one receives insufficient stock;
   - reserved total never exceeds quantity.

2. Same client reference, two parallel requests:
   - one intent only;
   - one reservation only;
   - both responses reference same intent.

3. Two different client references:
   - each reservation is independent;
   - combined reserved cannot exceed stock.

4. Duplicate PAID webhook:
   - one POS transaction;
   - one consume;
   - no negative reserved count.

5. FAILED and EXPIRED webhook race:
   - one release only;
   - terminal state deterministic.

6. Expiry worker versus PAID webhook:
   - intent lock decides one valid terminal path;
   - paid order is not expired/released;
   - expired order is not settled.

7. Provider charge failure after reservation:
   - policy defined: keep reservation until expiry or release immediately;
   - behavior tested;
   - no permanent orphan reservation.

## 7. Audit contract

For every sensitive event assert:

- correlation_id present;
- actor;
- actor role snapshot;
- organization;
- subject;
- action;
- reason when needed;
- occurred_at;
- no plaintext token/secret/QR/identity/NPWP/account;
- retry does not create misleading duplicate success event.

## 8. Pagination

For every paginated endpoint:

- `per_page=-1` becomes 1;
- `per_page=0` becomes 1;
- `per_page=999999` clamps to max;
- non-numeric value uses default;
- response meta reports effective value;
- query count remains bounded.

## 9. CI contract

Merge is forbidden unless:

- Pint passes;
- frontend build passes;
- Wayfinder generation has zero drift;
- PHPUnit fast suite passes;
- migrations apply and rollback where supported;
- seed smoke passes;
- OpenAPI snapshot has zero unexplained drift;
- generated files are committed;
- no skipped checks due earlier failure.
