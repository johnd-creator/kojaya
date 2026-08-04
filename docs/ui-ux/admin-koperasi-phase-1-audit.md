# Audit UI/UX Admin Koperasi — Phase 1

## Checkpoint

- Stable source branch: feature/role-ui-ux
- Stable source SHA: 88d8be1ce28dbc6cfef350ebdf9b3766d779c509
- Admin branch: feature/admin-koperasi-ui-ux
- Initial Admin SHA: 88d8be1ce28dbc6cfef350ebdf9b3766d779c509
- Merge base: 88d8be1ce28dbc6cfef350ebdf9b3766d779c509
- Worktree clean at audit start: yes

## Role boundary

Admin Koperasi adalah operator administrasi harian. Ia memeriksa kelengkapan
anggota, memvalidasi tahap awal, memeriksa pembayaran, menindaklanjuti iuran,
dan menyiapkan data untuk Manajer/Pengurus. Ia bukan final approver pinjaman
atau anggota.

Resolver resources/js/lib/role-experience.ts tetap menjadi sumber identitas
workspace. Precedence System Admin → Admin Pusat → Pengurus → Manajer → Admin
Koperasi → Kasir dipertahankan.

## Route inventory

| Page | Route name | Permission gate | Admin has permission | Task | Frequency | Risk | Phase 1 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard | dashboard | authenticated | yes | Prioritas kerja harian | daily | medium | P0 |
| Data anggota | cooperative.members.index | view_cooperative_member | yes | Cari, filter, validasi | daily | high | P0 |
| Detail anggota | cooperative.members.show | member policy + organization scope | yes | Periksa status dan data | daily | high | P0 |
| Edit anggota | cooperative.members.edit | manage_cooperative_member | yes | Koreksi data operasional | frequent | high | P0 |
| Validasi anggota | cooperative.members.validate | validate_cooperative_member | yes | Verifikasi awal | daily | high | P0 |
| Pembayaran | cooperative.payments.index | manage_cooperative_payment | yes | Verifikasi dan posting | daily | critical | P0 |
| Iuran/tagihan | cooperative.dues.index | manage_cooperative_dues | yes | Tunggakan, partial, periode | daily | high | P0 |
| Ledger simpanan | cooperative.ledger.index | view_cooperative_ledger | yes | Monitoring saldo dan posting | frequent | critical | P1 |
| Penarikan simpanan | cooperative.savings.withdrawals.index | view_cooperative_ledger | yes | Monitoring exception | weekly | critical | P1 |
| Pengunduran diri | cooperative.members.resignations.index | review_cooperative_resignation | yes | Review administratif | weekly | high | P1 |
| Pinjaman | cooperative.loans.index | view_cooperative_loan | yes | Monitoring data, bukan approval | weekly | high | P1 |
| Detail pinjaman | cooperative.loans.show | loan policy | yes | Konteks operasional | weekly | high | P1 |
| Jenis pinjaman | cooperative.loan-types.index | manage_cooperative_loan_types | yes | Master data | rare | high | P2 |
| Poin | cooperative.points.index | manage_cooperative_points | yes | Administrasi benefit | weekly | medium | P1 |
| Reward | cooperative.rewards.index | manage_cooperative_rewards | yes | Master benefit | weekly | medium | P1 |
| Penukaran reward | cooperative.redemptions.index | manage_cooperative_redemption | yes | Tindak lanjut redemption | weekly | medium | P1 |
| SHU | cooperative.shu.index | manage_cooperative_shu | yes | Data tahunan | rare | critical | P2 |
| POS | cooperative.pos.index | access_cooperative_pos | yes | Operasional toko | daily for POS staff | critical | OUT |
| Transaksi POS | cooperative.pos.transactions.index | access_cooperative_pos | yes | Histori transaksi | daily for POS staff | critical | OUT |
| Produk/stok | cooperative.pos-products.index | manage_pos_products | yes | Inventory | daily for inventory staff | high | P1 |
| Laporan POS | cooperative.pos.reports.index | view_pos_reports | yes | Rekap toko | weekly | high | P2 |
| Saldo Toko | cooperative.store-credit.index | view_store_credit | yes | Monitoring akun | weekly | critical | P2 |
| Laporan koperasi | cooperative.reports.index | view_cooperative_report | no in current Admin role seed | Pelaporan | monthly | high | OUT |

System Admin, Admin Pusat, Pengurus, Manajer, dan Kasir tetap memakai
navigation/experience existing. Menu Admin Koperasi tidak memakai item disabled:
item yang tidak lolos permission tidak dirender.

## Findings and decisions

1. Dashboard lama memiliki payload ERP/POS/SHU besar dan query tanpa
   organization scope. Admin Koperasi sekarang memakai read-only payload khusus
   yang scoped melalui OrganizationScopedQueryService.
2. Dashboard Admin hanya memprioritaskan pembayaran pending, anggota pending,
   revisi, tunggakan, dan pengunduran diri bila permission tersedia. KPI dibatasi
   empat dan tidak memakai synthetic trend.
3. CooperativeDuesController@index sebelumnya memanggil
   DuesGenerationService::generateForPeriod() pada GET. GET sekarang hanya
   membaca; generation tetap tersedia pada route POST yang sudah ada.
4. Status lifecycle anggota dan status validasi ditampilkan terpisah. Admin
   hanya mendapat action verifikasi awal/revisi sesuai permission; final
   approval tidak menjadi CTA Admin.
5. Tabel/payment/dues existing dipertahankan sebagai shared operational pages
   dan diperbaiki secara incremental agar perubahan tidak merombak role lain.

## Phase 1 classification

- P0: Dashboard, anggota index/detail/edit/validasi, pembayaran, iuran/tagihan.
- P1: ledger, penarikan simpanan, pengunduran diri, monitoring pinjaman,
  poin/reward, produk/stok.
- P2: jenis pinjaman, SHU, laporan POS, saldo toko, advanced reporting.
- OUT: POS/register dan workflow Kasir-only.

## Deferred follow-up

- Read model aktivitas administratif terbaru belum tersedia sebagai kontrak
  ringan; Phase 1 tidak menambahkan query audit-log berat.
- Visual baseline canonical harus ditangkap dan direview pada Ubuntu CI setelah
  fixture Admin Koperasi lengkap; local screenshots tidak menggantikan baseline
  canonical.
