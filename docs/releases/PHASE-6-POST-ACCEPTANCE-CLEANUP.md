# QA-SANITIZE-01 — Phase 6 post-acceptance cleanup

## QA-SANITIZE-01-RESUME — completed

2026-10-06, final database reconciliation at **10:01:35 WIB**
(`2026-10-06T03:01:35Z`): **PASS — CLEAN_FROZEN**.
This completion supersedes the historical blocked sanitation status below.
Phase 6 acceptance remains CLOSED PASS. No DEV rehearsal or Phase 7 started.

Evidence closure is documentation only, based on main
`6a8004686caf1c1d4474f6d6c3f007296c7fde91`; the serving application candidate
remains the separate SHA recorded below. Final sanitation status:

```text
QA-SANITIZE-01=PASS
QA_FINAL_STATE=CLEAN_FROZEN
Verified backup: kojaya-qa-kojaya_qa-20261006T024420Z-043b5b0
Serving application candidate: 043b5b004d66f04e60eef2b0c7a8cdb38fce278a
QA DB: kojaya_qa
Synthetic users: 5 -> 0
Synthetic members: 2 -> 0
Tokens: 4 -> 0
Device bindings: 1 -> 0
Payments: 2 -> 0
Ledger: 2 -> 0
Receipts: 2 -> 0
Loans: 1 -> 0
Installments: 3 -> 0
Notifications/outbox: 31 -> 0
Immutable audit rows retained: 104
Organizations: UNCHANGED
Roles: UNCHANGED
Permissions: UNCHANGED
Role-permission mapping: UNCHANGED
Queue: PASS
Scheduler: PASS
Queued jobs: 0
Failed jobs: 0
Origin smoke: login 200; anonymous dashboard 302; unexpected 5xx 0
Public HTTPS sanitation-time probe: NOT REVERIFIED FROM EXECUTION ENVIRONMENT
Windows temporary credential material: REMOVED
Server temporary credential material: REMOVED
Production touched: NO
Phase 7: NOT STARTED
```

The sanitation-time public HTTPS probe limitation does not invalidate the prior
public QA acceptance evidence in [PHASE-6-QA-RELEASE.md](./PHASE-6-QA-RELEASE.md):
no application source, deployment or runtime configuration changed during
sanitation. Windows removal is the supplied owner-confirmed completion status;
this operator did not access the Windows filesystem.

### Identity, backup and scope

- QA vhost `qa.kojaya.id` resolves in Nginx configuration to this checkout's
  `public` directory; serving checkout SHA remains
  `043b5b004d66f04e60eef2b0c7a8cdb38fce278a`.
- `qa:deployment-identity --expect=kojaya_qa` PASS before and after cleanup;
  application-runtime queries independently asserted `current_database()`
  equals `kojaya_qa` before every inventory/mutation/verification script.
- Exact backup ID `kojaya-qa-kojaya_qa-20261006T024420Z-043b5b0` verified
  independently before and after cleanup with `backup:verify`, explicit
  `--expected-database=kojaya_qa --require-private-permissions`: PASS.
  SHA-256 remains
  `709a05d07c0bb7f13216d1c239dd5a570596110aa764feee2b8dc98f1b217b36`.
  Backup files remain intact.
- Cleaned exact identities: `qa.bundleb.admin@example.test` (user 2),
  `qa.bundleb.pengurus@example.test` (3),
  `qa.bundleb.member@example.test` (4),
  `qa.bundleb.peer@example.test` (5), and
  `qa.bundleb.manajer@example.test` (6).
  `qa.bundlea.admin@example.test`: **ABSENT**, before and after.
- Synthetic members: IDs 1 / `KOP-001` (user 4) and 2 / `KOP-002` (user 5).
  Exact-email and user ownership were checked, including soft-deleted rows.
  No arbitrary user classification was used.

### Ownership checks and reconciliation

