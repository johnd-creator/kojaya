# Document 03 PII Encryption Remediation Evidence

Status: READY FOR INDEPENDENT REVIEW

Scope: Plan 03 — PII Encryption Rollout. Plan 04 was not started.

## Evidence identity

- Actual starting SHA: `366dec2cd1729388ebd205527da6433de79bf725`
- Implementation ending SHA before this evidence commit: `c8651533`
- Branch: `remediation/document-03-pii-encryption-rollout-clean`

## Rollout phases

The rollout is explicit and defaults to `dual_write`:

- `dual_write`: writes retain the compatibility plaintext copy and also write encrypted data, blind indexes, and metadata.
- `encrypted_preferred`: reads prefer encrypted data and fail closed when decryption fails; the plaintext copy remains for application rollback compatibility.
- `plaintext_retired`: plaintext reads are no longer a compatibility fallback and plaintext deletion requires explicit command confirmation, phase confirmation, production confirmation, and parity verification.

The plaintext column remains in the schema. The default backfill preserves plaintext. Retirement is a separate `--retire-plaintext` operation.

## Compatibility and rotation

- New ciphertext uses a versioned envelope.
- Legacy Laravel ciphertext is readable only through the explicitly configured legacy encryption key.
- Envelope metadata can be inspected without decrypting the value.
- Blind indexes use the canonical `version|field|normalized-value` contract.
- Search can generate candidates for all configured active blind-index versions.
- `--rotate-to-current` re-encrypts readable rows and re-indexes them with current versions while preserving plaintext unless retirement is explicitly active.
- Legacy encrypted-only rows are decrypted with the explicit legacy key during rotation; the new envelope, current metadata, and compatibility plaintext copy are written together.
- Missing compatibility copies are reported as `missing_plaintext_compatibility_copy`, repaired by normal backfill, and not required after `plaintext_retired`.
- Old encryption and blind-index versions remain configured and searchable until their rows are retired through an approved rotation process.
- Active blind-index versions are normalized, ordered deterministically, must be non-empty, must include the current version, and must have configured keys.
- v1 normalization remains compatible with main: digits-only identity/NIK and NPWP, and `strtoupper(trim(value))` for bank account numbers. Future normalization changes require a new version.

## Authorization and audit safety

- Exact sensitive search through the cooperative member web index and export query requires `view_cooperative_member_pii`.
- Organization scope is applied before returning search results.
- List, detail, and API resources mask sensitive fields without the dedicated view permission.
- Export audit metadata records actor context, scope, organization, `include_pii`, requested field names, search mode, and safe record count.
- Raw search filters, blind indexes, ciphertext, and sensitive request values are not written to audit metadata.
- Export accepts controlled reason codes; legacy free-text reasons are mapped to `other`, while raw reason text is never persisted.
- Generic `CooperativeMember` serialization hides logical PII fields and all encryption, index, and version metadata. Explicit resources retain permission-aware masking/reveal behavior.
- Temporary full-export files remain configured for deletion after response delivery.

## Backfill and verification

- Non-dry-run backfill locks and reloads each row inside its transaction before inspection, update calculation, and write.
- Retry is limited to transient database errors for serialization, deadlock, lock timeout, and SQLite busy/locked conditions. Application, validation, crypto, and consistency failures fail closed.
- Backfill supports dry-run, resume, idempotent repair, current-version rotation, and separately confirmed plaintext retirement.
- Verification reports rollout phase, field classifications, encryption/envelope versions, blind-index versions, and issue counts without PII.
- Verification detects decryption failure, plaintext/encrypted mismatch, missing or mismatched blind indexes, unknown key/index versions, envelope metadata mismatch, missing migration metadata, orphan metadata/indexes, and plaintext remaining after retirement.

## Migration rollback policy

- The historical encrypted-column migration is unchanged from the source migration.
- The metadata migration now fails fast before dropping any PII metadata column.
- Migration verification ran against the disposable SQLite `:memory:` test database. Fresh test migrations exposed all PII metadata columns; the metadata rollback guard threw before schema modification and the column list remained unchanged.
- Schema rollback requires the approved backup/procedure path and is not part of application rollback.

## Focused verification

Commands executed:

```text
./vendor/bin/pint --dirty
php artisan test --compact tests/Unit/Security/PiiCryptoServiceTest.php tests/Unit/Security/MemberSensitiveDataInspectorTest.php
php artisan test --compact tests/Feature/Security/MemberSensitiveDataRolloutTest.php tests/Feature/Security/MemberSensitiveDataSerializationTest.php tests/Feature/Security/PiiMigrationGuardTest.php tests/Feature/Cooperative/MemberExportAuthorizationTest.php
php artisan test --compact tests/Feature/MemberPortal/MemberUnifiedEndpointsTest.php tests/Feature/ProductionReadinessP0P2Test.php
```

Focused tests covered crypto compatibility, envelope and metadata inspection, staged rollout, retirement guards, rotation, stale-snapshot safety, authorization, organization scope, audit sanitization, and member profile preservation.

Results:

- Crypto and inspector tests: 19 passed, 31 assertions.
- Final-gap rollout, authorization, serialization, migration, export, and audit tests: 37 passed, 199 assertions.
- Affected member endpoint and P0 privacy regressions: 26 passed, 141 assertions.
- Total focused result: 82 passed, 371 assertions.
- Failed, skipped, and risky tests: none reported.

## Intentionally skipped

- Full PHPUnit suite
- Coverage
- PostgreSQL concurrency suite
- Production/shared-database migration execution
- Seeder execution against a shared database
- Frontend build
- GitHub Actions monitoring

## Residual risks

- No production migration or deployment was performed.
- Production key provisioning, backup, rollback rehearsal, and operator approval remain deployment responsibilities.
- Document 03 remains ready for independent review; it is not accepted by this remediation.

READY FOR INDEPENDENT REVIEW
