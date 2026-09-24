# FUNC-13 — Phase 4 Readiness Gate

## Control and purpose

| Attribute | Value |
| :--- | :--- |
| Document ID | `FUNC-13` |
| Scope | Final Phase 4 code-readiness and Release Candidate eligibility |
| Baseline | `main` at `e0e67e2233d4b7e9d110e4b699982ae91ae57ec8` |
| Evidence date | 2026-09-24 |
| Source contract | [FUNC-01](FUNC-01-functional-test-contract.md) |
| Integrated regression report | [FUNC-12](FUNC-12-full-regression-report.md) |
| Production business logic changed by FUNC-13 | None |
| Production UI templates changed by FUNC-13 | Three, to resolve deterministic critical/serious accessibility findings |

FUNC-13 establishes a durable, fail-closed decision from existing FUNC-02 through FUNC-12 evidence. It does not add product behavior or certify that production deployment has completed.

## States and final outcomes

- **PASS** — the named check ran and met its acceptance criteria.
- **PARTIAL** — evidence exists, but an explicitly recorded scenario or operational step remains incomplete. PARTIAL is not silently promoted by this gate.
- **BLOCKED** — required evidence failed, was skipped, was cancelled, is missing, or demonstrates an active release blocker.

Final code-readiness outcomes are exactly:

- `PHASE 4 PASS — RELEASE CANDIDATE ELIGIBLE`
- `PHASE 4 HOLD — RELEASE BLOCKER EXISTS`

Before the exact-head pull request CI evidence is available, local reporting must use `FUNC-13 LOCAL GATE PASS — EXACT-HEAD CI PENDING` or `FUNC-13 HOLD — LOCAL BLOCKER FOUND`. A local PASS is not a final Release Candidate approval.

## Three separate decisions

### Code readiness

Current source is eligible to enter the Release Candidate process only when every required full-CI job and the dedicated readiness job passes on the exact reviewed head. Required evidence includes:

- all four SQLite shards with no skipped tests, the existing minimum 2,211-test gate, and coverage at or above the existing 60% threshold;
- successful PostgreSQL `PostgreSQLConcurrency`, `Document05PostgreSQL`, and `BackupRestoreDrill` suites;
- migration and seed, SEED-09, generated-code drift, OpenAPI drift, dependency audit, Pint, and frontend build;
- focused `Phase4ReadinessGateTest`, `ReleasePreflightTest`, `ProductionReadinessP0P2Test`, and `PhaseDProductionSmokeTest`;
- four-shard MECE verification, UI baseline integrity, and deterministic desktop accessibility disposition;
- release-preflight behavior that remains non-mutating, fails closed on partial integration configuration, and prints no secret values.

The `phase4-readiness` CI job depends on the current required job IDs and uses `if: always()` followed by an explicit check that each dependency result is `success`. It also rejects the documentation-only path. Failures, cancellation, and skips are blockers. The job reruns focused readiness evidence, not the thousands of tests already owned by the aggregate, PostgreSQL, and other upstream jobs.

Schema readiness uses the repository's real isolated migration-and-seed job. FUNC-13 does not invent a schema checksum or connect to a shared database. Generated Wayfinder output remains untracked; OpenAPI is checked, not rewritten.

### Deployment readiness

Production deployment is a separate operational decision. **PAY-006 is a pre-deployment blocker** until the payment-proof migration is reviewed and completed in the deployment environment:

1. Review the dry-run output of `php artisan payments:migrate-proofs-to-private`.
2. Run `php artisan payments:migrate-proofs-to-private --execute` and require no missing files, failures, or public orphans.
3. Run a second dry run and require no public files left to remove and no public orphans.
4. Set `PAYMENT_PROOF_LEGACY_PUBLIC_FALLBACK=false` and reload cached configuration.

No production secrets or deployment execution are part of CI. A Release Candidate may include the migration mechanism while deployment remains blocked on these steps.

### Known limitations

Known evidence gaps and functional limitations remain visible in FUNC-01. They are not active defects solely because a coverage row is PARTIAL. A reproducible invariant violation, active P0/P1 defect, failed required suite, or confirmed critical/serious accessibility defect blocks code readiness. The owner and follow-up for each accepted limitation are recorded below.

## FUNC-12 integrated evidence consumed

The FUNC-12 report records exact-head CI evidence of 3,274 SQLite tests, 27,197 assertions, zero failures/errors/skips, 81.40% coverage against 60%, and passing PostgreSQL suites: 26 tests/375 assertions for concurrency, 71/393 for Document05, and 1/20 for the backup/restore drill. It also records migration/seed, SEED-09, frontend, generated drift, OpenAPI, dependency, Pint, and baseline results. These results describe the FUNC-12 reviewed revision; FUNC-13 still requires all relevant jobs to pass on its own exact head.

