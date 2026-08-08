# Content Maturity Audit — Pusat Panduan

Dokumen internal maintainer untuk editorial review berikutnya. Dokumen ini
tidak memiliki frontmatter artikel dan tidak ditampilkan pada `/documentation`.
Penilaian di sini tidak menggantikan verifikasi produk; `npm run docs:validate`
hanya memvalidasi struktur dan metadata, bukan kebenaran setiap langkah.

## Skala

- **A — mature:** tujuan, langkah, hasil, batasan, dan handoff cukup jelas serta
  memiliki sumber aplikasi yang dapat ditelusuri.
- **B — usable:** dapat dipakai sebagai panduan awal, tetapi masih perlu
  penyelarasan istilah, contoh, atau detail editorial.
- **C — incomplete/confusing:** terlalu luas, mencampur beberapa pekerjaan, atau
  memiliki klaim yang belum cukup mudah diverifikasi.
- **D — materially unreliable:** klaim utama bertentangan dengan sumber atau
  berisiko menyesatkan. Tidak ada artikel yang diberi D hanya dari audit ini;
  verifikasi manual tetap diperlukan.

## Ringkasan per artikel

| Role | File | Slug | Grade | Accuracy confidence | User clarity |
| --- | --- | --- | --- | --- | --- |
| Admin Koperasi | `admin-koperasi/operational-dashboard.md` | `admin-koperasi-operational-dashboard` | B | Sedang | Baik, tetapi terlalu banyak modul |
| Admin Koperasi | `admin-koperasi/payment-queue.md` | `admin-koperasi-payment-queue` | B | Sedang-tinggi | Baik |
| Admin Koperasi | `admin-koperasi/loan-types.md` | `admin-koperasi-loan-types` | B | Sedang | Baik |
| Admin Koperasi | `admin-koperasi/pos-inventory.md` | `admin-koperasi-pos-inventory` | C | Sedang-rendah | Terlalu padat |
| Anggota | `anggota/portal-overview.md` | `anggota-portal-overview` | B | Sedang | Baik |
| Anggota | `anggota/payment-flow.md` | `anggota-payment-flow` | B | Sedang | Baik, perlu ekspektasi status |
| Anggota | `anggota/loan-flow.md` | `anggota-loan-flow` | B | Sedang-tinggi | Baik, perlu batasan waktu/status |
| Manajer Koperasi | `manajer-koperasi/loan-review.md` | `manajer-loan-review` | B | Sedang-tinggi | Baik |
| Manajer Koperasi | `manajer-koperasi/financial-monitoring.md` | `manajer-financial-monitoring` | C | Rendah-sedang | Membutuhkan pemetaan layar |
| Pengurus Koperasi | `pengurus-koperasi/loan-approval.md` | `pengurus-loan-approval` | B | Sedang-tinggi | Baik |
| Pengurus Koperasi | `pengurus-koperasi/shu-and-governance.md` | `pengurus-shu-and-governance` | C | Sedang | Mencampur SHU dan tata kelola |
| Semua peran | `shared/glossary.md` | `shared-glossary` | C | Tinggi untuk istilah yang ada | Terlalu tipis |
| Anggota | `anggota/onboarding-and-access.md` | `anggota-onboarding-and-access` | B | Tinggi | Baik, perlu istilah status yang konsisten |
| Admin Koperasi | `admin-koperasi/member-validation.md` | `admin-koperasi-member-validation` | B | Tinggi | Baik, perlu batas aksi Admin/Pengurus |
| Admin Koperasi | `admin-koperasi/opening-balance.md` | `admin-koperasi-opening-balance` | B | Sedang-tinggi | Baik, perlu contoh data dan handoff posting |
| Semua role koperasi | `cooperative-dues-and-ledger.md` | `cooperative-dues-and-ledger` | B | Sedang-tinggi | Role-aware, perlu matriks aksi per role |
| Admin, Manajer, Pengurus | `cooperative-store-credit.md` | `cooperative-store-credit` | B | Sedang | Ringkas, perlu pembeda kewenangan yang lebih konkret |
| Manajer Koperasi | `manajer-koperasi/operator-cockpit.md` | `manajer-operator-cockpit` | B | Tinggi | Baik, perlu definisi metrik dan pengecualian |
| Pengurus Koperasi | `pengurus-koperasi/member-approval.md` | `pengurus-member-approval` | B | Tinggi | Baik, perlu checklist keputusan resmi |

Tidak ada artikel grade A atau D pada pass ini. Grade C berarti prioritas
editorial, bukan bukti bahwa artikel salah secara materiil.

## Audit detail

### 1. Admin Koperasi — Dashboard Operasional

- **Current purpose:** peta pintasan modul anggota, iuran, pinjaman, POS, dan
  inventori untuk memulai pekerjaan harian.
- **Maturity:** B.
- **Accuracy confidence:** Sedang; beberapa widget dan label perlu dicocokkan
  dengan halaman Dashboard Operasional aktual.
