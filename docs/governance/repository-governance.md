# Repository Governance Record

## 1. Document identity

- Audit timestamp: 2026-07-18 14:01:22 WIB (Asia/Jakarta).
- Repository: `johnd-creator/kojaya`.
- Audited default branch: `main`.
- Baseline `main` SHA after PR #12: `564f96095df3f763bdbfc56383457e0508cbf6a4`.
- Release tag: `v0.1.0`.
- Release tag peeled commit: `ad8bc3afc9b62f549e4f054e181ef9decbecb341`.
- Release classification: Internal Alpha / Not Production Release.
- API contract version: `1.0.0`.
- Auditor/tool provenance: implementation model using local Git inspection,
  GitHub repository and pull-request connectors, GitHub Actions job inspection,
  and read-only public GitHub REST endpoints. No administrative write was used.
- Scope: repository settings visible to the audit tools, `main` and release-tag
  governance, CI check mapping, environment inventory, release ownership, and
  retention classification for every remote branch.
- Explicit non-actions: no repository setting, branch protection, ruleset,
  environment, merge setting, branch, tag, release, workflow, or production
  code was changed. No deployment or secret inspection was performed.

## 2. Executive summary

The repository has an active internal-alpha baseline and a visible CI workflow,
but governance enforcement is still incomplete. The repository is public, the
default branch is `main`, and the authenticated repository permission available
to the audit connector is administrator. All eight release-gate CI jobs passed
on the post-merge `main` run for SHA `564f9609...`.

The public branch metadata reports `main` as not protected, the detailed
protection endpoint requires authentication unavailable to the REST fallback,
and no repository rulesets were visible. No tag-protection rule was observable.
The repository metadata exposes all three merge methods, does not expose
automatic branch deletion or update-branch settings, and reports auto-merge as
disabled. GitHub returned zero environments even though the deployment workflow
references `production`.

The highest risks are direct or force-push exposure on `main`, mutable release
tags, and unclear release/deployment ownership. Applying rules in one step could
lock out the only maintainer, so the recommended approach is staged: approve
the owner and bypass model first, apply `main` protection, protect `v*` tags,
configure environments, then clean up only an exact reviewed branch list.

## 3. Current-state evidence

| Area | Current state | Evidence | Risk |
| ---- | ------------- | -------- | ---- |
| Default branch | `main`; repository is public and not archived | Repository connector metadata; `default_branch=main`, `visibility=public`, `archived=false` | Medium |
| Merge strategy | Merge commit, rebase, and squash are all allowed; auto-merge is disabled; update-branch is disabled | Repository connector metadata: all three `allow_*_merge=true`, `allow_auto_merge=false`, `allow_update_branch=false` | Medium |
| Automatic branch deletion | Field was not returned by the repository metadata tool | `delete_branch_on_merge` not observable with current permission/tool | Medium |
| Main protection | Branch API reports `protected=false`; detailed protection request returned HTTP 401; no visible repository rulesets were returned | GitHub `/branches/main`, `/branches/main/protection`, and `/rulesets` read-only audit | Critical |
| Required checks | Eight CI jobs exist and all passed on post-merge run #110 for `main` SHA `564f9609...`; whether they are blocking branch rules is not observable | `.github/workflows/ci.yml`; Actions run #110, job inspection; protection policy unavailable | High |
| Force push | No enforced protection rule was observable; branch metadata reports `main` not protected | Branch API `protected=false`; detailed rule fields unavailable | High |
| Branch deletion | No enforced protection rule was observable; all listed branches report `protected=false` | Branch inventory API; detailed rules unavailable | High |
| Tag protection | No tag-protection rule was observable; `/tags/protection` returned HTTP 404 and `/rulesets` returned an empty list | Public GitHub REST audit | Critical |
| Release ownership | The authenticated connector has repository `admin` permission. The existing release was created by the repository owner role; no role-based release runbook was recorded before this document | Repository collaborator permission; release API metadata | High |
| `v0.1.0` release | Existing release is published, not draft, and marked prerelease. Release metadata reports `immutable=false`; that field is not tag protection. The annotated tag ref peels to the required exact commit. GitHub `latest` endpoint returned 404, so no stable latest release is present | Release API, annotated tag API, `/releases/latest` | Low |
| Environments | GitHub API returned `total_count=0`; workflow references `production`; no `staging` environment was returned | `/environments` read-only response; `.github/workflows/deploy.yml` | High |
| Branch hygiene | 16 remote branches, 12 closed PRs, 11 merged PRs, 0 open PRs. Ten remote branches are associated with merged PRs; plan, backup, and historical branches remain | Branch API, PR list, local ahead/behind inspection | High |

