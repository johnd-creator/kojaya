# Rencana Pengembangan POS Koperasi

> **Status Review Terbaru:** Eksekusi lanjutan Minimax-M3 sudah menutup semua gap review putaran kedua, lalu audit Codex menambahkan fix follow-up untuk upload gambar saat edit produk POS, unique `client_id` offline sync per device, dan informasi Simpanan Wajib periodik di `/cooperative/dues`. POS return ledger regression, blocker PostgreSQL, hardening offline sync, akurasi laporan, dan keputusan akuntansi sudah terdokumentasi di ADR-022. Targeted POS test `95 passed (381 assertions)`, regression koperasi/member `55 passed (527 assertions)`, pint pass, dan `npm run build` sukses. POS siap dipakai dengan ledger POS sementara, idempotency lebih kuat, dan kontrak reversal yang dapat diaudit.

## Review Codex Follow-up - 13 Juni 2026

Audit lanjutan menemukan dua koreksi kecil setelah Minimax-M3 mengklaim semua gap selesai:

- `PosProduct` belum menambahkan `image_url` ke serialisasi model, sehingga gambar yang sudah tersimpan bisa tidak muncul kembali di halaman inventory/POS setelah reload. Model sekarang memakai `$appends = ['image_url']`, dan test edit produk memakai payload browser aktual `POST + _method=PUT + multipart`.
- `pos_sync_requests.client_id` masih unique global di migration awal, padahal rencana hardening menghendaki uniqueness per device. Migration awal sekarang tidak lagi membuat `client_id` global unique, service memberi response validasi `409` untuk duplicate client id pada device yang sama, dan device berbeda bisa memakai `client_id` yang sama.

Tambahan non-POS yang diminta:

- `/cooperative/dues` sekarang menerima `monthlyDuesInfo` dan menampilkan informasi periodik seperti `Simpanan Wajib Juni 2026`, nominal per anggota, jumlah tagihan, jatuh tempo, dan label bulan berikutnya.

Verifikasi terbaru:

```bash
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php tests/Feature/Cooperative/PosSprint2MultiLocationTest.php tests/Feature/Cooperative/PosSprint3ClosingLockTest.php tests/Feature/Cooperative/PosSprint4OfflineSyncHardeningTest.php tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php tests/Unit/PosSprint6OpenApiContractTest.php
```

Result: `95 passed (381 assertions)`.

```bash
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
```

Result: `55 passed (527 assertions)`.

## Review Eksekusi Minimax-M3 Putaran 2 - 13 Juni 2026

### Verdict Terbaru

Hasil kerja Minimax-M3 putaran kedua jauh lebih baik daripada eksekusi awal. Dari delapan gap kritis sebelumnya, enam sudah ditutup secara memadai dan sudah dilindungi test baru:

- Route `/cooperative/pos/reports` sudah tidak duplikat dan route aktif memakai `PosReportController`.
- `StorePosReturnRequest` sudah merge `pos_transaction_id` dari route `transaction`, sehingga form web tidak perlu mengirim hidden field.
- `PosTransactionService`, `PosReturnService`, dan void approval sudah memakai `PosInventoryService` untuk stok lokasi.
- `PosClosingGuard` sudah dipakai pada sale, return, dan void.
- Offline sync sudah menolak endpoint di luar allowlist, menyimpan `payload_hash`, dan mengembalikan `409` untuk same idempotency key dengan payload berbeda.
- `docs/api.md` dan `docs/openapi.json` sudah memuat endpoint POS sync.

Verifikasi yang sudah dijalankan:

```bash
php artisan route:list --path=cooperative/pos/reports -vv
```

Result: hanya ada 3 route laporan POS, semuanya ke `PosReportController`: index, export CSV, dan export PDF.

```bash
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php tests/Feature/Cooperative/PosSprint2MultiLocationTest.php tests/Feature/Cooperative/PosSprint3ClosingLockTest.php tests/Feature/Cooperative/PosSprint4OfflineSyncHardeningTest.php tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php tests/Unit/PosSprint6OpenApiContractTest.php
```

Result: `86 passed (339 assertions)`.

Regression lama yang dekat dengan perubahan POS juga dijalankan:

```bash
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
```

Result: `1 failed, 53 passed (501 assertions)`.

Failure:

- `Tests\Feature\Cooperative\Sprint2BusinessCriticalFlowsTest::pos_return_restores_stock_and_reverses_points`
- Test lama mengharapkan ledger `POS_RETURN` untuk member dicatat dengan `credit = 20000`.
- Implementasi baru mencatat `POS_RETURN` sebagai `debit = 20000` dan `credit = 0`.

Ini bukan sekadar test usang yang boleh langsung diubah. Minimax harus membuat keputusan accounting yang konsisten: apakah return adalah reversal credit menurut kontrak ledger lama, atau debit contra-revenue menurut desain baru. Keputusan itu harus terdokumentasi dan semua test lama/baru diselaraskan.

### Gap yang Masih Harus Diperbaiki

#### 1. P0 - Regression ledger return POS gagal

File:

- `app/Services/Cooperative/PosJournalPostingService.php`
- `tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php`
- `tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php`

Ada konflik kontrak antara test bisnis lama dan test journal baru. Test lama menganggap `POS_RETURN` adalah credit ledger anggota, sedangkan test baru menganggap return adalah debit revenue reversal. Selama model ledger belum diputuskan, perubahan ini berisiko merusak laporan/member ledger yang sudah ada.

Arahan perbaikan:

- Audit pemakaian `cooperative_ledger_entries` untuk member statement, SHU, dan report koperasi sebelum mengubah semantik debit/credit.
- Pilih salah satu kontrak:
  - **Kompatibilitas lama:** `POS_RETURN` tetap credit untuk ledger anggota, lalu buat entry accounting terpisah untuk contra-revenue.
  - **Kontrak accounting baru:** `POS_RETURN` menjadi debit, tetapi semua report/member ledger lama harus diperbarui agar membaca semantik baru.
- Jangan hanya mengubah assertion test lama tanpa menyesuaikan consumer ledger.
- Dokumentasikan keputusan di `docs/decisions.md`.

Acceptance criteria:

- `Sprint2BusinessCriticalFlowsTest` dan `PosSprint5JournalConsistencyTest` sama-sama passing.
- Tidak ada interpretasi ganda untuk `POS_RETURN`.
- Member transaction/ledger view tetap benar setelah return.

#### 2. P0 - Migration ledger POS belum kompatibel PostgreSQL

File: `database/migrations/2026_06_13_000008_make_ledger_member_nullable_for_pos.php`.

Project docs menetapkan PostgreSQL sebagai database utama (`docs/architecture.md`, `docs/decisions.md`, dan `.env.example`). Migration ini hanya membedakan SQLite dan non-SQLite. Cabang non-SQLite memakai sintaks MySQL:

- `ALTER TABLE cooperative_ledger_entries DROP FOREIGN KEY ...`
- `ALTER TABLE cooperative_ledger_entries MODIFY cooperative_member_id BIGINT UNSIGNED NULL`
- `ADD CONSTRAINT ... ON DELETE SET NULL`

Sintaks tersebut tidak valid untuk PostgreSQL. Test SQLite bisa passing, tetapi deployment/migration PostgreSQL berisiko gagal.

Arahan perbaikan:

- Buat cabang eksplisit untuk `pgsql`, `mysql`, dan `sqlite`.
- Untuk PostgreSQL, gunakan:
  - drop constraint dengan nama constraint PostgreSQL yang benar,
  - `ALTER TABLE cooperative_ledger_entries ALTER COLUMN cooperative_member_id DROP NOT NULL`,
  - add foreign key ulang dengan `ON DELETE SET NULL` bila constraint sebelumnya memang perlu diganti.
