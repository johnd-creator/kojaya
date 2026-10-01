# Kojaya Web UI Audit Framework

## Tujuan

Framework ini membuka screen Kojaya melalui Chromium nyata, memakai akun role audit dengan data repeatable, menyimpan screenshot per viewport/state, membandingkan screenshot dengan golden baseline, menjalankan `@axe-core/playwright`, dan menghasilkan manifest yang dapat dibaca reviewer manusia maupun ChatGPT. Framework ini hanya menguji UI dan tidak mengubah business logic.

## Hubungan dengan test lain

- PHPUnit menguji business logic, authorization, persistence, dan kontrak HTTP secara cepat tanpa browser.
- Screenshot comparison menguji perubahan piksel yang terlihat, tetapi tidak memahami apakah tugas pengguna selesai atau copywriting tepat.
- Playwright UI audit menggabungkan browser nyata, Inertia navigation, runtime console/network health, screenshot, dan accessibility.
- ChatGPT bertindak sebagai UX reviewer atas artifact yang dihasilkan. ChatGPT tidak mengubah kode dalam audit ini; temuan menjadi backlog/task terpisah.

## Menjalankan lokal

Siapkan environment terisolasi dari `.env.playwright.example`, lalu gunakan database SQLite khusus:

```bash
cp .env.playwright.example .env.playwright
php artisan --env=playwright key:generate --force --no-interaction
php artisan --env=playwright migrate:fresh --force --no-interaction
php artisan --env=playwright db:seed --class=UiAuditSeeder --force --no-interaction
php artisan --env=playwright wayfinder:generate --no-interaction
npm run build
npx playwright install chromium
```

The first migration command must use `--env=playwright`; the shared
development `.env` and database are never changed. Playwright owns
`127.0.0.1:18080` and does not reuse an existing server. Laravel and
Chromium use `UI_AUDIT_FIXED_NOW`; the isolated session lifetime is long
enough that the fixed historical date cannot make the browser discard cookies.

## Inventory and coverage contract

`tests/visual/coverage/cooperative-pages.json` is the single source of truth
for Playwright discovery, manifest entries, accessibility selection, baseline
validation, and Laravel route reconciliation. Every named GET/HEAD route for
`/cooperative/**`, `cooperative.*`, `/member/**`, `member.*`, `/dashboard`, and
`/settings/profile` is either audited or listed in
`cooperative-route-exclusions.json`. Exclusions are limited to API JSON,
downloads, and PDFs and require a specific reason.

To add a page, add route, role/auth state, deterministic fixture, ready locator,
goal, viewport policy, and risk level to the registry. Add fixture data to
`UiAuditSeeder` when needed. Dynamic routes must use a seeded fixture. The
route guard fails closed for stale/missing routes, duplicate IDs, duplicate
screenshot names, and invalid exclusions.

Current coverage is 61 renderable named routes, 72 desktop scenarios, 59 tablet
scenarios, and 41 mobile scenarios. All desktop scenarios are mandatory.
Tablet/mobile policy is declared per scenario and is enforced by the manifest
and baseline guards.

## Determinism, artifacts, and baselines

The seeder is accepted only in `testing` or `playwright` and uses fixed IDs,
dates, roles, organizations, transactions, and local fixtures. Queues are
synchronous, mail uses the array driver, storage is local, and no external
provider is called. The stabilizer is installed before navigation and waits
after navigation for fonts, images, loading markers, Inertia content, and
stable layout measurements. Runtime reports capture page errors, console
issues, failed requests, unexpected HTTP responses, and hydration failures.
The application font is served from committed audit assets through Playwright
routing, so screenshots do not depend on a CDN response or a runner's font
cache. The production font family and weights remain unchanged.
The canonical comparison environment is the GitHub Actions Ubuntu 24.04
runner with PHP 8.4, Node 22, locked Playwright/Chromium, locale `id-ID`,
timezone `Asia/Jakarta`, the fixed backend/browser clock, and a clean
`database/playwright.sqlite`. Host-local screenshots are advisory; they must
not replace the Ubuntu baseline without runner evidence.

Run capture twice against clean audit databases and compare screenshot hashes:

```bash
UI_AUDIT_REPEATABILITY_LEFT=/tmp/a/screenshots \
UI_AUDIT_REPEATABILITY_RIGHT=/tmp/b/screenshots \
npm run ui:repeatability
```

