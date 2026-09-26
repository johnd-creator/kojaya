# RC-04 PAY-006 Proof Migration Dry-Run

## Baseline

- Starting release baseline: `main` at `40e20605d5df6a1c2c79f0757cdad9d99ce56a25` (`v1.0.0-rc.3` candidate).
- Work is isolated on `codex/rc-04-pay006-dry-run`; no RC tag was created.
- The existing baseline CI run was #476 at the baseline SHA and passed. Exact-head CI for this change is recorded below when available.
- Local PostgreSQL verification used a newly created, isolated `kojaya_test` database. The shared `kojaya_erp` database was not used. Test proof files used unique temporary directories and were removed after each test.

## Migration Architecture

- Command: `php artisan payments:migrate-proofs-to-private`; default mode is dry-run. `--execute` enables copy and verified-public-copy removal.
- Source: `public` disk, legacy namespace `cooperative/payment-proofs/`.
- Destination: `config('filesystems.payment_proof_disk')`; default is `local` (private local storage).
- Database reference: `CooperativePayment.proof_path`.
- Record processing: ascending ID, `chunkById(100)`.
- Integrity: SHA-256 streams. A public-only file is copied, private presence is checked, and source/destination hashes are checked before public removal. A second source hash check is made immediately before removal.
- Fallback: `filesystems.payment_proof_legacy_public_fallback`; default enabled. This RC does not disable it.
- An unreferenced legacy public file is counted as an orphan and preserved; it is never cleaned up by this command.
- Exit is non-zero for missing references, failed operations, or public orphans. Conflicting public/private contents also fail and remain intact.

## Safety Invariants

1. Reject paths outside the exact legacy namespace and malformed or platform-unsafe relative segments before storage access.
2. Do not remove a source unless the destination is present and content integrity has been verified.
3. Re-hash the public source immediately before deletion; require the storage adapter to report successful deletion and verify the path is absent afterward.
4. Any storage, read, write, hash, or delete uncertainty increments failure and prevents a clean exit. A failed deletion leaves the source in place when the adapter has not removed it.
5. Never overwrite a conflicting private copy, change `proof_path`, or remove an orphan.
6. Dry-run performs no file or database mutation.

Cross-disk storage has no atomic compare-and-delete operation. The pre-delete re-hash narrows the race but cannot eliminate a concurrent writer race. Production execution therefore requires a maintenance window or another control that quiesces proof uploads, plus database and storage backups.

## Path Validation

Accepted example: `cooperative/payment-proofs/admin/a.png`.

Rejected by regression coverage: `branding/logo.png`, traversal (`../` and an embedded `..` segment), `.` segment, empty suffix/trailing slash, doubled slash, backslash separators, and Windows reserved device name (`CON.txt`). The implementation also rejects control characters, colon, trailing dot/space segments, and empty segments. Invalid references fail without copying or deleting files.

## Test Matrix

All tests below ran against isolated PostgreSQL and unique temporary local filesystem roots.

| State | Dry-run / execute evidence | Result |
| --- | --- | --- |
| Public only, referenced | Dry-run fingerprint remains unchanged; execute copies and verifies private file, removes verified public copy, leaves `proof_path` unchanged | PASS |
| Private only, referenced | Execute twice; private bytes remain, no public file is created, reference unchanged | PASS |
| Matching public + private | Execute removes only the matching public duplicate | PASS |
| Conflicting public + private | Non-zero result; both contents are preserved | PASS |
| Missing everywhere | Non-zero result; no file is fabricated | PASS |
| Public orphan | Non-zero result; orphan remains on public storage | PASS |
| Unsafe/out-of-scope references | Non-zero result; neither disk is mutated | PASS |

The focused migration test class completed **14 tests / 63 assertions**. The two PAY-006 authorization/fallback tests completed **2 tests / 19 assertions**.

## Dry-Run Immutability

`test_dry_run_preserves_database_and_filesystem_fingerprints` captures all payment IDs and `proof_path` values plus sorted SHA-256 fingerprints for public and private files before and after a dry-run containing public-only, private-only, and matching-duplicate states. The complete structures compare equal. Result: **PASS; zero observed DB or file mutations**.

## Disposable Execute Rehearsal

The PostgreSQL-backed tests exercised `--execute` only with synthetic records and per-test temporary disks. A public-only proof was copied with identical contents, then its public copy was removed; the database path did not change. Matching duplicates were removed only after equality checks. Conflict, missing, orphan, unsafe-path, and injected-failure cases returned non-zero and preserved data as applicable. No production connection or production data was used.

## Failure Injection

