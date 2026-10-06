# Phase 6 — Bundle C final acceptance / QA-12

Final assessment: 2026-10-06 (Asia/Jakarta).

```text
QA_10=PASS
QA_11=PASS
QA_12=PASS
BUNDLE_C=CLOSED_PASS
PHASE_6=CLOSED_PASS
QA_ACCEPTANCE=PASS
PHASE_7=READY_FOR_RELEASE_PLANNING
PRODUCTION_RELEASE=NOT_EXECUTED
PRODUCTION_AUTHORIZED=NO
POST_ACCEPTANCE_CREDENTIAL_CLEANUP=REQUIRED_NOT_EXECUTED
```

## Scope, baselines and evidence authority

This task consolidates existing acceptance and performs QA-12 defect triage. It does not rerun regression, observe services, fix defects or deploy. Phase 7 readiness authorizes planning only, not execution or production deployment.

| Identity | Approved value |
| --- | --- |
| Backend application candidate | `v1.0.0-rc.14`, `043b5b004d66f04e60eef2b0c7a8cdb38fce278a` |
| Android baseline | `46070f836a4890eeb321d70dcef96822ff205a16` |
| QA hostname | `https://qa.kojaya.id` |
| QA database | `kojaya_qa`; legacy `kojaya` prohibited |
| Evidence branch base / control main | `751f9e661e1c3172ea012a6061450b812b04d677` |

Evidence commit/PR identities are control history, never a replacement application candidate. Bundle A/B closure and QA-06 through QA-09 acceptance are inherited from the authorized handoff. Final required inputs reviewed:

- BUNDLE-C-SERVER PASS: authoritative owner-supplied final server handoff in the QA-12 task. Not independently rerun or presented as new PC measurements.
- BUNDLE-C-BACKEND-PC PASS and COVERAGE-01 PASS: [detailed backend report](PHASE-6-BUNDLE-C-BACKEND-PC.md), read locally and included with this evidence PR.
- BUNDLE-C-ANDROID-PC PASS: detailed Android report, local source `F:\kojayaapp\docs\release\PHASE_6_BUNDLE_C_ANDROID_ACCEPTANCE.md`, final closure section dated 2026-10-06. The Android evidence is local/uncommitted in its repository; no published report commit is invented. Refer to that separate report for device logs and detailed results.

The prior BLOCKED assessment on this branch is preserved below as history. Current server PASS and completed PC/Android acceptance supersede its missing-access conclusions.

## QA-10 — final regression acceptance

**QA-10 PASS** combines Backend-PC and Android-PC accepted results.

Backend web/API: valid login, persistent sessions, logout and invalidation PASS; representative 401/403/422 PASS. Two active synthetic members independently verified. Admin Koperasi, Pengurus Koperasi and Manajer Koperasi login/session/capability checks PASS. Member management denied to Anggota; another member's receipts and loan denied to peer; Manajer creation form denied; system-role administration denied to all cooperative roles. Observed privilege escalation and cross-member leakage: 0. Read-only capability selection follows existing rc.14 route/policy/permission contracts, not invented grants.

Financial reconciliation: two APPROVED payments Rp25,000 + Rp100,000, two savings ledger credits and two pre-existing receipts reconcile to Rp125,000. Both protected PDF receipts loaded through normal authenticated web sessions. One approved loan has Rp500,000 principal and Rp515,000 scheduled total; three API installments sum exactly Rp515,000. Disbursement is NONE (`disbursed_at` null), installments unpaid. Readback showed no duplicate member payment/ledger entries. Peer's actual accepted fixture is zero savings and empty payment/loan lists; no replacement data created. Representative behavioral API contract PASS; exhaustive schema conformance is not claimed.