Only after `unexpected_hash_differences` is zero should reviewed Linux
baselines be generated with `npm run ui:update`. Screenshots and baselines are
full-page images: the project viewport controls the exact PNG width, while the
height follows the rendered document height and must be at least the project
viewport height. Legitimate page content changes may therefore change a
baseline's height. The visual comparison remains responsible for detecting
unexpected height or content drift; `ui:verify-baselines` validates PNG
integrity, the first IHDR chunk, the exact project width, and the minimum
viewport height, in addition to missing, orphan, and duplicate-name errors.
Never update a baseline merely to hide a regression, and never run `ui:update`
in CI.

For PR #21, the reviewed desktop candidates were captured by the Ubuntu 24.04
GitHub Actions environment at the exact tested head. The capture produced 72
desktop candidates and 72 clean runtime reports; these candidates are the
canonical source for the committed desktop baselines. A successful capture is
not a visual comparison result: the pull-request `compare` run must still pass
against the reviewed files. Local screenshots from another operating system
remain advisory and must not replace these baselines.

The manifest is generated from expected registry entries, not only successful
tests. Each entry has status (`passed`, `failed`, `captured`, `skipped`, or
`not-run`), route/role/viewport/state, screenshot paths, runtime and
accessibility report paths, and error text.

Manifest schema version 3 records separate PR and workflow-dispatch evidence:

- Pull-request runs use the PR head SHA, the tested synthetic merge SHA, the
  PR base SHA, the numeric PR number, and requested `compare`/`desktop`/`all`
  parameters.
- Workflow-dispatch runs use the checked-out branch HEAD for head and tested
  SHA, resolve the current remote default-branch SHA for `base_sha`, use
  `pull_request_number: null`, and preserve the requested mode, viewport, and
  scope even when `full` internally executes Playwright in compare mode.
- Required identity values are resolved before the audit and are validated as
  strict 40-character lowercase hexadecimal SHAs. Dispatch never uses the
  audited head as a base fallback.

The Laravel `ui-audit:coverage` command owns the canonical
`coverage/cooperative-route-coverage.json` and `.md` files. Global Playwright
setup removes stale `ui-audit-output`, recreates its runtime directories, runs
this command with `execFile`, and verifies that fresh canonical JSON exists
before tests start. They contain route reconciliation fields such as
discovered, renderable, audited, excluded, uncovered, stale, and duplicate
counts. Playwright teardown preserves those files, embeds the parsed object
under `route_coverage`, and writes visual-entry counts separately to
`coverage/visual-entry-coverage.json` and `.md`. The workflow does not run a
competing pre-cleanup coverage command; setup is the single authoritative
generation path.

The `ui:verify-artifact` contract verifier runs before upload in CI. It checks
manifest identity, requested parameters, dispatch base SHA, canonical route
coverage, and visual-entry coverage. Missing or inconsistent metadata fails
the workflow while the `if: always()` upload still preserves malformed output
for diagnosis.

Artifacts contain `ui-audit-output/`,
`playwright-report/`, and `test-results/`; auth state, cookies, `.env`, SQLite,
and secrets are excluded.

## Accessibility debt and UX handoff

Default desktop pages and important dialogs/forms run axe on desktop. New critical or
serious violations fail; moderate/minor findings are reported. The current
Linux audit records 210 exact existing critical/serious node fingerprints in
`tests/visual/accessibility-known-findings.json`, each with screen, rule,
impact, selector, tracking ID, reason, and expiry. Waivers never suppress new
nodes or broad selectors.
Responsive visual projects still run their declared viewport policy, but
accessibility debt fingerprints are evaluated on the mandatory desktop
surface. Responsive accessibility is a separate follow-up when a screen has
viewport-specific markup changes.

ChatGPT reviews the downloaded artifact; it does not modify code. Give it the
manifest and all screenshots/reports, then classify P1 blocker/P2 major/P3
polish/P4 preference with screen, role, viewport, evidence, impact, and
acceptance criteria. Visual diffs and traces are evidence, while PHPUnit and
runtime health remain correctness gates.

Capture pilot desktop tanpa membutuhkan baseline lengkap:

```bash
npm run ui:capture -- --project=desktop
```

Audit accessibility pilot:

```bash
npm run ui:a11y -- --project=desktop
```

Mode workflow: `capture`, `compare`, `accessibility`, dan `full`. Capture mode
also publishes `tests/visual/baseline-candidates/`; candidates must be
reviewed before copying them into `tests/visual/baselines/`.

## Baseline dan visual diff

Baseline berada di `tests/visual/baselines/<project>/`. Perbarui baseline hanya ketika perubahan visual memang disengaja, sudah direview manusia, dan diff-nya dipahami:

```bash
npm run ui:update
```

Jangan menjalankan `--update-snapshots` di GitHub Actions. Jangan menaikkan tolerance untuk sekadar membuat job hijau. Perubahan font, browser, atau dependency rendering adalah perubahan baseline besar dan harus direview. Baseline CI dibuat di Linux; baseline lokal dari platform lain tidak boleh menimpa baseline CI tanpa verifikasi.

Diff dapat dilihat dari HTML report atau file actual/diff di `test-results/`. Trace dibuka dengan:

```bash
npx playwright show-trace test-results/<test>/trace.zip
```

## Artifact GitHub Actions

Workflow `Kojaya UI Audit` berjalan pada `workflow_dispatch` dan pull request yang mengubah frontend, route/controller terkait, seeder audit, atau harness. Artifact `kojaya-ui-audit-<SHA>` berisi `ui-audit-output/manifest.json`, screenshot, laporan axe JSON, runtime report, `playwright-report/`, dan `test-results/` termasuk trace ketika test gagal. Storage state, cookie, `.env`, database SQLite, dan secret tidak di-upload.

## Peran ChatGPT dan prompt audit

Download artifact dari halaman run GitHub Actions, unzip, lalu berikan seluruh artifact kepada ChatGPT dengan prompt berikut:

```text
Audit artifact Kojaya Web UI Audit untuk commit <SHA>.

Nilai setiap screen berdasarkan:
- task completion;
- visual hierarchy;
- information architecture;
- consistency;
- readability;
- accessibility;
- responsiveness;
- error prevention;
- destructive-action safety;
- loading, empty, error, and validation states;
- copywriting;
- role and permission clarity.

Kelompokkan temuan:
- P1 blocker;
- P2 major;
- P3 polish;
- P4 preference.

Untuk setiap temuan sertakan:
- screen;
- role;
- viewport;
- bukti visual;
- dampak;
- rekomendasi;
- acceptance criteria.

Jangan mengubah kode. Hasilkan laporan audit dan task prompt terpisah.
```

Baseline tidak boleh diperbarui hanya untuk menyembunyikan regression. Perubahan yang terdeteksi harus dibahas dulu sebagai perubahan UI atau bug.
# RC-11-FIX-05D full-mode remediation evidence

Historical exact rc.9 audit #293 (`36791630817`, head
`f52610326e4ff43bff05b9afe2efcdc9646eefb7`) failed with 95 failures:
469 passed, 213 skipped, no reported flakes. The artifact was downloaded once.

Primary observed categories cover every failed test: D (shared session/state)
53; C (stale responsive comparison candidates) 40; B (onboarding fixture
assumption) 2; A/E/F/G 0. The 53 D failures include 5 logout tests,
18 Pengurus documentation tests, 2 inventory documentation tests,
18 store-credit tests, 9 member/POS/profile screens, and 1 store-credit
accessibility test. Profile additionally has a masked incorrect heading
expectation; user-menu logout additionally has an incorrect `data-testid`
locator and a closed mobile sidebar assumption.

Logout invalidated the shared server-side Pengurus session serialized for all
projects. Traces and error contexts show subsequent `/dashboard` and screen
requests redirecting to `/login`. Runtime network failures were aborted
store-credit navigations, not ignored console exceptions. Logout tests now
authenticate independently and check that the canonical shared session still
serves the dashboard after each logout, on each viewport.

The onboarding registry previously used an active member, whose intended
controller behavior redirects to `/member`. A separate pending-member user
now owns existing AUD-009; the active member is unchanged. Both visual and
accessibility scenarios assert that onboarding remains on its intended route.
The homepage screenshot is not accepted as an onboarding baseline.

All 40 remaining responsive baseline candidates were visually reviewed in
expected/actual pairs: their runtime reports are clean and their first/retry
images are byte-identical. Independent clean Linux audit `36804238517` at
`d54c4d5b7f25b9c84e2baab84bb703c1ae2afcd6` reproduces all 40 images
byte-for-byte against historical audit #293. These 40 candidates were adopted.
Five additional reviewed candidates were adopted: onboarding in all three
viewports now represents the pending member rather than the active-member
redirect; member-list tablet/mobile now reflects current lifecycle counts,
the import action and removal of synthetic sparklines already present in the
approved desktop UI. Member-table truncation/scroll behavior is unchanged.
All 45 first/retry pairs are byte-identical, with zero runtime issues. The
onboarding desktop image also matches independent PR audit `36804238522`.
Only these 45 PNGs were copied unmodified: desktop 1, tablet 22, mobile 22.
Screenshot thresholds, accessibility and full/all/all coverage enforcement
remain unchanged.