### Release and CI evidence

- PR #12 is merged into `main` with merge commit
  `564f96095df3f763bdbfc56383457e0508cbf6a4`.
- Post-merge GitHub Actions run #110 (run ID `29633080033`) completed with
  conclusion `success` for that exact `main` SHA.
- The existing tag ref `refs/tags/v0.1.0` points to an annotated tag object
  whose peeled object is commit
  `ad8bc3afc9b62f549e4f054e181ef9decbecb341`.
- The existing GitHub Release `v0.1.0` is published and prerelease, not draft.
  Its release metadata is not treated as tag-protection evidence.

## 4. Target governance

The target is proportional to a repository with a limited maintainer group:

- Changes to `main` require a pull request; direct pushes are restricted to an
  explicitly documented emergency path.
- Force pushes and deletion of `main` are blocked.
- The eight CI checks in Section 6 are required before merging.
- `v*` tags cannot be updated or deleted after creation.
- Squash merge is the primary method for routine changes. Whether merge commits
  or rebase remain enabled is a senior decision, not an implicit change in this
  documentation PR.
- Emergency bypass is limited to the repository owner/admin role, requires a
  written reason and post-event review, and must not silently bypass failed
  security or migration evidence.
- Rules must include a recovery path and must not require an external reviewer
  who cannot exist in a one-maintainer model. A second approval is a governance
  decision and must be balanced against lockout risk.
- A release is created only from an exact green `main` commit. The tag target is
  recorded before release publication and is never moved afterward.
- Production deployment uses a protected `production` environment with approval
  when that environment is created in a separately authorized task.
- Merged branch cleanup uses an exact reviewed list. It never uses a broad
  wildcard and never deletes `main` or tags.

## 5. Gap matrix

| ID | Area | Current | Target | Severity | Recommended action | Authorization required |
| -- | ---- | ------- | ------ | -------- | ------------------ | ---------------------- |
| G-01 | Main pull-request gate | `main` reports `protected=false`; detailed rules are not observable | Pull request required; direct push restricted | Critical | Apply a narrowly scoped `main` ruleset after owner-access rehearsal | Repository owner/admin |
| G-02 | Required CI checks | Eight jobs run and passed, but blocking status-check configuration is not observable | Require all eight exact checks from Section 6 | High | Configure required checks after confirming job names and owner bypass | Repository owner/admin |
| G-03 | Force push and deletion | No enforced protection rule was observable | Block force push and branch deletion on `main` | High | Include both restrictions in the `main` ruleset | Repository owner/admin |
| G-04 | Tag immutability | No tag protection rule was observable; `v0.1.0` currently points to the expected commit | Protect `v*` from update, force update, and deletion | Critical | Add a tag ruleset and test it on a non-release tag or ruleset preview | Repository owner/admin |
| G-05 | Merge methods | Merge, rebase, and squash are enabled | Squash is the primary routine method | Medium | Decide whether to retain merge/rebase for exceptional history-preserving cases | Repository owner/admin |
| G-06 | Branch auto-delete | Setting was not returned by the repository tool | Decide whether merged branches are deleted automatically or by reviewed cleanup | Medium | Record the choice, then apply only after retention decisions | Repository owner/admin |
| G-07 | Environment approval | No GitHub environments were returned; deploy workflow references `production` | Create `production` with approval and branch/ref policy; add `staging` only when needed | High | Configure environments and test approval/recovery before deployment | Repository owner/admin and deployment approver |
| G-08 | Release ownership | Admin permission exists, but role ownership is not recorded | Role-based ownership and evidence matrix in Section 8 | High | Approve the matrix and add it to the release operating procedure | Senior release approver |
| G-09 | Branch retention | 16 remote branches remain, including merged and backup branches | Exact classification, owner decision, and expiry for backups | High | Approve the register in Section 7; run a separate cleanup task | Repository owner/admin |
| G-10 | Bypass and lockout recovery | Bypass actors and administrator enforcement are not observable | Bypass is limited, documented, audited, and tested without locking out owner | High | Rehearse owner access and emergency recovery before enforcement | Repository owner/admin |