Android: approved source unchanged; 732 unit/architecture tests with zero failures/errors/skips PASS; QA Firebase guard/build PASS; QA lint PASS (0 errors, 49 warnings); Paparazzi/probe PASS. Exact approved-SHA [Android Checks 37308027080](https://github.com/johnd-creator/KojayaApp/actions/runs/37308027080) is reported PASS in the reviewed Android evidence. Physical device Infinix X6873 / Android 16, QA variant and QA-only endpoint: core launch/login/session/profile/savings/payment/receipt/loan/notification/logout journeys PASS. Existing physical error harness 403/422/503: three tests PASS; QA/harness crash records 0. Binary receipt rendering is not claimed by Android; backend PDF access is separately proven.

Network recovery: previously blocked device DNS environment resolved by the owner's connectivity repair. Focused retest on the same approved SHA/APK PASS: after restored connectivity, retry loaded Buku Besar and fresh populated records Rp100,000 + Rp25,000; profile loaded; logout and protected post-logout access PASS. Historical P2 C-ANDROID-ENV-01 is resolved, not erased.

FCM: prior QA-09 real-device delivery/foreground receipt/tap acceptance is referenced in Android [Bundle B detail](https://github.com/johnd-creator/KojayaApp/blob/46070f836a4890eeb321d70dcef96822ff205a16/docs/release/PHASE_6_BUNDLE_B_PC_ACCEPTANCE.md). Bundle C lightweight QA configuration/registration/inbox checks PASS; one active synthetic binding and zero cross-member associations recorded. No new push sent; full delivery was not unnecessarily rerun.

## QA-11 — server operational observation

**QA-11 PASS**, inherited from the final BUNDLE-C-SERVER handoff supplied by the owner:

| Server evidence | Final accepted result |
| --- | --- |
| Serving SHA / DB / migrations / maintenance | Exact rc.14; `kojaya_qa`; 0 pending; OFF |
| nginx / PHP-FPM | PASS / PASS |
| Corrected runtime permission contract / write probe | RESOLVED / PASS |
| Observation window | 75.18 minutes |
| Queue lifecycle | PASS: service-managed natural recycle; PID changed normally; automatic worker recovery accepted |
| Scheduler | PASS |
| Failed jobs | 0 → 0 |
| New Laravel ERROR/CRITICAL | 0 |
| New PHP fatal | 0 |
| Observed HTTP 5xx | 0 |
| Synthetic financial state | MATCH |
| Runtime source modified / production touched | NO / NO |

No new server probe or performance threshold is claimed. Current permission health supersedes the resolved runtime failure; future drift risk is retained below.

## QA-12 — remaining findings and release gate

| ID | Severity | Impact / disposition / smallest follow-up scope |
| --- | --- | --- |
| A / BC-PC-UI-01 | P3, open | Approved loan reference fallback still says waiting for Pengurus. Wording is stale; approval timestamps/status and workflow remain correct. Separate reviewed fallback-copy correction |
| B / BC-PC-UI-02 | P3, open | Whole-rupiah installment display visually sums Rp1 higher; exact API schedule and loan total are correct. Separate reviewed rounding/presentation correction; no financial data rewrite |
| C / C-ANDROID-TOOL-01 | P3, open | Supplemental native Windows PNG parser tests hit NamedTemporaryFile PermissionError; approved Linux gate and released Android runtime pass. Separate tooling-portability fix; no acceptance baseline refresh |
| D / C-SERVER-OPS-01 | P3 operational debt, open | Deployment-user CLI under umask 0022 can recreate mutable-cache permission drift. Current corrected permission contract/write probe and 75.18-minute healthy observation PASS. Separate operator/hardening task to prevent recurrence; no current runtime blocker established |

Finding D is **operational debt/future hardening**, not an unresolved current permission defect. A future reproducible runtime failure would require renewed severity evaluation. Nothing in the reviewed final inputs contradicts current accepted health. No finding was downgraded to conceal an active security, financial, runtime or required-journey failure.

Unresolved P0=0; P1=0; P2=0; P3=4 (including one operational-debt item, not counted twice). Observed critical unexpected 5xx=0; privilege escalation=0; failed jobs 0 → 0. Required QA-10/QA-11 PASS, compatible approved backend/Android identities, financial integrity and no production usage satisfy QA-12. **QA-12 PASS; BUNDLE C CLOSED PASS; PHASE 6 CLOSED PASS; QA ACCEPTANCE PASS.** Phase 7 is READY FOR RELEASE PLANNING. Production release NOT EXECUTED and not authorized.

## Required post-acceptance cleanup

**REQUIRED — pending separate controlled task after owner review.** Acceptance is closed; the QA environment is not yet claimed fully sanitized.

- Remove Windows temporary credential handoff files.
- Remove QA server temporary credential handoff files.
- Rotate/reset temporary synthetic staff/peer passwords.
- Verify temporary reset material is gone.

Do not publish credential values or private handoff paths. No credential cleanup, password reset, server file deletion or account mutation was performed by this consolidation. Record completed cleanup separately with bounded verification.

## Evidence change control

Dedicated branch `codex/phase6-bundle-c-acceptance` reused safely at current main. Allowed changes: consolidated report and backend detail only. Original F:\kojaya modifications retained. No application/runtime/deployment/config/Actions file, APK, binary, credential or key is included. No production operation, QA-12 source fix, rc.15, tag/release or Phase 7 execution. Evidence will be committed/pushed for review; PR must remain unmerged until owner authorization.

## Historical blocked checkpoint — superseded

# Phase 6 — Bundle C acceptance checkpoint

Assessment: 2026-10-05, 20:20 WIB (Asia/Jakarta).

**BUNDLE C = BLOCKED. PHASE 6 = REMAINS OPEN. PHASE 7 = NOT READY.**

## Approved identities and inherited evidence

- Backend application candidate: `v1.0.0-rc.14`, `043b5b004d66f04e60eef2b0c7a8cdb38fce278a` (owner-approved; active deployment not independently revalidated in this checkpoint).
- Android approved main: `46070f836a4890eeb321d70dcef96822ff205a16`.
- QA hostname: `https://qa.kojaya.id`; expected database: `kojaya_qa`.
- Legacy `kojaya` prohibited; production NOT AUTHORIZED.
- Bundle A/B CLOSED PASS and QA-06 through QA-09 PASS are supplied owner handoff, not newly executed evidence.
- Backend repository/control baseline for this report: `751f9e661e1c3172ea012a6061450b812b04d677`; it is not an application candidate.

## Preflight evidence

| Check | Result |
| --- | --- |
| QA reachability / login | PASS: HTTPS `/login` HTTP 200 |
| TLS verification | PASS: curl `ssl_verify_result=0`; no certificate bypass |
| SSH operator access | BLOCKED: configured QA alias rejected BatchMode/key-only authentication with `Permission denied (publickey,password)` |
| Deployed backend SHA | UNVERIFIED: SSH prerequisite unavailable |
| Effective DB identity | UNVERIFIED: expected `kojaya_qa`; no database accessed |
| Maintenance mode / pending migrations | UNVERIFIED; HTTP 200 alone does not establish both checks |
| Queue / scheduler | UNVERIFIED; no service operation performed |
| Failed jobs baseline | UNVERIFIED; no database accessed |
| Android working tree | CLEAN: `git status --short` empty |
| Android local HEAD | `18d89a93c383b2c724bc9500de35b125ad75cea0`, branch `chore/phase6-bundle-b-android-reconcile` |
| Android approved baseline availability | PASS after safe fetch: origin/main equals approved SHA; PR #34 MERGED, merge SHA matches |
| Android local-to-approved source | No changed paths; both Git trees `a1658ccc93a7eff8bdd25e9e8610a5c1df8dff21`; local HEAD not switched |
| Physical Android device | ADB reports one authorized `device`; serial intentionally omitted |
| Current QA APK endpoint / Firebase binding | NOT VERIFIED; no APK build/install or application login executed |

Codebase graph tooling is unavailable; discovery used focused source/document reads and Git metadata. No completeness claim is made.

## Gate disposition

- QA-10: FAIL TO ENTER / NOT EXECUTED. Mandatory server identity and operational preflight cannot be proven. No authenticated web/API, financial, Android journey, or FCM regression was executed.
- QA-11: FAIL TO ENTER / NOT EXECUTED. No 75–90 minute observation window began. Queue PIDs/recycle, automatic recovery, post-recycle test job, scheduler status and failed-job before/after counts are unavailable.
- QA-12: FAIL / RELEASE GATE BLOCKED by missing evidence. No application defect severity is inferred from operator authentication failure.

Prior synthetic financial expectations (Rp125,000 savings, Rp500,000 principal, Rp515,000 scheduled total, three installments) remain owner-provided expectations; they were not read or reconciled here.

## Defect and blocker register

| ID | Classification | Evidence / affected component | Affected SHA | Redeploy / smallest scope |
| --- | --- | --- | --- | --- |
| BC-PREFLIGHT-01 | Execution prerequisite blocker; not an established P0/P1 application defect | QA operator SSH authentication unavailable; required server identity/runtime evidence inaccessible | Approved backend `043b5b004d66f04e60eef2b0c7a8cdb38fce278a`; actual serving SHA unverified | Redeploy not established as necessary. Supply an authenticated operator session or approved SSH key, then rerun preflight before QA-10 |

Confirmed P0: 0; confirmed P1: 0; confirmed P2/P3: 0 in the limited completed preflight. These counts do not establish defect-free acceptance.
HTTP 5xx: 0 in the single completed unauthenticated login request; no wider distribution available.
Critical Laravel/PHP log findings: NOT OBSERVED, logs not inspected.
Failed jobs: unknown → unknown. Queue/scheduler health: unknown.
Android automated gates/build/install: NOT EXECUTED. Device journeys/crash/leakage/production endpoint checks: NOT EXECUTED.

## Authority and next checkpoint

No QA source patch, service/config change, migration, seeder, restore, financial mutation, provider send, production access, or legacy database access occurred. No uncontrolled fix was attempted. An authenticated SSH access prerequisite was requested without asking for passwords in chat.

Original backend local modifications were preserved in the existing checkout; this report lives on dedicated branch `codex/phase6-bundle-c-acceptance` in an isolated worktree. No commit, push, PR or merge performed. No Phase 7 activity is authorized.

Resume only after operator access is available: verify all preflight identities, align clean Android source to the approved SHA without discarding work, execute QA-10, then observe the natural queue lifecycle for 75–90 minutes and complete QA-12. Do not declare Phase 6 closed from this checkpoint.
