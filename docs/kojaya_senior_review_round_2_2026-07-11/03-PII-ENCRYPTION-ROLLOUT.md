# PII Encryption Rollout and Rollback-Safe Plan

## Scope

Hanya:

- `identity_number`;
- `npwp`;
- `no_rekening`.

Jangan menambah field lain sebelum tiga field ini selesai end-to-end.

## Goals

- no plaintext at rest;
- exact search via blind index;
- wrong key is observable;
- rotation supported;
- rollout/rollback does not destroy data;
- backfill resumable and verifiable.

---

## PII-1 — Dedicated versioned keys

Production env:

```text
PII_ENCRYPTION_KEY_V1
PII_ENCRYPTION_CURRENT_VERSION=v1
PII_BLIND_INDEX_KEY_V1
PII_BLIND_INDEX_CURRENT_VERSION=v1
```

Rules:

- no production fallback to `APP_KEY`;
- missing/invalid/short keys fail boot/deploy;
- encryption key != blind-index key;
- unknown current version fails;
- tests use explicit test keys.

---

## PII-2 — Version ciphertext/index

Use envelope or columns:

```text
*_enc
*_key_version
*_bidx
*_bidx_version
*_migrated_at
```

This permits v1 read while new writes move to v2.

---

## PII-3 — No silent decrypt fallback

### Dual-read window

- encrypted valid -> decrypted value;
- encrypted absent + legacy present -> legacy;
- encrypted present + decrypt failure:
  - emit security error/metric;
  - log subject ID/key version only;
  - throw explicit domain exception;
  - never silently fallback.

### Post-cutover

- no legacy read;
- decrypt failure always observable;
- alert threshold > 0.

---

## PII-4 — Expand-and-contract releases

### Release A — Expand

Add nullable encrypted/index/version columns. Keep plaintext.

### Release B — Dual write

Write encrypted + bidx; retain plaintext temporarily only per rollback policy.

### Release C — Backfill

Controlled command.

### Release D — Verify

No plaintext deletion until verification passes.

### Release E — Cutover

Read encrypted only; exact search bidx only.

### Release F — Retire plaintext

Null plaintext for one release, then drop in separate irreversible migration.

Do not combine source-of-truth deletion and reversible encrypted-column drop.

---

## PII-5 — Rollback safety

Current down migration must not drop encrypted source-of-truth after plaintext is null.

Preferred:

```php
public function down(): void
{
    throw new RuntimeException(
        'Irreversible after PII cutover. Restore backup or run dedicated rollback command.'
    );
}
```

Alternative rollback command must decrypt, restore plaintext, verify parity, then permit schema removal.

Document backup restore rehearsal.

---

## PII-6 — Backfill redesign

Command options:

```text
--dry-run
--chunk=250
--from-id=
--limit=
--resume-token=
--repair-missing-index
--confirm-production
--report=/secure/path/report.json
```

Classify rows:

- legacy-only;
- encrypted-only;
- dual-equal;
- dual-mismatch;
- missing bidx;
- decrypt failure;
- complete.

Rules:

- production defaults dry-run;
- explicit confirmation for writes;
- bounded transactions;
- checkpoint last ID;
- transient retry;
- stop on crypto errors;
- no PII in output/report.

Add:

```bash
php artisan members:verify-sensitive-data
```

It validates decryptability, bidx parity, key versions, plaintext retirement, and count parity. Non-zero exit on inconsistency.

---

## PII-7 — Exact search contract

Single normalizer used by write/backfill/search/verify:

- NIK: digits;
- NPWP: digits;
- account: digits unless documented otherwise;
- Unicode trim;
- explicit leading-zero policy.

Blind index supports exact match only. No LIKE search.

---

## PII-8 — Permissions

Separate:

```text
view_cooperative_member_pii
update_cooperative_member_pii
export_cooperative_member_pii
```

Default export masked.

Full export requires business approval, reason, audit, secure temporary file, and retention/deletion policy.

---

## PII-9 — Observability

Metrics:

```text
pii.decrypt.success
pii.decrypt.failure
pii.backfill.completed
pii.backfill.mismatch
pii.key_version.remaining
pii.search.exact
```

Never audit/log plaintext, ciphertext, bidx, key, or raw sensitive request.

---

## Required tests

### Unit

- normalizers;
- deterministic bidx;
- version/key changes index;
- encrypt/decrypt;
- wrong key throws;
- encrypted-present failure never falls back.

### Feature

- create/update encrypted, plaintext absent at cutover;
- omitted PII preserved;
- unauthorized update rejected;
- exact search formatted/unformatted;
- cross-org search hides existence;
- masking API/Inertia/export;
- dedicated permission required.

### Migration/backfill

- legacy row backfills;
- idempotent/resumable;
- mismatch stops;
- verification catches corruption;
- rollback cannot silently lose data.

### Deployment rehearsal

Use sanitized production-size copy and record duration, locks, DB load, disk growth, report parity, and backup restore result.

## Append-only closure note — release preparation

The historical independent-review status in this document predates the focused
implementation merge through PR #5. Main CI after that merge is represented by
successful run #101 at `21a45fc17f073b6b10f2a10c13798108110f2433`. The code
implementation is accepted as an internal-alpha baseline for release preparation;
production rollout responsibilities, key operations, backfill, and rehearsal
remain outstanding.