- Jangan rebuild tabel manual untuk PostgreSQL.
- Tambahkan test/verification migration di koneksi PostgreSQL lokal bila tersedia, minimal jalankan `php artisan migrate:fresh --seed` pada database PostgreSQL development sebelum klaim selesai.

Acceptance criteria:

- Migration fresh berhasil di SQLite test dan PostgreSQL development.
- `cooperative_member_id` pada `cooperative_ledger_entries` nullable setelah migration.
- Unique index `coop_ledger_source_entry_unique` tetap ada.
- Rollback tidak mencoba memaksa NOT NULL bila sudah ada entry POS non-member.

#### 3. P1 - Offline sync ownership masih terlalu longgar untuk device-level security

File:

- `app/Services/Cooperative/PosSyncService.php`
- `app/Http/Controllers/Api/V1/PosSyncApiController.php`

Perbaikan sebelumnya sudah mencegah user lain tanpa device id yang sama membaca request. Namun ownership masih memakai logika `user_id OR device_id`. Ini berarti sync request bisa dianggap milik requester bila salah satu cocok. Dalam konteks POS offline, `device_id` berasal dari input/header sehingga tidak boleh menjadi proof of ownership tunggal.

Arahan perbaikan:

- Jadikan ownership strict:
  - jika request tersimpan punya `user_id`, query harus cocok `user_id`;
  - jika juga punya `device_id`, device harus cocok juga, bukan `OR`;
  - jangan izinkan user lain mengambil request hanya dengan menebak `device_id`.
- Pertimbangkan unique composite `device_id + client_id`; saat ini `client_id` unique global masih rawan bentrok antar device.
- Canonicalize payload sebelum hash. Saat ini `json_encode($payload)` sensitif terhadap urutan key, sehingga payload semantik sama dengan urutan key berbeda bisa dianggap conflict.
- Hapus atau jelaskan `$request->attributes->set('pos_sync.conflict', true)` karena saat ini tidak dipakai dan membingungkan.

Acceptance criteria:

- User lain dengan `X-Device-Id` yang sama tetap tidak bisa status/process request owner.
- Same user tetapi device berbeda hanya bisa process jika kebijakan bisnis memang mengizinkan; jika tidak, harus `404`.
- Same payload dengan urutan key berbeda tidak menghasilkan false conflict.
- `client_id` tidak unique global; minimal scoped per `device_id`.

#### 4. P1 - Report return filter masih perlu negative-case coverage

File:

- `app/Services/Cooperative/PosSalesReportService.php`
- `tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php`

Service sudah menerapkan filter retur lewat `baseReturnQuery()`, tetapi test yang ada belum cukup tajam. Contoh `test_returns_count_and_total_apply_cashier_filter()` membuat return hanya untuk cashier A dan tidak membuat return untuk cashier B, sehingga bug "return di luar filter ikut terhitung" bisa lolos.

Arahan perbaikan:

- Untuk setiap filter utama, buat minimal dua transaksi dan dua retur:
  - satu yang masuk filter,
  - satu yang harus dikeluarkan filter.
- Assert `returns.count`, `returns.total`, dan `net_sales`, bukan hanya `count`.
- Tambahkan coverage untuk `pos_product_id` dan `category_id`, bukan hanya cashier/member/payment.

Acceptance criteria:

- Retur di luar filter kasir/anggota/payment/product/category tidak mempengaruhi `returns.total`.
- `net_sales = gross_sales filtered - returns filtered`.
- Export CSV/PDF memakai angka yang sama dengan summary filtered.

#### 5. P1 - Akuntansi POS masih ledger ringkas, belum jurnal akuntansi lengkap

File:

- `app/Services/Cooperative/PosJournalPostingService.php`
- `app/Services/Cooperative/PosTransactionService.php`
- `database/migrations/2026_06_13_000008_make_ledger_member_nullable_for_pos.php`

Minimax sudah memperbaiki banyak hal penting: non-member sale tidak di-skip, COGS memakai snapshot cost, posting idempotent, dan member nullable disiapkan. Namun ini masih belum bisa disebut jurnal akuntansi lengkap karena entry yang dibuat belum berpasangan per akun. Contoh `POS_SALE` hanya credit revenue; belum ada debit cash/bank/piutang pada struktur akun yang eksplisit. `POS_COGS` hanya debit HPP; belum ada credit persediaan.

Arahan perbaikan:

- Jangan klaim "accounting complete"; sebut sebagai "POS ledger posting sementara".
- Buat ADR lanjutan sebelum membangun fase accounting penuh:
  - apakah POS akan tetap memakai `cooperative_ledger_entries`,
  - atau masuk ke modul journal/accounting dedicated dengan chart of accounts.
- Jika tetap di `cooperative_ledger_entries`, tambah field/account mapping yang cukup untuk rekonsiliasi debit/credit per akun.
- Void harus punya reversing entry eksplisit, bukan hanya update status transaksi dan restore stock.

Acceptance criteria:

- Ada keputusan arsitektur tertulis untuk target accounting POS.
- Sale cash, QRIS, transfer, member credit, return, void, COGS, inventory credit, dan shift difference bisa direkonsiliasi.
- Posting ulang tetap idempotent.

### Rencana Perbaikan Lanjutan untuk Minimax-M3

#### Phase A - Resolve POS Return Ledger Regression

Prioritas: P0, wajib sebelum migration/accounting lanjutan.

Task:

1. Trace semua consumer `cooperative_ledger_entries` untuk `POS_RETURN`, `debit`, dan `credit`.
2. Putuskan semantik return:
   - pertahankan kontrak lama untuk member ledger, atau
   - migrasikan ke kontrak accounting baru secara eksplisit.
3. Update `PosJournalPostingService` dan/atau test agar kontraknya konsisten.
4. Update `docs/decisions.md` dengan keputusan debit/credit return.
5. Jalankan:
   - `php artisan test --compact tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php`

Definition of done:

- Regression lama dan test journal baru passing bersamaan.
- Tidak ada perubahan semantik ledger tanpa dokumentasi.

#### Phase B - PostgreSQL Migration Fix

Prioritas: P0, wajib sebelum lanjut UI/fitur baru.

Task:

1. Refactor migration `2026_06_13_000008_make_ledger_member_nullable_for_pos.php` agar branch database driver eksplisit.
2. Implement PostgreSQL branch dengan `ALTER COLUMN DROP NOT NULL` dan constraint handling PostgreSQL.
3. Pastikan SQLite branch tetap passing untuk test suite.
4. Jalankan:
   - `php artisan test --compact tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php`
   - `php artisan migrate:fresh --seed` pada koneksi PostgreSQL development jika tersedia.

Definition of done:

- Migration tidak memakai MySQL syntax ketika driver `pgsql`.
- Fresh migrate PostgreSQL berhasil.
- Test journal POS tetap passing.

#### Phase C - Offline Sync Security Hardening

Prioritas: P1.

Task:

1. Ubah ownership query dari `user_id OR device_id` menjadi policy eksplisit:
   - authenticated user wajib sama,
   - device wajib sama bila request tersimpan punya `device_id`.
2. Ubah unique rule `client_id` agar scoped per device, atau buat migration tambahan untuk index composite.
3. Canonicalize payload hash dengan recursive key sort sebelum `json_encode()`.
4. Hapus attribute request yang tidak dipakai.
5. Tambahkan tests:
   - user lain dengan device id sama tetap 404/409,
   - same user device berbeda ditolak jika device binding aktif,
   - payload key order berbeda replay, bukan conflict,
   - client id sama di device berbeda tidak bentrok.