- **User clarity:** Baik untuk orientasi, tetapi cakupannya terlalu lebar untuk
  satu artikel.
- **Missing information:** pekerjaan paling umum dan urutan prioritas per shift;
  kondisi ketika pintasan tidak tersedia; arti setiap indikator dashboard.
- **Redundant information:** mengulang tujuan artikel POS, antrean pembayaran,
  dan jenis pinjaman.
- **Technical jargon:** `POS`, “saldo awal”, “pembukuan”, “Antrean Verifikasi
  Bukti Pembayaran”.
- **Potentially unsupported claim:** sistem “akan membuka” Dashboard Operasional
  sebagai halaman awal dan setiap modul menampilkan status pekerjaan.
- **Suggested content additions:** tabel pekerjaan → menu → hasil; satu contoh
  memulai shift; jelaskan indikator yang benar-benar muncul.
- **Suggested deletions:** daftar langkah detail yang sudah dimiliki artikel
  khusus; klaim “dalam beberapa detik”.
- **Suggested restructure:** ringkas menjadi “mulai shift”, “pindah modul”, dan
  “minta akses”; pindahkan prosedur detail ke artikel terkait.
- **Source files that should be checked:** `routes/web.php`,
  `app/Http/Controllers/Cooperative/OperatorDashboardController.php`,
  `resources/js/pages/Cooperative/Operator/Dashboard.vue`,
  `resources/js/components/AppSidebar.vue`, dan dashboard feature tests.
- **User questions:** indikator apa yang harus diprioritaskan?; mengapa pintasan
  tidak muncul?; siapa yang membuka shift?; di mana melihat antrean pembayaran?

### 2. Admin Koperasi — Antrean Verifikasi Pembayaran

- **Current purpose:** memeriksa pembayaran iuran yang menunggu verifikasi,
  termasuk approve massal.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi; route dan status perlu dicocokkan
  dengan UI saat ini.
- **User clarity:** Baik dan cukup actionable.
- **Missing information:** cara membuka bukti pembayaran bila tersedia; hasil
  approve massal sebagian gagal; pembeda pembayaran otomatis dan manual.
- **Redundant information:** beberapa pengulangan status antara hasil dan
  status yang mungkin muncul.
- **Technical jargon:** “approve”, “bulk-approve”, “pembukuan”.
- **Potentially unsupported claim:** antrean “kosong pada akhir hari” adalah
  target operasional, bukan perilaku sistem.
- **Suggested content additions:** matriks status sebelum/sesudah; apa yang
  dilakukan jika nominal atau anggota tidak cocok; bukti audit yang tersimpan.
- **Suggested deletions:** target antrean kosong bila bukan kebijakan resmi.
- **Suggested restructure:** “periksa”, “setujui”, “tunda/klarifikasi”, dan
  “proses massal”.
- **Source files that should be checked:** `routes/web.php`,
  `app/Http/Controllers/Cooperative/PaymentController.php`,
  `resources/js/pages/Cooperative/Payments/Index.vue`, request/policy terkait,
  dan payment feature tests.
- **User questions:** apakah pembayaran otomatis masuk antrean?; bagaimana
  membatalkan approve yang salah?; di mana bukti perubahan tersimpan?; kapan
  harus eskalasi ke Manajer?

### 3. Admin Koperasi — Mengelola Jenis Pinjaman

- **Current purpose:** membuat, mengubah, menonaktifkan, dan memahami batas
  penghapusan jenis pinjaman.
- **Maturity:** B.
- **Accuracy confidence:** Sedang; validasi angka dan dampak ke pinjaman aktif
  perlu verifikasi kode.
- **User clarity:** Baik.
- **Missing information:** contoh nilai yang valid; konsekuensi perubahan
  terhadap aplikasi baru versus pinjaman berjalan; perilaku soft delete.
- **Redundant information:** aturan “tidak boleh dihapus” diulang pada langkah,
  status, dan larangan.
- **Technical jargon:** plafon, tenor, biaya admin, denda.
- **Potentially unsupported claim:** perubahan langsung berlaku untuk aplikasi
  baru dan batas bunga lebih dari 100% harus diverifikasi terhadap request.
- **Suggested content additions:** tabel field wajib dan sumber validasinya;
  contoh kapan menonaktifkan, bukan menghapus.
- **Suggested deletions:** detail yang belum diverifikasi dari validasi backend.
- **Suggested restructure:** “buat”, “ubah”, “nonaktifkan/hapus”, “dampak”.
- **Source files that should be checked:** `routes/web.php`,
  `app/Http/Controllers/Cooperative/LoanTypeController.php`, FormRequest,
  model/policy, `resources/js/pages/Cooperative/LoanTypes/Index.vue`, tests.
- **User questions:** apakah perubahan memengaruhi pinjaman aktif?; nilai bunga
  disimpan sebagai persen atau nominal?; siapa menyetujui perubahan?; apakah
  penghapusan dapat dipulihkan?

