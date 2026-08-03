# Responsive Baseline Refresh Review

## Decision

The responsive baselines were refreshed from canonical Ubuntu 24.04 workflow captures after the existing responsive comparison failures were reproduced deterministically. The full-page screenshot contract, viewport dimensions, route/state registry, screenshot threshold, `maxDiffPixelRatio`, and accessibility policy were unchanged.

- Previous head before refresh: `7ef612b222d0e3e68cd229d9da2a6d7a2f628740`
- Resulting baseline-refresh head: the focused commit containing this review and the 86 reviewed PNG replacements; the exact SHA is recorded in the final PR evidence.
- Failed full audit: `30787970382`
- Capture A: `30790075430`
- Capture B: `30790413737`

## Evidence

The failed audit had 172/172 expected/generated entries, with 72/72 desktop passing, 14/59 tablet passing, and 0/41 mobile passing. Runtime and accessibility evidence was clean, and all 86 first-attempt responsive screenshots matched their retry screenshots byte-for-byte.

Capture A and Capture B each produced exactly 172 candidates: 72 desktop, 59 tablet, and 41 mobile. Both artifacts tested head `7ef612b222d0e3e68cd229d9da2a6d7a2f628740`, used workflow dispatch with `pull_request_number: null`, passed PNG integrity/full-page geometry checks, and reported zero runtime page errors, console errors, unexpected warnings, failed requests, and unexpected HTTP responses. Every corresponding candidate in A and B had identical dimensions and SHA-256 bytes.

Using the unchanged Playwright comparator (`threshold: 0.15`, `maxDiffPixelRatio: 0.001`), Capture A differed from committed baselines on exactly the 86 failed paths: 45 tablet and 41 mobile. Desktop differences were zero under the comparator; the 14 previously passing tablet paths remained accepted. Capture A candidates matched the deterministic failed-run actual screenshots byte-for-byte for all 86 paths.

Only the following baselines were replaced directly from Capture A, without resizing, cropping, recompressing, or other transformation:

- Desktop: 0
- Tablet: 45
- Mobile: 41
- Total: 86

Representative expected/canonical pairs were reviewed for dashboard, member portal, member list/detail, loans, dues/payments/ledger, POS, POS inventory, store credit detail and dialog, reports, rewards/SHU, long-form/table content, and overlay states at the available affected responsive viewports. The reviewed pairs retained the same text, values, records, controls, sections, ordering, and actions, with no clipping, horizontal overflow, hidden controls, or layout collapse.

No UI source code, screenshot tolerance, masks, full-page mode, viewport policy, route coverage, or accessibility waivers changed as part of this refresh. The baseline verifier continues to enforce PNG integrity and exact viewport width with a minimum full-page height.