Local Windows has no PDO SQLite and no installed WSL/container runtime, so
equivalent full browser execution is delegated to the existing Ubuntu Actions
workflow. No workstation PHP changes or shared database operations are used.
Local harness checks: 5 passed; baseline inventory after replacement: 234
valid, 0 missing, 0 orphan, 0 duplicate, 0 invalid dimensions. ESLint cannot
include these tests through the existing TypeScript project service. Explicit
type checking finds three pre-existing errors in unchanged accessibility and
stable-screen helpers; scoped transpilation succeeds for all five changed TS
files. Final CI evidence remains pending.

The initial remediation full audit had 445 passed, 120 failed and 213
policy-skipped tests, but all 234 expected screens executed: 189 comparison
passes, 45 screenshot failures, zero skipped expected screens. The other 75
failures report expired waiver UI-A11Y-015. There are 84 existing waiver entries
across 22 screens, all expiring 2026-09-30; this is not evidence that all 84
violations remain. No waiver was removed, extended or suppressed. Owner
authorized the broader accessibility-debt scope on this PR. Labels/control
names, nested back-button links and contrast are being corrected. Axe output
is now retained before the unchanged expiry rejection, with expiry-boundary
regression coverage; only proven resolved fingerprints may be removed.

Owner authorized a necessary security patch on this PR. Only
`league/commonmark` changes in composer.lock, 2.10.0 to 2.10.2, addressing
[GHSA-3q6v-r5mr-hxv8](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8).
The dependency's GFM TableStartParser previously scanned the entire growing
paragraph for a pipe at every line; the patched parser checks the current
delimiter line first. Laravel Str::markdown delegates to this converter; no
application invocation accepting untrusted Markdown was found in the scoped
source search, so public exploitability is not asserted. A separate
compatibility review confirms table extension registration/API remains intact.
Regression checks enforce the patched installed version, process numeric and
punctuation-leading pipe-free paragraphs (25,000 lines each), and preserve
normal paragraphs and table cells: 3 tests, 13 assertions PASS locally.
No wall-clock assertion is used under parallel coverage. An initial 100,000
line probe exceeded local 128 MB PHP memory; the workstation was not altered.
Required Composer audit passes with existing severity rules. The unchanged
npm production audit finds a further high Axios advisory blocker at transitive
1.18.1; updating it to 1.20.0 requires separately requested owner authorization.

PR #96 remains draft and unmerged. Historical rc.9 evidence remains intact.
If this source/test/baseline remediation is later merged, it supersedes rc.9;
no rc.10 is declared yet. PR #92 and QA remain untouched; Phase 6 is not started.
# Historical failure inventory
## Historical failed-test inventory (all 95)

Primary categories follow the observed failure, with the independently proven
onboarding fixture error overriding its misleading screenshot symptom.