### 4. Admin Koperasi — Operasi Harian POS, Inventori, dan Setoran Kasir

- **Current purpose:** satu panduan besar untuk shift POS, penjualan, kopi, void,
  retur, kredit, setoran, opname, penerimaan, dan transfer inventori.
- **Maturity:** C.
- **Accuracy confidence:** Sedang-rendah karena banyak route dan permission
  digabung dalam satu alur.
- **User clarity:** Terlalu padat; pembaca sulit menemukan tugas spesifik.
- **Missing information:** pemisahan wewenang kasir/admin; prasyarat per alur;
  dampak void/retur; langkah rekonsiliasi; hasil approval.
- **Redundant information:** dashboard operasional dan pemantauan keuangan.
- **Technical jargon:** POS, void, store account, opname, stock movement.
- **Potentially unsupported claim:** menu “Penyetoran Kasir” dan tombol “Tutup
  Setoran” perlu dipastikan ada pada surface Admin yang berlaku.
- **Suggested content additions:** indeks tugas dengan tautan; satu checklist
  shift; tabel siapa boleh melakukan void, retur, closing, dan transfer.
- **Suggested deletions:** langkah untuk workflow yang tidak benar-benar tersedia
  pada satu role; istilah “setoran” sampai layar diverifikasi.
- **Suggested restructure:** split menjadi POS shift/transaksi, retur/void,
  closing/setoran, dan inventori; atau pertahankan satu artikel dengan task map.
- **Source files that should be checked:** `routes/web.php`, controller/service
  POS, `resources/js/pages/Cooperative/Pos/*`, inventory pages, policies, dan
  POS/inventory feature tests.
- **User questions:** kapan memakai void dibanding retur?; siapa menyetujui
  void?; bagaimana menangani selisih closing?; apakah transfer gudang langsung
  mengubah stok?; apa yang dilakukan saat shift tidak bisa ditutup?

### 5. Anggota — Mengenal Portal Anggota

- **Current purpose:** orientasi menu dan onboarding anggota.
- **Maturity:** B.
- **Accuracy confidence:** Sedang; status email, dokumen, dan fitur finansial
  perlu dicocokkan dengan gate aktual.
- **User clarity:** Baik untuk pengguna baru.
- **Missing information:** urutan onboarding yang sebenarnya; arti akun aktif;
  menu yang tetap tersedia ketika akses finansial dibatasi.
- **Redundant information:** daftar fitur berulang dengan artikel pembayaran dan
  pinjaman.
- **Technical jargon:** onboarding, Store Account, status keanggotaan.
- **Potentially unsupported claim:** “verifikasi email” sebagai prasyarat login
  dan sistem otomatis meminta onboarding setelah login pertama.
- **Suggested content additions:** peta menu aktual; kondisi akses terbatas;
  kapan menghubungi Admin versus masalah login.
- **Suggested deletions:** klaim alur email/onboarding yang belum diverifikasi.
- **Suggested restructure:** “setelah login”, “menu utama”, “jika akses belum
  lengkap”.
- **Source files that should be checked:** `routes/web.php`,
  `resources/js/components/AppSidebar.vue`, member pages, onboarding middleware,
  `EnsureMemberFullyActive`, dan member feature tests.
- **User questions:** mengapa menu finansial belum muncul?; apa yang harus
  disiapkan untuk onboarding?; siapa memverifikasi data?; kapan status aktif
  berubah?

### 6. Anggota — Alur Pembayaran Iuran Bulanan

- **Current purpose:** memilih tagihan dan menyelesaikan pembayaran digital.
- **Maturity:** B.
- **Accuracy confidence:** Sedang.
- **User clarity:** Baik, langkah utama mudah diikuti.
- **Missing information:** kapan pembayaran otomatis versus menunggu verifikasi;
  durasi pembaruan; apa yang dilakukan jika pembayaran terpotong tetapi status
  belum berubah; detail kanal yang benar-benar aktif.
- **Redundant information:** status tagihan dan handoff Admin mengulang hal yang
  sama.
- **Technical jargon:** QRIS, Virtual Account, E-Wallet, payment intent.
- **Potentially unsupported claim:** tagihan berubah langsung menjadi Lunas dan
  histori simpanan langsung memperbarui saldo untuk semua kanal.
- **Suggested content additions:** tabel kanal → status awal → status akhir;
  langkah refresh/status; jalur bantuan dengan nomor tagihan.
- **Suggested deletions:** kanal yang belum terbukti aktif di environment target.
- **Suggested restructure:** “pilih tagihan”, “pilih kanal”, “cek hasil”,
  “jika belum berubah”.
- **Source files that should be checked:** `routes/web.php`, member savings
  page, payment intent/status controllers and services, Midtrans adapter,
  `tests/Feature/MemberPortal/*Payment*Test.php`.
- **User questions:** apakah pembayaran perlu diverifikasi Admin?; berapa lama
  status berubah?; apakah pembayaran sebagian bisa dilanjutkan?; bagaimana jika
  QR/VA kedaluwarsa?; apakah saldo langsung berubah?

