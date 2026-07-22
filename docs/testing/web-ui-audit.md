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
cp .env.playwright.example .env
php artisan key:generate --force --no-interaction
php artisan migrate:fresh --force --no-interaction
php artisan db:seed --class=UiAuditSeeder --force --no-interaction
php artisan wayfinder:generate --no-interaction
npm run build
npx playwright install chromium
```

Capture pilot desktop tanpa membutuhkan baseline lengkap:

```bash
npm run ui:capture -- --project=desktop
```

Audit accessibility pilot:

```bash
npm run ui:a11y -- --project=desktop
```

Mode workflow: `capture`, `compare`, `accessibility`, dan `full`.

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