Read-only discovery inspected live schema foreign keys across all tables,
member/user references, financial source/payable polymorphic references,
approval subjects, download document relationships and audit subjects.
Incoming FK references to every selected parent were checked again before
mutation. No outside-owned incoming relationship was found. Shared reference
parents (organization, contribution types, loan type) were preserved.
Graph/Boost tools were unavailable; evidence came from source and runtime schema
inspection, rather than an index-completeness claim.

All database deletions used Laravel Query Builder/application models in one
serializable transaction. Dependents were explicitly removed before parents;
no blind cascade, reset command, migration, reseed or ad-hoc psql deletion ran.
Members were force-deleted using `CooperativeMember::withTrashed()` models.
Reference digests and zero residual selected IDs were checked before commit,
then independently checked again after commit.

| Category | Before | After |
| --- | ---: | ---: |
| Known synthetic users | 5 | 0 |
| Active synthetic member rows | 2 | 0 |
| Soft-deleted synthetic member rows | 0 | 0 |
| Personal access tokens | 4 | 0 |
| Social accounts | 0 | 0 |
| Device bindings | 1 | 0 |
| Attributable sessions | 0 | 0 |
| Password-reset rows | 0 | 0 |
| Notification preferences | 0 | 0 |
| User-role links | 5 | 0 |
| Direct user-permission links | 0 | 0 |
| Cooperative payments | 2 | 0 |
| Dues invoices | 4 | 0 |
| Cooperative ledger / savings entries | 2 | 0 |
| Receipts | 2 | 0 |
| Loans | 1 | 0 |
| Loan installments | 3 | 0 |
| Notifications | 21 | 0 |
| Cooperative notification outbox | 6 | 0 |
| General notification outbox | 4 | 0 |
| Fixture approval logs | 5 | 0 |
| Receipt download logs | 4 | 0 |
| Fixture receipt PDF artifacts | 2 | 0 |

Payment identity-linked amounts were Rp25,000 and Rp100,000; total Rp125,000.
Loan principal Rp500,000, scheduled total Rp515,000, three installments,
no disbursement. These were reconciliation markers, not selection criteria.
Peer had no payment, savings ledger or loan. Member documents, onboarding,
opening-balance batches, payment intents, loan payments/restructures,
resignation/support and POS/member-store dependents had no linked rows.
No synthetic document/upload files were identified beyond the two receipt PDFs.
The receipt service's local-disk path contract and exclusive receipt ownership
were checked; both PDFs were deleted after database commit and verified absent.

**Retained audit records: 104** attributable records; their nullable actor
references were explicitly cleared under the existing `SET NULL` contract.
Audit JSON was checked internally for populated reusable credential fields;
no such field was found and no audit payload or credential was printed.
Total audit rows after cleanup: 116, including 12 other retained records;
target actor references: 0. Audit subject history remains preserved.

### Reference integrity

All complete-row, sorted SHA-256 digests matched before commit and after commit.

| Reference table | Count unchanged | SHA-256 unchanged |
| --- | ---: | --- |
| organizations | 1 | `bfadfae02a0f9c084b8ecdebe7c600831a42da36b07ac258cfa70edbc673d5ac` |
| roles | 16 | `59c6da4afb414e09a1a1ca7d184a6ea3885ad1b77e8c71ca705fe2c33a189cfa` |
| permissions | 129 | `7d84bfeefbe195b656c801ccbcc70ab74409e0a6165a2047a24bf95938b73640` |
| role_has_permissions | 476 | `bae756a7af63b9e77a1f3f17b5ad33a435c65d3c832573bf70d517dd314137e5` |
| cooperative_contribution_types | 4 | `e4984912120ce5d8581c546c17c1bb1b735737d0de74f0654b2e96060c988621` |
| loan_types | 3 | `485d302edabb9e39eec4de18cac02738e4e94d20d759f4054454cdf9447f0047` |