| Viewport | Spec | Test | Category |
| --- | --- | --- | --- |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-dashboard-dashboard-admin-koperasi @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-members-index-default @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-members-index-pending-filter @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-members-index-no-results @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-payments-index-pending @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-payments-index-empty @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-ledger-index-default @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-loan-types-index-default @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-points-index-default @visual | C |
| tablet | screens/admin-koperasi.visual.spec.ts | admin-payments-index-selected @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-dashboard-dashboard-admin-koperasi @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-index-default @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-index-pending-filter @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-index-no-results @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-payments-index-pending @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-payments-index-empty @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-ledger-index-default @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-loan-types-index-default @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-show-pending-review @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-show-revision @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-members-show-active @visual | C |
| mobile | screens/admin-koperasi.visual.spec.ts | admin-payments-index-selected @visual | C |
| desktop | screens/inventory.visual.spec.ts | documentation-landing-pengurus @visual @inventory | D |
| desktop | screens/inventory.visual.spec.ts | documentation-article-pengurus @visual @inventory | D |
| tablet | screens/inventory.visual.spec.ts | ledger-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | loan-types-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | loans-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | loans-show-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | members-resignations-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | members-show-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | operator-dashboard-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | payments-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | shu-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | reports-index-default @visual @inventory | C |
| tablet | screens/inventory.visual.spec.ts | member-onboarding-default @visual @inventory | B |
| mobile | screens/inventory.visual.spec.ts | ledger-index-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | loan-types-index-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | members-resignations-index-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | members-show-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | payments-index-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | savings-withdrawals-index-default @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | member-onboarding-default @visual @inventory | B |
| mobile | screens/inventory.visual.spec.ts | documentation-article-anggota @visual @inventory | C |
| mobile | screens/inventory.visual.spec.ts | documentation-article-mobile-overflow @visual @inventory | C |
| desktop | screens/documentation.visual.spec.ts | landing shows articles for Pengurus | D |
| desktop | screens/documentation.visual.spec.ts | loan approval article renders body | D |
| desktop | screens/documentation.visual.spec.ts | SHU article renders body | D |
| desktop | screens/documentation.visual.spec.ts | contextual help on SHU page | D |
| desktop | screens/documentation.visual.spec.ts | no role selector with correct context label | D |
| desktop | screens/documentation.visual.spec.ts | pengurus cannot access anggota article (403) | D |
| tablet | screens/documentation.visual.spec.ts | landing shows articles for Pengurus | D |
| tablet | screens/documentation.visual.spec.ts | loan approval article renders body | D |
| tablet | screens/documentation.visual.spec.ts | SHU article renders body | D |
| tablet | screens/documentation.visual.spec.ts | contextual help on SHU page | D |
| tablet | screens/documentation.visual.spec.ts | no role selector with correct context label | D |
| tablet | screens/documentation.visual.spec.ts | pengurus cannot access anggota article (403) | D |
| mobile | screens/documentation.visual.spec.ts | landing shows articles for Pengurus | D |
| mobile | screens/documentation.visual.spec.ts | loan approval article renders body | D |
| mobile | screens/documentation.visual.spec.ts | SHU article renders body | D |
| mobile | screens/documentation.visual.spec.ts | contextual help on SHU page | D |
| mobile | screens/documentation.visual.spec.ts | no role selector with correct context label | D |
| mobile | screens/documentation.visual.spec.ts | pengurus cannot access anggota article (403) | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko index default @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko index empty @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko index search results @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko index no results @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko open account dialog @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko validation error @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko show positive balance @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko show negative balance @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko show suspended @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko show empty ledger @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko show with ledger @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko transfers pending @visual | D |
| desktop | screens/store-credit.visual.spec.ts | Saldo Toko transfers empty @visual | D |
| tablet | screens/store-credit.visual.spec.ts | Saldo Toko index default @visual | D |
| tablet | screens/store-credit.visual.spec.ts | Saldo Toko show positive balance @visual | D |
| mobile | screens/store-credit.visual.spec.ts | Saldo Toko index default @visual | D |
| mobile | screens/store-credit.visual.spec.ts | Saldo Toko open account dialog @visual | D |
| mobile | screens/store-credit.visual.spec.ts | Saldo Toko show positive balance @visual | D |
| desktop | auth/inertia-navigation.spec.ts | user menu logout reaches the login page without a reload | D |
| tablet | auth/inertia-navigation.spec.ts | header logout reaches the login page without a reload | D |
| tablet | auth/inertia-navigation.spec.ts | user menu logout reaches the login page without a reload | D |
| mobile | auth/inertia-navigation.spec.ts | header logout reaches the login page without a reload | D |
| mobile | auth/inertia-navigation.spec.ts | user menu logout reaches the login page without a reload | D |
| desktop | screens/members.visual.spec.ts | Anggota Koperasi default @visual | D |
| tablet | screens/members.visual.spec.ts | Anggota Koperasi default @visual | D |
| mobile | screens/members.visual.spec.ts | Anggota Koperasi default @visual | D |
| desktop | screens/pos.visual.spec.ts | POS register default @visual | D |
| tablet | screens/pos.visual.spec.ts | POS register default @visual | D |
| mobile | screens/pos.visual.spec.ts | POS register default @visual | D |
| desktop | screens/profile.visual.spec.ts | Profil default @visual | D |
| tablet | screens/profile.visual.spec.ts | Profil default @visual | D |
| mobile | screens/profile.visual.spec.ts | Profil default @visual | D |
| desktop | accessibility/inventory.accessibility.spec.ts | store-credit-index-default @accessibility | D |
## Selected baseline file inventory (45)

