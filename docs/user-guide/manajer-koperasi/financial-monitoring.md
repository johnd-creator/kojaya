---
title: Pemantauan Keuangan Harian
slug: manajer-financial-monitoring
summary: Cara membaca ringkasan simpan pinjam, NPL, dan pencairan.
category: Manajer Koperasi · Keuangan
module: reports
roles:
  - manajer_koperasi
permissions:
  - view_cooperative_report
permission_mode: all
route_names:
  - cooperative.operator.dashboard
  - cooperative.payments.index
  - cooperative.pos.closings.index
  - cooperative.ledger.index
  - cooperative.loans.index
  - cooperative.loans.disburse
  - cooperative.reports.index
risk_level: low
screenshot_entries:
  - manajer-financial-monitoring-desktop
related_articles:
  - manajer-loan-review
last_reviewed_commit: 20c86960
status: published
sort_order: 20
---

# Pemantauan Keuangan Harian

## Dashboard

- Ringkasan harian:
  `route('cooperative.operator.dashboard')` (widget Manajer).
- NPL: badge di pojok kanan atas widget.
- Angsuran tertunda: taut ke
  `route('cooperative.loans.index')` dengan filter
  `status=DEFAULTED`.

## Tindakan korektif

- Angsuran macet → tugaskan Admin Koperasi untuk
  follow-up lewat `route('cooperative.payments.index')`.
- Setoran kasir harian → rekonsiliasi di
  `route('cooperative.pos.closings.index')`; cocokan dengan
  `route('cooperative.ledger.index')`.
- Pencairan yang belum cair di
  `route('cooperative.loans.index')` (filter `status=APPROVED`)
  → hubungi Admin untuk status transfer lewat
  `route('cooperative.loans.disburse')`.

## Eskalasi

Permasalahan hukum (sengketa, audit eksternal) → teruskan ke
Pengurus via laporan triwulan pada
`route('cooperative.reports.index')`.

> **Catatan:** tidak ada preset laporan bernama "AnnualReport",
> "RAT", atau "Pengurus". `CooperativeReportController` hanya
> menyediakan `index`, `summary`, `sales`, dan `nplAging`.
