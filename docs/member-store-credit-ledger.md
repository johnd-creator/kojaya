# Member Store Credit Ledger

Member Store Account / Store Credit Ledger — a signed-balance store-credit
account for cooperative members, usable as a POS payment method.

> **Naming:** This is a *Member Store Account* / *Store Credit Ledger*. It is
> **not** a bank account, e-wallet, or savings product. Do not describe it as
> such to members or in marketing copy.

## Business objective

Allow cooperative members to hold a store balance at the cooperative that can
be used to pay for POS transactions. Staff may be authorized as delegates to
shop on a member's behalf.

## Signed balance semantics

The account uses a **signed BIGINT whole-Rupiah balance** (no float/double,
no sen). Rounding to whole Rupiah happens only at the POS integration boundary.

| Balance | Meaning |
| --- | --- |
| Positive | Deposit held by the cooperative — a **liability** owed to the member. |
| Zero | No deposit and no debt. |
| Negative | Store **receivable** — the member owes the cooperative. |

A negative balance is permitted only down to `-credit_limit`.
`credit_limit >= 0` and defaults to `0`.

Accounting examples (whole Rupiah):

| Event | Balance |
| --- | --- |
| Deposit Rp500.000 | `+500.000` |
| Purchase Rp200.000 | `+300.000` |
| Purchase Rp400.000 | `-100.000` |
| Cash payment Rp100.000 | `0` |
| Transfer Rp300.000 approved | `+300.000` |

## Invariants (enforced)

1. The ledger is the source of truth; the cached `balance` column mirrors the
   signed sum of ledger entries.
2. Balance is never edited through controllers/UI. All mutation flows through
   `StoreCreditLedgerService`, inside a DB transaction with `lockForUpdate()`.
3. Every balance change produces an immutable ledger entry.
4. Money is BIGINT whole Rupiah.
5. `amount` is always positive; `effect` (`credit`/`debit`) controls direction.
6. Purchase → debit. Cash/approved-transfer/refund → credit.
7. `projected_balance >= -credit_limit` for any debit.
8. Duplicate requests never post twice (unique `(account_id, idempotency_key)`
   and `(reference_type, reference_id, entry_type)`).
9. Posted entries cannot be updated or deleted (model-level guard). Corrections
   use a new reversal/adjustment entry.
10. All queries are organization-scoped (registered in `OrganizationScopeService`).
11. Suspended accounts cannot make purchases but may still receive payments.
12. Refund/void never credits twice.

## Data model

- `member_store_accounts` — one per member per organization. Signed `balance`,
  `credit_limit`, `status` (active/suspended/closed).
- `member_store_ledger_entries` — immutable entries: `entry_type`, `amount`,
  `effect`, `balance_before`, `balance_after`, polymorphic reference,
  `idempotency_key`, `reversal_of_entry_id`, actor/delegate, `occurred_at`.
- `member_store_funding_requests` — cash/transfer deposits. Cash posts
  immediately; transfer requires review.
- `member_store_delegates` — authorized staff. PIN stored only as a hash,
  rate-limited verification, per-transaction and daily limits.

## Funding

- **Cash** — a permitted cashier posts immediately with a receipt reference.
- **Transfer** — always `pending` until a reviewer (with
  `approve_store_credit_transfer`) verifies. Maker-checker: a reviewer cannot
  approve their own submission.
- Transfer proof is stored on the **private `local` disk**; the storage path is
  never exposed via API, resources, logs, or audit metadata.

## POS integration

`MEMBER_STORE_ACCOUNT` is a new payment method on the existing POS checkout
flow (`PosTransactionService::create`). It does **not** create a parallel POS
domain. Checkout:

1. Cashier selects the member owner (and optional delegate + PIN).
2. Inside the POS DB transaction, the account is locked (`lockForUpdate`); the
   service validates organization scope, account status, delegate
   status/PIN/expiry, per-transaction and daily limits, and projected balance.
3. If projected balance would breach the credit limit, the **entire** operation
   is rejected atomically — no POS transaction, no stock movement, no ledger
   entry.
4. The POS transaction + payments persist, then an immutable `pos_purchase`
   ledger entry is posted and the signed balance updated.
5. Void/return posts an idempotent `pos_refund` entry referencing the original
   transaction/return.

Existing payment methods (CASH/TRANSFER/QRIS/MEMBER_CREDIT) are unchanged.

## Permissions

Added to `PermissionEnum` and seeded via `RolePermissionSeeder`:

| Permission | Pengurus | Manajer | Admin | Kasir |
| --- | :---: | :---: | :---: | :---: |
| `view_store_credit` | ✓ | ✓ | ✓ | ✓ |
| `view_store_credit_all` | ✓ | | | |
| `manage_store_credit` | ✓ | ✓ | ✓ | |
| `manage_store_credit_limit` | ✓ | ✓ | | |
| `cashier_store_credit` | ✓ | ✓ | ✓ | ✓ |
| `approve_store_credit_transfer` | ✓ | ✓ | | |
| `adjust_store_credit` | ✓ | ✓ | | |
| `report_store_credit` | ✓ | ✓ | ✓ | |

Authorization uses policies extending `BasePolicy` (permission + organization
scope). Members access their own account via ownership, not permissions.
System Admin bypasses via `Gate::before`.

## Concurrency strategy

Balance mutation always:

1. runs inside `DB::transaction`,
2. acquires `lockForUpdate()` on the account row,
3. writes the immutable entry,
4. updates the cached balance,
5. asserts the cached balance equals the signed ledger sum.

`tests/Feature/Cooperative/StoreCreditConcurrencyTest.php` runs in the
PostgreSQL Concurrency CI job and proves concurrent purchases on a
credit-limited account can never overspend (true multi-process evidence, not
SQLite-only).

## Audit & observability

Sensitive actions are recorded via `AuditLogService` under module
`store-credit`:

- account opened/suspended/reactivated/closed
- credit limit changed
- delegate created/revoked/PIN reset
- cash funding posted
- transfer submitted/approved/rejected
- POS purchase posted
- refund/reversal posted
- manual adjustment posted

Audit metadata contains only safe fields (entry type, effect, amount, balance
after, reason). PINs, secrets, proof paths, tokens, and PII are never logged.

Structured `Log` warnings/criticals cover: over-limit purchase rejection,
idempotency replay, PIN rate limiting, and cached-balance invariant failure.

## Reports

`StoreCreditReportService` provides: total positive deposit liability, total
negative receivables, account counts by positive/zero/negative/suspended,
accounts exceeding a configurable utilization threshold, and the oldest
uncovered debt date. Debt age uses **FIFO allocation**: credit entries repay
the oldest outstanding purchase lots first; the oldest still-uncovered lot date
is the debt age (an exact, traceable allocation, not an approximation).

## API surface (member, additive & backward-compatible)

Under `/api/v1/member/store-account/*` (Sanctum, `ability:member:read|write`,
`member.api.active`): account summary, paginated ledger, delegates CRUD + PIN
reset, transfer submission, and funding-request history. Writes are
`throttle:api-write` + `idempotent`.

## Operational limitations & rollout

- No member-to-member transfers, no withdrawals/cash-out, no external payments.
- No interest, penalties, or automatic fees.
- No real payment gateway and **no production deployment** in this change.
- Business/accounting approval is required before production use.

## Rollback

The feature is additive. Rolling back requires running the four migrations'
`down()` methods (drop the four new tables) and removing the
`MEMBER_STORE_ACCOUNT` payment option. No existing POS/member table schema is
altered, so rollback does not affect historical POS data.