The stable contract is the named suite and its assertions, not a transient workflow run number.

## Accessibility disposition

FUNC-12 reported local failures on `pos-closings-index-default`, `pos-inventory-counts-index-default`, and `savings-withdrawals-index-default`. The deterministic desktop run reproduced the defects: the POS pages had unnamed back-navigation buttons (critical) and links (serious); the closing page also had an unlabeled date input (critical); the savings empty-state text had serious contrast failure at 2.62:1. These are normal reachable screens, so FUNC-13 treated them as a local release blocker and made the smallest markup/style correction.

The POS links now use the existing `Button as-child` pattern with an accessible link name, the closing date input has an associated visually hidden label, and the savings empty-state copy uses a higher-contrast light-mode color while retaining its dark-mode color. Resolved critical/serious waiver entries were removed from the accessibility known-findings file; axe rules and thresholds were unchanged. Because production UI changed to fix confirmed defects, desktop visual compare is also required.

FUNC-13 reruns desktop accessibility with the existing deterministic UI Audit setup semantics: isolated Playwright SQLite data, `UiAuditSeeder`, generated Wayfinder bindings, production frontend build, Chromium, and deterministic fonts. It preserves the axe rules and remaining known-finding behavior.

The readiness workflow additionally examines the exact desktop axe reports for these three screens. Missing reports are BLOCKED. Any critical or serious axe node on those screens is a HOLD and must include its screen, rule, impact, and DOM target in the audit output. The first deterministic run reproduced all three local findings; the corrections and successful rerun are recorded below. New or stale critical/serious findings anywhere remain failures under the existing UI Audit test. No snapshots or thresholds are changed.

| Screen | FUNC-12 local report | Deterministic result | Classification |
| :--- | :--- | :--- | :--- |
| `pos-closings-index-default` | `button-name` critical at `a[href$="pos"] > .rounded-full.hover\\:bg-zinc-100\\/90.hover\\:text-zinc-950`; `label` critical at `input`; `link-name` serious at `.gap-4.items-center > a[href$="pos"]`. Icon-only back navigation nested a button in a link without an accessible name; the date input had no label. | Deterministic desktop rerun: no critical/serious nodes; no new or stale findings | RESOLVED locally; exact-head CI pending |
| `pos-inventory-counts-index-default` | `button-name` critical at `a[href$="pos"] > .rounded-full.hover\\:bg-zinc-100\\/90[data-slot="button"]`; `link-name` serious at `.gap-4 > a[href$="pos"]`. Icon-only back navigation nested a button in a link without an accessible name. | Deterministic desktop rerun: no critical/serious nodes; no new or stale findings | RESOLVED locally; exact-head CI pending |
| `savings-withdrawals-index-default` | `color-contrast` serious at `.mt-1`; foreground `#9f9fa9` on `#ffffff`, 2.62:1 at 12px (4.5:1 required). Muted empty-state copy had insufficient contrast. | Deterministic desktop rerun: no critical/serious nodes; no new or stale findings | RESOLVED locally; exact-head CI pending |

The local deterministic accessibility run completed with **104 passed, 1 skipped, 0 failed**. The skipped test is a mobile-only scenario outside the selected desktop project. Canonical route coverage found 64 renderable routes, 17 declared exclusions, and 0 uncovered or stale routes. The dedicated FUNC-13 check confirms zero critical/serious axe nodes on all three repaired screens.

## UI visual compare

Because FUNC-13 changes three Vue templates, the normal desktop compare is required. The local full compare was attempted: 101 compare entries were generated, 99 failed comparison, and the Playwright run reported 77 passing and 99 failing tests. Failures included unrelated routes as well as the three repaired screens. The local image host lacks the `fonts-dejavu` package used by the repository UI Audit workflow: `fc-match "DejaVu Sans"` resolves to Noto Sans, and the host does not provide `apt-get` to install the workflow font packages. The local rendering therefore does not reproduce the configured CI font set; this prevents an authoritative local visual comparison. No baseline, threshold, or snapshot was changed. The PR changes `resources/js/pages/**`, so the existing pull-request UI Audit workflow will run its full deterministic compare with Chromium and the required fonts; that exact-head result remains pending.

## Known finding disposition matrix

