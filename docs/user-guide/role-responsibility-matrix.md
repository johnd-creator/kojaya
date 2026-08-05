# Matriks Tanggung Jawab Peran

> **Status:** Otomatis dihasilkan oleh
> `node scripts/generate-role-matrix.mjs`.
> Sumber data: `resources/docs/user-guide/role-permissions.json`
> (Fase 10 dari correction pass).

Matriks ini mencantumkan izin (permission) yang diberikan oleh
`RolePermissionSeeder` kepada setiap peran koperasi. Sumber
data mesin-mesin (JSON) dibandingkan dengan implementasi Spatie
oleh `tests/Feature/Documentation/RolePermissionMatrixTest.php`.
Jika izin ditambah, dihapus, atau dipindahkan antar peran, JSON
harus diperbarui dan skrip ini dijalankan ulang.

Tabel: ✅ = izin diberikan, — = tidak diberikan.

| Izin | Admin Koperasi | Anggota | Manajer Koperasi | Pengurus Koperasi |
| --- | :-: | :-: | :-: | :-: |
| `access_cooperative_pos` | ✅ | — | ✅ | ✅ |
| `adjust_store_credit` | — | — | ✅ | ✅ |
| `approve_cooperative_loan` | — | — | — | ✅ |
| `approve_cooperative_member` | — | — | — | ✅ |
| `approve_cooperative_opening_balance` | — | — | — | ✅ |
| `approve_pos_void` | ✅ | — | ✅ | ✅ |
| `approve_store_credit_transfer` | — | — | ✅ | ✅ |
| `cashier_store_credit` | ✅ | — | ✅ | ✅ |
| `export_cooperative_member` | ✅ | — | — | ✅ |
| `export_cooperative_member_pii` | — | — | — | ✅ |
| `manage_cooperative_dues` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_ledger` | — | — | ✅ | ✅ |
| `manage_cooperative_loan` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_loan_types` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_member` | ✅ | — | — | ✅ |
| `manage_cooperative_opening_balance` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_payment` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_points` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_redemption` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_rewards` | ✅ | — | ✅ | ✅ |
| `manage_cooperative_settings` | — | — | — | ✅ |
| `manage_cooperative_shu` | ✅ | — | ✅ | ✅ |
| `manage_pos_categories` | ✅ | — | ✅ | ✅ |
| `manage_pos_products` | ✅ | — | ✅ | ✅ |
| `manage_pos_shu` | — | — | ✅ | ✅ |
| `manage_store_credit` | ✅ | — | ✅ | ✅ |
| `manage_store_credit_limit` | — | — | ✅ | ✅ |
| `member_portal_access` | — | ✅ | — | — |
| `report_store_credit` | ✅ | — | ✅ | ✅ |
| `review_cooperative_loan` | — | — | ✅ | — |
| `review_cooperative_resignation` | ✅ | — | ✅ | ✅ |
| `update_cooperative_member_pii` | — | — | — | ✅ |
| `validate_cooperative_member` | ✅ | — | — | ✅ |
| `verify_cooperative_member` | ✅ | — | — | — |
| `view_cooperative_all` | — | — | — | ✅ |
| `view_cooperative_ledger` | ✅ | — | ✅ | ✅ |
| `view_cooperative_loan` | ✅ | — | ✅ | ✅ |
| `view_cooperative_member` | ✅ | — | ✅ | ✅ |
| `view_cooperative_member_pii` | — | — | — | ✅ |
| `view_cooperative_report` | — | — | ✅ | ✅ |
| `view_pos_reports` | ✅ | — | ✅ | ✅ |
| `view_store_credit` | ✅ | — | ✅ | ✅ |
| `view_store_credit_all` | — | — | — | ✅ |
| `void_cooperative_opening_balance` | — | — | — | ✅ |
| **Jumlah izin** | 26 | 1 | 29 | 41 |

---

## Deskripsi Peran

### Anggota

- **Tanggung jawab:** Mengelola simpanan dan pinjaman pribadi,
  membayar iuran tepat waktu, serta memantau poin dan rewards.
- **Aktivitas:** Melihat dashboard, mengajukan pinjaman, membayar
  iuran via QRIS/Virtual Account/E-Wallet, menukar poin dengan
  rewards, memperbarui profil.
- **Approval:** Tidak memiliki kewenangan approval. Semua
  pengajuan pinjaman ditinjau oleh Manajer dan Pengurus.
- **Batas kewenangan:** Hanya dapat melihat dan mengelola data
  milik sendiri. Tidak dapat mengakses data anggota lain atau
  modul koperasi.
- **Handoff:** Pengajuan pinjaman → Manajer Koperasi untuk
  tinjauan. Pertanyaan administratif → Admin Koperasi.
- **Tidak boleh:** Memalsukan data, membagikan kredensial login,
  membayar iuran anggota lain tanpa konfirmasi.

### Admin Koperasi

- **Tanggung jawab:** Operasional harian koperasi — keanggotaan,
  iuran, jenis pinjaman, POS, dan inventori.
- **Aktivitas:** Mendaftar dan memvalidasi anggota, mengelola
  jenis pinjaman, memverifikasi pembayaran, menjalankan shift
  kasir POS, mengelola stok, merekam setoran kasir.
- **Approval:** Memverifikasi pembayaran anggota dan permintaan
  void POS. Tidak melakukan approval pinjaman.
- **Batas kewenangan:** Tidak dapat menyetujui pinjaman, tidak
  dapat menutup periode SHU, tidak dapat mengelola pengaturan
  koperasi, tidak dapat melihat PII anggota.
- **Handoff:** Aplikasi pinjaman → Manajer Koperasi. Selisih
  berulang → Manajer Koperasi. Void di luar wewenang → Manajer
  Koperasi.
- **Tidak boleh:** Menyetujui pinjaman (bukan wewenang Admin),
  mengubah pembukuan tanpa otorisasi, membiarkan sesi login
  terbuka.

### Manajer Koperasi

- **Tanggung jawab:** Pengawasan operasional dan tinjauan
  aplikasi pinjaman tahap pertama.
- **Aktivitas:** Meninjau aplikasi pinjaman anggota, memantau
  keuangan harian, membaca laporan keuangan, mengelola buku
  besar, merekam pembukuan.
- **Approval:** Melakukan review awal aplikasi pinjaman (Catat
  review Manajer) dan dapat menolak aplikasi. Tidak melakukan
  approval final.
- **Batas kewenangan:** Tidak dapat menyetujui pinjaman secara
  final (hanya Pengurus). Tidak dapat mengelola pengaturan
  koperasi. Tidak dapat melihat PII anggota.
- **Handoff:** Aplikasi yang sudah direview → Pengurus Koperasi
  untuk keputusan akhir. Anomali berulang → Pengurus Koperasi.
- **Tidak boleh:** Melakukan approval final pinjaman, menutup
  periode SHU tanpa koordinasi Pengurus, mengabaikan anomali
  keuangan.

### Pengurus Koperasi

- **Tanggung jawab:** Tata kelola koperasi, keputusan strategis,
  dan approval final.
- **Aktivitas:** Menyetujui atau menolak aplikasi pinjaman tahap
  akhir, menutup periode SHU, mengelola pengaturan koperasi,
  mengelola PII anggota, menvalidasi anggota tahap final.
- **Approval:** Approval final pinjaman (Setujui sebagai
  Pengurus). Penutupan periode SHU. Validasi final anggota.
- **Batas kewenangan:** Tidak melakukan pencairan pinjaman
  secara langsung (dilakukan oleh peran dengan hak pencairan).
  Log Audit belum tersedia untuk Pengurus.
- **Handoff:** Pinjaman disetujui → peran berwenang menjalankan
  pencairan. Penutupan SHU → Admin Koperasi untuk rekonsiliasi.
- **Tidak boleh:** Menyetujui pinjaman yang belum direview
  Manajer, menutup SHU tanpa rekonsiliasi penuh, mendistribusikan
  poin sebelum SHU ditutup.
