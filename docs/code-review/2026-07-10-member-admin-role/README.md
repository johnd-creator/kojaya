# Code Review: Role Anggota dan Admin Koperasi

Tanggal review: 10 Juli 2026  
Repository: `johnd-creator/kojaya`  
Branch sumber: `main`  
Commit sumber: `c39ac5631222e06da0f7221fb6237715cdcdae54`

## Tujuan

Review ini memeriksa batas akses dan alur bisnis yang berkaitan dengan:

- role `Anggota`;
- role `Admin Koperasi`;
- role koperasi terkait: `Pengurus Koperasi`, `Manajer Koperasi`, dan `Kasir Koperasi`;
- autentikasi web dan mobile;
- pemetaan role/permission Spatie ke Sanctum token abilities;
- portal anggota, API member self-service, dan area ERP koperasi;
- lifecycle anggota: pending, verifikasi admin, approval pengurus, aktif, revisi, ditolak, nonaktif, dan resign;
- pembatasan data berdasarkan pemilik dan organisasi;
- test otorisasi yang perlu ditambahkan.

Review dilakukan secara statis melalui isi repository GitHub. Test suite tidak dieksekusi pada review ini, sehingga seluruh rekomendasi test pada dokumen ini perlu dijalankan di environment proyek.

## Kesimpulan Eksekutif

Fondasi akses sudah cukup baik karena aplikasi menggunakan:

- Spatie Permission untuk role dan permission;
- Laravel Policy pada sejumlah resource penting;
- Sanctum token abilities untuk API;
- middleware khusus portal anggota;
- audit log pada sebagian perubahan status;
- pemisahan verifikasi admin dan approval final pengurus.

Namun, batas antara **akses mandiri anggota** dan **akses administratif koperasi** belum dipisahkan secara konsisten. Permission `view_cooperative_member` dan `view_cooperative_loan` dipakai sekaligus untuk kebutuhan anggota dan halaman administrasi. Akibatnya, beberapa endpoint administratif dapat dimasuki oleh role `Anggota`, dan beberapa query/response administratif mengembalikan data global.

Risiko paling penting yang perlu ditangani sebelum production rollout adalah:

1. Role `Anggota` dapat memenuhi permission untuk export daftar anggota. Export tersebut tidak di-scope ke pemilik data dan berisi NPWP, nomor telepon, dan nomor rekening seluruh anggota.
2. API mobile memberikan `member:read` dan `member:write` kepada semua user yang mempunyai relasi `cooperativeMember`, tanpa memastikan status anggota `ACTIVE`.
3. Perubahan status menjadi revisi, ditolak, nonaktif, atau resign tidak mencabut token Sanctum. Token lama tetap memiliki abilities sampai dihapus atau kedaluwarsa.
4. User dengan permission administratif sistem menerima Sanctum ability `*` sebelum pembatasan berdasarkan aplikasi dijalankan. Login dengan parameter `app=member` tetap dapat menghasilkan token superuser.
5. Halaman administratif koperasi menggunakan permission yang juga dimiliki anggota. Beberapa response membocorkan statistik global, daftar anggota aktif, dan seluruh pengajuan resign.
6. Seeder membuat akun `admin@erp.com` dengan password literal `password`. Ini berbahaya bila seeder pernah dijalankan pada environment production atau staging yang dapat diakses publik.

## Ringkasan Temuan

| ID | Severity | Area | Ringkasan |
|---|---|---|---|
| AUTH-01 | Critical | Export anggota | Anggota berpotensi mengekspor PII dan data rekening seluruh anggota. |
| AUTH-02 | Critical | API anggota | Status pending/revisi/ditolak/nonaktif/resign tidak menjadi syarat abilities dan akses API self-service. |
| AUTH-03 | High | Token mobile | Admin sistem memperoleh ability `*` sebelum token dibatasi berdasarkan jenis aplikasi. |
| AUTH-04 | High | Permission model | Permission self-service dan permission administrasi menggunakan nama/semantik yang sama. |
| AUTH-05 | High | Resign anggota | Policy dan query daftar pengajuan resign memungkinkan role Anggota membaca data global. |
| AUTH-06 | High | Seeder | Kredensial admin default tertanam di source code. |
| AUTH-07 | High/Medium | Multi-organization | Banyak policy/query koperasi tidak membatasi `organization_id`; severity bergantung apakah deployment benar-benar single cooperative. |
| AUTH-08 | Medium | Sanctum abilities | Ability `cooperative:write` terlalu kasar dan mencakup banyak domain bisnis. |
| AUTH-09 | Medium | Approval workflow | Maker-checker belum memastikan verifier admin berbeda dari approver final. |
| AUTH-10 | Medium | PII | Field identitas dan rekening tersimpan plaintext dan sering diserialisasi melalui model langsung. |
| AUTH-11 | Medium | Profile update | Update `users` dan `cooperative_members` tidak berada dalam satu transaksi database. |
| AUTH-12 | Medium | Store order | Pengecekan stok dan idempotensi order masih rentan race condition tanpa reservation/unique constraint. |
| AUTH-13 | Medium | Pagination | Beberapa endpoint menerima `per_page` tanpa batas maksimum. |
| AUTH-14 | Low/Medium | Permission drift | Terdapat permission `validate` dan `verify` serta pengecekan role hard-coded yang berpotensi drift. |

## Dokumen Review

- [Review Role Anggota](./member-role-review.md)
- [Review Role Admin Koperasi](./cooperative-admin-role-review.md)
- [Roadmap Remediasi](./remediation-roadmap.md)
- [Matriks Test Otorisasi](./authorization-test-matrix.md)

## Prioritas Implementasi

### P0 — Hotfix sebelum production

- Tutup export anggota dari role `Anggota`.
- Tambahkan active-member middleware untuk seluruh endpoint finansial mobile.
- Cabut token saat status anggota tidak lagi aktif.
- Hilangkan wildcard token dari login aplikasi mobile.
- Scope daftar pengajuan resign dan statistik administratif.
- Hapus kredensial default dari seeder production.

### P1 — Penguatan model akses

- Pisahkan permission self-service dan admin.
- Gunakan abilities per domain, bukan `cooperative:read/write` yang terlalu umum.
- Terapkan scope organisasi secara eksplisit.
- Tambahkan negative authorization test untuk seluruh role koperasi.

### P2 — Hardening dan maintainability

- Masking/encryption untuk PII sensitif.
- Transaksi pada update data lintas tabel.
- Unique index idempotensi dan stock reservation.
- Konsolidasi permission legacy dan role hard-coded.

## Catatan Positif

Beberapa implementasi yang layak dipertahankan:

- Controller member umumnya menggunakan relasi milik user dan melakukan ownership check pada invoice, payment, loan, serta payment intent.
- `MemberStoreController` dan `MemberCoffeeOrderController` sudah mencari anggota aktif untuk proses order.
- Loan controller menggunakan policy per aksi untuk review, approve, reject, disburse, dan payment.
- Final approval dan perubahan status anggota dicatat melalui transaction serta audit log.
- Signed temporary URL digunakan untuk receipt.
- QRIS proxy mempunyai validasi content type, ukuran, dan pembatasan URL.

Masalah utama bukan ketiadaan mekanisme keamanan, melainkan **ketidakkonsistenan penerapannya antar web, API, role, dan lifecycle status anggota**.