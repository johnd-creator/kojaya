# FUNC-12 — Full Regression Suite & Release Evidence

## BASELINE

- Repository: `johnd-creator/kojaya`
- Branch: `test/func-12-full-regression`
- Base: `main` at `4dca014d74886c0ba6652d7e590ba8f7444d120d` (verified equal to `origin/main`; descendant of the requested authoritative SHA).
- Tested PR HEAD: `6f73167c20eb9140f0d784c861fa846fb3de1940`.
- Authoritative exact-head CI: **#455 PASS**.
- Evidence captured: 2026-09-24, local time WIB.
- Environment: Linux; PHP 8.5.10 CLI; Node 25.6.1; SQLite for canonical tests and isolated migration/seed; PostgreSQL client/extension present but no local PostgreSQL server; Chromium/Playwright available.
- Canonical PHPUnit inventory: 293 eligible test files.
- Production changes: none.

## PHASE 4 FOCUSED REGRESSION

- FUNC-02 through FUNC-11 dedicated suites plus `Phase4FullRegressionInventoryTest`: **236 tests, 2,314 assertions, 0 failures, 0 errors, 0 skips**; PASS.
- The inventory test asserts all ten dedicated functional evidence files exist and confirms PostgreSQL test-suite names and concurrency-file ownership in `phpunit.pgsql.xml`.

## SQLITE FULL REGRESSION

Four generated shard configurations ran via `php artisan test --compact --parallel`:

| Shard | Files | Estimated tests in summary | Executed tests | Assertions | Errors | Failures | Skipped | Result |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| 1 | 73 | 819 | 858 | 5,738 | 0 | 0 | 0 | PASS |
| 2 | 73 | 741 | 793 | 12,268 | 0 | 0 | 0 | PASS |
| 3 | 73 | 796 | 803 | 4,586 | 0 | 0 | 0 | PASS |
| 4 | 74 | 842 | 820 | 4,605 | 0 | 0 | 0 | PASS |
| **Total** | **293** | **3,198** | **3,274** | **27,197** | **0** | **0** | **0** | **PASS** |

The generated configs and temporary `build/func12` files are not release inputs and are not committed.

- Local coverage aggregation: **NOT AVAILABLE** (`xdebug` and `pcov` are absent). No coverage workaround or threshold change was made.
- Exact-head CI coverage: **81.40%**, above the **60.00%** required threshold; aggregate result PASS.

## SHARD MECE

- `php bin/ci/phpunit-shard verify --total=4`: zero missing files, zero duplicate files; pairwise disjoint; union equals canonical inventory.
- Result: **100% mutually exclusive & collectively exhaustive**.
- `php bin/ci/phpunit-shard summary --total=4`:

| Shard | Files | Estimated tests | Lines | Weight |
| --- | ---: | ---: | ---: | ---: |
| 1 | 73 | 819 | 24,096 | 44,571 |
| 2 | 73 | 741 | 26,049 | 44,574 |
| 3 | 73 | 796 | 24,670 | 44,570 |
| 4 | 74 | 842 | 23,558 | 44,608 |

## POSTGRESQL REGRESSION

- `PostgreSQLConcurrency`, `Document05PostgreSQL`, and `BackupRestoreDrill` were not executable locally: `pg_isready -h 127.0.0.1 -p 5432 -d kojaya_test -U kojaya` reported no response.
- The PostgreSQL PHPUnit configuration targets the isolated `kojaya_test` database. No SQLite substitution and no shared/development database were used.
- Exact-head CI #455: `PostgreSQLConcurrency` **26 tests / 375 assertions PASS**; `Document05PostgreSQL` **71 tests / 393 assertions PASS**; `BackupRestoreDrill` **1 test / 20 assertions PASS**.
- Result: **Local PostgreSQL = BLOCKED_BY_ENVIRONMENT; authoritative exact-head CI PostgreSQL = PASS**.

## MIGRATION / SEED

- `migrate:fresh --seed --force` passed against a newly created temporary SQLite database at `database/func12.sqlite`; the database was removed after evidence capture.
- `tests/Feature/SEED09/SeedIntegrityGateTest.php`: **41 tests, 507 assertions, PASS**.
- No shared database was reset or seeded.

## FRONTEND BUILD

- `npm ci --prefer-offline --no-audit`: PASS.
- `npm run build`: PASS; Vite transformed 3,904 modules. Existing CSS optimizer emitted a non-fatal warning for selector `article#article-*`.

## GENERATED DRIFT

- `php artisan wayfinder:generate`: PASS.
- `resources/js/actions` and `resources/js/routes` remained generated/untracked; no generated route/action drift is included.
- Rebuild after generation: PASS.

## OPENAPI

- `bin/openapi.sh check`: PASS; snapshot is up to date.

## DEPENDENCY AUDIT

- Composer: PASS under the CI command `composer audit --locked --ignore-severity=medium --ignore-severity=low --abandoned=report`; 7 medium/low advisories are ignored by the existing threshold. No high-severity blocker reported.
- NPM production dependencies: PASS under `npm audit --omit=dev --audit-level=high`; one moderate `qs` advisory is below the existing high threshold.

## PINT / DIFF

- `vendor/bin/pint --dirty --test`: PASS.
- `git diff --check`: PASS.

## UI AUDIT

