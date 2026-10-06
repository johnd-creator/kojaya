# Phase 6 — Bundle C Backend PC acceptance

Checkpoint: 2026-10-06 02:27 WIB (Asia/Jakarta).

**Current result: BUNDLE-C-BACKEND-PC = PASS. COVERAGE-01 = PASS.**

The bounded completion section at the end supersedes earlier BLOCKED statuses.
QA-12 and Phase 7 remain NOT EXECUTED; this result does not close Phase 6.

The authenticated continuation below supersedes the initial access-pending
checkpoint. Initial observations are preserved as historical evidence.

## Identities and inherited server evidence

- QA target: `https://qa.kojaya.id`.
- Deployed application candidate supplied by the owner: `v1.0.0-rc.14`, exact SHA `043b5b004d66f04e60eef2b0c7a8cdb38fce278a`.
- Expected database: `kojaya_qa`; legacy `kojaya` prohibited.
- Local control repository: `johnd-creator/kojaya`; HEAD `36da86702a63821b03539fdb9c9d48f0c49918b7`, branch `astra/phase5-rc11-qa-deployment-readiness`.
- Local HEAD is control evidence, not a replacement application candidate.

The owner supplied BUNDLE-C-SERVER PASS: exact serving rc.14, DB identity,
zero pending migrations, maintenance off, nginx/PHP-FPM/queue/scheduler health,
natural queue recycle, 75+ minute observation, failed jobs 0 to 0, no new
Laravel ERROR/CRITICAL or PHP fatal, no observed 5xx, and matching synthetic
financial state. These are inherited server findings, not independently rerun
from the PC. No SSH, database connection, service operation or QA-11 observation
was performed in this task.

## Executed client-side scenarios

| Scenario | Result |
| --- | --- |
| HTTPS login reachability | HTTP 200; TLS verification result 0, no certificate bypass |
| Anonymous web dashboard | HTTP 302; protected route redirects |
| Anonymous API session | HTTP 401 |
| Empty JSON API login | HTTP 422; required-field validation boundary |
| Empty JSON web login without CSRF | HTTP 419; CSRF rejection |
| Anonymous member dashboard API | HTTP 401 |
| Anonymous member savings summary API | HTTP 401 |
| Anonymous member notification list API | HTTP 401 |
| First-party compiled CSS | HTTP 200, `app-DGSZfKhZ.css` |
| First-party application JS | HTTP 200, `app-B_oHUHyM.js` |
| First-party login JS | HTTP 200, `Login-au_WjYKl.js` |
| Browser login rendering | Expected login form, logo, username/password controls and submit button rendered in the in-app browser |
| Browser console observation | No error/warning entries captured on the observed login page; authenticated flows not observed |

Unexpected HTTP 5xx: **0 across 11 completed HTTP probes**. This count does
not cover authenticated or financial flows. Browser console observation is
limited to the captured login page, not all application navigation.

## Pending acceptance and blocker

The available Chrome QA tab was on the login page; its automation attachment
timed out. A separate in-app browser successfully rendered the login page and
had no authenticated session. No approved synthetic credentials were supplied
for this task, and previously transferred plaintext handoff credentials were
documented as removed. The owner was asked to sign in using an approved
synthetic account or provide a private local handoff path without posting
passwords/tokens in chat. No credential recovery or account creation occurred.
The owner replied that the synthetic accounts are not remembered. Access
remains unavailable; retrieval by the separately authorized QA server operator
is the required handoff, since SSH is prohibited for this PC task.

| Gate | Disposition |
| --- | --- |
| QA-10A login/session | INCOMPLETE: initial page, anonymous boundary and CSRF pass; valid/invalid-credential login, persistence, logout and post-logout invalidation not executed |
| QA-10B authorization | INCOMPLETE: anonymous 401 verified; authenticated role/member/management 403 boundaries not executed |
| QA-10C member | NOT EXECUTED: zero synthetic members independently verified from the PC |
| QA-10D financial | NOT EXECUTED: expected Rp125,000 savings, Rp500,000 principal, Rp515,000 schedule and three installments not independently reconciled from the PC |
| QA-10E API contract | INCOMPLETE: 401 and required-field 422 verified; successful authenticated resources, 403, receipts and financial reads pending |
| QA-10F browser | PARTIAL: login and required first-party assets verified; authenticated navigation/logout pending |

Financial state: **UNVERIFIED**, not MISMATCH. Expected absence of disbursement
and duplicate payment/ledger/receipt records has not been established by PC
readback. No financial/business write request was issued.

Confirmed P0/P1/P2/P3 candidates: **0 / 0 / 0 / 0** in the limited completed
scenarios. Missing account access is an execution prerequisite blocker, not an
application defect or proof of defect-free acceptance. Smallest next scope:
provide approved synthetic member/staff authentication, then run remaining
read-only regression and normal login/logout flows. No source fix or redeploy
is currently justified by these observations.

## Boundaries and completion