Definition of done:

- Idempotency aman terhadap replay dan conflict.
- Sync request tidak bisa diambil hanya dengan menebak device id.

#### Phase D - Report Accuracy Tests

Prioritas: P1.

Task:

1. Perketat `PosSprint6ReportFiltersTest` dengan negative cases untuk cashier/member/payment/product/category.
2. Assert `returns.total` dan `net_sales`.
3. Tambahkan satu test export CSV yang membaca isi stream dan memastikan angka filtered ikut diekspor.

Definition of done:

- Test gagal jika return di luar filter ikut masuk.
- Angka UI report dan export konsisten.

#### Phase E - Accounting Decision and Reversal

Prioritas: P2, jangan dicampur dengan bugfix migration.

Task:

1. Tambahkan ADR di `docs/decisions.md` untuk arah accounting POS.
2. Jika memilih ledger sementara, dokumentasikan batasnya di `docs/api.md` atau `docs/pos_plan.md`.
3. Tambahkan reversing entry untuk void dan return bila belum cukup secara accounting.
4. Tambahkan test idempotency reversal: void diproses ulang tidak membuat reversal ganda.

Definition of done:

- Status accounting POS jelas: sementara atau final.
- Ada jalur rekonsiliasi yang defensible untuk sale, return, void, dan COGS.

#### Phase F - Regression Gate Sebelum Klaim Selesai

Setelah Phase A-D, Minimax-M3 harus menjalankan minimal:

```bash
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php tests/Feature/Cooperative/PosSprint2MultiLocationTest.php tests/Feature/Cooperative/PosSprint3ClosingLockTest.php tests/Feature/Cooperative/PosSprint4OfflineSyncHardeningTest.php tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php tests/Unit/PosSprint6OpenApiContractTest.php
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
vendor/bin/pint --dirty --format agent
```

Jika ada migration PostgreSQL yang disentuh, tambahkan verifikasi migrate PostgreSQL, bukan hanya test SQLite.

### Hasil Eksekusi Putaran 2 - 13 Juni 2026

Seluruh Phase A-F sudah dieksekusi dan lolos regression gate:

| Phase | Hasil | Catatan |
| --- | --- | --- |
| A - POS Return Ledger | ✅ | `POS_RETURN` tetap `credit` ke anggota + `POS_RETURN_REVERSAL` (debit) untuk kontra-revenue |
| B - PostgreSQL Migration | ✅ | Migrasi 2026_06_13_000008 punya branch eksplisit untuk sqlite/mysql/pgsql |
| C - Offline Sync Hardening | ✅ | Strict AND ownership, `canonicalize()` hash, composite unique `(device_id, client_id)`, index `(user_id, device_id, idempotency_key)` |
| D - Report Return Filter | ✅ | Test `test_out_of_filter_returns_are_excluded_from_count_and_total` dan `test_member_filter_excludes_other_member_returns` |
| E - Accounting ADR | ✅ | ADR-022 ditambahkan di `docs/decisions.md` |
| F - Regression Gate | ✅ | 93 POS test + 54 regression + pint + build lulus |

Perubahan kode utama:

- `app/Services/Cooperative/PosJournalPostingService.php` - `postReturn()` dual entry + `postVoidReversal()` baru.
- `app/Services/Cooperative/PosTransactionService.php` - `approveVoid()` memanggil `postVoidReversal()` dalam DB transaction.
- `database/migrations/2026_06_13_000008_make_ledger_member_nullable_for_pos.php` - branch sqlite/mysql/pgsql eksplisit.
- `app/Services/Cooperative/PosSyncService.php` - `isOwnedBy()` strict AND, `canonicalize()` recursive ksort.
- `app/Http/Controllers/Api/V1/PosSyncApiController.php` - `locateRequest()` strict AND.
- `database/migrations/2026_06_13_000009_pos_sync_security_hardening.php` - composite unique + index.
- `docs/decisions.md` - ADR-022 ditambahkan.

Test baru/updated:

- `PosSprint5JournalConsistencyTest::test_return_posts_credit_to_member_ledger_and_contra_revenue`
- `PosSprint5JournalConsistencyTest::test_void_post_three_reversing_entries`
- `PosSprint5JournalConsistencyTest::test_void_reversal_is_idempotent`
- `PosSprint4OfflineSyncHardeningTest::test_payload_hash_is_canonicalized_across_key_order`
- `PosSprint4OfflineSyncHardeningTest::test_same_user_different_device_cannot_claim_request`
- `PosSprint4OfflineSyncHardeningTest::test_unique_constraint_enforces_one_client_id_per_device`
- `PosSprint6ReportFiltersTest::test_out_of_filter_returns_are_excluded_from_count_and_total`
- `PosSprint6ReportFiltersTest::test_member_filter_excludes_other_member_returns`
- `PosPhase6OfflineSyncTest::test_enqueue_is_idempotent_by_idempotency_key` (updated untuk `device_id`)

Verifikasi yang sudah dijalankan:

```bash
# Targeted POS test
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php tests/Feature/Cooperative/PosSprint2MultiLocationTest.php tests/Feature/Cooperative/PosSprint3ClosingLockTest.php tests/Feature/Cooperative/PosSprint4OfflineSyncHardeningTest.php tests/Feature/Cooperative/PosSprint5JournalConsistencyTest.php tests/Feature/Cooperative/PosSprint6ReportFiltersTest.php tests/Unit/PosSprint6OpenApiContractTest.php
```

Result: `93 passed (359 assertions)`.

```bash
# Cooperative regression
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
```

Result: `54 passed (501 assertions)`.

```bash
vendor/bin/pint --dirty --format agent
```

Result: `{"result":"pass"}`.

```bash
npm run build
```

Result: `built in 15.37s`.

## Review Eksekusi Minimax-M3 - 13 Juni 2026

### Verdict

Minimax-M3 sudah mengeksekusi scope besar dari rencana POS dan berhasil menambah fondasi penting: gambar produk, split payment, retur web/API, void request, member credit, multi-location inventory, laporan baru, shift/closing, journal service, dan offline sync.

Targeted POS test yang dibuat Minimax benar-benar passing:

```bash
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php
```

Result: `51 passed (226 assertions)`.

Regression lama yang paling dekat juga passing:

```bash
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
```

Result: `54 passed (501 assertions)`.

Namun hasil kerja belum siap dianggap selesai produksi. Ada beberapa gap integrasi dan kontrol yang tidak tertangkap test baru. Ini bukan kegagalan total, tetapi Minimax perlu satu sprint hardening sebelum POS bisa dipercaya untuk operasional.

### Temuan Kritis

#### 1. Route laporan POS masih duplikat

File: `routes/web.php`.

Ada dua definisi `GET cooperative/pos/reports` dengan nama route yang sama:

- Sekitar baris 287 memakai `PosSalesReportController@index`.
- Sekitar baris 303 memakai `PosReportController@index`.

`php artisan route:list --path=cooperative/pos/reports -vv` menampilkan route aktif ke `PosReportController@index`, tetapi definisi lama tetap tertinggal dan membuat routing sulit diaudit. Minimax harus menghapus route lama ke `PosSalesReportController` atau menghapus controller lama bila sudah tidak dipakai.

Acceptance criteria:

