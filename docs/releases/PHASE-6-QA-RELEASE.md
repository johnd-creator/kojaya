# Phase 6 — QA Release

**Assessment date:** 2026-10-03 (Asia/Jakarta)

**QA-01 — EXECUTION PASS; REPOSITORY CLOSURE PENDING**

**Phase 5 CLOSED. Phase 6 STARTED. QA-02 — NOT EXECUTED.**

## QA-01 — Promotion lock

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
