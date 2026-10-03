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

Four runner shards remain, with the existing four ParaTest workers per shard.
Deterministic greedy LPT uses a committed JUnit timing manifest, source hashes
and conservative static fallback for new or changed files. Invalid/missing
manifests fail closed. The original bootstrap manifest was explicitly an
estimate; iteration 2 replaces it with all 305 files measured by successful
PHPUnit shards in CI #538. That workflow failed Phase 4 input equivalence and
is not an acceptance run. JUnit sums are worker time across parallel processes,
not wall time; the original parallel command/process count remains unchanged.

Within each measured shard, generated XML preserves descending runtime order
with path ties, so expensive files reach ParaTest's queue first. Previously
alphabetical execution could leave a heavy file late in the worker queue.
Static-only helper use retains its original alphabetical output. There is no
random order, mutable cache or external timing service. The collector rejects
failed/skipped/duplicate/malformed/incomplete JUnit reports. Changed CI test
files keep their original recorded hashes and safely use static fallback.

Pilot shard jobs were 15m14s / 25m42s / 17m25s / 30m05s. PHPUnit aggregation
passed 3,442 tests / 28,340 assertions, zero errors/failures/skips, 81.40%
coverage. The full failed workflow took 32m12s and is retained as evidence.
Measured LPT totals are almost equal at about 4.85 million worker milliseconds
per runner; five/six shards also balance but introduce extra runner/setup/
artifact cost. Four plus heavy-first worker scheduling is selected for the
next controlled full measurement before spending more runner capacity.

The largest measured worker-time files are SEED-09 SeedIntegrityGateTest
(851.666s), RoleSmokeTest (566.015s), CooperativeResetTestDataCommandTest
(546.182s), SensitiveEmployeeFileStorageTest (541.400s), and
ErpPayrollOrganizationIsolationTest (516.674s). These contain repeated
application/database fixture setup; their validations remain intact. Values
are JUnit worker time, not observed runner duration.

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
  passes 33 tests / 300 assertions, including heavy-first ordering; no warnings, failures or skips.
- `php bin/ci/phpunit-shard verify --total=4`: 305 canonical files assigned,
  zero missing/duplicates. Helper regressions also cover 2/4/5/6 partitions.
- Before/after PHPUnit catalog: 3,427 -> 3,442 IDs, missing zero, duplicates zero.
- `vendor/bin/pint --dirty --format agent` and explicit PHP CI helper formatting:
  PASS. YAML parse and `git diff --check`: PASS.
- Source-bound frontend create/verify-generated: PASS; tampering/wrong SHA,
  missing/extra/private/symlink and changed generation are regression-tested.

## Performance validation status

Iteration 1 full CI #538 completed FAIL in 32m12s; Phase 4 failed closed at the
frontend input-equivalence check. This pilot cannot qualify as PASS. The
Playwright-specific build is restored for iteration 2. Estimated balance is not a
performance acceptance claim. Iteration 2 aims at approximately 23–27 minutes
with measured weights and heavy-first worker scheduling; actual JUnit timings will drive the
next refinement within the maximum three iterations. No success will be
claimed until all mandatory full jobs finish and measured improvement reaches
at least 20%. Final results and exact control identity will be appended here.
