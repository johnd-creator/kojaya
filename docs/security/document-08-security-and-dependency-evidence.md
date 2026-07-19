# Document 08 Security and Dependency Evidence

> Append-only evidence record. No secret values, keys, tokens, credential URLs,
> raw configuration, or PII are stored here. Status is assigned only when
> traceable evidence exists in the repository; otherwise the item is reported as
> `PENDING` or `BLOCKED`.

## Scope and provenance

- Repository: `johnd-creator/kojaya`
- Branch: `agent/document-08-ops` (PR #15)
- Main SHA after PR #14 merge: `fc2ea4deb18477cec8ee8205d086d131a0ff3f58`
- PR #15 head before this evidence commit: `58d61e8edd7e49cfcf91a5699e99c0195e132c36`
- Release tag `v0.1.0` peeled SHA: `ad8bc3afc9b62f549e4f054e181ef9decbecb341` (unchanged)
- Application RBAC roles referenced below use the cooperative role hierarchy
  recorded in `AGENTS.md` (`System Admin`, `Pengurus Koperasi`,
  `Manajer Koperasi`, `Admin Koperasi`, `Kasir Koperasi`) only where
  runtime or business authorization is relevant.
- Operational, security, and engineering ownership is separate from application
  RBAC. It remains unassigned unless traceable evidence names an accountable
  owner or custodian.

This file records the repository-side state of Document 08 security and
dependency workstreams only. It does not assert that any staging deployment,
backup restore, rollback rehearsal, live alert delivery, secret rotation, token
cutover completion, or Android pilot has occurred operationally.

## Status legend

- `VERIFIED` — traceable repository evidence exists and no open operational
  blocker remains for the repository side of the item.
- `PENDING` — repository wiring or runbook exists but an explicit operational
  owner, staging rehearsal, or approval step has not been recorded.
- `BLOCKED` — required runtime, secret manager, operational database, or
  authorization is not available, so the item cannot be evidenced from the
  repository alone.

## Secret and key custody

| Item | Status | Owner role | Evidence | Remaining blocker |
|---|---|---|---|---|
| Exposed Google client secret rotation | `BLOCKED` | Operations/Security owner — unassigned | `config/services.php` reads `google.client_secret` from `env()`; `docs/google_sso.md` documents the env-var contract only. No operational credential rotation event is recorded in the repository. | No authorized secret manager, named custodian, or operational rotation rehearsal is available; rotation event must be performed and attested outside the repository. |
| `APP_KEY` custody and recovery | `BLOCKED` | Operations/Security owner — unassigned | `config/app.php` declares `key => env('APP_KEY')` with `AES-256-CBC` cipher and a `previous_keys` array for key rotation. No production custody record, recovery rehearsal, or secondary-custody attestation exists in the repository. | Production key custody, named custodian, backup, and recovery rehearsal are deployment activities; no authorized environment is available. |
| PII encryption key custody and recovery | `BLOCKED` | Operations/Security owner — unassigned | `config/security.php` resolves `PII_ENCRYPTION_KEY_V1`/`PII_ENCRYPTION_KEY_V2` and a legacy encryption key; `app/Services/Security/PiiCryptoService.php` and `app/Console/Commands/BackfillMemberSensitiveData.php` implement rotation/backfill. Custody, backup, and recovery of the actual key material is not recorded in the repository. | Production key custody, named custodian, and recovery rehearsal are operational and must be attested by an authorized owner; not advanceable from the repository. |
| PII blind-index key custody and recovery | `BLOCKED` | Operations/Security owner — unassigned | `config/security.php` resolves `PII_BLIND_INDEX_KEY_V1`/`PII_BLIND_INDEX_KEY_V2` and tracks `blind_index_active_versions`. Re-indexing requires an approved rotation process per `docs/code-review/00013_kojaya_document_03_pii_remediation.md`. No operational custody/recovery attestation exists in the repository. | Blind-index key custody, named custodian, and a production re-index rehearsal are operational and must be attested by an authorized owner. |
| Payment gateway (Midtrans) credential | `BLOCKED` | Operations/Security owner — unassigned | `config/services.php` reads `midtrans.server_key`, `midtrans.client_key`, `midtrans.is_production`, and `midtrans.merchant_id` from `env()`; `docs/PAYMENT_PLAN.md` records the sandbox-only local posture (`MIDTRANS_IS_PRODUCTION=false`) and states the server key is not committed. No production credential custody or attestation exists in the repository. | Production Midtrans credential custody, named custodian, rotation policy, and sandbox-to-production cutover must be attested operationally. |
| WhatsApp credential | `BLOCKED` | Operations/Security owner — unassigned | `config/services.php` declares a `whatsapp` block read from `env()`. No operational credential custody, sender verification, or rotation attestation exists in the repository. | Operational credential custody, named custodian, and rotation must be attested by an authorized owner. |
| FCM credential | `BLOCKED` | Operations/Security owner — unassigned | `config/services.php` declares an `fcm` block read from `env()`. No service-account JSON custody, project binding, or rotation attestation exists in the repository. | Operational FCM credential custody, named custodian, and rotation must be attested by an authorized owner. |
| Deployment SSH key | `BLOCKED` | Operations/Security owner — unassigned | `docs/api.md` and `docs/releases/v0.1.0-readiness.md` document deployment via `bin/deploy.sh` over SSH using GitHub Actions secrets `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, and `DEPLOY_SSH_KEY` with `workflow_dispatch`. No production host, key custody, or authorized deployment rehearsal exists in the repository. | Authorized staging/production host, named SSH key custodian, and key custody attestation must be provisioned; deployment rehearsal is a Document 08 operational acceptance item. |
| Database credential | `BLOCKED` | Operations/Security owner — unassigned | `config/database.php` reads connection parameters from `env()`; `AGENTS.md` forbids destructive operations against the local `kojaya_erp` database. No production database credential custody, rotation, or operational access attestation exists in the repository. | Production database credential custody and rotation must be attested by an authorized owner. |

## Rollout and measurement state

| Item | Status | Owner role | Evidence | Remaining blocker |
|---|---|---|---|---|
| Repository default and allowed PII phase | `VERIFIED` | Application/security owner — repository contract | `config/security.php` defaults `rollout_phase` to `dual_write`; `app/Providers/AppServiceProvider.php` validates the repository configuration against `PiiCryptoService::ROLLOUT_DUAL_WRITE`. This is repository default and validation-contract evidence only. | This evidence does not attest any deployed environment or permit advancing beyond `dual_write`; an operational owner must attest any later phase. |
| Actual deployed environment PII phase | `BLOCKED` | Operations/Security owner — unassigned | No traceable staging or production runtime evidence records the deployed PII phase. Repository defaults, local configuration, and test environments are not operational evidence of an actual deployed environment. | An authorized staging/production trace, phase observation, and owner/custody attestation are required before this acceptance item can be considered passed. |
| Legacy token usage measurement | `BLOCKED` | Manajer Koperasi / System Admin | `app/Console/Commands/ClassifyLegacyTokensCommand.php` and `app/Services/Auth/AbilityCutoverPolicy.php` exist, and `docs/code-review/00014_kojaya_document_04_organization_authorization_token_cutover.md` records the repository-side cutover design. Measurement requires execution against an approved operational database; test/fixture databases are not evidence of actual legacy-token usage. | No approved operational database is available; legacy-token measurement must be executed and attested against production-equivalent data by an authorized owner. |

## Dependency advisories

The audits below were executed against the current lockfiles on the PR #15
branch. Per instructions, residual advisories are reported as-is; no dependency,
lockfile, or upgrade was performed.

### Composer audit

- Command: `composer audit --locked --abandoned=report`
- Timestamp (Asia/Jakarta): 2026-07-19 01:11:07 WIB
- Exit code: `1`
- Summary: 4 advisories affecting 2 packages — 1 medium, 3 low; 0 high, 0 critical.

| Package | Severity | Advisory ID | CVE | Title (safe summary) | Remediation / risk owner |
|---|---|---|---|---|---|
| `phpseclib/phpseclib` | medium | PKSA-432p-hv1d-chf7 | CVE-2026-55599 | X.509 certificate validation can issue attacker-controlled outbound SSRF requests via AIA. | Application/Security engineering owner — unassigned. Requires coordinated dependency upgrade and regression test; not advanced in this PR per scope. |
| `symfony/yaml` | low | PKSA-v5yj-8nmz-sk2q | CVE-2026-45304 | YAML parser exponential memory allocation via recursive collection-alias expansion ("Billion Laughs"). | Application/Security engineering owner — unassigned. Upgrade tracked separately; this PR performs no lockfile change. |
| `symfony/yaml` | low | PKSA-ft77-7h5f-p3r6 | CVE-2026-45305 | YAML parser ReDoS via catastrophic backtracking in `Parser::cleanup()`. | Application/Security engineering owner — unassigned. Upgrade tracked separately; this PR performs no lockfile change. |
| `symfony/yaml` | low | PKSA-b14r-zh1d-vdrc | CVE-2026-45133 | YAML parser stack exhaustion via unbounded recursion in nested blocks, sequences, and mappings. | Application/Security engineering owner — unassigned. Upgrade tracked separately; this PR performs no lockfile change. |

No `abandoned` report rows were emitted. No advisory was hidden or downgraded.

### NPM production audit

- Command: `npm audit --omit=dev`
- Timestamp (Asia/Jakarta): 2026-07-19 01:11:14 WIB
- Exit code: `1`
- Summary: 2 vulnerabilities — 1 moderate, 1 low; 0 high, 0 critical.

| Package | Severity | Advisory | Remediation / risk owner |
|---|---|---|---|
| `esbuild` (0.27.3 - 0.28.0) | low | Arbitrary file read when running the development server on Windows (GHSA-g7r4-m6w7-qqqr). Dev-server-only exposure; CI build runs on Linux. | Application/Security engineering owner — unassigned. `npm audit fix` available but not applied in this PR per scope (no lockfile change). |
| `qs` (6.11.1 - 6.15.1) | moderate | Remotely triggerable DoS: `qs.stringify` crashes with `TypeError` on null/undefined entries in comma-format arrays when `encodeValuesOnly` is set (GHSA-q8mj-m7cp-5q26). | Application/Security engineering owner — unassigned. `npm audit fix` available but not applied in this PR per scope (no lockfile change). |

No advisory was hidden or downgraded. `npm audit` did not report any production
package as abandoned.

## Summary

- VERIFIED: 1 item (`Repository default and allowed PII phase`).
- PENDING: 0 items.
- BLOCKED: 11 items (9 secret/key/credential custody + deployment items, 1
  actual deployed PII phase item, and `Legacy token usage measurement`).

## Explicit non-actions

- No `APP_KEY`, PII encryption key, or PII blind-index key rotation was
  performed.
- No PII rollout phase was advanced beyond `dual_write`.
- No token cutover was advanced and no operational legacy-token measurement was
  claimed.
- No dependency, lockfile, generated artifact, migration, deployment, tag,
  release, or GitHub settings change was made.
- No PR was merged or marked ready for review, and no force-push occurred.

## Document 08 status

PARTIALLY ACHIEVED — NOT READY FOR DOCUMENT 08 CLOSEOUT

Operational acceptance for staging deployment, isolated backup/restore,
rollback rehearsal, live alert delivery, Android end-to-end pilot, governance
application, secret rotation attestation, and later Legacy ERP recovery waves
remains open and is not claimed by this evidence record.
