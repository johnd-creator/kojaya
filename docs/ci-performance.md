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
Canonical discovery: 302 eligible files. Before/after catalog comparison
preserves all 3,427 original testcase IDs; the initial change adds 15 cases.
No original test, assertion, suite, exclusion, coverage or quality threshold
is removed. A duplicate local timing profile was stopped without a PASS claim;
the successful full CI baselines remain authoritative.

## Execution design and trust boundaries

Four shards are retained for the first pilot. Deterministic greedy LPT uses a
committed manifest, content hashes and conservative static fallback for new or
changed files. Invalid/missing manifests fail closed. The bootstrap manifest
is explicitly an estimate: measured CI #531 shard durations apportioned by
old static file weights, not claimed per-file measurements. The pilot publishes
sanitized real JUnit per-file timing data for controlled refinement. The next
manifest can be generated with `php bin/ci/phpunit-timings`, which rejects
unsuccessful, skipped, duplicate, malformed or incomplete input. No runtime
network or mutable external timing service controls the partition.

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

Frontend build reuse is exact-source and checksum verified. Local controlled
builds under testing and playwright produced identical digests for all 333
files. A public-only provenance ledger binds the GitHub tested SHA, all asset
hashes and generated Wayfinder inputs. Every consumer verifies source/digests;
Playwright additionally compares freshly generated inputs before reuse. New
Vite environment inputs cannot silently diverge: helper regressions compare
all configured public Vite values for testing/playwright. Generated Drift
still independently checks tracked-file policy and generates routes; its
redundant Node install/build is removed. The one mandatory frontend build
remains required by final readiness.

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
  33 tests / 293 assertions PASS; no warnings, failures or skips.
- `php bin/ci/phpunit-shard verify --total=4`: 305 canonical files assigned,
  zero missing/duplicates. Helper regressions also cover 2/4/5/6 partitions.
- Before/after PHPUnit catalog: 3,427 -> 3,442 IDs, missing zero, duplicates zero.
- `vendor/bin/pint --dirty --format agent` and explicit PHP CI helper formatting:
  PASS. YAML parse and `git diff --check`: PASS.
- Source-bound frontend create/verify-generated: PASS; tampering/wrong SHA,
  missing/extra/private/symlink and changed generation are regression-tested.

## Performance validation status

Iteration 1 pilot is pending a real full CI run. Estimated balance is not a
performance acceptance claim. Expected critical path is ~26–30 minutes if the
bootstrap estimate is representative; actual JUnit timings will drive the
next refinement within the maximum three iterations. No success will be
claimed until all mandatory full jobs finish and measured improvement reaches
at least 20%. Final results and exact control identity will be appended here.
