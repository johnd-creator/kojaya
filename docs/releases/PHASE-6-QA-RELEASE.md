# Phase 6 — QA Release

**Assessment date:** 2026-10-03 (Asia/Jakarta)

**QA-01 CLOSED. Replacement promotion — rc.12.**

**Phase 5 CLOSED. Phase 6 STARTED. QA-02 — PASS on rc.11; QA-03 — FAILED; Bundle A — IN RECOVERY.**

## Current Bundle A replacement promotion

The owner's bounded recovery instruction authorizes automatic replacement
promotion for this deployment/runtime repair after merge and fully passing CI.
The exact repair merge is now the deploy candidate. Subsequent documentation
commits remain control evidence and must not replace this application identity.

```text
PREVIOUS_QA_CANDIDATE_SHA=1c257b3e5ad76d9d453222213dd055d9ab18c9a9
REPLACEMENT_QA_CANDIDATE_SHA=5a5ae698259d465bc5b5265fe14ca2580c9eb922
PROMOTED_QA_CANDIDATE_SHA=5a5ae698259d465bc5b5265fe14ca2580c9eb922
APPLICATION_RELEASE_CANDIDATE=v1.0.0-rc.12 (untagged)
PROMOTION_STATUS=PROMOTED_QA_CANDIDATE
REPAIR_ITERATIONS=1
REDEPLOYMENT=NOT_EXECUTED
QA_03_RETRY=NOT_EXECUTED
QA_04=NOT_EXECUTED
QA_05=NOT_EXECUTED
EXTERNAL_QA_TRAFFIC=HELD
```

