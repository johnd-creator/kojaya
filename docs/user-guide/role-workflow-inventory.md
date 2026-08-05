# Inventaris Workflow Aktif per Peran

Daftar workflow yang diimplementasikan aplikasi dan tercakup dalam
pusat panduan. Baris inventaris dihasilkan secara deterministik
dari `resources/docs/user-guide/role-workflow-inventory.json`.
Setiap artikel published pada pusat panduan harus muncul di
inventaris; setiap baris inventaris yang berstatus `documented`
harus menunjuk sebuah slug artikel yang valid.

## Ringkasan

- Active workflows: **17**
- Documented: **12**
- Partial: **0**
- Gap: **5**
- Deferred: **0**

## Matriks

| Role | Modul | Menu | Route | Permission | Aktivitas | Jenis aktivitas | Risiko | Screenshot tersedia | Status dokumentasi | Artikel |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Anggota | Portal Anggota | Beranda | `member.dashboard` | `member_portal_access` | melihat | informasional | informasional | ya | documented | `anggota-portal-overview` |
| Anggota | Pinjaman | Pinjaman Saya | `member.loans` | `member_portal_access` | melihat | informasional | operasional | ya | documented | `anggota-loan-flow` |
| Anggota | Pinjaman | Pinjaman Saya | `member.loans.store` | `member_portal_access` | membuat | transaksional | operasional | ya | documented | `anggota-loan-flow` |
| Anggota | Pembayaran | Pembayaran Iuran | `member.payments.intent` | `member_portal_access` | membuat | transaksional | operasional | ya | documented | `anggota-payment-flow` |
| Admin Koperasi | Dashboard Operasional | Dashboard Admin | `cooperative.operator.dashboard` | `view_cooperative_member` | melihat | informasional | operasional | ya | documented | `admin-koperasi-operational-dashboard` |
| Admin Koperasi | Pinjaman | Jenis Pinjaman | `cooperative.loan-types.index` | `manage_cooperative_loan_types` | mengubah | administrasi | operasional | ya | documented | `admin-koperasi-loan-types` |
| Admin Koperasi | Pembayaran | Antrean Verifikasi | `cooperative.payments.index` | `manage_cooperative_payment` | memverifikasi | approval | approval | ya | documented | `admin-koperasi-payment-queue` |
| Admin Koperasi | POS | Operasional POS | `cooperative.pos.index` | `access_cooperative_pos` | transaksi keuangan | transaksional | finansial | ya | documented | `admin-koperasi-pos-inventory` |
| Manajer Koperasi | Pinjaman | Tinjauan Pinjaman | `cooperative.loans.show` | `review_cooperative_loan` | memverifikasi | approval | approval | ya | documented | `manajer-loan-review` |
| Manajer Koperasi | Keuangan | Pemantauan Keuangan | `cooperative.reports.index` | `view_cooperative_report` | mengunduh laporan | informasional | operasional | ya | documented | `manajer-financial-monitoring` |
| Pengurus Koperasi | Pinjaman | Persetujuan Akhir Pinjaman | `cooperative.loans.show` | `approve_cooperative_loan` | menyetujui | approval | approval | ya | documented | `pengurus-loan-approval` |
| Pengurus Koperasi | Tata Kelola | SHU | `cooperative.shu.index` | `manage_cooperative_shu` | menutup periode | administrasi | finansial | ya | documented | `pengurus-shu-and-governance` |
| Anggota | Pembatalan Pinjaman | Pembatalan Aplikasi | _(belum tersedia)_ | _(belum tersedia)_ | membatalkan | transaksional | operasional | tidak | gap | _(lihat alasan)_ |
| Pengurus Koperasi | Tata Kelola | Approval Minute | _(belum tersedia)_ | _(belum tersedia)_ | administrasi lainnya | administrasi | operasional | tidak | gap | _(lihat alasan)_ |
| Pengurus Koperasi | Tata Kelola | Risalah RAT | _(belum tersedia)_ | _(belum tersedia)_ | administrasi lainnya | administrasi | operasional | tidak | gap | _(lihat alasan)_ |
| Pengurus Koperasi | Tata Kelola | Audit Log | `audit-logs` | `view_audit_logs` | melihat | informasional | operasional | tidak | gap | _(lihat alasan)_ |
| Pengurus Koperasi | Tata Kelola | Pengecualian | `exceptions.index` | `view_balance_sheet` | melihat | informasional | operasional | tidak | gap | _(lihat alasan)_ |

## Alasan Gap

- **Pembatalan Pinjaman oleh Anggota**: Pembatalan aplikasi
  pinjaman oleh anggota belum diimplementasikan di Kojaya.
- **Approval Minute**: Model `ApprovalMinute` belum tersedia;
  risalah keputusan tercatat pada log keputusan aplikasi.
- **Risalah RAT**: Model `RatMinute` belum tersedia di Kojaya;
  pencatatan dilakukan di luar aplikasi.
- **Audit Log (Pengurus)**: Izin `view_audit_logs` belum
  diberikan kepada Pengurus Koperasi; halaman Log Audit belum
  dapat diakses Pengurus secara langsung.
- **Pengecualian (Pengurus)**: Izin `view_balance_sheet`
  belum diberikan kepada Pengurus Koperasi; halaman Pengecualian
  belum dapat diakses Pengurus secara langsung.