QA runtime modified: NO. Production touched: NO. No SSH, deployment, migration,
seeder, direct SQL, new candidate, permission mutation, payment gateway action,
transfer, disbursement, or source fix. No QA-12 final gate or Phase 7 activity.
Existing local changes preserved. This file is local evidence only; no commit,
push or merge performed. Backend-PC acceptance remains BLOCKED until all
authenticated gates are completed; inherited server PASS does not substitute
for these PC checks.

## Authenticated continuation — 2026-10-06

The owner supplied the existing synthetic Anggota credential and authorized QA
testing. It was used only against `https://qa.kojaya.id`, without creating a
credential file or placing secrets in this report. All browser/web sessions and
temporary API tokens created during this continuation were logged out through
normal application endpoints. No logout-all, credential reset or account change.

| Executed scenario | Result |
| --- | --- |
| Valid web login | PASS: browser navigated to member dashboard, identity KOP-001, active synthetic member |
| Session persistence | PASS: dashboard, profile, savings and loans remained authenticated, including full-page navigation |
| Profile | PASS: expected synthetic identity and email; validated membership |
| Invalid API credentials | PASS: one controlled incorrect-password attempt rejected HTTP 422 |
| CSRF-protected web client login | PASS: normal XSRF-cookie flow, HTTP 200; no guard bypass |
| Member to management web boundary | PASS: `/cooperative/members` rendered 403 Forbidden |
| Member to management API boundary | PASS: `/api/v1/members` HTTP 403 |
| Member to monitoring API boundary | PASS: `/api/monitoring/health` HTTP 403 |
| API session / dashboard / profile | PASS: HTTP 200 |
| Savings summary / ledger / payments / loans | PASS: HTTP 200, observed expected response envelopes and values |
| Notification list API | PASS: HTTP 200; no mark-read or notification send |
| Receipt metadata | PASS: two pre-existing receipts, HTTP 200, no missing receipt issuance required |
| Actual protected receipt PDFs | PASS: both HTTP 200, application/pdf, 878797 and 878806 bytes via authenticated web session |
| Bearer-only signed PDF attempt | HTTP 401; web session authentication required. Normal authenticated web downloads above succeeded |
| API logout / revoked session | PASS: logout HTTP 200; subsequent old-token session HTTP 401 |
| Web logout / session invalidation | PASS: browser returned to login and protected member navigation remained at login; separate web client logout HTTP 204 and post-logout member HTTP 401 |
| Browser console | No error/warning captured through observed authenticated profile/savings/loan flows |

### Financial readback

- Savings and payment total: Rp125,000, matching web and API.
- Two APPROVED cash payments: Rp25,000 and Rp100,000; two existing receipt numbers.
- Two SAVING_PAYMENT ledger credits: SUKARELA Rp25,000 and WAJIB Rp100,000.
- Repeated reads retained two payments and two ledger entries; no duplicate in
  exposed member data. No global table-count claim is made without staff access.
- One APPROVED loan: principal Rp500,000; scheduled/outstanding total Rp515,000.
- Three pending installments: Rp171,666.67, Rp171,666.67 and Rp171,666.66;
  exact sum Rp515,000. Zero paid installments; `disbursed_at` null.
- Browser shows manager review and Pengurus approval complete, disbursement
  waiting. No payment, disbursement, gateway charge or business-state write.

**Financial state: MATCH for the authenticated member's exposed data.**
Synthetic members independently verified from PC: **1 active**, not the required
two. The five supplied account identifiers do not establish the peer's current
membership status or accessible financial state.

### Findings and remaining gate

| ID | Candidate severity | Observation / smallest correction scope |
| --- | --- | --- |
| BC-PC-UI-01 | P3 | Approved loan detail labels absent reference as "Menunggu Persetujuan Pengurus" although status is approved and approval timestamps exist. Correct only fallback copy in a separate reviewed task; no fix made |
| BC-PC-UI-02 | P3 | Whole-rupiah display shows all three installments as Rp171,667, summing visually to Rp515,001. API decimal schedule sums exactly Rp515,000. Improve rounding/presentation in a separate reviewed task; financial data is consistent |

Confirmed candidates: P0=0, P1=0, P2=0, P3=2 in executed coverage.
Unexpected HTTP 5xx=0 in completed probes and observed browser flows; no server
log review or exhaustive network distribution is claimed.

Login/session PASS; 401/403/422 PASS; representative member authorization PASS;
single-member financial readback MATCH; authenticated API scenarios executed.
Full member acceptance remains incomplete because the second active member was
not independently verified. Admin Koperasi/Pengurus/Manajer successful access
was not exercised: their passwords were reported removed, and no reset is
authorized. OpenAPI schema validation of all payloads was not performed; this
is representative behavioral/envelope verification only.