### 7. Anggota — Mengajukan dan Melacak Pinjaman

- **Current purpose:** mengajukan pinjaman, memantau review, pencairan, dan
  pembayaran angsuran.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi untuk role hierarchy; detail status
  dan kanal pembayaran tetap perlu regression check.
- **User clarity:** Baik, tetapi langkah 1–8 mencampur pengajuan dan pembayaran.
- **Missing information:** arti setiap status secara lengkap; kapan pencairan
  dilakukan; cara menangani penolakan; detail angsuran pertama.
- **Redundant information:** handoff mengulang bagian status.
- **Technical jargon:** outstanding, QRIS, Virtual Account, E-Wallet.
- **Potentially unsupported claim:** status “Dihapuskan” dan semua kanal
  pembayaran angsuran perlu diverifikasi terhadap UI/status enum.
- **Suggested content additions:** pisahkan “ajukan” dan “bayar angsuran”;
  tambahkan contoh timeline tanpa SLA; jelaskan nomor aplikasi.
- **Suggested deletions:** status/kanal yang tidak ditemukan pada source.
- **Suggested restructure:** pengajuan → review → pencairan → angsuran → bantuan.
- **Source files that should be checked:** loan routes/controllers/services,
  `resources/js/pages/Member/Loans/*`, payment intent service, policies, dan
  member loan feature tests.
- **User questions:** berapa lama review?; siapa yang meninjau?; mengapa
  ditolak?; kapan dana cair?; bagaimana membayar angsuran yang terlambat?

### 8. Manajer Koperasi — Tinjauan Aplikasi Pinjaman

- **Current purpose:** meninjau aplikasi, mencatat review, meneruskan atau
  menolak dengan alasan.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi.
- **User clarity:** Baik dan cukup actionable.
- **Missing information:** kriteria review resmi; batas kewenangan; cara
  memperbaiki review yang salah; notifikasi ke Pengurus.
- **Redundant information:** status diulang pada langkah, hasil, dan status.
- **Technical jargon:** outstanding, review, handoff.
- **Potentially unsupported claim:** seluruh daftar detail (misalnya daftar
  pembayaran dan riwayat keputusan) selalu tersedia pada halaman detail.
- **Suggested content additions:** checklist review yang disetujui bisnis;
  contoh catatan review; penanganan aplikasi tidak lengkap.
- **Suggested deletions:** field detail yang tidak selalu tampil.
- **Suggested restructure:** buka antrean → periksa → catat keputusan → handoff.
- **Source files that should be checked:** loan controller, FormRequest/policy,
  `resources/js/pages/Cooperative/Loans/Show.vue`, loan services, tests.
- **User questions:** data apa yang wajib diperiksa?; apakah bisa menyimpan
  draft?; apa yang terjadi setelah ditolak?; kapan Pengurus menerima tugas?

### 9. Manajer Koperasi — Pemantauan Keuangan Harian

- **Current purpose:** memantau pinjaman macet, angsuran tertunda, kasir,
  pembukuan, dan pencairan.
- **Maturity:** C.
- **Accuracy confidence:** Rendah-sedang; artikel menyebut banyak widget/menu
  yang harus dipetakan satu per satu.
- **User clarity:** Membutuhkan pengetahuan layar yang belum dijelaskan.
- **Missing information:** definisi metrik; periode pembanding; sumber angka;
  tindakan setelah menemukan anomali; batas “selisih” yang perlu eskalasi.
- **Redundant information:** tumpang tindih dengan POS dan loan-review.
- **Technical jargon:** NPL/pinjaman macet, outstanding, rekonsiliasi,
  pembukuan.
- **Potentially unsupported claim:** widget “Pinjaman Macet”, “Angsuran
  Tertunda”, dan “Penyetoran Kasir” berada di Dashboard Operasional Manajer.
- **Suggested content additions:** screenshot/label aktual; definisi setiap
  indikator; checklist awal hari; sumber laporan yang harus dibandingkan.
- **Suggested deletions:** metrik yang tidak ada pada halaman yang diverifikasi.
- **Suggested restructure:** dashboard → pinjaman → kas/setoran → eskalasi;
  pertimbangkan artikel terpisah untuk rekonsiliasi.
- **Source files that should be checked:** operator dashboard controller/page,
  cooperative reports, payments, ledger, POS closing pages, report services,
  and manager feature tests.
- **User questions:** angka ini berasal dari periode apa?; apa arti “macet”?;
  siapa yang memperbaiki selisih?; kapan harus melapor ke Pengurus?

### 10. Pengurus Koperasi — Persetujuan Akhir Pinjaman

- **Current purpose:** mengambil keputusan akhir setelah review Manajer dan
  meneruskan ke pencairan.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi.
- **User clarity:** Baik.
- **Missing information:** kriteria persetujuan; siapa tepatnya yang mencairkan;
  notifikasi; penanganan keputusan yang perlu dikoreksi.
