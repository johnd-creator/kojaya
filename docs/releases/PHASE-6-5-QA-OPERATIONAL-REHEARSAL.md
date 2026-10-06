# Phase 6.5 — QAR Correction Bundle 01

## Identitas dan batas eksekusi

- Repository: `johnd-creator/kojaya`.
- Baseline pengembangan: `fb46b8e20a84eb6cf02686481dbe27ebe1004274` (main setelah PR #111).
- Branch: `codex/qar-correction-bundle-01`.
- Lingkungan: Development PC, worktree terisolasi; pekerjaan lokal sebelumnya dipertahankan.
- Empat koreksi dicatat sebagai commit logis terpisah. Dokumen ini merupakan evidence koreksi pengembangan, bukan evidence deployment atau release candidate baru.
- QA tidak disentuh; production tidak disentuh; tidak ada pembayaran nyata atau transaksi keuangan historis yang dibuat.
- Tidak ada migration database, dependensi baru, bulk CSV saldo awal, atau perubahan workflow GitHub.
- PR memerlukan mandatory CI hijau dan review manusia; tidak di-auto-merge dan tidak di-deploy.

## Temuan historis dan koreksi

Temuan rehearsal QAR-F004/F006/F007/F008 tetap merupakan fakta historis. Koreksi berikut tidak menyatakan bahwa tampilan atau perilaku lama tidak pernah terjadi, dan belum menyatakan bahwa koreksi telah terpasang di QA.

### QAR-F004 — Member import netral lingkungan

Temuan: UI, pesan keberhasilan, audit reason, dan komentar gerbang masih menyebut DEV.

Koreksi: istilah aktif memakai Execution Gate / Eksekusi Impor / Member onboarding import completed. Nama checkpoint DEV pada spesifikasi historis diberi rekonsiliasi, bukan dihapus. Nama berkas `dev-member-import.md` dipertahankan untuk kompatibilitas tautan.

`COOPERATIVE_MEMBER_IMPORT_EXECUTION_ENABLED` tetap default false. Otorisasi khusus impor, organisasi target, preview proof, revalidasi di dalam transaksi, deteksi konflik, advisory lock, dan audit wajib tetap dipertahankan. Impor hanya membuat anggota PENDING; akun maupun transaksi keuangan tidak dibuat.

### QAR-F006 — Konsistensi verifikasi email Google

Temuan: anggota dapat memiliki akun Google CONNECTED tetapi status Email BELUM.

Discovery: provisioning pertama sudah mengisi `email_verified_at`; jalur login dengan SocialAccount yang sudah ada belum menyinkronkan verifikasi. Pemeriksaan lama juga mengubah string `false` menjadi boolean true.

Koreksi: provider harus secara eksplisit menyatakan true. Email Google yang dinormalisasi harus sama persis dengan User serta member bila tertaut. Verifikasi dilakukan setelah provider binding diterima dan tidak dilakukan bila email bertabrakan dengan User/member/SocialAccount lain. Tautan provider yang sudah ada tetap menjadi identitas login; perubahan email provider tidak merombak email kanonikal atau memverifikasi email yang berbeda. Timestamp verifikasi lama tidak ditimpa.

Provisioning pertama mengecek ulang email member dan konflik di dalam transaksi; kegagalan audit tetap membatalkan provisioning. Status profil membaca `User.email_verified_at`. Kontrak endpoint Android `auth/google/mobile` dan payload sesi tetap kompatibel; sumber Kotlin diperiksa tanpa mengubah Android.

### QAR-F007 — Siklus iuran dan autodebit bank / collect manual

Klarifikasi owner: bank mengeksekusi autodebit; admin koperasi hanya menyetujui hasil atau mencatat penerimaan manual.

Discovery dan keputusan:

- Scheduler penerbitan iuran tetap tanggal 1 pukul 03:00.
- `due_date` lama tetap tanggal 10; tanggal ini tidak memicu debit bank dan terpisah dari jendela operasional koleksi.
- Jendela iuran bulan M: tanggal 25 M awal hari sampai tanggal 7 M+1 akhir hari, inklusif. Ditampilkan pada halaman iuran staff.
- `autodebet` tetap preferensi anggota; tidak diperlakukan sebagai otorisasi payment-provider baru.
- Tidak ditemukan service/command/job bank debit atau mandat recurring debit. Payment intents yang ada bukan integrasi autodebit bank.
- Pencatatan CASH/TRANSFER/QRIS, rekonsiliasi referensi, persetujuan, ledger, receipt, audit, dan idempotency approval memakai alur pembayaran yang ada. Jendela tidak memblokir rekonsiliasi bukti bank yang diterima terlambat atau penerimaan manual.
- Catch-up anggota aktif yang ditambahkan/diaktifkan sesudah penerbitan bulanan menggunakan perintah yang sama dengan opsi `--member=<id>` untuk periode berjalan. Eligibility dan period lock tetap berlaku; anggota lain tidak ikut diterbitkan atau dipruning oleh opsi ini. Rerun periode berjalan tanpa opsi tetap dapat dipakai sesuai otoritas operasional yang ada.
- Parsing bulan direset ke hari 1 agar Februari tidak overflow ketika command dijalankan pada tanggal 29–31 bulan lain.

`AUTODEBIT_EXECUTION_CAPABILITY_GAP`: integrasi eksekusi debit bank/ingest hasil bank otomatis tidak tersedia dan tidak ditambahkan. Dukungan domain/jendela dan pencatatan/persetujuan manual tidak menyatakan debit bank otomatis telah berhasil.

### QAR-F008 — Direct Opening Balance

Temuan: data historis tidak cukup untuk mengetahui bulan yang pernah dibayar; saldo reconciled per cut-off merupakan input otoritatif.

Koreksi: UI default Saldo Langsung menyediakan cut-off, POKOK, WAJIB, SUKARELA, KHUSUS, total otomatis, sumber, referensi opsional, tanggal dokumen opsional, catatan, Preview, dan Simpan Draft. Perubahan input membatalkan preview lama sebelum draft dapat disimpan.

- `mode=DIRECT` menggunakan nominal akhir, tanpa months × tariff atau perhitungan dari join date.
- Nominal nonnegatif dengan maksimal dua desimal; total harus positif; cut-off tidak boleh di masa depan. Kategori positif harus memiliki tepat satu contribution type aktif; konfigurasi hilang/ambigu ditolak tanpa penulisan.
- Schema batch/line yang ada dipakai: metadata mode/cut-off, `calculation_method=DIRECT`, `months_count=0`, tanggal awal/akhir sama dengan cut-off. Tidak ada migration.
- Mode CALCULATED lama dan pembacaan histori tetap tersedia; metadata lama tanpa mode dibaca sebagai CALCULATED.
- Organisasi, permission draft/post/void, sumber, audit, konflik legacy, ledger dan reversal dipertahankan. Posting mengunci batch/member dan memeriksa ulang state/duplikasi di dalam transaksi.
- Setiap kategori positif menjadi kredit `OPENING_BALANCE` pada ledger SAVINGS. VOID menambah debit `OPENING_BALANCE_REVERSAL` dan tidak mengubah entry lama.
- Tidak membuat `SAVING_PAYMENT`, monthly dues invoice, pembayaran, atau receipt untuk saldo historis ini.

## Verifikasi Development PC

PHPUnit memakai konfigurasi repository: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` dipaksa. Database bersama `kojaya_erp` maupun database QA tidak diakses. Suite concurrency PostgreSQL yang dikecualikan oleh konfigurasi default tidak diklaim telah diuji lokal.

- F004: MemberImportExecution/Preview/Validator — **102 tes, 626 assertions PASS**.
- F006: GoogleSsoFlow/MemberMatching/OpenRedirectHardening — **94 tes, 442 assertions PASS** (termasuk web/mobile, normalized email, unverified/string false, mismatch, timestamp lama, binding lama, konflik dan rollback).
- F007: DuesOperationalCycle/DuesDomainSeparation/ContributionsDuesPaymentsFunctional — **36 tes, 273 assertions PASS**; tambahan regresi overflow Februari dan boundary eksplisit — **4 tes, 48 assertions PASS**.
- F008: DirectOpeningBalance/OpeningBalanceWizard/HTTP/unit legacy dan API saldo awal — **51 tes, 198 assertions PASS**.
- Regresi lintas domain: **364 tes, 2286 assertions PASS**; batas memori proses tes dinaikkan menjadi 1 GiB karena konfigurasi PHP lokal awal 128 MiB tidak cukup untuk suite gabungan.
- Frontend build: **PASS**; satu warning CSS optimizer berasal dari selector dokumentasi yang sudah ada.
- ESLint scoped tiga halaman: **PASS, 0 errors**, delapan warning import/order/unused yang sudah ada.
- Prettier scoped: **PASS**.
- UI Chrome headless atas build lokal: **PASS**, viewport 1440×900 dan 390×844. Seluruh HTTP diintersep dengan fixture sintetis; tidak ada backend/server/DB yang diakses. Memeriksa default direct, empat nominal/payload preview, perubahan input membatalkan preview, histori calculated dan direct, dialog post/void pada mode direct, perpindahan calculated/direct, tidak ada overflow horizontal, dan tidak ada error browser.
- Pint dan `git diff --check` wajib PASS pada diff akhir.

Reproduksi UI: `npm run build`, lalu `node --test tests/node/direct-opening-balance-ui.test.mjs` dengan Chrome terpasang. Reproduksi regresi gabungan: PHPUnit konfigurasi default dengan filter `MemberImport|GoogleSso|OpeningBalance|Dues|ContributionsDuesPayments|MemberAccountLink|CooperativeMember|PeriodLock|EmailVerification`, SQLite memory, dan memory_limit=1G.

Boost/codebase-memory MCP dan berkas skill domain tidak tersedia pada sesi ini. Discovery memakai sumber langsung, konvensi sibling, schema migration yang ada, dependensi terpasang, dan dokumentasi resmi; tidak ada dependency yang ditambahkan untuk mengganti tool tersebut.

## Koreksi gate CI dan review visual

CI #562 pada head `7a6b19a62a005abf99c3514386f1ccf9f61d136c` menemukan high dependency advisories pada lockfile baseline, bukan paket yang ditambahkan oleh empat koreksi. Patch closure memperbarui dependency existing Vue dan keluarganya dari 3.5.29 ke 3.5.43 serta source-map-js dari 1.2.1 ke 1.2.2, termasuk patch transitive compiler yang diperlukan. Constraint `package.json` tidak berubah; himpunan path package lock tidak bertambah/berkurang.

Rujukan: [Vue/server-renderer advisory](https://github.com/advisories/GHSA-g2v6-rqmx-r4w6), [source-map-js advisory](https://github.com/advisories/GHSA-68fv-2mgg-jv7q). `npm audit --omit=dev --audit-level=high` lulus sesudah patch; satu advisory moderate qs tetap mengikuti threshold CI yang ada. Build client dan SSR, lint/format scoped, serta UI desktop/mobile diulang dan lulus memakai install terisolasi dari lockfile baru; dependency checkout pengguna tidak diubah.

Audit visual #316 pada head awal menunjukkan lima mismatch screenshot desktop: empat tampilan iuran yang mendapatkan informasi jendela bank dan satu default saldo awal yang beralih ke input langsung. Perubahan visual ini harus diperiksa terhadap actual/diff dan baseline hanya diperbarui untuk tampilan yang memang berubah; baseline unrelated tidak boleh diganti. Histori dan aksi post/void juga diverifikasi tetap terlihat tanpa beralih ke mode calculated.

## Batas penutupan

Bundle baru READY bila mandatory CI pada exact PR head hijau. PR tetap untuk review manusia. Merge, deployment QA, activation scheduler/queue, pengiriman FCM, debit bank, dan production release tidak dijalankan oleh task ini.

### CI visual reconciliation — 2026-10-06

Core CI #563 pada `405843b81efbce79e604b998e2f1261442c2b4a8` PASS seluruh 17 job, termasuk Dependency Audit dan PostgreSQL Concurrency. Compare visual #318 masih gagal pada lima layar iuran/saldo awal yang berubah sesuai F007/F008. Capture #317 pada exact head yang sama PASS: 234/234 screenshot desktop/tablet/mobile dihasilkan, tanpa layar gagal/skipped.

Lima layar ditinjau terhadap baseline lama: tiga state halaman iuran admin (open/partial/no-results), halaman iuran system admin, dan saldo awal anggota. Perbedaan yang diterima adalah kartu jendela koleksi bank dan form Saldo Langsung; riwayat batch tetap terlihat, form tersusun pada viewport sempit. Hanya 15 PNG untuk lima layar tersebut pada tiga viewport diperbarui dari artifact #317; screenshot lain, threshold, inventory dan workflow tidak diubah.

Compare #318 juga mencatat satu tes gambar dokumentasi yang lulus saat retry. Assertion lama membaca ukuran gambar sekali sebelum selesai dimuat. Tes kini memakai polling assertion terhadap complete/naturalWidth/naturalHeight, tetap gagal bila gambar tidak pernah berhasil dimuat. Hasil CI setelah commit reconciliation tetap harus PASS sebelum rekomendasi READY. Tidak ada perubahan aplikasi/deployment dalam koreksi visual ini.
