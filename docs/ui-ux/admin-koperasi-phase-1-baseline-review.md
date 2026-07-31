# Admin Koperasi Phase 1 — Baseline Review

## Gate status

- Scoped Admin evidence: **PASS**.
- Repository full audit: **PASS AND REPRODUCIBLE**.
- Phase 1 final gate: **PASSED**.
- Final implementation SHA: `a94dae7d8a41d615608c34a177055685afc1ed18`.
- CI run: `30671084410` — success.
- Compare audit #1: `30671081619` — success.
- Full UI Audit run: `30671082921` — success.
- Compare audit #2: `30671680352` — success.
- Full-audit manifest head SHA: `a94dae7d8a41d615608c34a177055685afc1ed18`.

## Root-cause correction

Run `30640571911` proved that the earlier nondeterministic failures were data-state drift, not font rasterization. The member portal GET handlers (`dashboard` and `savings`) created dues invoices on every page load via `DuesGenerationService::generateForPeriod()` and `ensureOneTimeInvoice()`. Because the Full Audit includes accessibility tests that visit `/member/savings` before admin screens, the shared audit database accumulated invoices that the Compare Audit (which runs only visual tests) never produced.

The fix removed the write-on-GET side effects entirely. Invoice generation is already handled by the scheduled `cooperative:generate-monthly-dues` command, POST API endpoints, and the member provisioning flow. The `UiAuditSeeder` now produces the canonical invoice set (8 POKOK + 8 WAJIB across 8 active members) deterministically, so every screen reads the same data regardless of test order.

The prior conclusion that all four failing screens were font-rasterization drift was incorrect. The evidence is corrected here.

## Canonical database state

| Metric | Value |
| --- | --- |
| Total invoices after seed | 16 |
| UNPAID | 15 |
| PARTIAL | 1 |
| PAID | 0 |
| Sum amount | 2,400,000 |
| Sum paid | 25,000 |
| Outstanding | 2,375,000 |
| POKOK invoices | 8 |
| WAJIB invoices | 8 |
| After member dashboard GET | 16 (unchanged) |
| After member savings GET | 16 (unchanged) |
| After Compare audit | 16 (unchanged) |
| After Full audit | 16 (unchanged) |

## CI evidence

| Metric | Value |
| --- | --- |
| CI run | `30671084410` |
| CI head SHA | `a94dae7d8a41d615608c34a177055685afc1ed18` |
| CI conclusion | success |
| PHPUnit tests | 1451 |
| PHPUnit assertions | 9473 |
| PHPUnit skipped | 5 |
| All required jobs | success |

## Full UI Audit evidence

| Metric | Value |
| --- | --- |
| Full audit run | `30671082921` |
| Artifact ID | `8808933469` |
| Artifact head SHA | `a94dae7d8a41d615608c34a177055685afc1ed18` |
| Mode | full |
| Viewport | all |
| Scope | all |

### Visual results

| Viewport | Expected | Executed | Passed | Failed | Skipped |
| --- | ---: | ---: | ---: | ---: | ---: |
| Desktop | 89 | 89 | 89 | 0 | 0 |
| Tablet | 73 | 73 | 73 | 0 | 0 |
| Mobile | 57 | 57 | 57 | 0 | 0 |

### Accessibility results

| Metric | Value |
| --- | ---: |
| New critical | 0 |
| New serious | 0 |
| Stale findings | 0 |

### Runtime results

| Metric | Value |
| --- | ---: |
| Console errors | 0 |
| Page errors | 0 |
| Failed important requests | 0 |
| Unexpected responses | 0 |
| Horizontal overflow | 0 |

### Baseline verification

| Metric | Value |
| --- | ---: |
| Missing baselines | 0 |
| Orphan baselines | 0 |
| Duplicate baselines | 0 |
| Invalid dimensions | 0 |

## Repeatability evidence

Screenshot hashes for the four previously failing screens are identical across all three audit runs:

| Screen | Compare #1 | Full | Compare #2 |
| --- | --- | --- | --- |
| admin-dashboard | a5530360a3e17a73 | a5530360a3e17a73 | a5530360a3e17a73 |
| admin-dues-index-open | ecf573c3be483207 | ecf573c3be483207 | ecf573c3be483207 |
| dues-index-default | b9bef8543c89a825 | b9bef8543c89a825 | b9bef8543c89a825 |
| operator-dashboard | 964070fa4fe04adc | 964070fa4fe04adc | 964070fa4fe04adc |

## Baseline policy

- Visual tolerance: unchanged.
- Global screenshot masks: none.
- Text/state masks: none.
- New waivers: none.

## Historical failed runs

The following runs are retained for history only:

- Full UI Audit run `30447414712` — failure (head `d2aa70ee`, 18 visual failures).
- Compare UI Audit run `30640571911` — failure (head `160fcb45`, 4 desktop visual failures caused by data-state drift).
