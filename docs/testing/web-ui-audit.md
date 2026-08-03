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
accessibility report paths, and error text. Top-level head/tested/base SHA
fields provide PR traceability. Artifacts contain `ui-audit-output/`,
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