- **Redundant information:** status dan handoff berulang.
- **Technical jargon:** approval, outstanding, handoff.
- **Potentially unsupported claim:** semua peran koperasi dapat melihat catatan
  keputusan pada detail; perlu cek policy dan serialization.
- **Suggested content additions:** checklist keputusan; contoh alasan tolak;
  batas antara approve dan disburse.
- **Suggested deletions:** klaim akses lintas role jika tidak terbukti.
- **Suggested restructure:** antrean → review Manajer → keputusan Pengurus →
  pencairan → audit.
- **Source files that should be checked:** loan controller/service/policies,
  `resources/js/pages/Cooperative/Loans/Show.vue`, tests untuk manager/pengurus.
- **User questions:** apa yang harus dipastikan sebelum approve?; apakah bisa
  mengembalikan status?; siapa mencairkan?; di mana melihat alasan penolakan?

### 11. Pengurus Koperasi — SHU, Tata Kelola, dan Audit Internal

- **Current purpose:** mengelola preview/penutupan SHU dan memberi konteks tata
  kelola serta keterbatasan audit.
- **Maturity:** C.
- **Accuracy confidence:** Sedang; bagian SHU lebih konkret daripada bagian
  tata kelola.
- **User clarity:** SHU cukup jelas, tetapi judul mencakup tiga topik berbeda.
- **Missing information:** periode yang dapat dipilih; validasi pool; pembagian
  tanggung jawab; prosedur koreksi setelah tutup; sumber audit yang tersedia.
- **Redundant information:** handoff ke Manajer/Pengurus berulang dengan artikel
  lain.
- **Technical jargon:** SHU, pool, AD/ART, RAT, audit trail.
- **Potentially unsupported claim:** alokasi tidak dapat diubah dan data poin
  selalu dapat dipantau perlu diverifikasi pada controller/page.
- **Suggested content additions:** checklist pra-penutupan; definisi pool;
  langkah koreksi resmi; link ke laporan yang benar-benar tersedia.
- **Suggested deletions:** cakupan AD/ART/RAT bila belum menjadi workflow.
- **Suggested restructure:** split SHU operational guide from governance/audit
  notes once source workflow exists.
- **Source files that should be checked:** SHU controller/service/page, points,
  reports, policies, `resources/docs/user-guide/*`, and SHU feature tests.
- **User questions:** siapa mengisi pool?; apakah preview bisa diulang?; apa
  dampak menutup periode?; bagaimana koreksi?; di mana audit perubahan?

### 12. Semua Peran — Glosarium Istilah Koperasi

- **Current purpose:** memberi definisi singkat istilah yang muncul di panduan.
- **Maturity:** C.
- **Accuracy confidence:** Tinggi untuk tujuh definisi yang ada; rendah untuk
  kelengkapan cakupan.
- **User clarity:** Sangat mudah dibaca, tetapi terlalu sedikit untuk mendukung
  artikel yang ada.
- **Missing information:** istilah status pinjaman/pembayaran, SHU, approval,
  onboarding, POS, void, retur, dan kanal pembayaran.
- **Redundant information:** belum terlihat duplikasi berarti.
- **Technical jargon:** NPL, VA, 5C, SHU tanpa contoh penggunaan.
- **Potentially unsupported claim:** “dipakai di seluruh pusat panduan” terlalu
  kuat untuk tujuh istilah.
- **Suggested content additions:** definisi berdasarkan istilah yang benar-benar
  muncul; contoh kalimat; tautan artikel sumber.
- **Suggested deletions:** istilah yang tidak muncul setelah editorial review.
- **Suggested restructure:** kelompok “pinjaman”, “pembayaran”, “POS”, dan
  “tata kelola”; tambahkan alfabetisasi di dalam kelompok.
- **Source files that should be checked:** seluruh artikel, status/enum loan dan
  payment, `resources/js/pages/Member/*`, POS pages, serta feature tests.
- **User questions:** apa beda tagihan dan pembayaran?; apa beda review dan
  approval?; kapan NPL digunakan?; apa itu status “Diajukan”?

### 13. Anggota — Onboarding dan Akses

- **Current purpose:** Menjelaskan enam tahap onboarding, submit, status
  validasi, revisi, dan kapan fitur finansial terbuka.
- **Maturity:** B.
- **Accuracy confidence:** Tinggi; langkah dan status berasal dari
  `Onboarding.vue`, `MemberOnboardingService`, dan middleware akses anggota.
- **User clarity:** Baik dan berurutan.
- **Missing information:** daftar field wajib per tahap; cara membaca catatan
  revisi; kanal bantuan jika status terlalu lama.
- **Redundant information:** sebagian status juga muncul di artikel portal.
- **Technical jargon:** onboarding, approval final, validasi.
- **Potentially unsupported claim:** arti status `Ditolak` dan langkah setelah
  penolakan perlu dikonfirmasi dengan prosedur koperasi.
