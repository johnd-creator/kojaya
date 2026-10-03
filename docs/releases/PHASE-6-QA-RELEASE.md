# Phase 6 — QA Release

**Assessment date:** 2026-10-03 (Asia/Jakarta)

**QA-01 CLOSED. Replacement promotion — rc.12.**

**Phase 5 CLOSED. Phase 6 STARTED. rc.12 redeployment PASS; QA-03 PASS; QA-04 PASS; QA-05 PASS; Bundle A CLOSED PASS.**

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
REDEPLOYMENT=PASS
QA_03_RETRY=PASS
QA_04=PASS
QA_05=PASS
BUNDLE_A=CLOSED_PASS
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
bound to this SHA. Deployment and QA-03/04 have passed as recorded below;
authenticated QA-05 and safe temporary-account cleanup have passed as recorded
in the closure section below. General traffic remains held; production and
legacy `kojaya` are untouched. QA again contains zero users after verified
removal of the one explicitly approved temporary administrator.

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
REDEPLOYMENT=PASS
QA_03_RETRY=PASS
QA_04=PASS
QA_05=NOT_EXECUTED_IN_FULL (partial smoke PASS; admin bootstrap approval pending)
```

The replacement must be bound to the exact validated merge SHA before a fresh
attestation or deployment. No schema or business behavior change is authorized
by this recovery. Bundle B remains unexecuted.

## rc.12 actual hardened deployment and runtime revalidation

The hardened `bin/deploy-qa.sh` transaction exited zero for exact candidate
`5a5ae698259d465bc5b5265fe14ca2580c9eb922`, replacing historical rc.11.
Fresh public root/login requests returned HTTP 503 before a new protected
hold attestation was generated for this SHA. Candidate and serving strict
preflight and QA database identity checks passed. The script itself established
runtime access before migration and after optimization; no manual serving-code
permission repair was performed.

| Runtime evidence | Result |
| --- | --- |
| Promotion control closure | [PR #100](https://github.com/johnd-creator/kojaya/pull/100) MERGED; [PR CI #532](https://github.com/johnd-creator/kojaya/actions/runs/37128090928) and [exact-control/main CI #533](https://github.com/johnd-creator/kojaya/actions/runs/37128184173) SUCCESS under the existing docs-only contract; control SHA `28aab8248d5f24e6f2776a77748f36e43ef54d31` is not the app candidate |
| Fresh managed backup | `kojaya-qa-kojaya_qa-20261003T140604Z-5a5ae69`; hardened independent verification PASS; directory `0700`, dump/manifest/checksum `0600` |
| Recovery evidence and inputs | External recovery directory `0700`, evidence files `0600`; candidate environment input `0600`; serving environment contents preserved and metadata `fauzi:kojayaqa 0640` |
| Migration | `Nothing to migrate`; completed; pending migrations zero; no schema change |
| Source identity | Exact approved candidate; serving checkout clean; maintenance OFF |
| Real PHP-FPM health | HTTP 200 through approved loopback Nginx/FPM path; dedicated workers uid `996`, group `987` |
| Effective filesystem contract | Live FPM identity checked against 8,846 PHP files: unreadable zero, untraversable directories zero, no unexpected access ACLs; all ten required runtime directories writable/traversable; `.env` and generated configuration remain `0640` |
| Runtime write evidence | Controlled web requests created a real file session; encrypted cookie/prefix and backing file validated with the unchanged application key without exposing it; the web session persisted across requests |

`QA03-RUNTIME-01=CLOSED`. QA-03 passed before the existing unit readiness
marker was recorded and the approved units were activated. The marker records
the actual passed gate and exact candidate; it was absent before validation.

QA-04 passed after a 229-second observation window: queue loaded/active/running,
zero restarts; scheduler loaded/active/enabled; three successful real scheduler
completions and eleven scheduled commands DONE. No permission, database or
scheduler failure matches appeared. Post-smoke checks reconfirmed both units
active, zero restarts, and zero queued/failed/outbox rows. No provider send or
FCM delivery was tested.

## Historical QA-05 partial smoke and pending prerequisite

This section records the state before explicit bootstrap approval and is
superseded by the completed QA-05 and Bundle A closure below.

The controlled loopback smoke passed health/up, login HTML, all thirty referenced
static assets, CSRF cookie initialization, rejection without a token (419), and
validation with the real token (422). Anonymous web session persistence was
verified separately from token-API requests. Unauthenticated dashboard requests
redirected to login; OpenAPI returned 200; protected API health rejected an
unauthenticated request with 401. Completed smoke requests produced zero critical
HTTP 5xx and no new PHP fatal, permission or SQL error matches.

Authenticated admin login, authenticated dashboard/data, authenticated session
persistence, logout, post-logout authorization and authenticated API health are
NOT EXECUTED. Read-only reconciliation found zero users, sixteen existing roles
(including `System Admin`) and 129 permissions in `kojaya_qa`, unchanged from the
pre-smoke account baseline. The owner confirmed the user database is empty.
No account/role fixture was created on QA and no QA seeder ran.

The pending approval is for exactly one QA-only administrator
`qa.bundlea.admin@example.test` via the existing tested `admin:create` command,
using the existing `System Admin` role and a generated protected password on
standard input. This would add a `users` row and its `model_has_roles` link.
The project account-recovery instruction requires explicit authorization for
that one-off write; elapsed time and the explanation of an empty database are
not approval. No administrator password or API access token was generated or
published.

```text
QA_03=PASS
QA03_RUNTIME_01=CLOSED
QA_04=PASS
QA_05=NOT_EXECUTED_IN_FULL
BACKEND_WEB_PUBLIC_SMOKE=PASS
AUTHENTICATED_SMOKE=BLOCKED_ADMIN_BOOTSTRAP_APPROVAL
CRITICAL_HTTP_5XX_IN_COMPLETED_SMOKE=0
QUEUE=LOADED_ACTIVE_STABLE_IN_OBSERVED_WINDOW
SCHEDULER=LOADED_ACTIVE_ENABLED_STABLE_IN_OBSERVED_WINDOW
EXTERNAL_QA_TRAFFIC=HELD (public root/login HTTP 503 reconfirmed)
LEGACY_KOJAYA_TOUCHED=NO
PRODUCTION_TOUCHED=NO
BUNDLE_A=BLOCKED
BUNDLE_B=NOT_EXECUTED
```

## QA-05 approved authenticated smoke — Bundle A closure

The owner explicitly approved **QA-05 ADMIN BOOTSTRAP ONLY**, superseding the
historical pending-approval record above. Read-only checks confirmed APP_ENV=qa,
live database kojaya_qa, zero users and the existing System Admin role.
The existing tested admin:create --password-stdin command created exactly one
temporary QA-only administrator, qa.bundlea.admin@example.test, and one role
link. A strong private password was passed on stdin from a 0600 file inside a
0700 directory. It never appeared in arguments, logs or published evidence.
No seeder, additional account, role, permission, employee, member or business
fixture was created. The account was temporary test infrastructure only.

Controlled requests used the existing operator loopback and actual QA
Nginx/PHP-FPM runtime serving exact rc.12
`5a5ae698259d465bc5b5265fe14ca2580c9eb922`.

| Authenticated QA-05 check | Result |
| --- | --- |
| Admin web login | PASS; HTTP 200, identity verified, session regenerated |
| Authenticated dashboard | PASS; HTML HTTP 200, correct Dashboard component and user |
| Deferred dashboard data | PASS; real Inertia partial response HTTP 200 |
| Session persistence | PASS; repeated authenticated requests retained the validated encrypted-cookie session and runtime session file |
| Web logout | PASS; HTTP 204, session invalidated and regenerated |
| Post-logout authorization | PASS; dashboard HTTP 302 to login |
| Admin token API login/session | PASS; granular abilities, reports:read present, no wildcard; session HTTP 200 |
| Authenticated API health | PASS; HTTP 200 and data.status=ok |
| API access boundary | PASS; unauthenticated and revoked-token health HTTP 401 |
| API logout | PASS; HTTP 200; temporary token revoked and absence verified |
| Critical HTTP 5xx | ZERO across all controlled smoke requests |
| Queue/scheduler stability | PASS in 506 seconds: queue active/running with zero restarts; timer active/enabled; nine successful scheduler completions |

Earlier health/login/static-assets/CSRF/anonymous-session evidence remains
valid for this same immutable candidate. No new application fatal, permission
or database errors appeared. One retained CLI-only DecryptException resulted
from an initial smoke-client cookie-decoding mistake; corrected read-only
validation passed. Inertia initially returned expected asset-version
negotiation HTTP 409 before the smoke client supplied its version. These were
client adjustments, with no application change and no HTTP 5xx. No evidence
was cleared.

After authenticated evidence capture, the existing tested authenticated
DELETE /settings/profile path removed the temporary account using its current
password and CSRF token. The existing model deletion hook detached the role
link. Read-only verification confirmed users, role links and personal access
tokens returned to zero. Every other table's row count matched baseline except
eleven retained authentication audit records. Role, permission and
role-permission contents remained identical by digest; no business data
changed. Password absence was verified in new runtime logs and audit records
without disclosure. The protected credential file and directory were removed.
No temporary-account or credential cleanup remains before Phase 7.

The queue had previously exited successfully at its existing max-time=3600
boundary. The existing approved QA unit was restarted for this controlled
verification. Its configuration remains unchanged: Restart=on-failure does
not restart normal one-hour expiry, so that expiry requires an operator
restart. Stability PASS is scoped to the observed window, not unattended
operation beyond the configured lifetime. The scheduler remains enabled and
active. Queued/failed/cooperative outbox rows remain zero; no provider send
was tested.

```text
QA_03=PASS
QA03_RUNTIME_01=CLOSED
QA_04=PASS
QA_05=PASS
ADMIN_BOOTSTRAP=PASS (exactly one approved temporary QA-only account)
AUTHENTICATED_SMOKE=PASS
TEMPORARY_ACCOUNT_CLEANUP=VERIFIED_REMOVED_VIA_EXISTING_APPLICATION_PATH
TEMPORARY_ROLE_LINK=REMOVED
TEMPORARY_API_TOKEN=REVOKED_AND_ABSENT
PRIVATE_CREDENTIAL_FILE=REMOVED
CRITICAL_HTTP_5XX=0
QUEUE=LOADED_ACTIVE_STABLE_IN_OBSERVED_WINDOW (existing one-hour lifetime)
SCHEDULER=LOADED_ACTIVE_ENABLED_STABLE_IN_OBSERVED_WINDOW
EXTERNAL_QA_TRAFFIC=HELD (public root/login HTTP 503 reconfirmed)
SERVING_CANDIDATE=5a5ae698259d465bc5b5265fe14ca2580c9eb922
SERVING_CHECKOUT=CLEAN
APPROVED_RUNTIME_ENV=UNCHANGED
LEGACY_KOJAYA_TOUCHED=NO
PRODUCTION_TOUCHED=NO
BUNDLE_A=CLOSED_PASS
BUNDLE_B=NOT_EXECUTED
```

This closure changes control documentation only; serving remains exact rc.12.
No traffic release, deployment, source change, migration, financial/member
acceptance, Android integration or FCM execution occurs in this closure.
