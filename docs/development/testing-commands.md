# Local Testing Commands — Kojaya

Dokumen ini mendefinisikan command lokal untuk feedback cepat sesuai risk-based execution policy. Phase 2 hanya menambahkan command wrapper dan dokumentasinya; pengelompokan test `slow`, `pgsql`, dan `external` dikerjakan pada Phase 3.

## Prinsip Penggunaan

- Pilih command paling sempit yang mencakup perubahan.
- Test berat hanya dijalankan atas instruksi eksplisit user.
- Pastikan test memakai environment PHPUnit dari `phpunit.xml`, bukan database development bersama.
- Agent tidak menunggu GitHub Actions setelah push.

## Command Tersedia

### `composer lint:dirty`

Menjalankan Laravel Pint hanya pada file PHP yang berubah.

```bash
composer lint:dirty
```

### `composer test:fast`

Menjalankan PHPUnit dengan pengecualian group `slow`, `pgsql`, dan `external`.

```bash
composer test:fast
```

Catatan: metadata group belum diterapkan pada Phase 2. Sampai Phase 3 selesai, command ini belum dapat menjamin seluruh test berat ter-filter.

### `composer test:sso`

Menjalankan test Google SSO pada satu module.

```bash
composer test:sso
```

### `composer test:payment`

Menjalankan test unit money dan state machine/payment outbox yang relevan.

```bash
composer test:payment
```

Command ini tidak mencakup `PaymentConcurrencyTest`; test concurrency PostgreSQL tetap berada pada lane high-risk dan memerlukan persetujuan user.

### `composer test:full`

Menjalankan seluruh test secara parallel.

```bash
composer test:full
```

Ini adalah command berat. Jangan menjalankannya sebagai default agent workflow tanpa instruksi eksplisit user.

## Pemilihan Berdasarkan Risiko

| Risiko | Verifikasi lokal yang disarankan |
| --- | --- |
| Low risk | `git diff --check`, review diff, dan `composer lint:dirty` bila PHP berubah |
| Module risk | `composer lint:dirty` dan command module terkait seperti `composer test:sso` |
| Cross-module risk | Pint dan test unit/feature yang langsung terkait |
| High-risk data/concurrency | Test terfokus; PostgreSQL concurrency hanya dengan persetujuan user |

## Command yang Tidak Dijalankan Default oleh Agent

Command berikut memerlukan instruksi eksplisit user:

```text
composer test:full
full PHPUnit suite
parallel PHPUnit suite
coverage
PostgreSQL concurrency suite
migrate:fresh --seed
npm run build
full OpenAPI drift checks
full Wayfinder regeneration
```

## Hubungan dengan Phase Berikutnya

- Phase 3 menambahkan anotasi group `slow`, `pgsql`, `external`, dan `integration` pada test yang sesuai.
- Phase 4 mengatur conditional GitHub Actions berdasarkan file yang berubah.
- Dokumen ini tidak mengubah workflow CI dan tidak memulai Document 03.