- **Suggested content additions:** tabel status → tindakan anggota; contoh
  data yang perlu disiapkan tanpa memakai data produksi.
- **Suggested deletions:** janji waktu proses yang belum memiliki SLA resmi.
- **Suggested restructure:** persiapan → isi enam tahap → submit → status.
- **Source files that should be checked:** `resources/js/pages/Kojayaku/Onboarding.vue`,
  `app/Services/Cooperative/MemberOnboardingService.php`,
  `app/Services/Cooperative/MemberAccessService.php`,
  `app/Http/Middleware/EnsureMemberFullyActive.php`, dan member tests.
- **User questions:** data apa yang wajib disiapkan?; kapan Admin menerima
  pendaftaran?; apa yang harus diperbaiki saat revisi?; kapan menu finansial
  terbuka?

### 14. Admin Koperasi — Validasi Data Anggota

- **Current purpose:** mencari anggota, memeriksa detail, memverifikasi data,
  dan menindaklanjuti pengunduran diri.
- **Maturity:** B.
- **Accuracy confidence:** Tinggi untuk pembagian verifikasi Admin dan approval
  final Pengurus; filter dan label perlu regression check.
- **User clarity:** Baik, tetapi masih perlu contoh keputusan.
- **Missing information:** field yang wajib diperiksa; format catatan; hubungan
  validasi, aktivasi, dan pengunduran diri.
- **Redundant information:** ringkasan menu mengulang dashboard Admin.
- **Technical jargon:** validation status, lifecycle, handoff.
- **Potentially unsupported claim:** setiap perubahan data langsung mengubah
  status keanggotaan perlu dicek pada service.
- **Suggested content additions:** checklist pemeriksaan dan tabel status.
- **Suggested deletions:** klaim pemeriksaan PII bila tidak tersedia untuk Admin.
- **Suggested restructure:** cari → periksa → verifikasi → eskalasi.
- **Source files that should be checked:** `app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php`,
  `app/Services/Cooperative/MemberValidationService.php`,
  `resources/js/pages/Cooperative/Members/{Index,Show,Edit}.vue`, policies,
  dan member authorization tests.
- **User questions:** apa yang wajib dicek?; kapan diteruskan ke Pengurus?;
  bagaimana menangani revisi?; siapa memproses pengunduran diri?

### 15. Admin Koperasi — Saldo Awal Anggota

- **Current purpose:** menyiapkan batch saldo awal, melihat preview, dan
  meneruskan batch untuk posting.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi; UI dan route jelas, tetapi aturan
  bisnis sumber saldo perlu review manual.
- **User clarity:** Baik untuk alur dasar.
- **Missing information:** definisi setiap source type; siapa yang melakukan
  posting dan void; aturan koreksi batch.
- **Redundant information:** sebagian besar konteks anggota sudah ada di
  artikel validasi anggota.
- **Technical jargon:** batch, preview, post, void.
- **Potentially unsupported claim:** role Pengurus selalu menjadi pelaksana
  posting/void harus dicocokkan dengan permission aktual dan SOP.
- **Suggested content additions:** tabel status batch → aksi yang tersedia;
  contoh rekonsiliasi non-produksi.
- **Suggested deletions:** handoff yang belum disepakati sebagai SOP.
- **Suggested restructure:** buat batch → preview → simpan → posting/koreksi.
- **Source files that should be checked:**
  `resources/js/pages/Cooperative/Members/OpeningBalance/Wizard.vue`,
  `app/Http/Controllers/Cooperative/CooperativeOpeningBalanceWizardController.php`,
  opening-balance service, policies, dan feature tests.
- **User questions:** apa sumber saldo yang boleh dipakai?; kapan batch boleh
  diposting?; bagaimana membatalkan batch?; siapa menyetujui?

### 16. Role Koperasi — Iuran dan Ledger

- **Current purpose:** memisahkan pemantauan tagihan, pembacaan ledger, dan
  tindakan koreksi/penarikan berdasarkan role.
- **Maturity:** B.
- **Accuracy confidence:** Sedang-tinggi; route dan status UI tersedia, namun
  pembagian tugas operasional perlu disepakati.
- **User clarity:** Baik untuk orientasi, belum cukup sebagai SOP koreksi.
- **Missing information:** matriks aksi per role; bukti yang dibutuhkan untuk
  koreksi; alur penarikan simpanan.
- **Redundant information:** beririsan dengan antrean pembayaran dan monitoring
  keuangan.
- **Technical jargon:** ledger, outstanding, scope, void.
- **Potentially unsupported claim:** semua role dapat menjalankan aksi koreksi;
  controller/policy harus menjadi acuan final.
- **Suggested content additions:** status tagihan → tindakan; contoh filter;
  definisi mutasi ledger.
- **Suggested deletions:** langkah yang hanya tersedia untuk Manajer/Pengurus
  dari bagian Admin.
