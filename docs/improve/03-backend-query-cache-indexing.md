# Plan 03 - Backend Query, Cache, dan Database Indexing

## Tujuan

Mengurangi latency backend pada modul besar dengan menghapus N+1 query, menambahkan index yang tepat, dan menerapkan cache untuk data master yang jarang berubah.

## Prioritas

P1. Dampaknya besar, tetapi perlu test database dan kehati-hatian migration.

## Sumber Dari new_improve.md

- Backend: Eradikasi N+1 Query
- Backend: Targeted Database Indexing
- Backend: Caching Layer
- Phase 1: Audit Query Sederhana
- Phase 2: Caching Strategy
- Phase 3: Database Indexing Khusus

## Scope

Termasuk:

- Aktifkan atau pastikan `Model::preventLazyLoading()` untuk local/non-production.
- Audit N+1 pada modul Koperasi, HR, POS, Payroll, dan Reports.
- Tambahkan eager loading `with()` atau `load()` pada list/detail yang butuh relasi.
- Tambahkan index via migration pada kolom filter/sort yang terbukti sering dipakai.
- Tambahkan cache untuk master data dan dropdown yang jarang berubah.

Tidak termasuk:

- Memindahkan operasi berat ke queue. Itu masuk `07-background-jobs-export-progress.md`.
- Mengubah struktur domain model besar tanpa kebutuhan performa yang jelas.

## Kandidat Area

- Koperasi: anggota, payments, dues, loans, ledger, SHU.
- POS: products, transactions, stock movement, reports.
- HR/Payroll: employees, attendance, payroll, overtime, reimbursements.
- Reports: financial summary, compliance, export.

## Langkah Implementasi

1. Cari controller list/detail yang memakai relasi di Vue/API resource.
2. Tambahkan eager loading di query utama dan pastikan pagination tetap jalan.
3. Audit filter umum: `status`, `user_id`, `member_id`, `employee_id`, `created_at`, `date`, `period`, `reference_number`.
4. Sebelum membuat migration index, cek migration/table existing agar tidak membuat index duplikat.
5. Untuk cache, mulai dari data master:
   - roles/permissions untuk dropdown.
   - departments/positions.
   - product categories.
   - cooperative settings.
6. Buat invalidation yang eksplisit saat data master berubah.
7. Tambahkan test untuk memastikan data cache berubah setelah update/delete.

## Acceptance Criteria

- Query list prioritas tidak memicu lazy loading saat render/test.
- Migration index hanya menambahkan index yang punya alasan pemakaian jelas.
- Cache punya key yang konsisten dan invalidation saat write.
- Tidak ada penggunaan `env()` langsung di luar config.
- Tidak ada raw query jika Eloquent relationship cukup.

## Verifikasi Minimal

- Test feature modul yang disentuh, contoh: `php artisan test --compact tests/Feature/Cooperative`
- `php artisan test --compact tests/Feature/P1ArchitectureTest.php` bila menyentuh deferred/report.
- `vendor/bin/pint --dirty --format agent`

## Risiko

- Index berlebih memperlambat write dan memperbesar storage.
- Cache tanpa invalidation bisa membuat dropdown atau setting stale.
- Eager loading yang terlalu luas bisa memperbesar payload. Gunakan `select()` dan relasi spesifik jika perlu.