## 6. Required checks contract

The following names are the exact GitHub Actions job names from `.github/workflows/ci.yml`
and were observed on successful post-merge run #110. The workflow triggers on
`push` to `main` and `pull_request` targeting `main`.

| Exact check name | Workflow source | Event | Purpose | Recommended blocking status | Consequence if failed | Bypass policy |
| ---------------- | --------------- | ----- | ------- | ------------------------- | -------------------- | ------------- |
| Dependency Audit | `.github/workflows/ci.yml` (`dependency-audit`) | `push`, `pull_request` | Block critical/high/unknown Composer and high NPM production advisories | Required | Security risk may enter `main` | No routine bypass; emergency bypass requires written owner reason and follow-up |
| Pint | `.github/workflows/ci.yml` (`style`) | `push`, `pull_request` | Enforce PHP formatting | Required | Code style drift and avoidable review noise | Owner emergency only; rerun required |
| Frontend Build | `.github/workflows/ci.yml` (`frontend-build`) | `push`, `pull_request` | Prove frontend assets compile | Required | Deployable frontend artifact is unproven | Owner emergency only; rerun required |
| Generated Drift | `.github/workflows/ci.yml` (`generated-drift`) | `push`, `pull_request` | Detect missing or untracked generated Wayfinder output | Required | Route/client generated contract may drift | Owner emergency only; generated evidence required |
| PHPUnit Parallel | `.github/workflows/ci.yml` (`unit-feature-tests`) | `push`, `pull_request` | Run the default PHPUnit release gate with coverage threshold | Required | Regression or coverage failure blocks merge | No routine bypass; senior review required for emergency |
| Migration and Seed | `.github/workflows/ci.yml` (`migration-seed`) | `push`, `pull_request` | Validate migration and seed boot path in isolated CI SQLite | Required | Schema bootstrap is unproven | Owner emergency only; isolated rerun required |
| OpenAPI Drift | `.github/workflows/ci.yml` (`openapi-drift`) | `push`, `pull_request` | Confirm generated API contract matches the snapshot | Required | API contract drift may reach `main` | No routine bypass; contract review required |
| PostgreSQL Concurrency | `.github/workflows/ci.yml` (`postgres-concurrency`) | `push`, `pull_request` | Validate PostgreSQL concurrency and Document 05 contracts | Required | database-specific correctness is unproven | No routine bypass; database evidence required |

The CI workflow itself was not changed by this audit. “Observed success” is not
the same as “configured as a required branch rule”; that configuration remains a
gap until an authorized settings task verifies it.

## 7. Branch retention register

Relationship counts use `git rev-list --left-right --count origin/main...origin/<branch>`:
`behind/ahead`. A diverged branch is not, by itself, evidence of a missing fix.
Merged PR and merge SHA references below were verified from the GitHub PR list.
The category `protected` means “reserved from cleanup”; it does not claim that a
GitHub protection rule is currently active.