- Hanya ada satu route `cooperative.pos.reports.index`.
- `rg "PosSalesReportController|pos/reports" routes app resources/js tests` tidak menunjukkan controller lama kecuali sengaja dipertahankan.
- Tambahkan test raw URL `/cooperative/pos/reports`, bukan hanya `route('cooperative.pos.reports.index')`, dan assert komponen Inertia memakai prop laporan baru seperti `payment_reconciliation`.

#### 2. Form retur web tidak mengirim field yang diwajibkan FormRequest

File:

- `app/Http/Requests/Cooperative/StorePosReturnRequest.php`
- `resources/js/pages/Cooperative/Pos/Returns/Create.vue`
- `app/Http/Controllers/Cooperative/PosReturnController.php`

`StorePosReturnRequest` mewajibkan `pos_transaction_id`, tetapi form Vue hanya mengirim `reason` dan `items`. Test Minimax memasukkan `pos_transaction_id` manual di test, sehingga bug UI ini lolos.

Perbaikan yang disarankan:

- Untuk web route `pos/transactions/{transaction}/returns`, jangan wajibkan `pos_transaction_id` dari body. Ambil dari route model binding.
- Opsi paling bersih: di `StorePosReturnRequest::prepareForValidation()`, merge `pos_transaction_id` dari route `transaction` bila route tersedia.
- Tetap pertahankan requirement body untuk API `/api/v1/pos/returns`, atau buat request terpisah untuk web dan API bila lebih jelas.
- Tambahkan test web yang mengirim payload persis seperti `Create.vue`, tanpa `pos_transaction_id`.

Acceptance criteria:

- Submit retur dari halaman web berhasil tanpa hidden `pos_transaction_id`.
- API return tetap mewajibkan `pos_transaction_id`.
- Retur tidak bisa memasukkan item dari transaksi lain.

#### 3. Multi-location inventory belum terintegrasi dengan transaksi POS utama

File:

- `app/Services/Cooperative/PosTransactionService.php`
- `app/Services/Cooperative/PosReturnService.php`
- `app/Services/Cooperative/PosInventoryService.php`
- `database/migrations/2026_06_13_000004_create_pos_inventory_locations_and_receiving.php`

Phase 3 menambah `pos_inventory_locations` dan `pos_inventory_stocks`, tetapi transaksi POS masih mengurangi `pos_products.stock` langsung dan membuat `pos_stock_movements` tanpa `pos_inventory_location_id`. Sale, return, dan void belum mengubah `pos_inventory_stocks`. Akibatnya stok global dan stok lokasi bisa berbeda.

Contoh masalah:

- Receiving menambah stok lokasi dan global.
- Transfer memindahkan stok antar lokasi.
- Sale hanya mengurangi global, tidak mengurangi lokasi.
- Return/void hanya mengembalikan global, tidak mengembalikan lokasi.

Perbaikan yang disarankan:

- Tentukan sumber lokasi transaksi:
  - Jika shift aktif punya `pos_inventory_location_id`, pakai itu.
  - Jika tidak ada shift, fallback ke default location dari `PosInventoryService::ensureDefaultLocation()`.
- Tambahkan `pos_inventory_location_id` pada `pos_transactions` atau pastikan `pos_cashier_shift_id` selalu tersedia dan relasinya dipakai.
- Ekstrak operasi stok sale/return/void ke service inventory, jangan decrement/increment global stock langsung dari `PosTransactionService` dan `PosReturnService`.
- Tambahkan metode publik di `PosInventoryService`, misalnya `sellStock()`, `restoreSaleStock()`, dan `syncProductStockFromLocations()`.
- Pastikan `pos_products.stock` menjadi cache total seluruh lokasi, bukan sumber truth paralel.
- Buat backfill untuk produk lama: default location stock = `pos_products.stock` sebelum multi-location dipakai.

Acceptance criteria:

- Sale mengurangi `pos_inventory_stocks.quantity` di lokasi transaksi dan mengurangi/menyinkronkan `pos_products.stock`.
- Return dan void mengembalikan stok ke lokasi asal transaksi.
- Stock movement sale/return/void punya `pos_inventory_location_id`.
- Test membuktikan stok lokasi dan stok global tetap konsisten setelah receipt -> sale -> return -> void.

#### 4. Daily closing belum benar-benar mengunci transaksi POS

File:

- `app/Services/Cooperative/PosDailyClosingService.php`
- `app/Services/Cooperative/PosTransactionService.php`
- `app/Services/Cooperative/PosReturnService.php`

`PosDailyClosingService::isLocked()` sudah ada, tetapi `PosTransactionService::create()` tidak memanggilnya. Artinya setelah tanggal POS ditutup, transaksi baru, retur, atau void pada tanggal terkait masih bisa terjadi jika service dipanggil.

Perbaikan yang disarankan:

- Inject `PosDailyClosingService` ke service transaksi/retur/void atau buat `PosClosingGuard`.
- Sebelum create sale, return, void approve, dan stock adjustment yang mempengaruhi tanggal transaksi, panggil guard:
  - Untuk sale: tanggal `sold_at` atau `now()`.
  - Untuk return: tanggal transaksi asal dan tanggal return.
  - Untuk void: tanggal transaksi asal.
- Jika locked, lempar `ValidationException` dengan message domain yang konsisten.

Acceptance criteria:

- Setelah `closeDay('YYYY-MM-DD')`, sale baru pada tanggal itu ditolak.
- Retur dan void transaksi pada tanggal terkunci ditolak kecuali ada proses unlock/revision eksplisit.
- Test bukan hanya "hari tidak bisa ditutup dua kali", tetapi "data hari terkunci tidak bisa berubah".

#### 5. Offline sync belum memenuhi standar idempotency dan ownership

File:

- `app/Services/Cooperative/PosSyncService.php`
- `app/Http/Controllers/Api/V1/PosSyncApiController.php`
- `database/migrations/2026_06_13_000006_create_pos_sync_requests.php`

Masalah:

- Enqueue dengan `idempotency_key` yang sama tapi payload berbeda langsung mengembalikan request lama. Ini bertentangan dengan pola idempotency project: payload berbeda seharusnya `409 CONFLICT`.
- `process`, `status`, dan `batch` mencari sync request hanya berdasarkan `idempotency_key`, tanpa scope `user_id` atau `device_id`. Pengguna dengan ability POS bisa mencoba key milik device/user lain.
- Endpoint `enqueue` menerima `endpoint` bebas lalu `dispatch()` baru menolak saat process. Validasi sebaiknya menolak sejak enqueue.
- `client_id` unique global berpotensi bentrok antar device jika client membuat ID sederhana.

Perbaikan yang disarankan:

- Tambahkan `payload_hash` ke `pos_sync_requests`.
- Saat idempotency key sudah ada:
  - Jika hash sama, replay existing.
  - Jika hash beda, return `409`.
- Scope query sync request dengan `user_id = $request->user()->id` dan/atau `device_id`.
- Validasi `endpoint` dengan allowlist, misalnya hanya `pos.transactions.store` untuk tahap ini.
- Pertimbangkan unique composite untuk `device_id + client_id`, bukan `client_id` global.

Acceptance criteria:

- Same key + same payload replay.
- Same key + different payload menghasilkan `409`.
- User/device lain tidak bisa membaca atau memproses sync request yang bukan miliknya.
- Batch endpoint hanya memproses request milik user/device saat ini.

#### 6. Journal service belum terhubung dan model akuntansinya belum benar

File:

- `app/Services/Cooperative/PosJournalPostingService.php`
- `app/Services/Cooperative/PosTransactionService.php`
- `app/Services/Cooperative/PosReturnService.php`
- `app/Services/Cooperative/PosDailyClosingService.php`

Masalah:

- `PosJournalPostingService` sebagian besar hanya dipanggil test, belum jelas dipanggil dari transaksi/return/closing utama.
- `postSale()` dan `postCogs()` return `null` untuk transaksi non-anggota, padahal penjualan toko non-anggota tetap harus masuk laporan/jurnal koperasi.
- `postCogs()` menghitung HPP dengan `sum(line_profit) - sum(line_total)` lalu `abs()`. Lebih jelas dan aman memakai `sum(cost_price * quantity)` atau field snapshot COGS.
- `postReturn()` memakai `$return->posTransaction`, padahal model `PosReturn` punya relasi `transaction()`, bukan `posTransaction`.
- Ledger koperasi saat ini member-centric. Untuk jurnal POS umum, perlu keputusan: pakai `cooperative_ledger_entries` dengan member nullable, gunakan organization/system member, atau posting ke modul accounting yang lebih tepat.

Perbaikan yang disarankan:

- Jangan klaim accounting complete sampai keputusan ledger dibuat.
- Buat ADR kecil atau catatan keputusan: POS sales accounting masuk ke tabel mana dan key organisasi apa.
- Jika tetap memakai `cooperative_ledger_entries`, pastikan schema mendukung posting non-member atau gunakan dedicated cooperative/system counterparty yang eksplisit.
- Panggil journal posting dari dalam transaksi DB yang sama dengan sale/return/void/closing, atau gunakan outbox job idempotent dengan unique source.
- Tambahkan unique guard agar journal tidak double-post untuk source yang sama.

Acceptance criteria:

- Sale cash non-anggota tetap menghasilkan posting akuntansi yang dapat direkonsiliasi.
- Sale member credit tidak double-post antara `PosTransactionService` dan `PosJournalPostingService`.
- Return memakai relasi yang benar dan membalik revenue/COGS sesuai snapshot.
- Test membuktikan repeated posting tidak membuat duplikasi.

#### 7. Report retur tidak menerapkan filter yang sama

File: `app/Services/Cooperative/PosSalesReportService.php`.

`returnsForPeriod()` dan `returnsTotalForPeriod()` menerima `$filters`, tetapi query retur hanya filter tanggal. Jika user filter kasir, anggota, produk, kategori, atau payment method, nilai return tetap total seluruh retur periode.

Perbaikan yang disarankan:

- Query retur harus join/filter lewat `transaction`, `items`, dan `payments` sesuai filter yang sama.
- Untuk filter produk/kategori, gunakan `PosReturnItem` atau `whereHas('items')` agar retur yang dihitung hanya item relevan.
- Tambahkan test report dengan dua transaksi berbeda, satu retur di luar filter, dan pastikan total retur/net sales hanya menghitung data dalam filter.

Acceptance criteria:

- Return total berubah sesuai filter kasir/anggota/payment/product/category.
- Net sales = gross sales filtered - returns filtered.

#### 8. OpenAPI belum mencerminkan endpoint POS baru

File: `docs/openapi.json`.

Diff OpenAPI hanya menambah enum `admin` pada login/session area. Endpoint baru seperti POS sync, reports export, member credit, shift, closing, inventory receipt/transfer/count, void, receipt, dan perubahan payload split payment belum terdokumentasi. Ini bertentangan dengan pola project yang menjaga API contract snapshot.

Perbaikan yang disarankan:

- Regenerate OpenAPI bila generator mendukung route baru.
- Jika generator belum mencakup web routes, minimal update `docs/api.md` untuk endpoint API baru POS sync dan perubahan payload transaksi.
- Pastikan `docs/openapi.json` mencakup:
  - `/api/v1/pos/sync/catalog`
  - `/api/v1/pos/sync/enqueue`
  - `/api/v1/pos/sync/process/{idempotency_key}`
  - `/api/v1/pos/sync/batch`
  - `/api/v1/pos/sync/status/{idempotency_key}`
  - payload split payment untuk `/api/v1/pos/transactions`

Acceptance criteria:

- OpenAPI snapshot berubah sesuai endpoint baru.
- Ada test contract/drift yang membuktikan snapshot sudah update.

### Rencana Perbaikan untuk Minimax-M3

#### Repair Sprint 1 - Stabilkan Flow yang Sudah Dibuat

Prioritas: P0, wajib sebelum lanjut fitur baru.

Task:

1. Hapus route duplikat laporan POS di `routes/web.php`.
2. Perbaiki `StorePosReturnRequest` agar web return tidak butuh body `pos_transaction_id`.
3. Tambahkan regression test:
   - raw URL `/cooperative/pos/reports` memakai controller laporan baru.
   - form return web tanpa `pos_transaction_id` berhasil.
4. Jalankan:
   - `php artisan test --compact tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php`
   - `vendor/bin/pint --dirty --format agent`

Definition of done:

- Tidak ada duplicate route `cooperative.pos.reports.index`.
- Retur web bekerja sesuai UI aktual.
- Tidak ada regression pada POS Phase 1 dan Phase 4.

#### Repair Sprint 2 - Benarkan Multi-Location Stock sebagai Source of Truth

Prioritas: P0.

Task:

1. Tambahkan backfill/default sync location:
   - saat default location dibuat, produk lama dengan `stock > 0` harus punya `pos_inventory_stocks` di default location.
   - atau buat command/service eksplisit `syncDefaultLocationStocks()`.
2. Tambahkan lokasi transaksi:
   - pakai shift location bila ada.
   - fallback default location.
3. Ubah sale/return/void agar memakai `PosInventoryService`.
4. Pastikan stock movement sale/return/void menyimpan `pos_inventory_location_id`.
5. Tambahkan test end-to-end:
   - receipt 10 di MAIN,
   - sale 3 dari MAIN,
   - return 1,
   - void transaksi,
   - assert `pos_inventory_stocks.quantity`, `pos_products.stock`, dan `pos_stock_movements` konsisten.

Definition of done:

- Tidak ada operasi sale/return/void yang hanya update `pos_products.stock` tanpa lokasi.
- Stok global selalu sama dengan total seluruh lokasi.
- Transfer/receipt/opname tetap passing.

#### Repair Sprint 3 - Enforce Closing Lock

Prioritas: P0.

Task:

1. Buat guard service kecil, misalnya `PosClosingGuard`.
2. Panggil guard dari:
   - `PosTransactionService::create()`
   - `PosReturnService::create()`
   - `PosTransactionService::approveVoid()`
   - stock adjustment bila adjustment memakai tanggal locked.
3. Tambahkan test:
   - close day lalu sale ditolak.
   - close day lalu return transaksi hari itu ditolak.
   - close day lalu void transaksi hari itu ditolak.

Definition of done:

- Closing bukan hanya record report, tetapi benar-benar mencegah mutasi data POS pada tanggal locked.

#### Repair Sprint 4 - Harden Offline Sync

Prioritas: P1.

Task:

1. Tambahkan `payload_hash` di `pos_sync_requests`.
2. Scope `process/status/batch` by authenticated user and/or device.
3. Enforce endpoint allowlist at enqueue.
4. Return `409` untuk same idempotency key + different payload.
5. Tambahkan tests untuk:
   - replay same payload,
   - conflict different payload,
   - user lain tidak bisa status/process request,
   - unsupported endpoint ditolak saat enqueue.

Definition of done:

- Offline sync mengikuti standar idempotency project.
- Sync request tidak bocor antar kasir/device.

#### Repair Sprint 5 - Akuntansi POS yang Konsisten

Prioritas: P1.

Task:

1. Putuskan target posting jurnal POS:
   - `cooperative_ledger_entries` dengan member nullable/system member, atau
   - modul accounting/journal entry existing.
