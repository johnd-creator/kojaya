# Payment and Reservation State Machine Plan

## Scope lock

Hanya:

- member coffee/store order;
- `MemberPaymentIntent`;
- charge creation;
- webhook;
- reservation;
- settlement;
- expiry worker.

Dilarang mengubah UI Android, loan rules, PII crypto, role seeder, dan fitur bisnis baru.

## Invariants

```text
1 client_reference -> 1 authoritative intent
1 intent -> maksimum 1 active provider charge
1 reservation -> tepat 1 terminal state
PAID tidak boleh coexist dengan EXPIRED/RELEASED
1 paid intent -> tepat 1 business transaction
reserved stock tidak pernah negatif/melebihi quantity
```

---

## PAY-1 — Formal state machine

### Gateway

```text
NEW
CHARGE_CREATING
PENDING
PAID
FAILED
CANCELLED
DENIED
EXPIRED
```

### Reservation

```text
NONE
RESERVED
CONSUMED
RELEASED
EXPIRED
```

### Settlement

```text
NOT_SETTLED
SETTLING
SETTLED
FAILED
```

Invalid combinations seperti `PAID+EXPIRED`, `PAID+RELEASED`, atau terminal non-paid + CONSUMED harus ditolak oleh domain service.

---

## PAY-2 — Central resolve-or-create intent

Buat:

```php
MemberOrderIntentService::resolveOrCreate(
    member,
    payableType,
    clientReference,
    canonicalRequest,
)
```

### Canonical request/fingerprint

Normalize lalu hash:

- member;
- payable type;
- sorted product IDs;
- quantities;
- unit-price snapshot;
- customization;
- fulfillment;
- channel;
- total.

Simpan `request_fingerprint`.

### Algorithm

1. Begin transaction.
2. Fetch unique key dengan `lockForUpdate`.
3. Existing:
   - fingerprint/amount/channel harus sama;
   - metadata DB adalah authoritative;
   - state harus PENDING+RESERVED;
   - settled/terminal/expired -> 409.
4. New:
   - aggregate duplicate products;
   - sort product IDs;
   - reserve;
   - create intent.
5. Commit.
6. Return typed `Created`, `Reused`, atau `Conflict`.

### Unique violation

Jika insert kalah race:

1. mulai transaction baru;
2. fetch existing dengan lock;
3. jalankan validasi existing yang sama;
4. jangan langsung reuse tanpa validasi;
5. jangan log `reservation.created` untuk loser.

Response selalu dari `intent.metadata`, bukan request loser.

---

## PAY-3 — Serialize provider charge

Buat:

```php
PaymentIntentChargeService::ensureCharge(intent)
```

### Minimum algorithm

Transaction A:

- lock intent;
- reject settled/expired/terminal;
- return reusable charge bila ada;
- bila `CHARGE_CREATING`, return preparing/retryable;
- set `CHARGE_CREATING`;
- commit.

Provider call:

- di luar long DB transaction;
- gunakan stable provider idempotency key:
  ```text
  member-intent:{intent_id}:{attempt}
  ```

Transaction B:

- lock intent;
- verify still CHARGE_CREATING+RESERVED;
- save charge;
- set PENDING.

Failure:

- persist safe failure category;
- bounded retry;
- recovery job untuk stale CHARGE_CREATING;
- jangan membuat orphan charge.

Preferred robust design: outbox/job.

---

## PAY-4 — Webhook, expiry, settlement satu lock path

Buat:

```php
MemberPaymentIntentStateService::applyGatewayEvent(reference, event)
```

Semua webhook dan expiry wajib memanggil service yang sama.

### Algorithm

1. verify signature;
2. begin transaction;
3. find intent dengan `lockForUpdate`;
4. re-read gateway/reservation/settlement/expiry;
5. validate transition;
6. PAID:
   - hanya dari state valid;
   - reservation harus RESERVED;
   - mark PAID/SETTLING;
   - settle exactly once;
7. failed/cancelled/denied:
   - release exactly once;
8. expired:
   - hanya PENDING+RESERVED+not paid;
9. commit;
10. notify after commit.

Tidak ada controller/service lain yang boleh menulis `gateway_status` langsung.

---

## PAY-5 — Settlement guard

Store/coffee settlement mensyaratkan:

```text
gateway=PAID
reservation=RESERVED
settlement in [NOT_SETTLED, SETTLING]
```

Jika reservation sudah EXPIRED/RELEASED:

- jangan create POS transaction;
- create reconciliation incident;
- audit;
- manual resolution queue.

Successful settlement atomik:

```text
reservation=CONSUMED
settlement=SETTLED
settled_at=now
settled_by_service=...
```

---

## PAY-6 — Canonical lock ordering

Sebelum lock:

1. aggregate duplicate product;
2. sort ascending `pos_product_id`;
3. resolve location;
4. lock stock in sorted order.

Jangan lock menurut item order dari client.

---

## PAY-7 — Expiry worker

- bounded batch;
- `SKIP LOCKED` bila supported;
- same state service;
- `withoutOverlapping`;
- explicit scheduler;
- metrics: scanned, expired, skipped_paid, skipped_locked, failed.

---

## True concurrency tests

Gunakan PostgreSQL/MySQL, bukan SQLite single connection.

### C1 — Same key, same payload

32 parallel requests:

- one intent;
- one reservation;
- one provider call;
- responses same intent;
- one created audit.

### C2 — Same key, different payload

- one winner;
- loser 409;
- loser items tidak muncul;
- reservation sesuai winner.

### C3 — Reuse settled key

- 409;
- no new charge/reservation.

### C4 — PAID versus expiry

Barrier synchronized:

- exactly one valid terminal path;
- never PAID+EXPIRED/RELEASED.

### C5 — Duplicate PAID webhook

- one transaction;
- one consume;
- one notification.

### C6 — Charge race

- provider fake call count = 1.

### C7 — Opposite item ordering

- no deadlock;
- no over-reservation.

### C8 — Provider timeout

- stable idempotency key;
- no orphan charge;
- stale CHARGE_CREATING recoverable.

## Allowed files

Intent model/migrations, focused services, thin store/coffee controllers, webhook, expiry scheduler, focused tests.

## Forbidden files

Member PII, role seeder, Inertia screens, Android, loan approval, unrelated refactor.