| Branch | SHA | Relationship to main | Associated PR | Category | Recommendation | Proposed expiry | Owner decision |
| ------ | --- | -------------------- | ------------- | -------- | -------------- | --------------- | -------------- |
| `main` | `564f96095df3f763bdbfc56383457e0508cbf6a4` | 0 behind / 0 ahead | PR #12 merged at this SHA | protected | Never delete; apply protection separately | Never | Confirm owner/bypass model |
| `agent/ai-development-policy` | `5092ea7c432176b4959eaa7cd684dae29d1152b5` | 66 behind / 1 ahead | None found | historical-evidence | Retain until policy decisions are copied to the current plan, then review | Review by 2026-08-17 | Yes |
| `agent/document-08-release-staging-mobile-pilot` | `80cc39bf5535d1098ed69130d86097ad386fdc70` | 2 behind / 2 ahead | PR #11 merged at `ed8f2026...` | merged-candidate-for-deletion | Delete only after exact merged SHA and any needed evidence are confirmed | Next owner-approved cleanup window | Yes |
| `agent/08-a-release-metadata` | `3ab52303f3a81858da91a3f9bc62ad48dd4bb398` | 1 behind / 1 ahead | PR #12 merged at `564f960...` | merged-candidate-for-deletion | Safe candidate after PR evidence is retained | Next owner-approved cleanup window | Yes |
| `audit/member-admin-role-review-2026-07-10` | `e557224dcc107accc9cc6df729f96d1e3f454738` | 72 behind / 0 ahead | PR #2 merged at `a7e8826...` | merged-candidate-for-deletion | Delete after PR #2 evidence is retained | Next owner-approved cleanup window | Yes |
| `backup/mixed-document-03-ai-workflow-2026-07-14` | `ace85aafd46089b840b57d2a8e5581c85b65eaf7` | 66 behind / 3 ahead | None found | backup-with-expiry | Retain as a named backup only; verify the backup need before expiry | Proposed 2026-08-17 | Yes |
| `chore/ai-development-workflow` | `642b954bbbe04954c26da9da3fefaf6487121d2a` | 66 behind / 2 ahead | None found | historical-evidence | Retain until its verification guidance is superseded | Review by 2026-08-17 | Yes |
| `fix/pgsql-concurrency-worker-script-race` | `262977ba4b4fbf7e814ed40b0727ebff36065f0f` | 64 behind / 1 ahead | PR #7 merged at `154bf24...` | merged-candidate-for-deletion | Delete after CI evidence and merge SHA are retained | Next owner-approved cleanup window | Yes |
| `hotfix/google-sso-timeout` | `62c6ce5685ee71588687ec197b658768f488b6d1` | 70 behind / 3 ahead | PR #4 merged at `b8a0fa3...` | merged-candidate-for-deletion | Delete after PR evidence is retained | Next owner-approved cleanup window | Yes |
| `plan/member-pos-cart` | `3d40629ec1a8389eec34603e47b737656abc1979` | 95 behind / 1 ahead | PR #1 closed, not merged | historical-evidence | Preserve until useful decisions are moved to the current plan | No expiry until transfer | Yes |
| `release/v0.1.0-prep` | `a4c37fdb7806d88f523b9dafa2d024b79b4d18bb` | 3 behind / 0 ahead | PR #10 merged at `ad8bc3af...` | merged-candidate-for-deletion | Delete only after tag/release evidence is retained | Next owner-approved cleanup window | Yes |
| `remediation/document-02-payment-reservation` | `879d649d50b9f807da65e284b6e64d4f7f50b87a` | 71 behind / 23 ahead | PR #3 merged at `5d48749...` | merged-candidate-for-deletion | Delete after PR #3 evidence is retained; do not infer missing fixes from divergence | Next owner-approved cleanup window | Yes |
| `remediation/document-03-pii-encryption-rollout-2026-07-14` | `ace85aafd46089b840b57d2a8e5581c85b65eaf7` | 66 behind / 3 ahead | None found | historical-evidence | Retain only while its rollback-safe PII evidence is needed; then review with backup branch | Review by 2026-08-17 | Yes |
| `remediation/document-03-pii-encryption-rollout-clean` | `c02baa003a7a047f284e0755baa524c736cd23b1` | 66 behind / 18 ahead | PR #5 merged at `27a7275...` | merged-candidate-for-deletion | PR merge is verified, but branch-only commit count requires owner review before deletion | Pending owner decision | Yes |
| `remediation/document-04-organization-auth-token-cutover` | `e5bffaac0a9dbd6947d4568870794b2ab05f5506` | 40 behind / 0 ahead | PR #8 merged at `0684e32...` | merged-candidate-for-deletion | Delete after PR #8 evidence is retained | Next owner-approved cleanup window | Yes |
| `remediation/document-05-audit-pagination-contract-tests` | `05ea5e36ded1d8b21643c2015e20e162814a8d85` | 10 behind / 0 ahead | PR #9 merged at `21a45fc...` | merged-candidate-for-deletion | Delete after PR #9 and CI evidence are retained | Next owner-approved cleanup window | Yes |