Additional unchanged empty reference tables: chart_of_accounts,
cooperative_closing_checklists, cooperative_period_locks,
cooperative_shu_periods; each has SHA-256
`e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.

### Temporary material and runtime health

- `/tmp/kojaya-qa-credentials-im7_fw1f/`: **REMOVED**, verified absent.
- `/tmp/kojaya-bundleb-private-2hrzgwp5/`: **REMOVED**, verified absent.
  Neither directory's credential contents were read during removal.
- Known reset database material: 0; metadata-only top-level `/tmp` search
  found no Kojaya/QA-SANITIZE password/reset filename artifact. Unrelated
  temporary trees, release evidence, runtime logs and backups were preserved.
- Windows credential handoff cleanup: **REMOVED**, supplied owner status;
  no Windows filesystem access was claimed.
- Application boot PASS; maintenance **OFF**; pending migrations **0**.
- `kojaya-qa-queue.service`: **ACTIVE**.
- `kojaya-qa-schedule.timer`: **ACTIVE / ENABLED**;
  scheduler service last result success, exit status 0.
- Queued jobs **0**, failed jobs **0**.
- HTTP origin smoke with `Host: qa.kojaya.id`: `GET /login` **200**,
  anonymous `GET /dashboard` **302**; repeated after cleanup.
  Unexpected HTTP 5xx **0 in these smoke requests**.
  Public HTTPS did not complete from this execution environment; origin smoke
  is verified, external HTTPS reachability is not claimed. Nginx origin listens
  on port 80, so a loopback TLS request was not a valid origin health check.
- Application source modified **NO**; deploy/migrate/reseed **NO**;
  production and legacy `kojaya` database touched **NO**.
- Existing user changes in `PHASE-6-QA-RELEASE.md` were preserved;
  no commit or push was performed during sanitation. Evidence closure brings
  only this document into its dedicated branch; unrelated local changes are
  excluded. This documentation PR does not deploy or change runtime state.

**QA final state: CLEAN_FROZEN. STOP.**

## Historical QA-SANITIZE-01-FIX-02 — backup gate resolved

2026-10-06: **FIX-02 PASS**. The historical backup blocker below is resolved;
sanitation remains unexecuted and QA is not yet CLEAN_FROZEN.

The owner supplied privileged metadata inventory: four expected directories and
nine managed backup files, no symlinks or unrelated content. Preflight reconfirmed
operator `fauzi`, the exact rc.14 SHA and `kojaya_qa` identity PASS. No active
backup:database, pg_dump or pg_restore process was detected before repair.

Ownership was normalized only under `storage/app/private/backups` to
`fauzi:kojayaqa`, using the authorized bounded sudo chown. Modes were preserved:
all directories `0700`, all regular files `0600`. The managed backup then ran
as normal `fauzi`, without sudo or switching to the PHP-FPM user. Its three new
files initially inherited group `fauzi`; their group was normalized to
`kojayaqa` as their owner, with no mode change.

- Backup ID: `kojaya-qa-kojaya_qa-20261006T024420Z-043b5b0`.
- Manifest created: `2026-10-06T02:44:21+00:00` (09:44:21 WIB).
- Format: PostgreSQL custom; size: 622514 bytes; environment: `qa`.
- SHA-256: `709a05d07c0bb7f13216d1c239dd5a570596110aa764feee2b8dc98f1b217b36`.
- Git SHA: `043b5b004d66f04e60eef2b0c7a8cdb38fce278a`.
- Independent `backup:verify` for this exact artifact with
  `--expected-database=kojaya_qa --require-private-permissions`: PASS, repeated
  successfully after the new-file group normalization. Archive integrity,
  checksum, manifest/provenance, database identity and private permissions PASS.
- Final complete subtree metadata: four directories `fauzi:kojayaqa 0700`,
  twelve regular managed files `fauzi:kojayaqa 0600`; no other file types.
  Temporary `tmp` and `verify` directories are empty.
- No source, business database state, production, legacy database, other
  storage trees or configuration changes. No sanitation/deletion performed.

Resume QA-SANITIZE-01 from STEP 2: **YES**, after returning control to the owner.
The historical observations below remain retained as prior evidence.

## Historical blocked attempt

Status: **BLOCKED**. Assessment: 2026-10-06T02:35:02Z (09:35:02 WIB).
Phase 6 remains CLOSED PASS. This operation does not reopen acceptance or begin
Phase 7 or DEV rehearsal.

### Target and safety gate

- Nginx QA vhost: `qa.kojaya.id`, docroot matches this checkout's `public` directory.
- Serving checkout SHA: `043b5b004d66f04e60eef2b0c7a8cdb38fce278a`.
- `qa:deployment-identity --expect=kojaya_qa`: PASS, including independent
  PostgreSQL identity check. Inventory also independently asserted `kojaya_qa`.
- Pre-cleanup backup: FAIL; no verified final snapshot, identifier, or checksum.
- Official `backup:database --purpose=manual --disk=local
  --directory=backups/database --no-interaction` failed with
  `Unable to create private backup directory`.
- An alternate output directory through the same official mechanism also failed
  with `mkdir(): Permission denied`; its temporary staging path remains under
  `storage/app/private/backups/tmp`.
- The backup parent is owned by `kojayaqa:kojayaqa`, mode `0700`.
  Running the official backup as `kojayaqa` with noninteractive sudo failed:
  `sudo: a password is required`. No permissions were changed.
- No cleanup mutation was attempted because the mandatory verified snapshot
  gate did not pass. Partial filesystem artifacts from failed backup attempts
  have not been exhaustively inventoried; no successful backup is claimed.

### Read-only inventory

Exact email matches only; no arbitrary identity classification was performed.

| Synthetic identity | Users before | Users after |
| --- | ---: | ---: |
| qa.bundleb.admin@example.test | 1 | 1 |
| qa.bundleb.pengurus@example.test | 1 | 1 |
| qa.bundleb.manajer@example.test | 1 | 1 |
| qa.bundleb.member@example.test | 1 | 1 |
| qa.bundleb.peer@example.test | 1 | 1 |
| qa.bundlea.admin@example.test | 0 | 0 |

Synthetic users: **5 → 5** (row count; active status not separately inspected).
Personal access tokens matching those user IDs and the User morph type:
**4 → 4**. No token values were read or printed.

Members, payments, ledger entries, receipts, loans, installments, device
bindings, notifications/outbox, sessions and reset material: counts not verified;
no deletion or sanitization performed. Earlier identities beyond the six exact
targets have not been exhaustively inventoried.

### Preservation and runtime observations

- Queue service: active at inspection.
- Scheduler timer: active and enabled at inspection.
- Failed/queued job counts, pending migrations, maintenance status, HTTP smoke
  and unexpected HTTP 5xx counts: NOT VERIFIED. No full runtime PASS claimed.
- Temporary credential directories/files: BLOCKED; no deletion performed.
- Roles, permissions, mappings, organization/master data and audit history:
  no cleanup writes performed; content digests not independently compared.
- Application source modified: NO. Deployment executed: NO.
- Migration executed: NO. Production and legacy `kojaya` accessed: NO.
- Business/reference data outside synthetic acceptance scope modified: NO.
- Existing user changes to `PHASE-6-QA-RELEASE.md` were preserved.
- No commit or push performed. Evidence is documentation only.

### Remaining dependency

An authorized operator with access as the QA backup directory owner must create
and independently verify the final backup using the existing mechanism before
any sanitation continues. Verification must require `kojaya_qa` and private
artifact permissions. A historical backup is not a substitute. No application
source repair is authorized. The existing reset/reseed command is not a suitable
substitute for bounded Phase 6 cleanup.

Historical QA state at this attempt: **BLOCKED**. This was superseded by the
completed sanitation above.

Historical Windows cleanup requirement (now owner-confirmed REMOVED):

- `C:\Users\John\AppData\Local\Temp\kojaya-qa-acceptance\acceptance-credentials.json`
- `C:\Users\John\AppData\Local\Temp\kojaya-qa-acceptance\development-pc-member-credential.json`

No Windows-local file contents were requested. Stop at this gate.