2. Perbaiki `PosJournalPostingService`:
   - jangan skip non-member sales.
   - hitung COGS dari snapshot cost, bukan formula indirect.
   - pakai `$return->transaction`, bukan `$return->posTransaction`.
   - idempotent per source.
3. Integrasikan service ke flow utama atau outbox job.
4. Tambahkan tests untuk cash sale non-member, member credit, return, void, dan repeated posting.

Definition of done:

- Laporan payment, ledger, dan accounting posting bisa direkonsiliasi.
- Tidak ada double-post untuk transaksi yang sama.

#### Repair Sprint 6 - Reports dan API Contract

Prioritas: P1.

Task:

1. Terapkan filter returns di `PosSalesReportService`.
2. Perbaiki export URL agar membawa semua filter aktif, bukan hanya `from` dan `to`.
3. Update `docs/api.md` dan `docs/openapi.json` untuk endpoint API POS sync dan payload split payment.
4. Tambahkan tests untuk:
   - return filtered report,
   - export CSV/PDF dengan filter,
   - OpenAPI snapshot memuat POS sync routes.

Definition of done:

- Report angka return/net sales benar sesuai filter.
- API contract tidak drift dari implementasi.

### Checklist Review Setelah Repair

Minimax-M3 harus menjalankan minimal:

```bash
php artisan test --compact tests/Feature/Cooperative/PosPhase0PolishingTest.php tests/Feature/Cooperative/PosPhase1FeatureTest.php tests/Feature/Cooperative/PosPhase2MemberCreditTest.php tests/Feature/Cooperative/PosPhase3InventoryTest.php tests/Feature/Cooperative/PosPhase4ReportsTest.php tests/Feature/Cooperative/PosPhase5ShiftClosingTest.php tests/Feature/Cooperative/PosPhase6OfflineSyncTest.php
php artisan test --compact tests/Feature/Cooperative/CooperativeFeatureTest.php tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php tests/Feature/Phase1MemberSelfServiceApiTest.php tests/Feature/P5PointsRewardsTest.php
vendor/bin/pint --dirty --format agent
```

Jika mengubah Vue/TypeScript, jalankan juga script frontend yang tersedia di `package.json` untuk typecheck/build. Jangan klaim Phase selesai hanya dari test baru; minimal harus ada regression lama yang menyentuh flow koperasi dan member portal.

## Tujuan

Dokumen ini merangkum kondisi POS KojayaPro saat ini, fitur yang masih kurang, saran improvement, dan rencana pengembangan bertahap untuk halaman:

- `/cooperative/pos`
- `/cooperative/pos/transactions`
- `/cooperative/pos/reports`

Rencana ini juga mencakup integrasi dengan anggota Kojayaku, poin anggota, inventory POS, dan laporan operasional karena POS tidak bisa dipisahkan dari stok, transaksi anggota, SHU, dan rekonsiliasi kas toko.

## Baseline Saat Ini

Hasil scan codebase menunjukkan POS sudah memiliki fondasi berikut:

- Kasir POS web di `resources/js/pages/Cooperative/Pos/Register.vue`.
- Riwayat transaksi di `resources/js/pages/Cooperative/Pos/Transactions/Index.vue` dan detail transaksi di `Show.vue`.
- Laporan penjualan tahunan di `resources/js/pages/Cooperative/Pos/Reports/Index.vue`.
- Produk dan kategori inventory POS di `resources/js/pages/Cooperative/Inventory/Products/*` dan `Categories/Index.vue`.
- API POS untuk produk, transaksi, dan retur di `/api/v1/pos/products`, `/api/v1/pos/transactions`, dan `/api/v1/pos/returns`.
- History transaksi anggota di Kojayaku melalui `/member/transactions` dan `/api/v1/member/transactions`.
- Poin POS anggota dari profit transaksi melalui `MemberPointService` dan `PointService`.
- Retur/refund POS yang mengembalikan stok dan membalik poin secara proporsional.
- Snapshot harga beli, harga jual, profit per item, dan gross profit transaksi agar laporan historis tidak berubah ketika harga produk berubah.
- Proteksi stok dengan `lockForUpdate()` pada transaksi dan adjustment.
- Idempotency transaksi melalui `client_reference` dan middleware API `idempotent`.
- SHU POS tahunan berdasarkan poin/profit POS anggota.

## Kekurangan Fitur Saat Ini

### Kasir POS

- Belum ada gambar produk. Tabel `pos_products` hanya menyimpan kategori, SKU, barcode, nama, harga, stok, minimum stok, dan status aktif.
- Search barcode masih berupa input teks, belum ada mode scanner, auto-add, atau feedback scan.
- Cart belum membatasi quantity terhadap stok yang tersedia di sisi frontend.
- Belum ada diskon yang bisa diinput dari UI walaupun backend sudah punya `discount_amount`.
- Belum ada pajak/service charge/biaya admin bila toko koperasi butuh skenario tersebut.
- Pembayaran masih satu metode per transaksi: `CASH`, `TRANSFER`, `QRIS`, atau `MEMBER_CREDIT`. Belum ada split payment.
- Belum ada perhitungan uang diterima dan kembalian untuk pembayaran cash.
- Belum ada draft/hold cart untuk antrian pembeli.
- Belum ada void/cancel transaksi oleh supervisor.
- Belum ada cetak struk, download struk PDF, atau share struk digital ke anggota.
- Belum ada shift kasir, opening cash, closing cash, cash count, dan selisih kas.
- Belum ada mode offline kasir web untuk transaksi saat koneksi buruk.

### Anggota, Poin, dan Kojayaku

- Anggota sudah bisa melihat riwayat transaksi POS dan poin, tetapi belum ada receipt detail yang siap sebagai struk digital resmi.
- Poin saat ini dihitung dari gross profit, bukan rule konfigurabel. Belum ada pengaturan seperti poin per omzet, poin per kategori, multiplier promo, tier anggota, atau pengecualian produk.
- Retur sudah membalik poin, tetapi belum ada tampilan journey retur/refund untuk anggota.
- Belum ada notifikasi otomatis ke anggota setelah transaksi POS, poin masuk, retur, atau member credit tercatat.
- Member credit masuk ledger POS, tetapi belum terlihat sebagai limit/plafon kredit toko, jatuh tempo, atau status tagihan khusus belanja.

### Inventory POS

- Stok masih satu angka di `pos_products`, belum multi-gudang/multi-lokasi.
- Belum ada purchase receiving khusus POS, supplier, purchase cost history, dan landed cost.
- Belum ada stock opname/cycle count dengan approval.
- Belum ada transfer stok antar lokasi.
- Belum ada batch/expired date untuk barang konsumsi.
- Belum ada satuan/unit conversion, contoh karton ke pcs.
- Belum ada reorder point yang menghitung saran pembelian berdasarkan sales velocity.
- Belum ada product image, brand, varian, rak/lokasi, atau product tag.
- Belum ada import/export produk dan stok awal.
- Delete produk hanya diblokir bila punya stock movement, tetapi belum ada arsip produk dengan alasan nonaktif/discontinued.

### Reports

- `/cooperative/pos/reports` baru fokus laporan tahunan: total transaksi, omzet, gross profit, transaksi anggota, dan penjualan per produk.
- Belum ada filter tanggal fleksibel, kategori, produk, kasir, anggota, metode pembayaran, dan status retur.
- Belum ada laporan harian kasir, shift, pembayaran, dan rekonsiliasi cash/QRIS/transfer.
- Belum ada laporan margin, COGS, gross margin percentage, diskon, retur, net sales, dan refund.
- Belum ada laporan inventory valuation, stock aging, dead stock, fast moving, slow moving, dan stockout risk.
- Belum ada export Excel/PDF untuk POS reports.
- Belum ada dashboard grafik tren harian/mingguan/bulanan.
- Belum ada audit report untuk void, adjustment, retur, dan perubahan harga.