No branch was deleted. The 16-branch count includes `main`. There are ten
remote branches associated with merged PRs, but deletion remains a separate
authorized action and is not implied by this register.

## 8. Release ownership matrix

Roles are intentionally used instead of individual names. The role assignments
below are a target operating model and are not evidence that GitHub settings
have already been applied.

| Activity | Responsible role | Approver | Evidence | Prohibited action |
| -------- | ---------------- | -------- | -------- | ----------------- |
| Approve PR | Maintainer/reviewer role | Senior release approver for release-scoped work | Review record and green required checks | Approve without reviewing scope or failed required evidence |
| Merge PR | Repository maintainer role | Repository owner/admin role | Merge SHA and PR record | Merge directly to `main` outside the approved bypass path |
| Create release tag | Release owner role | Senior release approver | Exact green `main` SHA and tag ref | Move, force-update, or delete an existing release tag |
| Publish GitHub Release | Release owner role | Senior release approver | Release notes, tag, prerelease state, and release URL | Mark an internal alpha as stable/latest without approval |
| Approve production deployment | Deployment approver role | Repository owner/admin or designated operations owner | Environment approval and exact target SHA | Approve a mutable branch-only deployment identity |
| Execute deployment | Deployment operator role | Deployment approver role | Workflow run, target SHA, previous SHA, and maintenance result | Deploy from an unreviewed ref or expose secrets in logs |
| Approve rollback | Incident/release approver role | Repository owner/admin role | Incident record and verified rollback plan | Automatic destructive database rollback without review |
| Rotate secrets | Security owner role | Repository owner/admin role | Secret-manager audit and rotation record | Commit, print, or paste credential values |
| Delete merged branches | Repository maintainer role | Repository owner/admin role | Exact reviewed branch list and retained PR evidence | Wildcard deletion, deleting `main`, or deleting tags |
| Modify rulesets | Repository owner/admin role | Senior governance approver role | Before/after rule record and recovery rehearsal | Apply rules that can lock out the owner without recovery |

## 9. Proposed rollout phases

### Phase 1 — Observe and approve

- Review this record and confirm the limited-maintainer model.
- Confirm the required approval count, bypass owner, and emergency evidence
  requirements.
- Confirm whether branch-up-to-date enforcement is compatible with the workflow.
- Approve the exact proposed rules and recovery path before applying anything.

### Phase 2 — Apply main protection

Separate authorized task. Apply the pull-request gate, exact required checks,
force-push block, deletion block, and a tested owner/admin bypass. Verify the
owner can still open, review, and merge a controlled test PR.

### Phase 3 — Apply tag protection

Separate authorized task. Protect `v*` from update, force update, and deletion.
Verify the rule on a disposable non-release tag or through a ruleset preview;
do not test by modifying `v0.1.0`.

### Phase 4 — Configure environment governance

Separate authorized task. Create `production` only with the designated
deployment approver, ref policy, and recovery path. Create `staging` when the
staging workstream has an owner and isolated credentials. Do not read or print
secret values.

### Phase 5 — Branch cleanup

Separate authorized task using the exact register above. First preserve PR,
merge-SHA, release, and historical evidence. Delete only branches explicitly
approved by the owner; preserve `plan/member-pos-cart` until its useful
decisions are transferred.

### Phase 6 — Verify and rollback rehearsal

