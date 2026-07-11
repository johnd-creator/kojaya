# Message to DeepSeek Junior

Target repository is **`johnd-creator/kojaya` backend**, not `KojayaApp`.

Commit `89e9e2279bfd5826bb0ee676bbf9d43e949840cf` contains meaningful improvements, tetapi belum disetujui.

```text
REQUEST CHANGES
KEEP DRAFT
DO NOT MERGE
```

## Perbaikan yang harus dipertahankan

- member active gates;
- synchronized terminal lifecycle state;
- organization checks pada key policies;
- narrower token revocation;
- Inertia allowlists;
- initial PII encryption;
- reservation state/expiry worker;
- granular ability migration start;
- pagination expansion;
- PII audit redaction.

## Urutan wajib

### Step 1

Kerjakan **hanya** `01-P0-SECURITY-CORRECTNESS-CLOSURE.md`:

- CI fully runs;
- export scope fail-closed;
- remove lifecycle/user-link/PII fields from generic update;
- prevent PII data loss;
- enforce lifecycle transitions in domain;
- preflight/backfill legacy status.

Buat focused PR dan berhenti untuk review.

### Step 2

Setelah Step 1 disetujui, kerjakan `02-PAYMENT-RESERVATION-STATE-MACHINE.md`.

### Step 3

Setelah payment disetujui, kerjakan `03-PII-ENCRYPTION-ROLLOUT.md`.

### Step 4

Kerjakan `04-ORGANIZATION-AUTHORIZATION-AND-TOKEN-CUTOVER.md`.

### Step 5

Kerjakan `05-AUDIT-PAGINATION-AND-CONTRACT-TESTS.md`.

Selalu ikuti `06-CI-PR-AND-JUNIOR-EXECUTION-PROTOCOL.md`.

## Non-negotiable

- Do not touch KojayaApp.
- Do not keep adding everything to PR #2.
- Do not weaken/skip CI.
- Do not claim tests passed when skipped.
- Do not add features/UI.
- Do not drop data in rollback.
- Do not call sequential tests concurrency tests.
- Do not call granular cutover final while legacy fallback remains.
- Do not change member status through generic update.
- Do not use null organization ID for both global and invalid scope.

After **Step 1 only**, hand off as:

```text
READY FOR SENIOR REVIEW: P0 security/correctness closure
```

Include repository, base/head SHA, focused PR, changed files, commands/results, CI link, migration behavior, rollback, and known gaps. Stop before Step 2.
