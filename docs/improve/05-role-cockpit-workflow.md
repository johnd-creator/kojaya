# Plan 05 - Role-based Cockpit dan Workflow UX

## Tujuan

Mengurangi perpindahan menu untuk pekerjaan harian dengan cockpit berbasis role: pending approval, anomali data, ringkasan risiko, dan jalan pintas aksi.

## Prioritas

P2. Dampaknya besar untuk operator, tetapi butuh desain alur kerja dan permission yang rapi.

## Sumber Dari new_improve.md

- Workflow UX: Cockpit / Dashboard Berbasis Role
- Phase 2: Role-based Cockpit UI

## Baseline Repo

Cockpit Operator Koperasi sudah ada dan terdokumentasi di `docs/PHASE_A_EXECUTION_REPORT.md`.

File utama:

- `resources/js/pages/Cooperative/Operator/Dashboard.vue`
- `resources/js/pages/Cooperative/Operator/Closing.vue`
- Service operator koperasi dan route terkait di area `Cooperative`.

## Scope

Termasuk:

- Audit cockpit yang sudah ada untuk gap UX dan data.
- Buat cockpit tambahan hanya jika role benar-benar punya pekerjaan harian berbeda.
- Tambah shortcut aksi yang mengarah ke halaman kerja, bukan sekadar kartu statistik.
- Tampilkan exception/anomali yang perlu tindakan.
- Pastikan permission guard di backend dan frontend konsisten.

Tidak termasuk:

- BI dashboard eksekutif yang sifatnya analitik jangka panjang.
- Redesign seluruh navigasi aplikasi.

## Kandidat Role

Prioritas role dari `new_improve.md`:

- Operator Koperasi: sudah ada, perlu audit dan polish.
- Finance: payment approvals, invoice aging, failed reconciliation, export/report.
- Kasir/POS: shift status, stock alert, pending coffee/order, transaksi hari ini.
- HR/Payroll: pending leave, overtime, payroll issue, compliance expiry.

## Langkah Implementasi

1. Pilih satu role per batch.
2. Definisikan 3 sampai 5 pekerjaan harian utama role tersebut.
3. Mapping pekerjaan ke data yang sudah tersedia di controller/service.
4. Buat payload cockpit yang ringan:
   - summary counts.
   - approval inbox.
   - exception list.
   - quick actions.
5. Gunakan deferred props untuk metrik berat.
6. Tambahkan route, permission, dan sidebar entry bila cockpit baru dibuat.
7. Buat test akses role: authorized role bisa buka, role lain ditolak/redirect.

## Acceptance Criteria

- Cockpit menjawab pekerjaan harian role, bukan hanya dashboard statistik.
- Setiap item actionable punya link atau action yang jelas.
- Permission backend melindungi route cockpit.
- Sidebar hanya menampilkan cockpit untuk role yang relevan.
- Ada empty state ketika tidak ada pekerjaan menunggu.

## Verifikasi Minimal

- `php artisan test --compact tests/Feature/RoleSmokeTest.php`
- Test feature cockpit yang dibuat/diubah.
- `npm run build`
- Manual smoke dengan user role terkait.

## Risiko

- Cockpit bisa menjadi duplikasi dashboard jika tidak berbasis pekerjaan harian.
- Query agregat bisa berat. Gunakan deferred props atau cache bila perlu.
- Permission mismatch antara sidebar dan route dapat membuat link muncul tetapi akses ditolak.

