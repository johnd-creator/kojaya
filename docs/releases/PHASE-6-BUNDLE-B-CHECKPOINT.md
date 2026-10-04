# Phase 6 — Bundle B server checkpoint

This checkpoint supersedes the prior pending server statuses while retaining their failure history. Android and physical-device acceptance remain assigned to the development PC.

```
PREVIOUS_QA_CANDIDATE_SHA=5a5ae698259d465bc5b5265fe14ca2580c9eb922
HISTORICAL_RC13_SHA=4607ae5db36e5d6909572ac81f6bb95c727b5d21
REPLACEMENT_DESIGNATION=v1.0.0-rc.14 (untagged)
REPLACEMENT_APPLICATION_CANDIDATE_SHA=043b5b004d66f04e60eef2b0c7a8cdb38fce278a
PROMOTION_STATUS=PROMOTED_QA_CANDIDATE
PROMOTED_QA_CANDIDATE_SHA=043b5b004d66f04e60eef2b0c7a8cdb38fce278a
REPAIR_ITERATIONS=3
QA_06=REVALIDATION_PENDING
QA_07=NOT_EXECUTED
QA_08_BACKEND_PREREQUISITE=REVALIDATION_PENDING
QA_09_BACKEND_PREREQUISITE=REVALIDATION_PENDING
ANDROID_QA_08=NOT_EXECUTED_ON_QA_SERVER
PHYSICAL_DEVICE_QA_09=NOT_EXECUTED_ON_QA_SERVER
BUNDLE_B=INCOMPLETE
PUBLIC_QA_TRAFFIC=HELD
```

The first runtime repair made tracked user-guide articles readable under the existing deployment permissions contract. The second aligned five paginated member response schemas with the existing API payloads. The third applies the private shared file-creation mask only to QA PHP-FPM requests; CLI and other environments preserve their masks and existing deployment ownership guards remain unchanged.

The initial member HTTP 500, queue replay failure, and two pre-migration rc.13 deployment failures remain retained. Neither failed cutover started migrations. Financial acceptance has not yet run. Exact-SHA CI has passed and the owner-authorized bounded recovery promotes this candidate. Hardened deployment and applicable runtime gates remain required before member and financial revalidation.

[Repair PR #104](https://github.com/johnd-creator/kojaya/pull/104), [repair PR #106](https://github.com/johnd-creator/kojaya/pull/106), [PR CI #549](https://github.com/johnd-creator/kojaya/actions/runs/37196118601), and [UI audit #315](https://github.com/johnd-creator/kojaya/actions/runs/37196118626) passed the applicable source checks. PR CI #549 passed all 18 mandatory jobs with 3,451 tests, 28,445 assertions, zero errors/failures/skipped tests and 81.42% line coverage against the unchanged 60% gate. Exact-candidate [CI #550](https://github.com/johnd-creator/kojaya/actions/runs/37197521587) passed all 18 mandatory jobs with the same complete test totals and 81.41% line coverage against the unchanged 60% gate.

Only minimum synthetic QA fixtures are authorized. Secrets, device tokens, detailed operational configuration and personal data are excluded from this checkpoint. Production and legacy data remain prohibited. The development-PC workspace is `F:\kojayaapp`; its Android SHA/build and physical-device reception remain unverified. Bundle B cannot pass before those gates complete.