| Replacement evidence | Result |
| --- | --- |
| Scoped source repair | [PR #99](https://github.com/johnd-creator/kojaya/pull/99), merged; branch `fix/qa-runtime-readability` |
| Final source-head CI | [CI #530](https://github.com/johnd-creator/kojaya/actions/runs/37122719074), head `36b03a1d07c42fbce37d92299e4ff92717d56c52`; all 15 mandatory jobs SUCCESS; four shards completed 3,427 tests / 28,150 assertions |
| Exact candidate CI | [CI #531](https://github.com/johnd-creator/kojaya/actions/runs/37125296562), push on main, exact replacement SHA; all 15 mandatory jobs SUCCESS, including full PHPUnit, PostgreSQL, build/drift/audit and readiness gates |
| UI evidence | [UI Audit #312](https://github.com/johnd-creator/kojaya/actions/runs/37122719078), final source head SUCCESS; candidate tree exactly equals tested source tree `64c4a36ca18cd34698f384a541ca9c4544517ce2` |
| Focused regression | 27 filesystem/QA transaction tests PASS, 223 assertions; broader deployment/private-backup/preflight set completed 100 tests without failures, 689 assertions |
| Scope comparison | [rc.11 to replacement](https://github.com/johnd-creator/kojaya/compare/1c257b3e5ad76d9d453222213dd055d9ab18c9a9...5a5ae698259d465bc5b5265fe14ca2580c9eb922): deployment/runtime repair, regression tests, inherited CI control and release history only; no business code or migrations |

No tag or GitHub release is created. The hardened transaction must generate
fresh hold evidence, strict preflight, backup and independent verification
bound to this SHA. Actual deployment and QA-03/04/05 remain separate gates.
General traffic remains held; production and legacy `kojaya` are untouched.
QA contains zero users and an existing `System Admin` role. The single-account
bootstrap approval is pending; no account, role or fixture data was created.

The following QA-01 lock and its original execution boundary are historical.
The current recovery authority and replacement identity above govern Bundle A.

## Historical QA-01 — original rc.11 promotion lock

The owner authorized this control/documentation lock on 2026-10-03. The
application release candidate and repository/control head are separate
identities. Promotion records the candidate for subsequent QA deployment;
it does not record a completed deployment or authorize execution of QA-02.

```text
APPLICATION_RELEASE_CANDIDATE=v1.0.0-rc.11
PROMOTION_STATUS=PROMOTED_QA_CANDIDATE
PROMOTED_QA_CANDIDATE_SHA=1c257b3e5ad76d9d453222213dd055d9ab18c9a9
POST_MERGE_MAIN_SHA=1a3641cf5303344b68c72ba8dcac524e97fdadd7
QA_01=PASS
PHASE_5=CLOSED
PHASE_6=STARTED
QA_02=NOT_EXECUTED
```

Only `PROMOTED_QA_CANDIDATE_SHA` is the application/deploy candidate. The
post-merge main SHA is control evidence and does not replace rc.11. This lock
does not create a Git tag or GitHub release.

## Evidence bound to the lock

| Evidence | Identity and result |
| --- | --- |
| Application CI | [CI #523 / 37029602498](https://github.com/johnd-creator/kojaya/actions/runs/37029602498), exact rc.11 SHA; SUCCESS, 15 mandatory jobs PASS as recorded in the merged RC-11 dossier |
| Application UI Audit | [UI Audit #310 / 37071233636](https://github.com/johnd-creator/kojaya/actions/runs/37071233636), exact rc.11 SHA; full/all/all SUCCESS as recorded in the merged RC-11 dossier |
| Phase 5 dossier | [PR #92](https://github.com/johnd-creator/kojaya/pull/92), MERGED at `2026-10-03T04:26:52Z`; merge commit equals `POST_MERGE_MAIN_SHA` |
| Post-merge control CI | [CI #526 / 37096571257](https://github.com/johnd-creator/kojaya/actions/runs/37096571257), push on main, exact `POST_MERGE_MAIN_SHA`; completed SUCCESS, all 15 jobs SUCCESS |
| Candidate-to-control comparison | [Exact SHA comparison](https://github.com/johnd-creator/kojaya/compare/1c257b3e5ad76d9d453222213dd055d9ab18c9a9...1a3641cf5303344b68c72ba8dcac524e97fdadd7), one commit ahead, zero behind; only the three paths below |

Verified GitHub comparison paths:

- `.github/workflows/ci.yml` — modified, CI control correction.
- `docs/log.md` — modified, evidence history.
- `docs/releases/PHASE-5-RC-11.md` — added to the candidate tree by PR #92, evidence dossier.

No application/runtime source changed between the locked candidate and control
head. Existing application CI/UI evidence remains bound to rc.11; CI #526 is
post-merge control evidence. Prior runtime readiness evidence is inherited from
[the merged dossier, section 19](https://github.com/johnd-creator/kojaya/blob/1a3641cf5303344b68c72ba8dcac524e97fdadd7/docs/releases/PHASE-5-RC-11.md#19-final-rc11-dossier-reconciliation--2026-10-03),
and was not rerun during QA-01.

## Historical reconciliation

Earlier `OWNER APPROVAL: PENDING`, `PR #92 OPEN/UNMERGED`, and `Phase 6 NOT
STARTED` statements remain historical evidence of their assessment times.
For the present control state, the owner's QA-01 instruction authorizes promotion
of the exact rc.11 candidate; PR #92 is MERGED; Phase 5 is CLOSED and Phase 6
STARTED upon QA-01 PASS. Earlier candidates and failed/blocked assessments are
not retroactively changed. Phase 6 STARTED means this documentation/control
step has passed; the new QA-02 runtime cutover remains NOT EXECUTED.

The historical `RC-11-RC11-QA-02` readiness handoff in section 19 of the dossier
is prior readiness evidence, not execution of Phase 6 QA-02 runtime cutover.

## Authority and execution boundary

- QA ONLY; intended target database: `kojaya_qa`.
- Legacy `kojaya` MUST NOT be the migration target.
- Production NOT AUTHORIZED.
- QA-02 runtime cutover NOT EXECUTED; stop after QA-01 documentation.
- No QA runtime, database, migration, queue, scheduler, or FCM operation was performed during QA-01.
- No GitHub Environment/gate, branch protection, repository rule, new tag, or release was created during QA-01.

A subsequent candidate change requires a new explicit promotion decision and
evidence bound to its exact SHA; a moving branch reference cannot replace this
lock. QA-02 requires a separate instruction to proceed.

## Repository closure acceptance

The QA-01 documentation commit and its eventual merge SHA are control evidence
only, never a new application candidate. Repository closure requires a reviewed
documentation-only PR, successful required PR CI, normal merge to main, and
successful exact-main CI under the existing docs-only contract. Only after those
checks may QA-01 be reported CLOSED. Closure identifiers and CI results are
recorded in the repository-closure handoff and GitHub PR/run metadata.

The owner authorized branch creation, commit, push, PR and merge for this
documentation closure. This authority does not extend to QA-02 runtime cutover.

## Bundle A runtime recovery — QA03-RUNTIME-01

The owner authorized up to three scoped source-control repair iterations and
automatic re-promotion only after all required CI passes. Production and legacy
`kojaya` remain outside scope. General QA traffic remains held.

Historical candidate `1c257b3e5ad76d9d453222213dd055d9ab18c9a9` passed
QA-02 deployment, managed backup verification, strict preflight and QA database
identity. Migration completed with zero pending migrations. QA-03 then failed:
CLI boot passed, but PHP-FPM returned HTTP 500 because checkout/cache files
were deployment-user-only. Queue and scheduler remained inactive; QA-04/05
were not executed. This failure history is retained.

A real temporary Git checkout under `umask 077` reproduces `0600` PHP files.
The prior transaction tests mocked Git/dependency/build commands and therefore
did not assert filesystem access. The scoped repair retains `umask 077` and
adds explicit runtime permission establishment before migration and after
optimization, including the pre-migration recovery path. The runtime group
comes from verified serving environment metadata, not a hardcoded account.

Tracked application source and dependencies/static assets are readable and
traversable; executable tools retain execution permission. Runtime storage
paths are group-writable with group inheritance. Generated configuration caches
remain private to deployment/runtime identities. Environment inputs, credentials,
managed backup descendants and external recovery evidence are not normalized.
Symlink targets and insecure serving environment metadata fail closed.

```text
PREVIOUS_QA_CANDIDATE_SHA=1c257b3e5ad76d9d453222213dd055d9ab18c9a9
REPLACEMENT_QA_CANDIDATE_SHA=5a5ae698259d465bc5b5265fe14ca2580c9eb922
REPLACEMENT_DESIGNATION=v1.0.0-rc.12 (untagged, validated and promoted)
REDEPLOYMENT=NOT_EXECUTED
QA_03_RETRY=NOT_EXECUTED
QA_04=NOT_EXECUTED
QA_05=NOT_EXECUTED
```

The replacement must be bound to the exact validated merge SHA before a fresh
attestation or deployment. No schema or business behavior change is authorized
by this recovery. Bundle B remains unexecuted.
