---
title: Operasi Harian POS, Inventori, dan Setoran Kasir
slug: admin-koperasi-pos-inventory
summary: Alur shift, penjualan, retur, penyetoran kas, dan opname stok.
category: Admin Koperasi · Operasional
module: pos
roles:
  - admin_koperasi
permissions:
  - access_cooperative_pos
  - manage_pos_products
permission_mode: all
route_names:
  - cooperative.pos.index
  - cooperative.pos.shifts.index
  - cooperative.pos.shifts.open
  - cooperative.pos.shifts.close
  - cooperative.pos.transactions.index
  - cooperative.pos.transactions.store
  - cooperative.pos.coffee-orders.index
  - cooperative.pos.coffee-orders.update-status
  - cooperative.pos.void-requests.index
  - cooperative.pos.void-requests.process
  - cooperative.pos.returns.create
  - cooperative.pos.returns.store
  - cooperative.pos.credit.create
  - cooperative.pos.credit.store
  - cooperative.pos.closings.index
  - cooperative.pos.closings.close
  - cooperative.pos.inventory.counts.index
  - cooperative.pos.inventory.counts.create
  - cooperative.pos.inventory.counts.show
  - cooperative.pos.inventory.receipts.index
  - cooperative.pos.inventory.receipts.create
  - cooperative.pos.inventory.transfers.index
  - cooperative.pos.inventory.transfers.create
  - cooperative.pos.reports.index
  - cooperative.reports.index
  - cooperative.shu.index
risk_level: medium
screenshot_entries:
  - admin-koperasi-pos-inventory-desktop
related_articles:
  - admin-koperasi-payment-queue
last_reviewed_commit: 20c86960
status: published
sort_order: 30
---

# Operasi Harian POS, Inventori, dan Setoran Kasir

## POS

- Buka shift: `route('cooperative.pos.shifts.index')` lalu
  `route('cooperative.pos.shifts.open')`.
- Catat order: `route('cooperative.pos.transactions.store')` dan
  pantau di `route('cooperative.pos.transactions.index')`.
- Pantauan coffee orders:
  `route('cooperative.pos.coffee-orders.index')` dan ubah status
  lewat `route('cooperative.pos.coffee-orders.update-status')`.
- Void: `route('cooperative.pos.void-requests.index')` (perlu
  permission `approve_pos_void`) lalu proses via
  `route('cooperative.pos.void-requests.process')`.
- Retur: `route('cooperative.pos.returns.create')` (per
  transaksi) lalu simpan di
  `route('cooperative.pos.returns.store')`.
- Kredit/angsuran anggota:
  `route('cooperative.pos.credit.create')` dan
  `route('cooperative.pos.credit.store')`.

## Setoran kasir

Setelah shift, tutup lewat
`route('cooperative.pos.shifts.close')`. Setoran harian kemudian
direkam di `route('cooperative.pos.closings.index')` dan
ditutup via `route('cooperative.pos.closings.close')` (perlu
permission `view_pos_reports`).

## Inventori

- Stok opname: `route('cooperative.pos.inventory.counts.index')`,
  `route('cooperative.pos.inventory.counts.create')`,
  `route('cooperative.pos.inventory.counts.show')`.
- Penerimaan barang:
  `route('cooperative.pos.inventory.receipts.index')` /
  `route('cooperative.pos.inventory.receipts.create')`.
- Transfer gudang:
  `route('cooperative.pos.inventory.transfers.index')` /
  `route('cooperative.pos.inventory.transfers.create')`.

## Laporan

- Laporan POS: `route('cooperative.pos.reports.index')`.
- Laporan koperasi: `route('cooperative.reports.index')`.
- SHU: `route('cooperative.shu.index')`.
