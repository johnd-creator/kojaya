# Senior Review Round 2 — Verdict

**Tanggal review:** 11 Juli 2026  
**Repository yang benar:** `johnd-creator/kojaya`  
**Bukan:** `johnd-creator/KojayaApp`  
**Pull request:** `#2`  
**Commit junior:** `89e9e2279bfd5826bb0ee676bbf9d43e949840cf`  
**Baseline senior:** `b89f04bd4a709de4e1ee9fa8bfc7eb2bc207b895`

## Keputusan

> **REQUEST CHANGES — KEEP DRAFT — DO NOT MERGE**

Status yang benar:

```text
P0: sebagian selesai, masih ada blocker
P1: sebagian selesai, masih ada correctness/concurrency gap
P2: implementasi awal, rollout belum production-safe
CI: gagal
PR: tetap draft
```

## Klarifikasi repository

Enam dokumen senior sebelumnya membahas Laravel backend. Implementasi DeepSeek juga berada di `johnd-creator/kojaya`.

Repository Android `johnd-creator/KojayaApp` tidak berisi implementasi enam plan tersebut. Jangan mengerjakan plan backend ini di repository Android.

## Perbaikan yang dipertahankan

1. Active gate sudah ditambahkan ke payment charge, points, dan rewards.
2. Gate API mensyaratkan `status=ACTIVE` dan `validation_status=ACTIVE`.
3. Lifecycle deactivate/resign mulai menyamakan kedua kolom status.
4. Role `Anggota` dilepas saat lifecycle tidak aktif.
5. Revocation tidak lagi menghapus token ESS/technician biasa secara langsung.
6. Organization checks mulai diterapkan pada policy penting.
7. Inertia member page mulai memakai allowlisted page-data service.
8. Shared Inertia auth user tidak lagi mengirim model mentah.
9. PII mulai dienkripsi dan diberi blind index.
10. Audit redaction mencakup NIK, NPWP, dan rekening.
11. Reservation mempunyai state dan expiry command.
12. Granular abilities mulai digunakan dengan fallback telemetry.
13. Pagination mulai diperluas ke ESS dan technician.

## Blocker senior review

| ID | Severity | Temuan |
|---|---|---|
| R2-P0-01 | Critical | CI kembali gagal pada Wayfinder drift; PHPUnit, OpenAPI, migration, dan seeding tidak berjalan. |
| R2-P0-02 | Critical | Export memakai `null` untuk global scope dan user non-global tanpa organization; berpotensi fail-open. |
| R2-P0-03 | Critical | Generic update member masih menerima lifecycle fields, sehingga status dapat berubah tanpa transition/revocation. |
| R2-P0-04 | High | API member update dapat menulis PII tanpa dedicated PII-write permission. |
| R2-P0-05 | High | Web update tanpa PII permission dapat menghapus `no_rekening` secara tidak sengaja. |
| R2-P0-06 | High | Strict active gate berisiko memblokir data legacy dengan `validation_status=NULL`; belum ada preflight/backfill. |
| R2-P1-01 | High | Transition service menerima arbitrary target state; invariant masih bergantung controller. |
| R2-P1-02 | High | Unique-conflict checkout path tidak memvalidasi ulang amount/channel/expiry/state/items. |
| R2-P1-03 | Critical/High | `createIntentCharge()` tidak diserialisasi; dua request paralel dapat membuat dua external charge. |
| R2-P1-04 | Critical/High | Webhook tidak lock intent; race PAID versus expiry dapat menghasilkan state kontradiktif. |
| R2-P1-05 | High | Settlement dapat berjalan saat reservation sudah EXPIRED/RELEASED. |
| R2-P1-06 | Medium/High | Product lock mengikuti urutan client; berpotensi deadlock. |
| R2-P1-07 | High | Provisioning hanya melarang dua privileged roles; role koperasi lain masih dapat ditautkan sebagai Anggota. |
| R2-P1-08 | Medium/High | Revocation berbasis ability masih dapat menghapus wildcard/combined token. |
| R2-P1-09 | Medium/High | Organization scope memakai `$fillable` sebagai deteksi strategy. |
| R2-P2-01 | Critical rollout | Down migration PII dapat menghapus source-of-truth encrypted data setelah plaintext dinolkan. |
| R2-P2-02 | High rollout | Decrypt failure diam-diam fallback; wrong key dapat terlihat sebagai data kosong. |
| R2-P2-03 | High rollout | Blind-index key fallback ke `APP_KEY`; belum ada production validation/rotation. |
| R2-P2-04 | Medium/High | Backfill tidak punya dry-run, checkpoint, verification, repair mode, atau report. |
| R2-P2-05 | Medium | Audit export dicatat sebelum file berhasil dibuat. |
| R2-P2-06 | Medium | Legacy `cooperative:read/write` masih diterima; cutover belum final. |
| R2-P2-07 | Medium | Pagination test hanya membuktikan notification endpoint. |
| R2-TEST-01 | High | Beberapa test tidak menjalankan surface yang disebut dalam nama test. |
| R2-PR-01 | High process | PR masih docs-only, tetapi sudah 271 file dan hampir 12 ribu additions. |

## Merge gate

PR tidak boleh menjadi ready atau di-merge sampai:

- Wayfinder drift nol;
- PHPUnit benar-benar berjalan dan hijau;
- OpenAPI drift nol;
- migrations dan seed hijau;
- seluruh R2-P0 selesai;
- payment/reservation race ditutup;
- PII rollout rollback-safe;
- perubahan dipecah menjadi focused PR;
- senior review ulang pada SHA baru.

## Urutan eksekusi

1. `01-P0-SECURITY-CORRECTNESS-CLOSURE.md`
2. `02-PAYMENT-RESERVATION-STATE-MACHINE.md`
3. `03-PII-ENCRYPTION-ROLLOUT.md`
4. `04-ORGANIZATION-AUTHORIZATION-AND-TOKEN-CUTOVER.md`
5. `05-AUDIT-PAGINATION-AND-CONTRACT-TESTS.md`
6. `06-CI-PR-AND-JUNIOR-EXECUTION-PROTOCOL.md`
7. `07-MESSAGE-TO-DEEPSEEK.md`