| Finding | Classification | Evidence and risk | Owner | Required follow-up | Release Candidate impact | Production deployment impact |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| PAY-006 | PRE-DEPLOY BLOCKER | Historical payment-proof files may remain on public storage until migration and fallback disablement. | Deployment / Operations | Complete and verify the four migration steps above. | Does not by itself block code eligibility. | Deployment must remain blocked until complete. |
| EDGE-001 | KNOWN EVIDENCE GAP | PostgreSQL same-item, stock-one contention proves one sale and one rejection; different-item lock ordering/deadlock evidence remains absent. | POS engineering | Add direct different-item lock-order evidence before claiming that scenario covered; investigate if a deadlock is reproduced. | Scope claim to tested same-item race; no active defect is currently evidenced. | No added deployment action established by this evidence gap. |
| EDGE-002 | KNOWN EVIDENCE GAP | PostgreSQL capacity race proves one purchase succeeds within the credit floor; delegate-attributed concurrent checkout is not directly covered. | Store-credit engineering | Add a direct delegate-attributed concurrency case before claiming it covered. | Keep PARTIAL; covered main-account case remains valid. | No added deployment action established by this evidence gap. |
| EDGE-004 | KNOWN EVIDENCE GAP | Middleware replay, conflict, expiry, and content-aware upload fingerprint behavior are tested; domain duplicate behavior after the one-day cache expires remains unverified. | API / payments engineering | Add domain-level duplicate evidence before claiming an indefinite idempotency guarantee. | Keep PARTIAL and make no broader guarantee. | No added deployment action established by this evidence gap. |
| EDGE-005 | KNOWN LIMITATION | No authoritative cooperative bank destination exists; the gateway failure path remains fail-closed. | Cooperative product / payments owner | Configure an authoritative destination only through an approved business source before offering automatic manual-transfer fallback. | No fabricated bank details; no automatic fallback is promised. | No manual bank-transfer destination may be communicated from this system. |
| EDGE-006 | KNOWN EVIDENCE GAP | Payment notification outbox delivery recovery and import rollback are tested; explicit POS and loan rollback injection is not. | POS / loan engineering | Add focused rollback evidence if those failure paths are expanded or claimed fully covered. | Keep PARTIAL; no failure was reproduced by FUNC-12 evidence. | No added deployment action established by this evidence gap. |
| `pos-closings-index-default` | RESOLVED | Deterministic desktop axe found an unnamed critical button, unnamed serious link, and unlabeled critical date input; the page now uses a named single link and associated date label. Post-fix audit has no critical/serious nodes, new findings, or stale findings. | POS UI owner | Keep the deterministic check and visual compare green. | No longer a local blocker; exact-head CI remains required. | No separate deployment action. |
| `pos-inventory-counts-index-default` | RESOLVED | Deterministic desktop axe found an unnamed critical button and serious POS link; the page now exposes one named link. Post-fix audit has no critical/serious nodes, new findings, or stale findings. | POS UI owner | Keep the deterministic check and visual compare green. | No longer a local blocker; exact-head CI remains required. | No separate deployment action. |
| `savings-withdrawals-index-default` | RESOLVED | Deterministic desktop axe measured 2.62:1 for the empty-state copy; light-mode text color now meets body-text contrast. Post-fix audit has no critical/serious nodes, new findings, or stale findings. | Savings UI owner | Keep the deterministic check and visual compare green. | No longer a local blocker; exact-head CI remains required. | No separate deployment action. |

The six EDGE/PAY rows remain PARTIAL in FUNC-01 unless direct new evidence changes a specific contract row. This gate does not convert them for presentation purposes.

## Blocking rules and Release Candidate entry conditions

Phase 4 is HOLD if exact-head required CI fails, is skipped/cancelled, or contradicts source; aggregate skipped tests are non-zero; coverage falls below 60%; any required PostgreSQL suite, migration/seed, seed integrity, OpenAPI, generated drift, dependency threshold, release-preflight test, shard MECE, baseline integrity, or readiness test fails; or an active P0/P1 regression, unresolved security defect, or confirmed critical/serious accessibility defect remains.

Phase 4 may be PASS — RELEASE CANDIDATE ELIGIBLE when all these blockers are absent and evidence is exact-head. The documented PARTIAL findings above can remain with their stated risk, owner, follow-up, and scope. PAY-006 still blocks production deployment until its separate operational steps are complete. Release Candidate eligibility is not production-readiness certification.

## Current evidence status

Local validation encountered an environment blocker in the full desktop visual compare because the host cannot provide the workflow's required DejaVu fonts. Accessibility rerun and all other local checks pass. Report `FUNC-13 HOLD — LOCAL BLOCKER FOUND` until the exact-head deterministic UI Audit compare and required CI results are available and reviewed. The final CI result belongs in the pull request review; do not hard-code future run identifiers here.