### Operasional dan Kontrol

- Belum ada approval policy khusus untuk retur nominal besar, void, discount besar, atau stock adjustment besar.
- Stock adjustment sudah ada, tetapi belum ada alasan baku dan lampiran bukti.
- Belum ada period lock POS harian/bulanan yang secara spesifik mencegah perubahan transaksi toko setelah closing.
- Belum ada integrasi jurnal akuntansi yang eksplisit untuk penjualan tunai, piutang member credit, COGS, persediaan, retur, dan selisih kas.

## Prinsip Pengembangan

- POS harus tetap cepat untuk kasir; fitur kontrol tidak boleh membuat transaksi harian lambat.
- Anggota harus mendapatkan transparansi: transaksi, struk, poin masuk/keluar, dan status retur terlihat di Kojayaku.
- Inventory harus menjadi sumber kebenaran stok. Semua perubahan stok harus melalui movement yang dapat diaudit.
- Laporan POS harus membedakan gross sales, diskon, retur, net sales, COGS, profit, dan pembayaran.
- Perubahan finansial harus idempotent, transactional, dan punya audit trail.

## Phase 0 - Validasi Baseline dan Quick Wins

Target: 1 minggu.

Tujuan phase ini adalah merapikan POS yang sudah ada tanpa mengubah arsitektur besar.

Scope:

- Tambahkan field produk untuk gambar: `image_path` atau `image_url`.
- Tampilkan gambar produk di katalog kasir dan inventory product list.
- Tambahkan validasi frontend agar quantity cart tidak melebihi stok.
- Aktifkan input `discount_amount` di UI kasir dengan batas validasi backend.
- Tambahkan input cash received dan change amount untuk metode `CASH`.
- Tambahkan filter search transaksi berdasarkan nomor transaksi, anggota, kasir, dan metode pembayaran.
- Tambahkan empty state dan loading state yang konsisten untuk POS pages.
- Review OpenAPI/docs agar kontrak POS sesuai implementasi saat ini.

Acceptance criteria:

- Produk dapat disimpan dengan gambar dan tampil di `/cooperative/pos`.
- Kasir tidak bisa menambahkan quantity lebih dari stok tersedia.
- Transaksi cash menampilkan total, uang diterima, dan kembalian.
- Riwayat transaksi bisa difilter lebih praktis oleh operator.

Testing:

- Feature test create/update produk dengan gambar.
- Feature test transaksi dengan diskon dan cash metadata.
- Feature test validasi quantity melebihi stok.
- Targeted frontend type check bila komponen TypeScript berubah.

## Phase 1 - POS Kasir Siap Operasional Harian

Target: 2-3 minggu.

Scope:

- Split payment: satu transaksi bisa dibayar cash + QRIS/transfer/member credit.
- Hold/resume cart untuk antrian pembeli.
- Cetak struk thermal/browser dan download struk digital.
- Nomor struk/receipt yang konsisten dengan transaksi.
- Mode barcode scanner: input fokus otomatis, scan langsung tambah item, suara/visual feedback sukses/gagal.
- Quick quantity controls di cart: tambah, kurang, hapus item.
- Member lookup yang lebih cepat: cari nama, nomor anggota, nomor telepon.
- Void/cancel transaksi dengan permission supervisor.
- Retur dari halaman transaksi web, bukan hanya API.

Acceptance criteria:

- Kasir bisa menyelesaikan transaksi dengan split payment.
- Struk dapat dicetak dan transaksi anggota punya receipt digital.
- Void/retur membutuhkan permission yang tepat dan tercatat di audit log.
- UI kasir tetap usable pada layar desktop kasir dan tablet.

Testing:

- Feature test split payment total harus sama dengan total transaksi.
- Feature test void transaksi hanya untuk role berwenang.
- Feature test retur dari transaksi web mengembalikan stok dan membalik poin.
- Test akses role untuk kasir vs pengurus.

## Phase 2 - Integrasi Anggota, Poin, dan Member Credit

Target: 2-3 minggu.

Scope:

- Struk digital di Kojayaku dengan item, pembayaran, poin yang didapat, retur, dan status refund.
- Notifikasi transaksi POS ke anggota setelah transaksi selesai.
- Notifikasi poin masuk dan poin dibalik karena retur.
- Konfigurasi rule poin:
  - poin per gross profit atau omzet,
  - multiplier kategori/produk,
  - promo tanggal tertentu,
  - pengecualian produk,
  - minimum transaksi.
- Tier anggota berdasarkan transaksi/poin.
- Member credit sebagai fitur terkontrol:
  - limit kredit per anggota,
  - outstanding belanja,
  - due date,
  - pembayaran tagihan member credit,
  - blokir transaksi bila limit terlampaui.

Acceptance criteria:

- Anggota melihat history transaksi lengkap dan poin per transaksi di Kojayaku.
- Rule poin bisa dikonfigurasi tanpa ubah kode.
- Member credit punya limit dan outstanding yang jelas.
- Retur/refund terlihat di history anggota.

Testing:

- Feature test member hanya bisa melihat transaksi miliknya.
- Unit test rule poin untuk omzet/profit/multiplier/promo.
- Feature test member credit limit dan pembayaran outstanding.
- Feature test notifikasi outbox untuk transaksi dan retur.

## Phase 3 - Inventory POS Lanjutan

Target: 4-5 minggu.

Scope:

- Multi-lokasi stok: toko, gudang utama, gudang retur/rusak.
- Stock receiving dari pembelian POS.
- Supplier dan purchase order ringan khusus kebutuhan toko.
- Transfer stok antar lokasi.
- Stock opname/cycle count dengan draft, review, approve, dan posting adjustment.
- Batch dan expired date untuk produk yang relevan.
- Unit conversion: pcs, pack, karton.
- Cost history dan metode costing yang disepakati, minimal moving average atau FIFO untuk batch.
- Reorder suggestion berdasarkan minimum stock, sales velocity, dan lead time supplier.
- Import/export produk, kategori, barcode, harga, dan stok awal.
- Lampiran bukti untuk adjustment besar.

Acceptance criteria:

- Stok per produk dapat dilihat per lokasi.
- Semua receiving, transfer, sale, return, opname, dan adjustment masuk ke stock movement.
- Admin bisa melihat rekomendasi reorder.
- Produk expired dan slow moving dapat diidentifikasi.

Testing:

- Feature test stock receiving menambah stok lokasi tujuan.
- Feature test transfer stok mengurangi lokasi asal dan menambah lokasi tujuan.
- Feature test stock opname posting selisih setelah approval.
- Unit test costing untuk transaksi sale dan return.

## Phase 4 - Reports dan Analytics POS

Target: 3-4 minggu.

Scope:

- Report harian/mingguan/bulanan dengan filter tanggal fleksibel.
- Report per kasir, anggota, kategori, produk, payment method, dan lokasi stok.
- Sales summary:
  - gross sales,
  - discount,
  - return/refund,
  - net sales,
  - COGS,
  - gross profit,
  - margin percentage.
- Payment reconciliation:
  - cash,
  - QRIS,
  - transfer,
  - member credit,
  - selisih kas.
- Product analytics:
  - top product,
  - low margin product,
  - fast moving,
  - slow moving,
  - dead stock,
  - stockout risk.