- `npm run ui:test-global-setup`: PASS; route coverage found 81 GET routes, 64 renderable/audited, 17 explicitly excluded, zero uncovered or stale entries, and zero duplicate screen IDs.
- `npm run ui:verify-baselines`: PASS; 234 baseline files valid, missing 0, orphan 0, duplicate 0, invalid dimensions 0.
- Desktop all-route visual compare: **77 passed, 99 visual mismatches of 176**; local metadata also reported `base_sha = unknown`. Classify this as **LOCAL UI ENVIRONMENT VARIANCE / NON-AUTHORITATIVE LOCAL RESULT**; no snapshots were refreshed. Deterministic prior evidence: Kojaya UI Audit #282 PASS for PR #85 HEAD `803de19e0b4454649874ef08bbf3e48231cadfef`, whose Git tree is identical to current main merge commit `4dca014d74886c0ba6652d7e590ba8f7444d120d`.
- PR #86 changes no UI source, CSS, visual baselines, routes, or UI seed. Its visual mismatch result is not a FUNC-12-introduced production regression.
- `npm run ui:a11y`: 156 passed, 3 failed, 144 skipped. Local findings: `pos-closings-index-default`, `pos-inventory-counts-index-default`, and `savings-withdrawals-index-default` on desktop. Preserve these as local accessibility findings for FUNC-13 disposition; no accessibility rules were disabled and they are not attributed to PR #86.

## CRITICAL JOURNEYS

Automated evidence is mapped to the four journeys actually defined in FUNC-01; this is not a claim that one browser test covers each entire journey end to end.

1. **Member Onboarding & Profile Completion** — Entry and login/access redirects: `AuthenticationAndAccessFunctionalTest`; member onboarding eligibility, submission, lifecycle constraints, and profile ownership/updates: `MemberLifecycleAndProfileFunctionalTest`; staff authorization, validation/revision/final approval and maker-checker restrictions: `AdminMemberManagementFunctionalTest`. Negative lifecycle and authorization paths are asserted.
2. **Monthly Dues Generation & Midtrans Payment** — Staff generation and duplicate-period behavior, member invoice/intent ownership, signed/invalid webhook transitions, exact ledger credit, receipt creation, replay handling, manual payment, approval, rollback, and outbox retry: `ContributionsDuesPaymentsFunctionalTest`. Payment intent and charge recovery paths are also in `PaymentChargeRecoveryTest`.
3. **POS Cashier Shift, Store-Credit Sale & Daily Closing** — Shift and POS authorization, sale validation/stock effects, void/return and close/duplicate-close behavior: `PosCashierFunctionalTest`; credit boundary, ledger debit, delegate attribution, replay, and atomic rejection: `StoreCreditFunctionalTest`; PostgreSQL checkout/closing concurrency ownership is retained in `phpunit.pgsql.xml` and passed in exact-head CI #455 (local PostgreSQL was unavailable).
4. **Loan Maker-Checker Application, Disbursement & Payoff** — Member/staff application entry, authorization, calculator validation, manager review, Pengurus approval, disbursement ledger effect, installment repayment, payoff, replay, and negative state/role cases: `LoanFunctionalTest`.

## KNOWN PARTIAL FINDINGS

- PAY-006 remains operationally PARTIAL until production runs `payments:migrate-proofs-to-private --execute`, verifies the result, and disables `PAYMENT_PROOF_LEGACY_PUBLIC_FALLBACK`.
- EDGE-001 remains PARTIAL: different-item POS lock ordering/deadlock evidence is uncovered.
- EDGE-002 remains PARTIAL: delegate-attributed concurrent store-credit checkout is uncovered.
- EDGE-004 remains PARTIAL: domain-level duplicate protection after the one-day middleware response cache expires is unverified.
- EDGE-005 remains PARTIAL: no authoritative cooperative bank-destination configuration exists for a truthful automatic manual-transfer fallback.
- EDGE-006 remains PARTIAL: POS and loan rollback failure-injection cases remain uncovered.
- EDGE statuses were not promoted based on this regression run. FUNC-01 no longer cites transient CI run `#452` for EDGE-001/EDGE-002; stable test and suite names are recorded instead.

## NEW REGRESSIONS

- No production regression was introduced by PR #86; it changes no production or UI files.
- Local screenshot comparisons are non-authoritative environment variance; deterministic prior UI Audit #282 passed on a tree identical to current main.
- Three local accessibility findings are preserved for FUNC-13 disposition.
- Local PostgreSQL is environment-blocked, superseded by exact-head CI #455 PostgreSQL PASS.

## RELEASE EVIDENCE STATUS

**FUNC-12 PASS — EXACT-HEAD CI VERIFIED — FUNC-13 DISPOSITION PENDING**

Exact-head CI #455 passed the SQLite shard aggregate (3,274 tests, 27,197 assertions, zero errors/failures/skips), 81.40% line coverage, and all PostgreSQL suites. Local migration/seed, frontend build, OpenAPI, generated drift, dependency thresholds, Pint, diff, and UI baseline integrity also passed. Local PostgreSQL unavailability and non-authoritative visual variance are documented; accessibility findings remain for FUNC-13 disposition. Existing PAY-006 and EDGE PARTIAL findings are unchanged. This status is not a release approval; FUNC-13 owns that decision.
