# FUNC-12 — Full Regression Suite & Release Evidence

## BASELINE

- Repository: `johnd-creator/kojaya`
- Branch: `test/func-12-full-regression`
- Base: `main` at `4dca014d74886c0ba6652d7e590ba8f7444d120d` (verified equal to `origin/main`; descendant of the requested authoritative SHA).
- Head before push: recorded in the final local handoff after commit; exact-head CI is pending.
- Evidence captured: 2026-09-24, local time WIB.
- Environment: Linux; PHP 8.5.10 CLI; Node 25.6.1; SQLite for canonical tests and isolated migration/seed; PostgreSQL client/extension present but no local PostgreSQL server; Chromium/Playwright available.
- Canonical PHPUnit inventory: 293 eligible test files.
- Production changes: none.

## PHASE 4 FOCUSED REGRESSION

- FUNC-02 through FUNC-11 dedicated suites plus `Phase4FullRegressionInventoryTest`: **236 tests, 2,314 assertions, 0 failures, 0 errors, 0 skips**; PASS.
- The inventory test asserts all ten dedicated functional evidence files exist and confirms PostgreSQL test-suite names and concurrency-file ownership in `phpunit.pgsql.xml`.

## SQLITE FULL REGRESSION

Four generated shard configurations ran via `php artisan test --compact --parallel`:

| Shard | Files | Estimated tests in summary | Executed tests | Assertions | Result |
| --- | ---: | ---: | ---: | ---: | --- |
| 1 | 73 | 819 | 858 | 5,738 | PASS |
| 2 | 73 | 741 | 793 | 12,268 | PASS |
| 3 | 73 | 796 | 803 | 4,586 | PASS |
| 4 | 74 | 842 | 820 | 4,605 | PASS |
| **Total** | **293** | **3,198** | **3,274** | **27,197** | **PASS; no skipped tests reported** |

The generated configs and temporary `build/func12` files are not release inputs and are not committed.

- Local coverage aggregation: **NOT AVAILABLE** (`xdebug` and `pcov` are absent). No coverage workaround or threshold change was made. The exact-head CI gate remains authoritative for minimum 2,211 tests and 60% coverage.

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
- Result: **LOCAL_POSTGRESQL = BLOCKED_BY_ENVIRONMENT**. Exact-head CI evidence remains pending; no remote CI result is claimed here.

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
- Desktop all-route visual compare: **77 passed, 99 failed of 176**, all failed cases report `expect(page).toHaveScreenshot(expected)` differences (example diff ratio 0.01). There was also one audit metadata error: local `base_sha` was `unknown`, rejected as an invalid SHA. Existing snapshots were not refreshed. This checkout has no production UI changes, so the screenshot failures are in the current-main UI/baseline combination, not changes introduced by FUNC-12.
- `npm run ui:a11y`: 156 passed, 3 failed, 144 skipped. Failures: `pos-closings-index-default`, `pos-inventory-counts-index-default`, and `savings-withdrawals-index-default` on desktop. No accessibility rules were disabled.

## CRITICAL JOURNEYS

Automated evidence is mapped to the four journeys actually defined in FUNC-01; this is not a claim that one browser test covers each entire journey end to end.

1. **Member Onboarding & Profile Completion** — Entry and login/access redirects: `AuthenticationAndAccessFunctionalTest`; member onboarding eligibility, submission, lifecycle constraints, and profile ownership/updates: `MemberLifecycleAndProfileFunctionalTest`; staff authorization, validation/revision/final approval and maker-checker restrictions: `AdminMemberManagementFunctionalTest`. Negative lifecycle and authorization paths are asserted.
2. **Monthly Dues Generation & Midtrans Payment** — Staff generation and duplicate-period behavior, member invoice/intent ownership, signed/invalid webhook transitions, exact ledger credit, receipt creation, replay handling, manual payment, approval, rollback, and outbox retry: `ContributionsDuesPaymentsFunctionalTest`. Payment intent and charge recovery paths are also in `PaymentChargeRecoveryTest`.
3. **POS Cashier Shift, Store-Credit Sale & Daily Closing** — Shift and POS authorization, sale validation/stock effects, void/return and close/duplicate-close behavior: `PosCashierFunctionalTest`; credit boundary, ledger debit, delegate attribution, replay, and atomic rejection: `StoreCreditFunctionalTest`; PostgreSQL checkout/closing concurrency ownership is retained in `phpunit.pgsql.xml` but blocked locally by unavailable PostgreSQL.
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

- No production change or new application-code regression was identified in this test-only phase.
- Current-main UI release evidence is not clean locally: 99 desktop visual screenshots differ from stored baselines and three desktop accessibility cases fail; local UI metadata also lacks a valid base SHA. These are recorded as release blockers for disposition; no baselines or UI code were altered.
- Local PostgreSQL execution is environment-blocked. This is not represented as a PostgreSQL test pass.

## RELEASE EVIDENCE STATUS

**HOLD — REGRESSION FOUND**

SQLite regression, shard coverage, migration/seed, build, OpenAPI, generated drift, dependency threshold, Pint, diff, and baseline integrity passed. The desktop visual compare and three accessibility tests failed, while PostgreSQL suites could not run locally. Exact-head CI is pending. FUNC-13 must decide disposition; this report does not declare release readiness.