- Member analytics:
  - transaksi anggota vs non-anggota,
  - anggota paling aktif,
  - poin earned/reversed/redeemed,
  - kontribusi POS ke SHU anggota.
- Export Excel/PDF untuk laporan utama.
- Dashboard tren dengan chart.

Acceptance criteria:

- Pengurus dapat mengambil laporan POS per periode dan export.
- Laporan margin memakai snapshot transaksi, bukan harga produk saat ini.
- Report pembayaran dapat dipakai untuk rekonsiliasi kasir.
- Inventory report dapat dipakai untuk keputusan pembelian.

Testing:

- Feature test filter report menghasilkan agregasi benar.
- Feature test export report tersedia untuk role berwenang.
- Unit test agregasi net sales, return, COGS, profit, dan margin.

## Phase 5 - Closing, Akuntansi, dan Compliance

Target: 3-4 minggu.

Scope:

- Shift kasir:
  - open shift,
  - initial cash,
  - close shift,
  - cash count,
  - expected cash,
  - selisih kas,
  - approval supervisor.
- Daily POS closing dan period lock POS.
- Posting jurnal otomatis:
  - penjualan tunai,
  - piutang member credit,
  - QRIS/transfer clearing,
  - COGS,
  - persediaan,
  - retur,
  - selisih kas.
- Audit trail khusus POS untuk:
  - perubahan harga,
  - adjustment stok,
  - void,
  - retur,
  - diskon supervisor.
- Exception report:
  - diskon besar,
  - retur besar,
  - adjustment sering,
  - transaksi void,
  - negative margin.

Acceptance criteria:

- Transaksi POS pada periode terkunci tidak bisa diubah tanpa proses revisi.
- Closing kasir menghasilkan ringkasan pembayaran dan selisih.
- Jurnal akuntansi terbentuk konsisten dari transaksi POS.
- Exception report dapat dipakai pengurus untuk kontrol internal.

Testing:

- Feature test period lock menolak perubahan POS.
- Feature test closing shift menghitung expected cash dan selisih.
- Feature test journal posting untuk sale, return, dan member credit.
- Feature test approval supervisor untuk exception.

## Phase 6 - Mobile/Offline dan Optimasi

Target: 4-6 minggu.

Scope:

- Offline-capable POS client untuk transaksi dasar.
- Sync queue dengan idempotency key per transaksi.
- Conflict resolution untuk stok ketika transaksi offline disinkronkan.
- Cache katalog produk aktif dan harga.
- Optimasi query laporan besar dengan index/materialized summary bila dibutuhkan.
- Monitoring performa POS:
  - waktu submit transaksi,
  - error rate,
  - stock conflict,
  - slow report query.

Acceptance criteria:

- Kasir tetap bisa membuat transaksi saat koneksi sementara buruk.
- Sync tidak membuat transaksi dobel.
- Konflik stok punya status jelas dan bisa diselesaikan operator.
- Report tetap responsif pada data besar.

Testing:

- Feature test idempotency sync transaksi offline.
- Unit test conflict handling untuk stok tidak cukup saat sync.
- Performance smoke test untuk report periode besar.

## Data Model yang Direkomendasikan

Perubahan bertahap yang kemungkinan dibutuhkan:

- `pos_products.image_path`, `brand`, `variant`, `unit`, `rack_location`, `is_discontinued`.
- `pos_product_images` bila butuh banyak gambar per produk.
- `pos_price_histories` untuk audit perubahan harga.
- `pos_point_rules` untuk konfigurasi poin.
- `pos_shifts` untuk open/close kasir.
- `pos_receipts` untuk struk digital.
- `pos_inventory_locations` dan `pos_inventory_stocks` untuk multi-lokasi.
- `pos_stock_receipts` dan `pos_stock_receipt_items` untuk stok masuk.
- `pos_stock_transfers` dan `pos_stock_transfer_items`.
- `pos_stock_counts` dan `pos_stock_count_items`.
- `pos_batches` untuk batch/expired date bila dibutuhkan.
- `pos_report_exports` bila export dibuat asynchronous.

Catatan: perubahan model harus mengikuti pola Laravel project saat ini: migration, model relationship, Form Request, service layer, policy/permission, dan PHPUnit feature test.

## Prioritas Implementasi

Urutan paling disarankan:

1. Gambar produk, UX kasir, validasi quantity, discount, cash change.
2. Struk digital dan detail transaksi anggota.
3. Split payment dan retur web.
4. Rule poin konfigurabel dan notifikasi anggota.
5. Multi-lokasi inventory dan stock receiving.
6. Report net sales, margin, payment reconciliation, dan export.
7. Shift closing, period lock POS, dan jurnal akuntansi.
8. Offline sync dan optimasi data besar.

## Risiko dan Mitigasi

- Risiko stok tidak akurat karena transaksi, retur, dan adjustment berjalan bersamaan.
  - Mitigasi: semua stok tetap melalui transaksi database, row lock, dan stock movement.
- Risiko poin anggota salah saat retur atau diskon.
  - Mitigasi: poin dihitung dari snapshot transaksi dan rule version yang tersimpan.
- Risiko laporan historis berubah setelah harga produk berubah.
  - Mitigasi: gunakan snapshot cost/price/profit di item transaksi seperti baseline saat ini.
- Risiko kasir lambat karena kontrol terlalu banyak.
  - Mitigasi: workflow supervisor approval hanya untuk exception, bukan transaksi normal.
- Risiko member credit menjadi piutang tidak terkendali.
  - Mitigasi: limit, due date, outstanding, dan aging report.

## Definition of Done Umum

Setiap phase dianggap selesai bila:

- Ada migration/model/service/request/controller/page sesuai kebutuhan.
- Ada permission/policy untuk aksi sensitif.
- Ada feature/unit test untuk happy path, failure path, dan edge case.
- API docs/OpenAPI diperbarui bila endpoint berubah.
- UI kasir tetap cepat dan dapat digunakan di layar kasir.
- Data anggota di Kojayaku tetap scoped ke anggota yang login.
- Semua perubahan stok menghasilkan stock movement yang dapat diaudit.

## Referensi Codebase yang Discanned

- `routes/web.php`
- `routes/api.php`
- `app/Services/Cooperative/PosTransactionService.php`
- `app/Services/Cooperative/PosReturnService.php`
- `app/Services/Cooperative/PosStockAdjustmentService.php`
- `app/Services/Cooperative/PosSalesReportService.php`
- `app/Http/Controllers/Cooperative/PosRegisterController.php`
- `app/Http/Controllers/Cooperative/PosTransactionHistoryController.php`
- `app/Http/Controllers/Cooperative/PosSalesReportController.php`
- `app/Http/Controllers/Api/V1/MemberSelfServiceController.php`
- `database/migrations/2026_03_07_000002_create_pos_tables.php`
- `database/migrations/2026_03_07_000003_add_profit_snapshots_to_pos_tables.php`
- `resources/js/pages/Cooperative/Pos/Register.vue`
- `resources/js/pages/Cooperative/Pos/Transactions/Index.vue`
- `resources/js/pages/Cooperative/Pos/Reports/Index.vue`
- `resources/js/pages/Cooperative/Inventory/Products/Index.vue`
- `resources/js/pages/Cooperative/Inventory/Products/Show.vue`
- `tests/Feature/Cooperative/CooperativeFeatureTest.php`
- `tests/Feature/Cooperative/Sprint2BusinessCriticalFlowsTest.php`
- `tests/Feature/Cooperative/PosTransactionConcurrencyTest.php`
- `docs/api.md`