**BUNDLE-C-BACKEND-PC remains BLOCKED**, with no observed P0/P1 blocker.
Smallest remaining prerequisite: approved peer/staff access or a new explicit
owner decision about the required evidence scope. No runtime/source fix or
redeployment is justified by missing credentials. QA-12 and Phase 7 not executed.
QA runtime configuration/source modified NO; production touched NO; no SSH or
database connection. Auth/session/token/audit side effects occurred normally
through login/logout; no financial mutation was requested. Local evidence only,
no commit/push/merge; original user changes preserved.

## BUNDLE-C-BACKEND-PC-COVERAGE-01 — 2026-10-06

**COVERAGE-01 PASS; FINAL BUNDLE-C-BACKEND-PC PASS.**

Credential source method: **PRIVATE QA HANDOFF**. Both owner-specified local
Windows handoff files were available outside the repository. Staff/peer
credentials were read only by the local HTTP client and submitted to the QA
login endpoint; primary-member financial scenarios were retained from earlier
PASS and were not rerun. No credential, token, cookie, signed URL or reset token
is included in evidence. Handoff files were not moved, copied into the repository
or deleted by this task; no deletion was requested.

Target remains `https://qa.kojaya.id`, deployed candidate
`043b5b004d66f04e60eef2b0c7a8cdb38fce278a` per server PASS handoff.
Local control HEAD remains `36da86702a63821b03539fdb9c9d48f0c49918b7`.
No SSH, server observation rerun, database connection or source/runtime change.

### Identity and session results

All four accounts: API login HTTP 200, identity email matched the intended
synthetic account, session HTTP 200, web login using normal CSRF cookie flow
HTTP 200. Each API logout returned 200 and its revoked-token session returned
401; each web logout returned 204 and subsequent protected dashboard returned
401. No unexpected login/session error occurred.

| Account | Actual role | Coverage result |
| --- | --- | --- |
| Peer | Anggota; member status ACTIVE | PASS: member dashboard/profile web and API 200; savings, payment history, loans and notifications API 200 |
| Admin | Admin Koperasi | PASS: dashboard, member-management read, member creation form and import page 200; member/loan API reads 200 |
| Pengurus | Pengurus Koperasi | PASS: dashboard, member/loan web and API reads, member creation form 200 |
| Manajer | Manajer Koperasi | PASS: dashboard and member/loan web and API reads 200; creation boundary denied |

### Peer data and isolation

Peer savings balance and total paid are both 0. Payments and loans are empty
arrays, consistent with the actual peer fixture; no replacement financial data
was created. Two existing primary-member receipt metadata paths (payments 1 and
2) and primary-member loan 1 each returned 403 for the peer. Peer notification
list and membership/profile resources remained accessible. Together with the
prior primary-member ACTIVE check, **two active synthetic members are now
independently verified from the PC**. No requirement was imposed that the peer
match the primary member's financial fixture.

### Bounded authorization matrix

Route selection used the exact rc.14 source contract, not invented permissions:
`RolePermissionSeeder`, `CooperativeMemberPolicy::create` (`manage_cooperative_member`),
`RoleController::index` (`manage_roles`), and existing web/API route middleware.
No permission or role was changed during testing.

| Role | Allowed capability / observed result | Denied capability / observed result |
| --- | --- | --- |
| Anggota (peer) | Own profile/dashboard/savings/payments/loans/notifications: 200 | Staff member list API and web: 403; system roles: 403; primary-member receipt/loan objects: 403 |
| Admin Koperasi | Member list, creation form and import page: 200; member/loan API: 200 | System role administration `/roles`: 403 |
| Pengurus Koperasi | Member/loan reads and member creation form: 200 | System role administration `/roles`: 403 |
| Manajer Koperasi | Member/loan reads: 200 | Member creation form: 403; system role administration `/roles`: 403 |

Only GET capability checks were used after authentication. No import execution,
member creation, loan review/approval/rejection, or system-role mutation request
was submitted. Role-specific loan review/final approval mutation gates are not
claimed tested here; read-only representative capabilities satisfy this bounded
completion scope.

### Final disposition

- Peer Member PASS; Admin Koperasi PASS; Pengurus PASS; Manajer PASS.
- Representative authorization matrix PASS; unexpected 401/403: 0 (all denials
  above were expected); privilege escalation observed: 0; cross-role/member data
  leakage observed: 0; unexpected 5xx: 0.
- Login/session, member regression, representative API behavior, 401/403/422 and
  primary-member financial MATCH combine with prior executed PASS evidence.
- P0=0, P1=0, P2=0, P3=2. Preserve BC-PC-UI-01 and BC-PC-UI-02 above; no fix.
- Full OpenAPI schema conformance is not claimed; API PASS is the requested
  representative behavioral contract acceptance.
- QA runtime modified NO; business data modified NO; production touched NO.
  Normal login/logout token, session and audit side effects occurred; every
  session/token created in this completion was logged out/revoked.
- No account reset, seeding, migration, deployment, direct SQL or rc.15.
- Evidence remains local/uncommitted. QA-12 and Phase 7 NOT EXECUTED. Stop here.