- **Suggested restructure:** tagihan → ledger → penarikan/koreksi → handoff.
- **Source files that should be checked:** `resources/js/pages/Cooperative/Dues/Index.vue`,
  `resources/js/pages/Cooperative/Ledger/Index.vue`, withdrawal controller,
  ledger controller/service/policies, dan payment tests.
- **User questions:** apa beda Belum Lunas dan Sebagian?; kapan memakai void?;
  siapa memproses penarikan?; bagaimana menelusuri koreksi?

### 17. Role Koperasi — Saldo Toko

- **Current purpose:** memandu pencarian akun, detail saldo, riwayat, transfer,
  dan laporan dengan pembeda kewenangan role.
- **Maturity:** B.
- **Accuracy confidence:** Sedang; controller dan UI tersedia, tetapi detail
  batas operasi Admin/Manajer/Pengurus perlu review policy.
- **User clarity:** Cukup jelas dan sengaja tidak menjanjikan tombol tertentu.
- **Missing information:** status akun; status transfer; prosedur suspend,
  limit, adjustment, dan cash funding.
- **Redundant information:** overlap dengan POS dan artikel monitoring.
- **Technical jargon:** store credit, funding, adjustment, delegate.
- **Potentially unsupported claim:** cakupan laporan tiap role perlu diverifikasi
  dari policy dan query service.
- **Suggested content additions:** tabel aksi → role; penjelasan status akun;
  pembeda transfer dan cash funding.
- **Suggested deletions:** istilah bahasa Inggris jika sudah ada padanan UI.
- **Suggested restructure:** lihat akun → proses transfer → kelola akun → laporan.
- **Source files that should be checked:** `resources/js/pages/Cooperative/StoreCredit/*.vue`,
  `app/Http/Controllers/Cooperative/MemberStoreCreditController.php`,
  store-credit policies/services, member StoreAccount page, dan store-credit tests.
- **User questions:** apa beda saldo dan limit?; siapa menyetujui transfer?;
  kapan akun ditangguhkan?; bagaimana adjustment dicatat?

### 18. Manajer Koperasi — Cockpit Operasional

- **Current purpose:** memantau dashboard operator, inbox persetujuan,
  pengecualian, analitik, dan ekspor.
- **Maturity:** B.
- **Accuracy confidence:** Tinggi untuk route dan permission
  `view_cooperative_report`.
- **User clarity:** Baik, tetapi belum menjelaskan arti setiap metrik.
- **Missing information:** definisi sumber data, periode, dan kriteria eskalasi.
- **Redundant information:** beririsan dengan monitoring keuangan dan review
  pinjaman.
- **Technical jargon:** deferred, exception, analytics, export.
- **Potentially unsupported claim:** semua pengecualian otomatis memiliki solusi
  dari cockpit.
- **Suggested content additions:** kamus metrik dan checklist pemeriksaan.
- **Suggested deletions:** klaim prioritas yang belum ditetapkan bisnis.
- **Suggested restructure:** ringkasan → inbox → pengecualian → analitik → ekspor.
- **Source files that should be checked:** `app/Http/Controllers/Cooperative/OperatorProcedureController.php`,
  `app/Services/Cooperative/OperatorProcedureService.php`,
  `resources/js/pages/Cooperative/Operator/Dashboard.vue`, dan operator tests.
- **User questions:** angka berasal dari periode apa?; apa arti pengecualian?;
  kapan perlu ekspor?; siapa menerima eskalasi?

### 19. Pengurus Koperasi — Approval Final Data Anggota

- **Current purpose:** meninjau hasil verifikasi Admin lalu approve final,
  meminta revisi, atau menolak.
- **Maturity:** B.
- **Accuracy confidence:** Tinggi untuk state transition yang terlihat pada
  controller dan onboarding UI.
- **User clarity:** Baik, tetapi belum memiliki checklist keputusan resmi.
- **Missing information:** kriteria approval; format alasan; konsekuensi setiap
  keputusan terhadap akses anggota.
- **Redundant information:** handoff ke onboarding mengulang artikel Anggota.
- **Technical jargon:** approval final, validation status, revision.
- **Potentially unsupported claim:** approval final selalu langsung membuka
  semua fitur perlu diverifikasi terhadap status member.
- **Suggested content additions:** checklist persetujuan dan matriks keputusan.
- **Suggested deletions:** kriteria bisnis yang belum tertulis di source.
- **Suggested restructure:** antrean → pemeriksaan → keputusan → handoff.
- **Source files that should be checked:** `app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php`,
  `app/Services/Cooperative/MemberValidationService.php`,
  `resources/js/pages/Cooperative/Members/Show.vue`, onboarding/access service,
  dan authorization tests.
- **User questions:** apa yang dianggap lengkap?; kapan memilih revisi?; apa
  dampak reject?; kapan anggota bisa masuk ke portal finansial?

## Top 10 content gaps

