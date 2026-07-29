# Admin Koperasi Phase 1 — Baseline Review

## Gate status

- Scoped Admin evidence: **PASS**.
- Repository full audit: **FAIL**.
- Phase 1 final gate: **NOT PASSED**.
- Implementation evidence head: `d2aa70eedd33e8ca66e1f5943cce37a574e696f9`.
- CI run: `30447412317` — success.
- PR UI compare run: `30447394702` — success.
- Full UI Audit run: `30447414712` — failure.
- Full-audit manifest head: `d2aa70eedd33e8ca66e1f5943cce37a574e696f9`.

The failed full audit is not final success evidence. This document will be updated with the exact final head, run IDs, and artifact metrics only after a new full audit succeeds.

## Failed-audit review

The failed artifact contains 18 responsive visual failures. Every expected, actual, and diff image was reviewed. None showed a loading page, application error, 403 response, or incorrect authenticated role.

| Screens | Viewport | Classification | Decision |
| --- | --- | --- | --- |
| Dues, Ledger, Loans, Members, Payments | Mobile and tablet | Intentional shared responsive UI change | Capture and adopt only repeatable canonical candidates. |
| Member dashboard, Savings, Transactions | Mobile and tablet | Intentional deterministic member operational presentation | Capture and adopt only repeatable canonical candidates. |
| Operator dashboard | Tablet | Intentional dashboard/responsive change | Capture and adopt only repeatable canonical candidate. |
| Loans | Mobile and tablet | Prior CI deterministic rendering drift | Local clean candidate matched the existing baseline; retain baseline and revalidate in the final audit. |
| Points | Tablet | Prior CI deterministic rendering drift | Local clean candidate matched the existing baseline; retain baseline and revalidate in the final audit. |

The 18 candidates were captured twice on the canonical local Ubuntu environment. All paired captures had identical hashes. No tolerance, global mask, text/state mask, loading state, error state, 403 state, or wrong-role screenshot was accepted.

## Failed-audit metrics

| Viewport | Expected | Passed | Failed | Skipped |
| --- | ---: | ---: | ---: | ---: |
| Desktop | 89 | 89 | 0 | 0 |
| Tablet | 73 | 63 | 10 | 0 |
| Mobile | 57 | 49 | 8 | 0 |

The same failed artifact reported four new serious accessibility findings and 18 stale known findings. They are correction work, not accepted debt. Runtime reports in that artifact had zero console errors, page errors, failed important requests, and unexpected responses.

## Baseline policy

- Missing baselines: 0.
- Orphan baselines: 0.
- Duplicate baselines: 0.
- Invalid dimensions: 0.
- Visual tolerance: unchanged.
- Global screenshot masks: none.
- Text/state masks: none.

## Pending final evidence

Before this gate can pass, the correction head must have a successful CI run, PR UI compare, and a manual Full UI Audit (`mode=full`, `viewport=all`, `scope=all`). The final evidence will record the exact SHA, run IDs, visual counts, accessibility counts, runtime counts, and canonical retry hashes.