Injected failures covered source stream read/hash, private destination hash read, private write, private-disk `exists`, public-disk `exists`, and public delete returning false. Every case returned failure; the public source remained present and unchanged. For destination hash-read uncertainty, the copied private file was retained for inspection and the public source was not removed. Result: **PASS**.

## Idempotency

Two consecutive successful execute invocations were verified for a private-only reference. The private content and `proof_path` remained unchanged, no public file was recreated, and the repeated run succeeded. Result: **PASS**.

## Authorized Retrieval

`OrganizationPermissionIsolationFunctionalTest::test_pay006_payment_proof_storage_and_authorized_download_access` passed. Coverage verifies the owning member and same-organization staff can retrieve through protected application routes, while another member, cross-organization staff, and a guest are denied. Retrieval is not through a direct public URL. The same test verifies a migrated legacy proof can be served by the normal authorized route.

## Legacy Fallback

- Fallback enabled: the default configuration remains enabled during the migration window; the authorized retrieval test verifies the payment-proof application flow.
- Fallback disabled: `test_pay006_disabled_legacy_fallback_does_not_serve_a_public_only_proof` passes; public-only content is unavailable through the protected route. Private-disk retrieval is covered by the authorized retrieval test.
- Fallback is **not** disabled globally by RC-04.

## Real Dataset Availability

**REAL LEGACY DATASET AVAILABLE: NO approved production-like snapshot was supplied or found in the repository/workspace inventory.** No production system was contacted and no production counts are asserted.

**REAL-DATA DRY-RUN: PENDING PRODUCTION INVENTORY.** Synthetic verification does not establish actual production readiness. This pending inventory does not block planning RC-05 Backup & Restore Preflight; it does block production PAY-006 execution and final migration go-live approval until an approved isolated snapshot has a zero-missing, zero-failed, zero-orphan dry-run.

## Snapshot Dry-Run

Not performed: no approved snapshot is available. Production dataset counts are **not known** and are not fabricated.

## Findings

- Fixed path validation that previously accepted an empty suffix, empty segments, and `.` segments. Validation now rejects malformed and Windows-unsafe relative path segments.
- Fixed deletion handling so adapter-reported failure cannot be reported as successful cleanup; the command also re-hashes source immediately before deletion and confirms absence after a reported successful delete.
- Added isolated storage roots for PAY-006 tests because pre-existing test disk folders contained artifacts that must be preserved.
- No unresolved synthetic conflicts remain after the passing focused tests. Any real-data conflict, missing reference, or orphan remains a production planning blocker and requires reconciliation; this command will not erase it.

## Production Preconditions

Do not run `--execute` in production until all are true:

1. Approved database and storage backups are complete, restorable, and retained.
2. `filesystems.payment_proof_disk` resolves to the intended private disk; it is writable. The public legacy disk is readable.
3. New proof uploads are quiesced for the migration window or an equivalent control prevents concurrent changes.
4. An approved isolated production-like snapshot dry-run reports `missing=0`, `failed=0`, and `public_orphans=0`; all counts and total bytes are recorded.
5. Application release is deployed and owner/member plus same-org staff retrieval are verified; cross-org, other-member, and guest access remain denied.
6. A rollback/recovery procedure and an operator responsible for reconciliation are available.
7. The command output and post-run private/public inventories are captured without disclosing sensitive filenames.

RC-04 does not disable legacy fallback. Disablement is a later operational gate after production inventory and migration verification.

## Rollback Characteristics

- The operation is resumable and idempotent for completed references: private-only files are classified as already private.
- Partial completion can be detected by re-running the dry-run and reconciling `migrated`, `already_private`, `missing`, `failed`, and orphan metrics against DB references and both storage namespaces.
- Public-only files remain available for retry if copy/write/hash verification fails. Once a verified public copy has been deleted, operational restoration requires restoring that exact object from the approved storage backup or copying the verified private object back to the legacy location while writes are quiesced. Do not change the database path as an ad hoc rollback.
- No production rollback was performed.

## RC-04 Verdict

**Technical synthetic gate: PASS.** Path safety, immutable dry-run, disposable execution, integrity, failure handling, idempotency, authorization, fallback behavior, focused PostgreSQL tests, production prerequisites, and rollback characteristics are verified/documented.

**Production data gate: PENDING.** No approved real-data snapshot exists; production migration is not authorized by this result. The pending snapshot blocks production execution, but not RC-05 Backup & Restore planning.

**Required exact-head Linux CI: PENDING until the change is pushed and the authoritative run for the final SHA completes.** This is a runtime change, so the candidate version should advance from `v1.0.0-rc.3` to proposed `v1.0.0-rc.4`; no tag is created or pushed.