1. Tidak ada matriks status → arti → tindakan untuk pembayaran dan pinjaman.
2. Belum ada ekspektasi waktu/status tanpa menjanjikan SLA.
3. Jalur pembayaran gagal atau pembayaran terpotong belum dijelaskan lengkap.
4. Batas tanggung jawab Admin, Manajer, Pengurus, dan peran pencair belum selalu
   eksplisit.
5. Panduan POS/inventori terlalu banyak workflow dalam satu artikel.
6. Metrik Dashboard Manajer belum memiliki definisi dan sumber angka.
7. Kriteria review/approval pinjaman belum tersedia sebagai checklist resmi.
8. Prosedur koreksi setelah SHU ditutup belum jelas.
9. Onboarding dan kondisi akses anggota terbatas belum dipetakan end-to-end.
10. Glosarium belum mencakup istilah yang paling sering muncul di SOP.

## Top 10 unsupported or unclear claims to verify

1. Dashboard Admin otomatis menjadi halaman awal.
2. Semua modul menampilkan status pekerjaan pada dashboard.
3. Antrean pembayaran kosong pada akhir hari.
4. Perubahan jenis pinjaman langsung berlaku untuk aplikasi baru.
5. Bunga lebih dari 100% adalah validasi yang pasti ditolak.
6. Menu/tombol setoran kasir tersedia dengan nama yang disebut.
7. Semua kanal QRIS, VA, dan E-Wallet aktif untuk setiap pembayaran.
8. Status pinjaman “Dihapuskan” tampil pada portal anggota.
9. Widget metrik Manajer ada pada dashboard dan memiliki definisi yang disebut.
10. Semua peran koperasi dapat melihat riwayat keputusan pinjaman.

## Top 10 terminology problems

1. “artikel” dan “panduan” dipakai bergantian; UI kini distandardisasi ke
   “panduan”.
2. `POS`, `Store Account`, dan “Saldo Toko” belum konsisten.
3. “Approve”, “Setujui”, dan “approval” bercampur.
4. “Review”, “tinjauan”, dan “direview” perlu satu gaya bahasa.
5. “Void” belum diberi padanan/definisi operasional.
6. “Opname” perlu ditulis “opname stok” pada kemunculan pertama.
7. “Pool SHU” membutuhkan definisi sederhana.
8. “Outstanding” dapat diganti “sisa kewajiban” untuk pembaca anggota.
9. “Handoff” sebaiknya diganti “Diteruskan kepada” pada copy user-facing.
10. “VA” dan “Virtual Account” perlu konsisten dengan glosarium.

## Recommended additions

- Panduan ringkas “Memahami status pembayaran iuran”.
- Panduan ringkas “Memahami status pengajuan pinjaman”.
- Checklist review pinjaman Manajer.
- Checklist approval akhir Pengurus.
- Runbook penanganan pembayaran terpotong/status belum berubah.
- Runbook closing shift dan selisih setoran.
- Peta onboarding dan akses anggota terbatas.
- Glossary yang ditautkan dari istilah sulit.
- Peta metrik Dashboard Manajer setelah widget diverifikasi.
- Halaman bantuan “Kapan menghubungi Admin, Manajer, atau Pengurus?”.

## Recommended merge/split

- Pecah `admin-koperasi-pos-inventory.md` menjadi POS harian, closing/void/retur,
  dan inventori jika ketiga workflow memang dipakai terpisah.
- Pecah `pengurus-shu-and-governance.md` menjadi SHU operasional dan tata kelola
  hanya setelah workflow governance memiliki source yang cukup.
- Pertahankan `anggota-portal-overview.md` sebagai orientasi; jangan duplikasi
  langkah detail pembayaran/pinjaman di dalamnya.
- Pertahankan artikel Manajer dan Pengurus terpisah karena kewenangannya berbeda.
- Pertahankan `shared-glossary.md`, tetapi perluas secara bertahap berdasarkan
  istilah yang tervalidasi.

## User questions still unanswered

1. Berapa lama pembayaran berubah menjadi lunas atau terverifikasi?
2. Apa yang dilakukan jika dana sudah terpotong tetapi status belum berubah?
3. Mengapa menu finansial anggota belum muncul?
4. Siapa yang memproses pinjaman setelah diajukan?
5. Berapa lama review dan persetujuan pinjaman?
6. Apa beda void dan retur pada POS?
7. Siapa yang menyelesaikan selisih closing kasir?
8. Bagaimana memperbaiki keputusan atau data setelah approval?
9. Apa dampak menutup periode SHU dan bagaimana koreksinya?
10. Di mana pengguna dapat melihat jejak perubahan atau alasan penolakan?

## Editorial review order

Prioritaskan artikel grade C (`admin-koperasi-pos-inventory.md`,
`manajer-koperasi/financial-monitoring.md`,
`pengurus-koperasi/shu-and-governance.md`, dan `shared/glossary.md`), lalu
verifikasi klaim yang tercantum di atas terhadap source aplikasi. Setelah itu,
rapikan artikel grade B tanpa mengubah alur bisnis yang belum disepakati.
