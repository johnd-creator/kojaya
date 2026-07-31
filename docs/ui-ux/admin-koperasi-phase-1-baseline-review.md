# Admin Koperasi Phase 1 — Baseline Review

## Gate status

- Scoped Admin evidence: **PASS**.
- Repository full audit: **PASS**.
- Phase 1 final gate: **PASSED**.
- Final implementation SHA: `7878b3fd23345875465e79f819f4122e76f67647`.
- CI run: `30629660404` — success.
- Full UI Audit run: `30629662394` — success.
- Full-audit manifest head SHA: `7878b3fd23345875465e79f819f4122e76f67647`.
- Full-audit artifact ID: `8793008660`.

## Final implementation summary

The implementation head moved from the initial reviewed head `b69436a0` to `7878b3fd` through three review-aligned commits:

1. `6503db11` — Expanded the Member Savings dark accessibility scan from a single `.text-emerald-700` selector to a full-page audit, matching the canonical dark accessibility pattern used by sibling specs.
2. `89d65b20` — Fixed dark-mode color-contrast on the Member Savings page by adding `dark:text-zinc-400` companions to labels, table headers, and empty states that used `text-zinc-500` without a dark variant. The expanded dark scan surfaced these once it covered the whole page.
3. `7878b3fd` — Adopted four canonical desktop baselines (dashboard, dues index, dues ledger, operator dashboard) after proving byte-identical actuals across two independent full audits. Tolerance and masks are unchanged.

## CI evidence

| Metric | Value |
| --- | --- |
| CI run | `30629660404` |
| CI head SHA | `7878b3fd23345875465e79f819f4122e76f67647` |
| CI conclusion | success |
| Change Classification | success |
| Dependency Audit | success |
| Migration and Seed | success |
| Frontend Build | success |
| PostgreSQL Concurrency | success |
| Generated Drift | success |
| PHPUnit Parallel | success |
| OpenAPI Drift | success |
| Pint | success |
| PHPUnit tests | 1446 |
| PHPUnit assertions | 9458 |
| PHPUnit skipped | 5 |

## Full UI Audit evidence

| Metric | Value |
| --- | --- |
| Full audit run | `30629662394` |
| Artifact ID | `8793008660` |
| Artifact head SHA | `7878b3fd23345875465e79f819f4122e76f67647` |
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
| Expired findings | 0 |
| Invalid selectors | 0 |
| Duplicate tracking IDs | 0 |

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

### Route coverage

| Metric | Value |
| --- | ---: |
| Audited routes | 61 |
| Uncovered routes | 0 |

## Baseline policy

- Visual tolerance: unchanged.
- Global screenshot masks: none.
- Text/state masks: none.
- New waivers: none.
- Canonical retry hashes: four desktop baselines adopted after byte-identical actuals across two independent full audits (runs `30626306598` and `30628474393`).

## PR compare

The last successful PR compare run was `30628464091` (`89d65b20`, compare/desktop, success). On the final implementation SHA `7878b3fd`, compare/desktop smoke runs (`30629648065`, `30640006898`) failed the same four font-heavy desktop screens due to pre-existing systemic runner font-rasterization drift. The authoritative Full UI Audit (`30629662394`, full/all/all) passed all 219 screens including those four, confirming the rendering is canonical.

## Member Savings accessibility

- UI-A11Y-082: removed from the registry.
- Light full-page audit: 0 critical, 0 serious.
- Dark full-page audit: 0 critical, 0 serious.
- New waiver: none.
- Remaining contrast violation: none.

## Historical failed audit

The following run is retained for history only and is no longer current evidence:

- Full UI Audit run `30447414712` — failure (head `d2aa70ee`, 18 visual failures, 4 new serious accessibility findings).

The prior evidence document revisions that referenced `d2aa70ee` / `30447414712` as current evidence are superseded by the final runs above.
