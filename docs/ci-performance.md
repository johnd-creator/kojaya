# CI-PERF-02 — full CI execution performance

Assessment date: 2026-10-04 (Asia/Jakarta).

Scope: CI workflow, CI helpers and their tests/documentation only. Bundle A
remains CLOSED PASS. Bundle B is NOT EXECUTED. The promoted QA application
candidate remains untagged rc.12, exact SHA
`5a5ae698259d465bc5b5265fe14ca2580c9eb922`. No QA deployment, database,
production, legacy, application/runtime or dependency change is authorized here.
Repository/control baseline is `3cf66febb83549eb4c8eb1aaf53382b4d5f59b74`.

## Measured successful full baselines

Documentation-only runs are excluded. GitHub run creation-to-completion time,
job boundaries and individual step timestamps were inspected for all three
successful full application runs.

| Full run | Wall time | Shard jobs 1 / 2 / 3 / 4 | Readiness job |
| --- | --- | --- | --- |
| [CI #531](https://github.com/johnd-creator/kojaya/actions/runs/37125296562), primary | 43m 29s | 36m14s / 20m44s / 22m05s / 18m54s | 5m17s |
| [CI #530](https://github.com/johnd-creator/kojaya/actions/runs/37122719074) | 44m15s | 36m33s / 32m00s / 22m01s / 28m27s | 5m48s |
| [CI #523](https://github.com/johnd-creator/kojaya/actions/runs/37029602498) | 44m23s | 37m19s / 26m45s / 34m05s / 28m54s | 4m12s |

Primary critical path: classification/build/startup (80 seconds), shard 1
(2,174 seconds), gap + PHPUnit aggregation (34 seconds), gap + Phase 4
execution (320 seconds), workflow completion (1 second). Total 2,609 seconds.
Primary PHPUnit execution steps were 2,153 / 1,223 / 1,302 / 1,113 seconds.
Composer installs were generally 5–14 seconds; npm installs 5–10 seconds;
artifact transfers 0–3 seconds. These are small beside test execution.
PostgreSQL took 6m27s, with concurrency 104 seconds, Document05 223 seconds,
and the independent backup/restore drill 4 seconds; all remain required.

Pint duration is repeatable work, not just setup: CI #531 actual Pint 424 seconds
versus checkout 4, PHP 6 and Composer 9; CI #530 Pint 309 seconds and CI #523
roughly six minutes. Whole-repository formatting checks remain mandatory.

Baseline aggregation: 3,427 tests, 28,150 assertions, zero skips/errors/failures;
combined line coverage 81.39%, required >=60%; minimum test threshold 2,211.
Canonical discovery: 302 eligible files. The original partition assigned
75 / 75 / 76 / 76 files and nearly equal static weights (45,724 / 45,722 /
45,761 / 45,762), despite the large measured runtime imbalance. Before/after
catalog comparison
preserves all 3,427 original testcase IDs; the initial change adds 15 cases.
No original test, assertion, suite, exclusion, coverage or quality threshold
is removed. A duplicate local timing profile was stopped without a PASS claim;
the successful full CI baselines remain authoritative.

## Execution design and trust boundaries

Final iteration selects six standard runner shards, retaining the existing four
ParaTest workers per runner. Deterministic greedy LPT uses a committed JUnit
manifest with source hashes and conservative static fallback for new or changed
files. Invalid/missing manifests fail closed. The original bootstrap manifest
was labelled an estimate. Final weights are the complete 305-file observations
from successful full validation CI #539; its wall time missed performance
acceptance. JUnit sums are parallel worker time, not runner wall time.

Generated XML preserves descending runtime order with path ties, so expensive
files reach ParaTest's existing queue first. Static-only helper use retains
alphabetical output. No random order, mutable runtime cache or external timing
service chooses partitions. The collector rejects failed/skipped/duplicate/
malformed/incomplete reports. Changed CI files retain their recorded hashes
and receive conservative fallback until measured again.

`PHPUNIT_SHARD_COUNT` is the only workflow count setting. The classifier emits
validated count/matrix outputs; `fromJSON` creates the matrix and every MECE,
configuration and aggregation command uses that same output. Empty/malformed/
unbounded counts fail closed. The existing `PHPUnit Parallel` and final
`Phase 4 Readiness Gate` required check names are retained.

| Optimization iteration | Full run | Wall | Shard jobs | Result |
| --- | --- | --- | --- | --- |
| 1: calibrated estimate, four shards | [#538](https://github.com/johnd-creator/kojaya/actions/runs/37161183209) | 32m12s | 15m14s / 25m42s / 17m25s / 30m05s | FAIL: Phase 4 input equivalence; PHPUnit PASS |
| 2: measured weights/order, four shards | [#539](https://github.com/johnd-creator/kojaya/actions/runs/37163301328) | 36m09s | 31m12s / 30m43s / 33m25s / 20m33s | All 16 jobs PASS; only 16.86% improvement, target unmet |
| 3: latest complete measurements, six shards | pending | pending | pending | No acceptance claim until completed |

Both earlier runs remain in the evidence; no favorable run is cherry-picked.
Iteration 2 passed 3,442 tests / 28,347 assertions, zero errors/failures/skips,
81.40% coverage. Phase 4 execution took 5m41s in parallel; final readiness
only 7 seconds. Its UI/a11y audit passed 105 checks. Generated Drift took
26 seconds versus 66 baseline; whole-repository Pint 45 versus 447 seconds.

The latest corpus totals 27,275,148 worker milliseconds. Modelling the existing
four-worker queues gives longest estimated execution of 28m35s with four
shards, 22m52s with five, and 22m04s with six. These estimates exclude startup,
transfer, merge and runner variance and are not measured acceptance. Six adds
headroom for peer-runner variance; marginal improvement over five is bounded
by the largest indivisible file. Additional setup is two short ~20-second
runner preparations and two artifact transfers, which run concurrently.
Full job count becomes 18, within the documented standard Free-plan ceiling
of 20 concurrent jobs; actual account-wide queueing remains part of wall time.
No plan, policy, runner type or paid infrastructure changes. Reference:
[GitHub Actions limits](https://docs.github.com/en/actions/reference/limits).

The largest latest measured worker-time files are SEED-09 SeedIntegrityGateTest
(1,323.857s), PosTransactionVoidOrganizationIsolationTest (852.826s),
CooperativeReportsOrganizationIsolationTest (796.155s), CooperativeFeatureTest
(780.288s), and CooperativeResetTestDataCommandTest (765.437s). These contain
repeated application/database fixture work, and all validations remain intact.
The observed corpus increased ~41% between runs, so forecasts remain uncertain
and the final full measurement decides acceptance.

MECE covers every canonical file once. The existing aggregate merges raw
Xdebug coverage, enforces zero skips/failures/errors, >=2,211 tests and >=60%
line coverage. Xdebug and source filters are unchanged. Actual merge regression
covers three/five/six artifacts, plus real below-threshold failure.

Phase 4 execution depends only on classification and the exact frontend build,
running alongside shards, PostgreSQL and all other jobs. The existing required
`Phase 4 Readiness Gate` becomes the final cheap decision; `always()` and the
standalone tested helper reject every failed/cancelled/skipped/missing upstream,
including Phase 4 execution. Documentation-only decisions also require every
mandatory job to succeed. Unknown comparisons choose full CI. Unknown result
classification cannot pass final readiness. `PHPUnit Parallel` keeps its name.

Frontend artifact consumers verify exact source and every public asset digest.
A public-only provenance ledger binds the tested SHA, assets and generated
Wayfinder inputs. Local controlled testing/playwright builds had identical
333-file digests, but iteration 1's CI input check correctly rejected reuse
because the explicit Playwright Wayfinder command regenerated inputs without
the Vite plugin's form variants (`formVariants: true` / `--with-form`).
Reproducing the workflow order locally confirmed the digest difference; the
simplified local builds did not reproduce that intermediate state. Iteration 2 retains the independent Playwright-environment
build and its route generation; no integrity check is bypassed to permit reuse.
This small build runs in parallel and does not extend the PHPUnit critical path.
Generated Drift still independently checks tracked-file policy and generates
routes; its redundant Node install/build is removed. Both required frontend
build paths and the final readiness dependency remain enforced.

npm download caching is enabled through setup-node with package-lock binding;
npm ci still verifies locked dependencies. Composer download preparation is
unchanged because measured install cost is small. No vendor, environment,
APP_KEY, credential, backup or database artifact is cached or uploaded.
Frontend payload is public build files and public checksum/source metadata only.
Chromium, system dependencies and deterministic font setup remain unchanged:
their measured ~35–37 seconds no longer sit on the serial critical path.
Pint uses its existing two-process option and still checks the whole repository.

## Initial local validation

- Isolated PHP 8.4, phpunit.xml forces APP_ENV=testing and SQLite :memory:;
  no QA/shared DB used. A temporary extracted SQLite extension is local only.
- `php artisan test --compact tests/Unit/Ci tests/Feature/Phase4ReadinessGateTest.php`:
  33 tests / 293 assertions PASS initially; the retained-build refinement
  passes 33 tests / 300 assertions, including heavy-first ordering. The
  six-shard count refinement passes 34 tests / 324 assertions; no warnings, failures or skips.
- `php bin/ci/phpunit-shard verify --total=4`: 305 canonical files assigned with both four and six shards,
  zero missing/duplicates. Helper regressions also cover 2/4/5/6 partitions.
- Before/after PHPUnit catalog: 3,427 -> 3,442 IDs initially, then 3,443
  with matrix validation; missing zero, duplicates zero.
- `vendor/bin/pint --dirty --format agent` and explicit PHP CI helper formatting:
  PASS. YAML parse and `git diff --check`: PASS.
- Source-bound frontend create/verify-generated: PASS; tampering/wrong SHA,
  missing/extra/private/symlink and changed generation are regression-tested.

## Performance validation status

Iterations 1 and 2 are fully recorded above. The third and final optimization
iteration uses six shards, the newest complete timing observations, unchanged
four-worker execution and dynamic aggregate counts. Local validation and the
real full run will be recorded before any PASS claim or merge. The maximum is
three code optimization iterations. QA candidate, deployment and data remain
unchanged; Bundle B is NOT EXECUTED.