- `tests/visual/baselines/mobile/dashboard--dashboard--admin-koperasi.png`
- `tests/visual/baselines/tablet/dashboard--dashboard--admin-koperasi.png`
- `tests/visual/baselines/mobile/dues-payments-ledger--ledger--admin-default.png`
- `tests/visual/baselines/tablet/dues-payments-ledger--ledger--admin-default.png`
- `tests/visual/baselines/mobile/loans--index--admin-default.png`
- `tests/visual/baselines/tablet/loans--index--admin-default.png`
- `tests/visual/baselines/mobile/members--index--admin-default.png`
- `tests/visual/baselines/tablet/members--index--admin-default.png`
- `tests/visual/baselines/mobile/members--index--no-results.png`
- `tests/visual/baselines/tablet/members--index--no-results.png`
- `tests/visual/baselines/mobile/members--index--pending-filter.png`
- `tests/visual/baselines/tablet/members--index--pending-filter.png`
- `tests/visual/baselines/mobile/members--show--active.png`
- `tests/visual/baselines/mobile/members--show--pending-review.png`
- `tests/visual/baselines/mobile/members--show--revision.png`
- `tests/visual/baselines/mobile/payments--index--empty.png`
- `tests/visual/baselines/tablet/payments--index--empty.png`
- `tests/visual/baselines/mobile/payments--index--pending.png`
- `tests/visual/baselines/tablet/payments--index--pending.png`
- `tests/visual/baselines/mobile/payments--index--selected.png`
- `tests/visual/baselines/tablet/payments--index--selected.png`
- `tests/visual/baselines/tablet/rewards-shu--index--admin-default.png`
- `tests/visual/baselines/mobile/documentation--article--default-anggota.png`
- `tests/visual/baselines/mobile/documentation--article--mobile-overflow.png`
- `tests/visual/baselines/mobile/dues-payments-ledger--ledger--default.png`
- `tests/visual/baselines/tablet/dues-payments-ledger--ledger--default.png`
- `tests/visual/baselines/mobile/loans--index--default.png`
- `tests/visual/baselines/tablet/loans--index--default.png`
- `tests/visual/baselines/tablet/loans--loans--default.png`
- `tests/visual/baselines/tablet/loans--show--default.png`
- `tests/visual/baselines/desktop/member-portal--onboarding--default.png`
- `tests/visual/baselines/mobile/member-portal--onboarding--default.png`
- `tests/visual/baselines/tablet/member-portal--onboarding--default.png`
- `tests/visual/baselines/mobile/members--index--default.png`
- `tests/visual/baselines/tablet/members--index--default.png`
- `tests/visual/baselines/mobile/members--members-resignations--default.png`
- `tests/visual/baselines/tablet/members--members-resignations--default.png`
- `tests/visual/baselines/mobile/members--show--default.png`
- `tests/visual/baselines/tablet/members--show--default.png`
- `tests/visual/baselines/tablet/operator--dashboard--default.png`
- `tests/visual/baselines/mobile/dues-payments-ledger--payments--default.png`
- `tests/visual/baselines/tablet/dues-payments-ledger--payments--default.png`
- `tests/visual/baselines/tablet/reports--reports--default.png`
- `tests/visual/baselines/mobile/dues-payments-ledger--withdrawals--default.png`
- `tests/visual/baselines/tablet/rewards-shu--shu--default.png`

### Expanded accessibility remediation evidence

- Owner authorized real remediation of all 84 legacy waivers across 22 screens; no expiry extension or axe exclusion was added.
- Diagnostic accessibility run `36807340471` on `f94bf0cd9d6e56ff1cf92f38afb2ff59d1cdd088` retained 71 raw axe reports before failing closed on expired waivers. Every one of the 22 waiver screens has a report, with zero critical/serious nodes. All 84 resolved waiver records were therefore removed, not renewed.
- Capture run `36807343081` on the same SHA executed 234/234 expected screens with no runtime errors, warnings, failed requests, or unexpected responses. Using the unchanged comparator (`threshold=0.15`, `maxDiffPixelRatio=0.001`), exactly nine baselines differ: loans detail, POS transaction detail, and profile, each at desktop/tablet/mobile. Reviewed differences are the intended destructive-button and warning-text contrast improvements, not changed layout or behavior.
- The three desktop captures are byte-identical to independent PR audit `36807344007`; that audit passed 174 tests and failed only those three visual comparisons. Nine contrast baselines are refreshed from the reviewed capture artifact. One tablet loan baseline overlaps the previous 45-file refresh, so the cumulative baseline inventory is 53 unique files.
- These diagnostic runs are not final full-audit acceptance. Final exact-head full/all/all, automatic PR audit, CI, and Phase 4 must still pass. npm Dependency Audit remains blocked by Axios 1.18.1 until separate minor-update authorization is received.
