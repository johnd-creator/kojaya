# Admin Koperasi Phase 1 — Baseline Review

## Final evidence

- Implementation evidence head: `d2aa70eedd33e8ca66e1f5943cce37a574e696f9`
- CI run: `30447412317` — success
- PR UI compare run: `30447394702` — success
- Full UI Audit run: `30447414712` — completed with scoped legacy visual debt
- Full-audit manifest head: `d2aa70eedd33e8ca66e1f5943cce37a574e696f9`

## Visual coverage

The final full audit generated 219 visual entries:

| Viewport | Expected | Passed | Failed | Skipped |
| --- | ---: | ---: | ---: | ---: |
| Desktop | 89 | 89 | 0 | 0 |
| Tablet | 73 | 63 | 10 | 0 |
| Mobile | 57 | 49 | 8 | 0 |

All Admin Koperasi dashboard, members, payments, dues, ledger, loans, loan types, and points states pass across their covered viewports. The remaining 18 failures are existing non-Admin responsive baselines: System Admin cooperative pages, member portal pages, and operator/payment/points pages. They are outside this Admin correction pass and were not hidden with masks, waivers, or tolerance changes.

## Accessibility and runtime

- Admin accessibility scenarios: 0 new critical/serious findings and 0 stale findings, including Loan Types create, edit, and delete dialogs.
- Full artifact runtime reports: 227.
- Console errors: 0.
- Page errors: 0.
- Failed important requests: 0.
- Unexpected responses: 0.
- Full-artifact accessibility debt outside Admin scope: 4 new serious findings and 18 stale legacy findings; no new waiver was added.

## Baseline policy

- Canonical captures were reviewed from the Ubuntu GitHub workflow artifact.
- Responsive Admin captures were repeatable across clean retries; each adopted state had identical retry hashes.
- Desktop Admin and shared Loan Types captures were adopted from repeatable CI actuals.
- Missing baselines: 0.
- Orphan baselines: 0.
- Duplicate baselines: 0.
- Invalid dimensions: 0.
- Visual tolerance: unchanged.
- Global screenshot masks: none.
- Text/state masks: none.
- Loading, error, 403, and wrong-role captures: not accepted.

## Local verification

- Focused PHPUnit: 90 passed, 2,309 assertions.
- Full PHPUnit: 1,441 passed, 5 skipped, 9,458 assertions.
- Frontend role/queue/payment/contribution tests: 16 passed.
- Admin accessibility scenarios: 9 passed.
- Frontend build: passed.
- Pint: 1,261 files passed.
- UI route coverage: 0 uncovered routes.

The final documentation commit follows the implementation evidence head above and does not change application behavior or visual output.