Prove that the owner can recover from an overly strict ruleset, that required
checks resolve to the intended job names, that tag rules do not alter existing
tags, and that deployment environment approval can be cancelled safely.

## 10. Proposed application commands

The following are runbook examples only. Every mutating command is explicitly
marked `DO NOT RUN IN 08-B` and was not executed during this audit.

### Read-only checks

```bash
gh api repos/johnd-creator/kojaya
gh api repos/johnd-creator/kojaya/branches/main
gh api repos/johnd-creator/kojaya/rulesets
gh api repos/johnd-creator/kojaya/environments
gh pr list --repo johnd-creator/kojaya --state all --limit 100
git ls-remote --heads origin
git ls-remote origin refs/tags/v0.1.0
```

### Main ruleset application

```bash
# DO NOT RUN IN 08-B. Apply only after owner-access rehearsal and approval.
gh api --method POST repos/johnd-creator/kojaya/rulesets \
  --input approved-main-ruleset.json
```

Recovery note: record the returned ruleset ID and use a targeted `DELETE` only
after confirming the ruleset caused a lockout. If the owner cannot recover,
use a pre-confirmed repository-admin recovery path; do not improvise a broad
ruleset deletion.

### Tag ruleset application

```bash
# DO NOT RUN IN 08-B. Protect v* only after exact rule review.
gh api --method POST repos/johnd-creator/kojaya/rulesets \
  --input approved-v-tag-ruleset.json
```

Recovery note: disable or delete only the recorded tag ruleset ID if it blocks
an approved non-release operation. Never delete or move `v0.1.0` as a test.

### Environment application

```bash
# DO NOT RUN IN 08-B. Configure only in the separately authorized environment task.
gh api --method PUT repos/johnd-creator/kojaya/environments/production \
  --input approved-production-environment.json
```

Recovery note: cancel pending deployments and use the recorded environment
configuration to restore the intended reviewer/ref policy. Do not delete an
environment as an unreviewed rollback when it may contain protected settings.

### Exact branch cleanup

```bash
# DO NOT RUN IN 08-B. Use an owner-approved exact list, never a wildcard.
git push origin --delete agent/08-a-release-metadata
git push origin --delete release/v0.1.0-prep
```

Recovery note: restore a mistakenly deleted branch from its recorded SHA with a
targeted `git push origin <recorded-sha>:refs/heads/<branch>`. This does not
restore unrecorded branch metadata or replace a lost tag, so verify the list
before deleting anything.

## 11. Acceptance checklist

- [x] Current repository state and evidence are recorded.
- [x] Permission and tool limitations are recorded.
- [x] All eight CI checks are mapped by exact name.
- [x] `main` target rules are documented without claiming they are applied.
- [x] `v*` tag target rules are documented without modifying `v0.1.0`.
- [x] Environment gaps are documented.
- [x] Every remote branch found in the audit is classified.
- [x] Deletion candidates have PR and merge evidence where applicable.
- [x] Release ownership is documented by role.
- [x] Lockout risk and recovery requirements are documented.
- [x] Staged rollout phases are documented.
- [x] No GitHub setting was changed.
- [x] No branch or tag was deleted or moved.
- [x] No secret or credential value was exposed.

## 12. Senior decisions required

The following decisions must be made before an administrative task applies
governance changes:

1. Required approval count for ordinary PRs, and whether one-maintainer work
   uses a self-review or documented emergency path.
2. Which role may bypass rules, for which failure classes, and how the bypass is
   audited.
3. Whether `main` must be up to date with the base branch before merge.
4. Whether automatic deletion after merge is enabled, disabled, or replaced by
   reviewed cleanup windows.
5. Whether merge commits and rebase remain allowed in addition to squash.
6. Branch retention duration for historical and backup branches.
7. Which role approves `production` deployments and whether a `staging`
   environment is created in the next operations workstream.
8. Which role may create release tags and publish GitHub Releases.
9. Whether the 18 branch-only commits on the merged Document 03 branch are
   retained as evidence or approved for deletion after review.

This record does not silently decide any of the above.
